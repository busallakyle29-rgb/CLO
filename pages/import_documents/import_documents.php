<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/models/AuditTrailModel.php';
require_once dirname(__DIR__, 2) . '/functions/document_assignment.php';

start_secure_session();
auth_require_role(['INCOMING']);
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;

$currentUser = auth_user();
$currentUserId = (int) ($currentUser['user_id'] ?? 0);
$currentUserDepartmentId = (int) ($currentUser['department_id'] ?? 0);
$message = flash_get('success') ?? '';
$messageType = 'success';
$previewToken = (string) ($_GET['preview'] ?? $_SESSION['document_import_preview_token'] ?? '');
$preview = $previewToken !== ''
    ? ($_SESSION['document_import_previews'][$previewToken] ?? null)
    : null;

if (
    !is_array($preview)
    || (int) ($preview['user_id'] ?? 0) !== $currentUserId
    || (int) ($preview['created_at'] ?? 0) < time() - 3600
) {
    $preview = null;
    $previewToken = '';
}

function import_normalize_header(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';
    return trim($value, '_');
}

function import_value(array $row, array $names): string
{
    foreach ($names as $name) {
        $key = import_normalize_header($name);
        if (array_key_exists($key, $row)) {
            return trim((string) $row[$key]);
        }
    }

    return '';
}

function import_column_for(array $headers, array $names): ?string
{
    $aliases = array_map('import_normalize_header', $names);
    foreach ($headers as $column => $header) {
        if (in_array($header, $aliases, true)) {
            return (string) $column;
        }
    }

    return null;
}

function import_date_value(mixed $value, bool $required = false): ?string
{
    if ($value === null || trim((string) $value) === '') {
        if ($required) {
            throw new RuntimeException('Date is required.');
        }
        return null;
    }

    if (is_numeric($value)) {
        try {
            return SpreadsheetDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        } catch (Throwable $exception) {
            throw new RuntimeException('Invalid Excel date.');
        }
    }

    $value = trim((string) $value);
    foreach (['Y-m-d', 'm/d/Y', 'm/d/y', 'n/j/Y', 'n/j/y', 'd/m/Y', 'd/m/y', 'Y/m/d', 'd-M-Y', 'd-M-y'] as $format) {
        $date = DateTime::createFromFormat('!' . $format, $value);
        $dateErrors = DateTime::getLastErrors();
        if (
            $date instanceof DateTime
            && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
        ) {
            return $date->format('Y-m-d');
        }
    }

    throw new RuntimeException('Invalid date: ' . $value);
}

function import_lookup_id(string $sql, string $value, string $label, ?string $secondValue = null): int
{
    if ($value === '') {
        throw new RuntimeException($label . ' is required.');
    }

    $values = $secondValue === null ? [$value] : [$value, $secondValue];
    $types = $secondValue === null ? 's' : 'ss';
    $stmt = db_execute($sql, $types, $values);
    $row = db_fetch_one_assoc($stmt);
    $stmt->close();

    if (!$row) {
        throw new RuntimeException($label . ' not found: ' . $value);
    }

    return (int) array_values($row)[0];
}

function import_lookup_assignee(string $value): ?array
{
    if ($value === '') {
        return null;
    }

    $stmt = db_execute(
        'SELECT user_id, department_id
         FROM users
         WHERE is_active = 1
           AND (username = ? OR CONCAT(first_name, " ", last_name) = ?)
         LIMIT 1',
        'ss',
        [$value, $value]
    );
    $row = db_fetch_one_assoc($stmt);
    $stmt->close();

    if (!$row) {
        throw new RuntimeException('Assigned Staff not found: ' . $value);
    }

    return [
        'user_id' => (int) $row['user_id'],
        'department_id' => (int) $row['department_id'],
    ];
}

function import_validate_xlsx(string $filePath, int $defaultDepartmentId): array
{
    $reader = IOFactory::createReaderForFile($filePath);
    if (!$reader instanceof PhpOffice\PhpSpreadsheet\Reader\Xlsx) {
        throw new RuntimeException('The selected file is not a valid .xlsx workbook.');
    }

    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestDataRow();
    $highestColumn = $sheet->getHighestDataColumn();
    if ($highestRow > 501) {
        $spreadsheet->disconnectWorksheets();
        throw new RuntimeException('Import is limited to 500 document rows per workbook.');
    }
    if (Coordinate::columnIndexFromString($highestColumn) > 30) {
        $spreadsheet->disconnectWorksheets();
        throw new RuntimeException('The workbook may contain no more than 30 columns.');
    }

    $rows = $sheet->rangeToArray('A1:' . $highestColumn . $highestRow, null, false, false, true);
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);

    if (count($rows) < 2) {
        throw new RuntimeException('The Excel file has no document rows.');
    }

    $headerRow = array_shift($rows);
    $headers = [];
    foreach ($headerRow as $column => $header) {
        $normalized = import_normalize_header((string) $header);
        if ($normalized !== '') {
            if (in_array($normalized, $headers, true)) {
                throw new RuntimeException('Duplicate column heading: ' . (string) $header . '.');
            }
            $headers[$column] = $normalized;
        }
    }

    $requiredHeaderAliases = [
        ['label' => 'Title', 'aliases' => ['title', 'document_title']],
        ['label' => 'Document Type', 'aliases' => ['document_type', 'document_type_name', 'type_code']],
        ['label' => 'Origin Department', 'aliases' => ['origin_department', 'origin_department_name']],
        ['label' => 'Date Received', 'aliases' => ['date_received', 'received_date']],
    ];
    foreach ($requiredHeaderAliases as $requiredHeader) {
        if (!array_intersect($requiredHeader['aliases'], array_values($headers))) {
            throw new RuntimeException('Missing required column: ' . $requiredHeader['label'] . '.');
        }
    }

    $validRows = [];
    $rowErrors = [];
    $seenReferences = [];
    $nonEmptyRows = 0;

    foreach ($rows as $rowIndex => $sourceRow) {
        $row = [];
        foreach ($headers as $column => $header) {
            $row[$header] = $sourceRow[$column] ?? '';
        }

        if (implode('', array_map(static fn ($value): string => trim((string) $value), $row)) === '') {
            continue;
        }

        $nonEmptyRows++;
        $excelRow = $rowIndex + 2;
        $errorField = 'Title';
        $errorAliases = ['title', 'document_title'];

        try {
            $title = import_value($row, ['title', 'document_title']);
            if ($title === '') {
                throw new RuntimeException('Title is required.');
            }
            if (mb_strlen($title) > 255) {
                throw new RuntimeException('Title must not exceed 255 characters.');
            }

            $errorField = 'Description';
            $errorAliases = ['description', 'details'];
            $description = import_value($row, $errorAliases);
            if (mb_strlen($description) > 2000) {
                throw new RuntimeException('Description must not exceed 2000 characters.');
            }

            $errorField = 'Document Type';
            $errorAliases = ['document_type', 'document_type_name', 'type_code'];
            $documentTypeValue = import_value($row, $errorAliases);
            $documentTypeId = import_lookup_id(
                'SELECT document_type_id FROM document_types WHERE is_active = 1 AND (type_name = ? OR type_code = ?) LIMIT 1',
                $documentTypeValue,
                'Document Type',
                $documentTypeValue
            );
            $errorField = 'Origin Department';
            $errorAliases = ['origin_department', 'origin_department_name'];
            $originDepartmentId = import_lookup_id(
                'SELECT department_id FROM departments WHERE is_active = 1 AND department_name = ? LIMIT 1',
                import_value($row, $errorAliases),
                'Origin Department'
            );

            $errorField = 'Current Department';
            $errorAliases = ['current_department', 'current_department_name'];
            $currentDepartmentValue = import_value($row, $errorAliases);
            $currentDepartmentName = $currentDepartmentValue;
            $currentDepartmentId = $defaultDepartmentId;
            if ($currentDepartmentValue === '') {
                if ($defaultDepartmentId <= 0) {
                    throw new RuntimeException('Current Department is required and your account has no department set.');
                }
                $departmentStmt = db_execute(
                    'SELECT department_name FROM departments WHERE department_id = ? LIMIT 1',
                    'i',
                    [$currentDepartmentId]
                );
                $departmentRow = db_fetch_one_assoc($departmentStmt);
                $departmentStmt->close();
                $currentDepartmentName = (string) ($departmentRow['department_name'] ?? '');
            } else {
                $currentDepartmentId = import_lookup_id(
                    'SELECT department_id FROM departments WHERE is_active = 1 AND department_name = ? LIMIT 1',
                    $currentDepartmentValue,
                    'Current Department'
                );
            }
            if ($currentDepartmentId <= 0) {
                throw new RuntimeException('Current Department is required and your account has no department set.');
            }

            $errorField = 'Assigned Staff';
            $errorAliases = ['assigned_staff', 'assigned_to', 'assignee'];
            $assignee = import_lookup_assignee(import_value($row, $errorAliases));
            if ($assignee !== null && $assignee['department_id'] !== $currentDepartmentId) {
                throw new RuntimeException('Assigned Staff must belong to the Current Department.');
            }

            $errorField = 'Status';
            $errorAliases = ['status', 'document_status'];
            $statusName = import_value($row, $errorAliases);
            $statusId = import_lookup_id(
                'SELECT document_status_id FROM document_status WHERE status_name = ? LIMIT 1',
                $statusName !== '' ? $statusName : 'Received',
                'Status'
            );

            $errorField = 'Date Received';
            $errorAliases = ['date_received', 'received_date'];
            $dateReceived = import_date_value(import_value($row, $errorAliases), true);
            $errorField = 'Due Date';
            $errorAliases = ['date_due', 'due_date'];
            $dateDue = import_date_value(import_value($row, $errorAliases));

            $errorField = 'Priority';
            $errorAliases = ['priority', 'priority_level'];
            $priority = strtoupper(import_value($row, $errorAliases));
            $priority = $priority !== '' ? $priority : 'MEDIUM';
            if (!in_array($priority, ['LOW', 'MEDIUM', 'HIGH', 'URGENT'], true)) {
                throw new RuntimeException('Priority must be Low, Medium, High, or Urgent.');
            }

            $errorField = 'Confidentiality';
            $errorAliases = ['confidentiality', 'confidentiality_level'];
            $confidentiality = strtoupper(import_value($row, $errorAliases));
            $confidentiality = $confidentiality !== '' ? $confidentiality : 'INTERNAL';
            $confidentiality = match ($confidentiality) {
                'NORMAL', 'INTERNAL' => 'INTERNAL',
                'PUBLIC' => 'PUBLIC',
                'CONFIDENTIAL' => 'CONFIDENTIAL',
                'HIGHLY CONFIDENTIAL', 'HIGHLY_CONFIDENTIAL' => 'HIGHLY_CONFIDENTIAL',
                default => throw new RuntimeException('Invalid confidentiality level.'),
            };
            $confidentialityLabel = match ($confidentiality) {
                'CONFIDENTIAL' => 'Confidential',
                'HIGHLY_CONFIDENTIAL' => 'Highly Confidential',
                default => 'Normal',
            };

            $errorField = 'Ref No';
            $errorAliases = ['ref_no', 'reference_no', 'external_reference_no'];
            $refNo = import_value($row, $errorAliases);
            if (mb_strlen($refNo) > 100) {
                throw new RuntimeException('Ref No must not exceed 100 characters.');
            }

            $warnings = [];
            $normalizedReference = mb_strtolower(trim($refNo));
            $duplicateWorkbookFirstIndex = null;
            if ($normalizedReference !== '') {
                if (isset($seenReferences[$normalizedReference])) {
                    $duplicateWorkbookFirstIndex = (int) $seenReferences[$normalizedReference];
                    $warnings[] = 'Duplicate Ref No in this workbook (row ' . $validRows[$duplicateWorkbookFirstIndex]['excel_row'] . ').';
                }

                $duplicateStmt = db_execute(
                    'SELECT tracking_no FROM documents WHERE LOWER(TRIM(external_reference_no)) = LOWER(TRIM(?)) LIMIT 1',
                    's',
                    [$refNo]
                );
                $duplicate = db_fetch_one_assoc($duplicateStmt);
                $duplicateStmt->close();
                if ($duplicate) {
                    $warnings[] = 'Ref No already exists on ' . (string) $duplicate['tracking_no'] . '.';
                }
            }

            $validRowIndex = count($validRows);
            $validRows[] = [
                'excel_row' => $excelRow,
                'title' => $title,
                'description' => $description,
                'document_type_name' => $documentTypeValue,
                'document_type_id' => $documentTypeId,
                'status_id' => $statusId,
                'status_name' => $statusName !== '' ? $statusName : 'Received',
                'origin_department_id' => $originDepartmentId,
                'origin_department_name' => import_value($row, ['origin_department', 'origin_department_name']),
                'current_department_id' => $currentDepartmentId,
                'current_department_name' => $currentDepartmentName,
                'assigned_user_id' => $assignee['user_id'] ?? null,
                'assigned_staff_name' => import_value($row, ['assigned_staff', 'assigned_to', 'assignee']),
                'priority' => $priority,
                'confidentiality' => $confidentiality,
                'confidentiality_label' => $confidentialityLabel,
                'ref_no' => $refNo,
                'date_received' => $dateReceived,
                'date_due' => $dateDue,
                'warnings' => $warnings,
            ];

            if ($normalizedReference !== '') {
                if ($duplicateWorkbookFirstIndex !== null) {
                    $validRows[$duplicateWorkbookFirstIndex]['warnings'][] = 'Ref No is also used on row ' . $excelRow . '.';
                } else {
                    $seenReferences[$normalizedReference] = $validRowIndex;
                }
            }
        } catch (Throwable $exception) {
            $rowErrors[] = [
                'excel_row' => $excelRow,
                'cell' => ($errorColumn = import_column_for($headers, $errorAliases)) !== null
                    ? $errorColumn . $excelRow
                    : null,
                'field' => $errorField,
                'message' => $exception->getMessage(),
            ];
        }
    }

    if ($nonEmptyRows === 0) {
        throw new RuntimeException('The workbook contains no document rows.');
    }

    return ['valid_rows' => $validRows, 'errors' => $rowErrors, 'total_rows' => $nonEmptyRows];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_require('import_documents');
        $action = (string) ($_POST['action'] ?? 'preview');

        if ($action === 'preview') {
            if (!isset($_FILES['import_file']) || ($_FILES['import_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Please select an .xlsx file.');
            }
            if ((int) ($_FILES['import_file']['size'] ?? 0) > 10 * 1024 * 1024) {
                throw new RuntimeException('The workbook must be 10 MB or smaller.');
            }
            if (!is_uploaded_file($_FILES['import_file']['tmp_name'])) {
                throw new RuntimeException('Invalid uploaded workbook.');
            }
            $extension = strtolower(pathinfo((string) $_FILES['import_file']['name'], PATHINFO_EXTENSION));
            if ($extension !== 'xlsx') {
                throw new RuntimeException('Only .xlsx files are supported.');
            }

            $previewData = import_validate_xlsx($_FILES['import_file']['tmp_name'], $currentUserDepartmentId);
            $previewToken = bin2hex(random_bytes(24));
            if (!isset($_SESSION['document_import_previews']) || !is_array($_SESSION['document_import_previews'])) {
                $_SESSION['document_import_previews'] = [];
            }
            $_SESSION['document_import_previews'] = [];
            $_SESSION['document_import_previews'][$previewToken] = [
                'user_id' => $currentUserId,
                'created_at' => time(),
                ...$previewData,
            ];
            $_SESSION['document_import_preview_token'] = $previewToken;
            redirect_to('import_documents.php?preview=' . urlencode($previewToken));
        } elseif ($action === 'cancel') {
            $cancelToken = (string) ($_POST['preview_token'] ?? '');
            $storedPreview = $_SESSION['document_import_previews'][$cancelToken] ?? null;
            if (!is_array($storedPreview) || (int) ($storedPreview['user_id'] ?? 0) !== $currentUserId) {
                throw new RuntimeException('Preview expired or not found. Please upload the workbook again.');
            }
            unset($_SESSION['document_import_previews'][$cancelToken]);
            unset($_SESSION['document_import_preview_token']);
            flash_set('success', 'Import preview cancelled. No documents were added.');
            redirect_to('import_documents.php');
        } elseif ($action === 'confirm') {
            $confirmToken = (string) ($_POST['preview_token'] ?? '');
            $storedPreview = $_SESSION['document_import_previews'][$confirmToken] ?? null;
            if (
                !is_array($storedPreview)
                || (int) ($storedPreview['user_id'] ?? 0) !== $currentUserId
                || (int) ($storedPreview['created_at'] ?? 0) < time() - 3600
            ) {
                throw new RuntimeException('Preview expired or not found. Please upload the workbook again.');
            }

            $validRows = $storedPreview['valid_rows'] ?? [];
            if (!is_array($validRows) || $validRows === []) {
                throw new RuntimeException('There are no valid rows to import.');
            }

            $conn = db_connection();
            $conn->begin_transaction();
            try {
                $importedCount = 0;
                foreach ($validRows as $row) {
                    $stmt = $conn->prepare(
                        'INSERT INTO documents
                            (tracking_no, title, description, document_type_id, current_status_id,
                             origin_department_id, current_department_id, created_by_user_id,
                             current_assignee_user_id, priority_level, confidentiality_level,
                             external_reference_no, date_received, date_due, created_at, updated_at)
                         VALUES (\'\', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
                    );
                    $assignedUserId = $row['assigned_user_id'];
                    $stmt->bind_param(
                        'ssiiiiiisssss',
                        $row['title'],
                        $row['description'],
                        $row['document_type_id'],
                        $row['status_id'],
                        $row['origin_department_id'],
                        $row['current_department_id'],
                        $currentUserId,
                        $assignedUserId,
                        $row['priority'],
                        $row['confidentiality'],
                        $row['ref_no'],
                        $row['date_received'],
                        $row['date_due']
                    );
                    $stmt->execute();
                    $documentId = (int) $stmt->insert_id;
                    $stmt->close();

                    $year = date('Y', strtotime((string) $row['date_received']));
                    $trackingNumber = 'CLO-' . $year . '-' . str_pad((string) $documentId, 6, '0', STR_PAD_LEFT);
                    $stmt = $conn->prepare('UPDATE documents SET tracking_no = ? WHERE document_id = ?');
                    $stmt->bind_param('si', $trackingNumber, $documentId);
                    $stmt->execute();
                    $stmt->close();

                    if ($assignedUserId !== null) {
                        document_assignment_set_receipt($conn, $documentId, (int) $assignedUserId, $currentUserId);
                    }
                    audit_log_create(
                        $currentUserId,
                        'DOCUMENT',
                        $documentId,
                        'IMPORTED',
                        null,
                        ['tracking_no' => $trackingNumber, 'source' => 'Excel import']
                    );
                    if ($assignedUserId !== null) {
                        audit_log_create(
                            $currentUserId,
                            'DOCUMENT',
                            $documentId,
                            'ASSIGNED',
                            null,
                            ['assigned_to_user_id' => (int) $assignedUserId]
                        );
                    }
                    $importedCount++;
                }

                $conn->commit();
            } catch (Throwable $exception) {
                $conn->rollback();
                throw $exception;
            }

            $skippedCount = count($storedPreview['errors'] ?? []);
            unset($_SESSION['document_import_previews'][$confirmToken]);
            unset($_SESSION['document_import_preview_token']);
            flash_set('success', $importedCount . ' document(s) imported. ' . $skippedCount . ' invalid row(s) skipped.');
            redirect_to('import_documents.php');
        } else {
            throw new RuntimeException('Unknown import action.');
        }
    } catch (Throwable $exception) {
        $message = $exception->getMessage();
        $messageType = 'danger';
    }
}

require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="page-header bg-white rounded-3 shadow-sm p-4 mb-4">
        <h3 class="mb-1"><i class="bi bi-file-earmark-spreadsheet text-success"></i> Import Documents</h3>
        <div class="text-muted">Review validated rows before adding them to the system.</div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= e($messageType) ?> alert-dismissible fade show">
            <?= e($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($preview === null): ?>
        <div class="page-card bg-white rounded-3 shadow-sm p-4">
            <form method="POST" enctype="multipart/form-data">
                <?= csrf_field('import_documents') ?>
                <input type="hidden" name="action" value="preview">
                <label class="form-label" for="import_file">Excel workbook (.xlsx)</label>
                <input class="form-control" type="file" name="import_file" id="import_file" accept=".xlsx" required>
                <div class="form-text mt-3">
                    Required columns: <strong>Title</strong>, <strong>Document Type</strong>, <strong>Origin Department</strong>, <strong>Date Received</strong>.<br>
                    Optional columns: Description, Ref No, Current Department, Assigned Staff, Due Date, Priority, Confidentiality, Status.<br>
                    Tracking numbers are generated after confirmation. Maximum 500 rows and 10 MB.
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-eye"></i> Preview Import
                    </button>
                    <a
                        class="btn btn-outline-success"
                        href="<?= e(app_url('docs/Document_Import_Template.xlsx')) ?>"
                        download
                    >
                        <i class="bi bi-download"></i> Download Sample XLSX
                    </a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="page-card bg-white rounded-3 shadow-sm p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h4 class="h6 mb-1">Import Preview</h4>
                    <div class="text-muted small">
                        <?= count($preview['valid_rows'] ?? []) ?> valid row(s),
                        <?= count($preview['errors'] ?? []) ?> invalid row(s),
                        <?= (int) ($preview['total_rows'] ?? 0) ?> non-empty row(s)
                    </div>
                </div>
                <span class="badge text-bg-secondary">Tracking numbers generated after confirmation</span>
            </div>

            <?php if (!empty($preview['valid_rows'])): ?>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Excel Row</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Ref No</th>
                                <th>Origin</th>
                                <th>Current Department</th>
                                <th>Assigned Staff</th>
                                <th>Date Received</th>
                                <th>Due Date</th>
                                <th>Priority</th>
                                <th>Confidentiality</th>
                                <th>Status</th>
                                <th>Review</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview['valid_rows'] as $row): ?>
                                <tr>
                                    <td><?= (int) $row['excel_row'] ?></td>
                                    <td><?= e((string) $row['title']) ?></td>
                                    <td><?= e((string) $row['document_type_name']) ?></td>
                                    <td><?= e((string) $row['description']) ?></td>
                                    <td><?= e((string) $row['ref_no']) ?></td>
                                    <td><?= e((string) $row['origin_department_name']) ?></td>
                                    <td><?= e((string) $row['current_department_name']) ?></td>
                                    <td><?= e((string) ($row['assigned_staff_name'] ?? 'Unassigned')) ?></td>
                                    <td><?= e((string) $row['date_received']) ?></td>
                                    <td><?= e((string) ($row['date_due'] ?? '')) ?></td>
                                    <td><?= e((string) $row['priority']) ?></td>
                                    <td><?= e((string) $row['confidentiality_label']) ?></td>
                                    <td><?= e((string) $row['status_name']) ?></td>
                                    <td>
                                        <?php if (!empty($row['warnings'])): ?>
                                            <?php foreach ($row['warnings'] as $warning): ?>
                                                <span class="badge text-bg-warning mb-1"><?= e((string) $warning) ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="badge text-bg-success">Ready</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($preview['errors'])): ?>
                <div class="alert alert-warning">
                    <strong>Rows with errors will be skipped. Check the Excel Row and Cell columns for the exact location.</strong>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm table-bordered bg-white mb-0">
                            <thead>
                                <tr>
                                    <th>Excel Row</th>
                                    <th>Cell</th>
                                    <th>Field</th>
                                    <th>Problem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($preview['errors'] as $error): ?>
                                    <tr>
                                        <td><?= (int) $error['excel_row'] ?></td>
                                        <td><?= e((string) ($error['cell'] ?? 'Row-level')) ?></td>
                                        <td><?= e((string) ($error['field'] ?? 'Workbook')) ?></td>
                                        <td><?= e((string) $error['message']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2">
                <?php if (!empty($preview['valid_rows'])): ?>
                    <form method="POST">
                        <?= csrf_field('import_documents') ?>
                        <input type="hidden" name="action" value="confirm">
                        <input type="hidden" name="preview_token" value="<?= e($previewToken) ?>">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i>
                            Import <?= count($preview['valid_rows']) ?> Valid Row(s)
                        </button>
                    </form>
                <?php endif; ?>
                <form method="POST">
                    <?= csrf_field('import_documents') ?>
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="preview_token" value="<?= e($previewToken) ?>">
                    <button type="submit" class="btn btn-outline-secondary">Cancel Preview</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
