<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| CLO DOCUMENT MANAGEMENT
|--------------------------------------------------------------------------
| Features:
| - Receive Document
| - Auto Generate Tracking Number
| - Edit Document
| - Archive Document
| - Search Documents
| - Upload PDF/DOC/DOCX
| - Active / Archived / All filtering
| - CSRF protection
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/functions/security.php';
require_once dirname(__DIR__, 2) . '/functions/auth.php';
require_once dirname(__DIR__, 2) . '/functions/document_access.php';
require_once dirname(__DIR__, 2) . '/functions/document_assignment.php';
require_once dirname(__DIR__, 2) . '/functions/attachment_storage.php';
require_once dirname(__DIR__, 2) . '/models/AuditTrailModel.php';
require_once dirname(__DIR__, 2) . '/models/DocumentRoutingModel.php';

/*
|--------------------------------------------------------------------------
| SECURE SESSION
|--------------------------------------------------------------------------
*/

if (function_exists('start_secure_session')) {
    start_secure_session();
}

if (function_exists('auth_require_page')) {
    auth_require_page('document_management.php');
} elseif (function_exists('auth_require_login')) {
    auth_require_login();
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
| Local token so this page does not depend on an unknown CSRF function
| existing in security.php.
|--------------------------------------------------------------------------
*/

function document_csrf_token(): string
{
    if (
        !isset($_SESSION['document_csrf_token']) ||
        !is_string($_SESSION['document_csrf_token']) ||
        $_SESSION['document_csrf_token'] === ''
    ) {
        $_SESSION['document_csrf_token'] = bin2hex(

            random_bytes(32)
        );
    }

    return $_SESSION['document_csrf_token'];
}

function document_verify_csrf(): bool
{
    $sessionToken = $_SESSION['document_csrf_token'] ?? '';
    $postedToken  = $_POST['csrf_token'] ?? '';

    if (
        !is_string($sessionToken) ||
        !is_string($postedToken) ||
        $sessionToken === '' ||
        $postedToken === ''
    ) {
        return false;
    }

    return hash_equals(
        $sessionToken,
        $postedToken
    );
}

function document_user_is_latest_route_recipient(
    int $documentId,
    int $userId
): bool {
    return $documentId > 0
        && $userId > 0
        && routing_is_latest_recipient($documentId, $userId);
}

$csrfToken = document_csrf_token();

/**
 * Fetches all uploaded file versions for a document.
 */
function get_document_attachments(mysqli $conn, int $documentId): array
{
    $stmt = $conn->prepare("
        SELECT
            a.*,
            CONCAT(
                COALESCE(u.first_name, ''),
                ' ',
                COALESCE(u.last_name, '')
            ) AS uploaded_by_name
        FROM attachments a
        LEFT JOIN users u
            ON u.user_id = a.uploaded_by_user_id
        WHERE a.document_id = ?
        ORDER BY a.uploaded_at DESC, a.attachment_id DESC
    ");

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $documentId);

    if (!$stmt->execute()) {
        $stmt->close();
        return [];
    }

    $result = $stmt->get_result();
    $attachments = $result->fetch_all(MYSQLI_ASSOC) ?: [];
    $stmt->close();

    return $attachments;
}

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = get_database_config();

$conn = new mysqli(
    $db['host'],
    $db['user'],
    $db['pass'],
    $db['name'],
    $db['port']
);

if ($conn->connect_error) {
    die('Database connection failed.');
}

$conn->set_charset(
    $db['charset'] ?? 'utf8mb4'
);

/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$currentUserId = 0;
$canManageDocuments = function_exists('auth_can_manage_documents')
    && auth_can_manage_documents();
$canPrintTrackingSlips = auth_is_role('INCOMING');
$canUploadDocumentFiles = auth_can_access_page('document_management.php');

if (function_exists('auth_user')) {

    $currentUser = auth_user();

    if (is_array($currentUser)) {

        $currentUserId = (int) (
            $currentUser['user_id'] ?? 0
        );
    }
}

/*
|--------------------------------------------------------------------------
| UPLOAD CONFIGURATION
|--------------------------------------------------------------------------
*/

$uploadDir = dirname(__DIR__, 2) . '/uploads/';

$maxFileSize = 10 * 1024 * 1024;

$allowedFileTypes = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$allowedExtensions = array_keys($allowedFileTypes);
$allowedMimeTypes = array_values($allowedFileTypes);

if (!is_dir($uploadDir)) {

    if (
        !mkdir(
            $uploadDir,
            0755,
            true
        )
        &&
        !is_dir($uploadDir)
    ) {

        die(
            'Unable to create uploads directory.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function document_redirect(
    string $type,
    string $message,
    array $extra = []
): never {

    $params = array_merge(
        [
            'message_type' => $type,
            'message' => $message
        ],
        $extra
    );

    header(
        'Location: document_management.php?' .
        http_build_query($params)
    );

    exit;
}

function document_valid_datetime(
    string $value
): bool {

    if ($value === '') {
        return false;
    }

    $date = DateTime::createFromFormat(
        'Y-m-d\TH:i',
        $value
    );

    return $date !== false;
}

function document_valid_date(string $value): bool
{
    $date = DateTime::createFromFormat('!Y-m-d', $value);

    return $date !== false && $date->format('Y-m-d') === $value;
}

function document_normalize_reference(string $value): string
{
    $value = trim($value);
    if ($value === '' || stripos($value, 'CLO-') === 0) {
        return $value;
    }

    return preg_match('/^[0-9][0-9-]*$/', $value) === 1
        ? 'CLO-' . $value
        : $value;
}

function document_tracking_slip_override_key(int $documentId): string
{
    return 'tracking_slip_overrides_' . $documentId;
}

function document_tracking_slip_get_overrides(int $documentId): array
{
    $key = document_tracking_slip_override_key($documentId);
    $value = $_SESSION[$key] ?? [];

    if (!is_array($value)) {
        return [];
    }

    return $value;
}

function document_priority_from_db(?string $value): string
{
    $normalized = strtoupper(trim((string) $value));

    return match ($normalized) {
        'LOW' => 'Low',
        'HIGH' => 'High',
        'URGENT' => 'Urgent',
        'MEDIUM', 'NORMAL' => 'Medium',
        default => 'Medium',
    };
}

function document_priority_to_db(string $value): string
{
    return match (document_priority_from_db($value)) {
        'Low' => 'LOW',
        'High' => 'HIGH',
        'Urgent' => 'URGENT',
        default => 'MEDIUM',
    };
}

function document_confidentiality_from_db(?string $value): string
{
    $normalized = strtoupper(
        str_replace(
            [' ', '-'],
            '_',
            trim((string) $value)
        )
    );

    return match ($normalized) {
        'CONFIDENTIAL' => 'Confidential',
        'HIGHLY_CONFIDENTIAL', 'RESTRICTED' => 'Highly Confidential',
        default => 'Normal',
    };
}

function document_confidentiality_to_db(string $value): string
{
    $normalized = strtoupper(
        str_replace(
            [' ', '-'],
            '_',
            trim($value)
        )
    );

    return match ($normalized) {
        'CONFIDENTIAL' => 'CONFIDENTIAL',
        'HIGHLY_CONFIDENTIAL', 'RESTRICTED' => 'HIGHLY_CONFIDENTIAL',
        'PUBLIC' => 'PUBLIC',
        default => 'INTERNAL',
    };
}

function document_user_exists(
    mysqli $conn,
    int $userId
): bool {

    if ($userId <= 0) {
        return false;
    }

    $stmt = $conn->prepare("
        SELECT user_id
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new RuntimeException(
            'Unable to prepare user validation.'
        );
    }

    $stmt->bind_param(
        'i',
        $userId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $exists = $result->num_rows > 0;

    $stmt->close();

    return $exists;
}

function document_user_belongs_to_department(
    mysqli $conn,
    int $userId,
    int $departmentId
): bool {
    if ($userId <= 0 || $departmentId <= 0) {
        return false;
    }

    $stmt = $conn->prepare('
        SELECT user_id
        FROM users
        WHERE user_id = ?
          AND department_id = ?
          AND is_active = 1
        LIMIT 1
    ');

    if (!$stmt) {
        throw new RuntimeException(
            'Unable to prepare staff department validation.'
        );
    }

    $stmt->bind_param(
        'ii',
        $userId,
        $departmentId
    );

    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

/*
|--------------------------------------------------------------------------
| DOCUMENT STATUS PRIMARY KEY
|--------------------------------------------------------------------------
*/

function get_document_status_primary_key(
    mysqli $conn
): string {

    static $primaryKey = null;

    if ($primaryKey !== null) {
        return $primaryKey;
    }

    $result = $conn->query(
        'SHOW COLUMNS FROM document_status'
    );

    if (!$result) {

        throw new RuntimeException(
            'Unable to inspect document_status table.'
        );
    }

    $columns = [];

    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    $result->free();

    if (
        in_array(
            'status_id',
            $columns,
            true
        )
    ) {

        $primaryKey = 'status_id';

        return $primaryKey;
    }

    if (
        in_array(
            'document_status_id',
            $columns,
            true
        )
    ) {

        $primaryKey = 'document_status_id';

        return $primaryKey;
    }

    throw new RuntimeException(
        'No valid primary key was found in document_status.'
    );
}

/*
|--------------------------------------------------------------------------
| GET STATUS ID
|--------------------------------------------------------------------------
*/

function get_document_status_id(
    mysqli $conn,
    string $statusName
): int {

    $statusPrimaryKey =
        get_document_status_primary_key($conn);

    $sql = "
        SELECT
            `$statusPrimaryKey`
        FROM document_status
        WHERE LOWER(status_name) = LOWER(?)
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        throw new RuntimeException(
            'Unable to prepare status query.'
        );
    }

    $stmt->bind_param(
        's',
        $statusName
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    $stmt->close();

    return (int) (
        $row[$statusPrimaryKey] ?? 0
    );
}

/*
|--------------------------------------------------------------------------
| GET DOCUMENT
|--------------------------------------------------------------------------
*/

function get_document_by_id(
    mysqli $conn,
    int $documentId,
    string $statusPrimaryKey
): ?array {

    $sql = "
        SELECT
            d.*,
            ds.status_name
        FROM documents d
        LEFT JOIN document_status ds
            ON ds.`$statusPrimaryKey` =
               d.current_status_id
        WHERE d.document_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException(
            'Unable to prepare document lookup.'
        );
    }

    $stmt->bind_param(
        'i',
        $documentId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $document = $result->fetch_assoc();

    $stmt->close();

    return $document ?: null;
}

/*
|--------------------------------------------------------------------------
| POST REQUEST VALIDATION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!document_verify_csrf()) {

        $errors = [
            'Invalid or expired security token. Please refresh the page and try again.'
        ];

    } else {

        $errors = [];
    }

} else {

    $errors = [];
}

/*
|--------------------------------------------------------------------------
| ACCEPT ASSIGNED DOCUMENT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['accept_assignment'])
    && empty($errors)
) {
    $assignmentDocumentId = filter_input(INPUT_POST, 'document_id', FILTER_VALIDATE_INT);
    $assignmentDocumentId = $assignmentDocumentId !== false && $assignmentDocumentId !== null
        ? (int) $assignmentDocumentId
        : 0;

    try {
        $stmt = db_execute(
            'UPDATE document_assignment_receipts dar
             INNER JOIN documents d ON d.document_id = dar.document_id
             SET dar.accepted_at = CURRENT_TIMESTAMP
             WHERE dar.document_id = ?
               AND dar.user_id = ?
               AND dar.accepted_at IS NULL
               AND d.current_assignee_user_id = ?',
            'iii',
            [$assignmentDocumentId, $currentUserId, $currentUserId]
        );
        $accepted = $stmt->affected_rows === 1;
        $stmt->close();

        if (!$accepted) {
            throw new RuntimeException('This document is not awaiting your acceptance.');
        }

        audit_log_create(
            $currentUserId,
            'DOCUMENT',
            $assignmentDocumentId,
            'ASSIGNMENT_ACCEPTED',
            ['accepted_at' => null],
            ['accepted_by_user_id' => $currentUserId]
        );
        routing_set_document_status($conn, $assignmentDocumentId, 'Received');
        document_redirect('success', 'Assigned document accepted successfully.');
    } catch (Throwable $exception) {
        $errors[] = 'Unable to accept assignment: ' . $exception->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| DELETE ATTACHMENT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['delete_attachment'])
    && empty($errors)
) {

    $documentId = filter_input(
        INPUT_POST,
        'document_id',
        FILTER_VALIDATE_INT
    );

    $attachmentId = filter_input(
        INPUT_POST,
        'attachment_id',
        FILTER_VALIDATE_INT
    );

    if (!$documentId || !$attachmentId) {

        $errors[] = 'Invalid attachment selected.';
    }

    if (empty($errors)) {

        $transactionStarted = false;
        $movedAttachmentPath = null;
        $attachmentArchivePath = null;

        try {

            $stmt = $conn->prepare('
                                SELECT
                                        a.file_name,
                                        a.file_path,
                                        a.uploaded_by_user_id,
                                        d.document_id,
                                        d.confidentiality_level,
                                        d.created_by_user_id,
                                        d.current_assignee_user_id
                                FROM attachments a
                                INNER JOIN documents d
                                        ON d.document_id = a.document_id
                                WHERE a.attachment_id = ?
                                    AND a.document_id = ?
                LIMIT 1
            ');

            if (!$stmt) {

                throw new RuntimeException(
                    'Unable to prepare attachment lookup.'
                );
            }

            $stmt->bind_param(
                'ii',
                $attachmentId,
                $documentId
            );

            $stmt->execute();

            $attachment =
                $stmt->get_result()->fetch_assoc();

            $stmt->close();

            if (!$attachment) {

                throw new RuntimeException(
                    'Attachment was not found for this document.'
                );
            }

            $attachmentUploaderId = (int) ($attachment['uploaded_by_user_id'] ?? 0);
            $isAssignedOrRoutedToUser =
                $currentUserId === (int) ($attachment['current_assignee_user_id'] ?? 0)
                || document_user_is_latest_route_recipient(
                    $documentId,
                    $currentUserId
                );
            $canDeleteAttachment =
                $canManageDocuments
                || $attachmentUploaderId === $currentUserId
                || $isAssignedOrRoutedToUser;

            if (!$canDeleteAttachment) {
                throw new RuntimeException(
                    'You do not have permission to delete this document file.'
                );
            }

            $relativePath = ltrim(
                (string) ($attachment['file_path'] ?? ''),
                '/\\'
            );
            $uploadsRoot = realpath($uploadDir);
            $filePath = realpath(dirname(__DIR__, 2) . '/' . $relativePath);

            if (
                $uploadsRoot !== false
                && $filePath !== false
                && str_starts_with(
                    $filePath,
                    $uploadsRoot . DIRECTORY_SEPARATOR
                )
                && is_file($filePath)
            ) {
                $archiveDirectory = $uploadDir . 'orphaned' . DIRECTORY_SEPARATOR;

                if (
                    !is_dir($archiveDirectory)
                    && !mkdir($archiveDirectory, 0755, true)
                    && !is_dir($archiveDirectory)
                ) {
                    throw new RuntimeException(
                        'Unable to prepare the orphaned attachment folder.'
                    );
                }

                $archiveFilename = attachment_storage_filename(
                    (string) ($attachment['file_name'] ?? ''),
                    $archiveDirectory
                );
                $attachmentArchivePath = $archiveDirectory . $archiveFilename;

                if (!rename($filePath, $attachmentArchivePath)) {
                    throw new RuntimeException(
                        'Unable to move the attachment to the orphaned folder.'
                    );
                }

                $movedAttachmentPath = $filePath;
            }

            $conn->begin_transaction();
            $transactionStarted = true;

            $stmt = $conn->prepare('
                DELETE FROM attachments
                WHERE attachment_id = ?
                  AND document_id = ?
            ');

            if (!$stmt) {

                throw new RuntimeException(
                    'Unable to prepare attachment delete.'
                );
            }

            $stmt->bind_param(
                'ii',
                $attachmentId,
                $documentId
            );

            if (!$stmt->execute() || $stmt->affected_rows !== 1) {

                $stmt->close();

                throw new RuntimeException(
                    'Unable to delete attachment.'
                );
            }

            $stmt->close();

            $conn->commit();

            document_redirect(
                'success',
                'Attachment deleted successfully.'
            );

        } catch (Throwable $exception) {

            if ($transactionStarted) {
                $conn->rollback();
            }

            if (
                $movedAttachmentPath !== null
                && $attachmentArchivePath !== null
                && is_file($attachmentArchivePath)
            ) {
                rename($attachmentArchivePath, $movedAttachmentPath);
            }

            $errors[] =
                'Unable to delete attachment: ' .
                $exception->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| DOCUMENT TYPES
|--------------------------------------------------------------------------
*/

$documentTypes = [];

$typeResult = $conn->query("
    SELECT
        document_type_id,
        type_name
    FROM document_types
    ORDER BY type_name ASC
");

if ($typeResult) {

    while ($row = $typeResult->fetch_assoc()) {
        $documentTypes[] = $row;
    }

    $typeResult->free();
}

/*
|--------------------------------------------------------------------------
| DEPARTMENTS
|--------------------------------------------------------------------------
*/

$departments = [];

$departmentResult = $conn->query("
    SELECT
        department_id,
        department_name
    FROM departments
    WHERE is_active = 1
    ORDER BY department_name ASC
");

if ($departmentResult) {

    while ($row = $departmentResult->fetch_assoc()) {
        $departments[] = $row;
    }

    $departmentResult->free();
}

/*
|--------------------------------------------------------------------------
| USERS
|--------------------------------------------------------------------------
*/

$users = [];

$userResult = $conn->query("
    SELECT
        user_id,
        username,
        first_name,
        last_name,
        department_id
    FROM users
    WHERE is_active = 1
    ORDER BY first_name ASC, last_name ASC
");

if ($userResult) {

    while ($row = $userResult->fetch_assoc()) {
        $users[] = $row;
    }

    $userResult->free();
}

/*
|--------------------------------------------------------------------------
| STATUS LIST
|--------------------------------------------------------------------------
*/

$statusPrimaryKey =
    get_document_status_primary_key($conn);

$statuses = [];

$statusResult = $conn->query("
    SELECT
        `$statusPrimaryKey` AS status_id,
        status_name
    FROM document_status
    ORDER BY `$statusPrimaryKey` ASC
");

if ($statusResult) {

    while ($row = $statusResult->fetch_assoc()) {
        $statuses[] = $row;
    }

    $statusResult->free();
}

/*
|--------------------------------------------------------------------------
| RECEIVE DOCUMENT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['receive_document'])
    && $canManageDocuments
    &&
    empty($errors)
) {

    $dateReceived = trim(
        (string) (
            $_POST['date_received'] ?? ''
        )
    );

    $title = trim(
        (string) (
            $_POST['title'] ?? ''
        )
    );

    $description = trim(
        (string) (
            $_POST['description'] ?? ''
        )
    );

    $documentTypeId = filter_input(
        INPUT_POST,
        'document_type_id',
        FILTER_VALIDATE_INT
    );

    $originDepartmentId = filter_input(
        INPUT_POST,
        'origin_department_id',
        FILTER_VALIDATE_INT
    );

    $originDepartmentSelection = trim(
        (string) (
            $_POST['origin_department_id'] ?? ''
        )
    );

    $originDepartmentOther = trim(
        (string) (
            $_POST['origin_department_other'] ?? ''
        )
    );

    $currentDepartmentId = filter_input(
        INPUT_POST,
        'current_department_id',
        FILTER_VALIDATE_INT
    );

    $assignedUserId = filter_input(
        INPUT_POST,
        'current_assignee_user_id',
        FILTER_VALIDATE_INT
    );

    $priority = trim(
        (string) (
            $_POST['priority_level']
            ?? 'Normal'
        )
    );

    $confidentiality = trim(
        (string) (
            $_POST['confidentiality_level']
            ?? 'Normal'
        )
    );

    $externalReference = document_normalize_reference(
        (string) (
            $_POST['external_reference_no']
            ?? ''
        )
    );

    $dateDue = trim(
        (string) (
            $_POST['date_due'] ?? ''
        )
    );

    $statusId = filter_input(
        INPUT_POST,
        'current_status_id',
        FILTER_VALIDATE_INT
    );

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($dateReceived === '') {

        $errors[] =
            'Date received is required.';

    } elseif (
        !document_valid_datetime(
            $dateReceived
        )
    ) {

        $errors[] =
            'Invalid date received.';
    }

    if ($title === '') {

        $errors[] =
            'Document title is required.';

    } elseif (
        mb_strlen($title) > 255
    ) {

        $errors[] =
            'Document title must not exceed 255 characters.';
    }

    if (
        mb_strlen($description) > 2000
    ) {

        $errors[] =
            'Description must not exceed 2000 characters.';

    } elseif ($description === '') {

        $errors[] =
            'Description is required.';
    }

    if (!$documentTypeId) {

        $errors[] =
            'Document type is required.';
    }

    if ($originDepartmentSelection === 'other') {
        $othersDepartmentStmt = $conn->prepare(
            'SELECT department_id FROM departments WHERE department_code = ? LIMIT 1'
        );
        $othersDepartmentCode = 'OTHERS';

        if ($othersDepartmentStmt) {
            $othersDepartmentStmt->bind_param('s', $othersDepartmentCode);
            $othersDepartmentStmt->execute();
            $othersDepartmentRow = $othersDepartmentStmt->get_result()->fetch_assoc();
            $othersDepartmentStmt->close();
            $originDepartmentId = $othersDepartmentRow
                ? (int) $othersDepartmentRow['department_id']
                : false;
        }

        if (!$originDepartmentId) {
            $errors[] =
                'The Others department option is not configured.';
        } elseif ($originDepartmentOther === '') {
            $errors[] =
                'Please enter the name of the person or source document.';
        } elseif (mb_strlen($originDepartmentOther) > 255) {
            $errors[] =
                'The Others name must not exceed 255 characters.';
        }
    } elseif (!$originDepartmentId) {

        $errors[] =
            'Origin department is required.';
    } else {
        $originDepartmentOther = '';
    }

    if (!$currentDepartmentId) {

        $errors[] =
            'Current department is required.';
    }

    if (
        !in_array(
            $priority,
            [
                'Low',
                'Medium',
                'Normal',
                'High',
                'Urgent'
            ],
            true
        )
    ) {

        $errors[] =
            'Invalid priority level.';
    }

    if (
        !in_array(
            $confidentiality,
            [
                'Normal',
                'Confidential',
                'Highly Confidential'
            ],
            true
        )
    ) {

        $errors[] =
            'Invalid confidentiality level.';
    }

    if (
        $dateDue !== ''
        &&
        !document_valid_date(
            $dateDue
        )
    ) {

        $errors[] =
            'Invalid due date.';
    }

    /*
    |--------------------------------------------------------------------------
    | CURRENT USER
    |--------------------------------------------------------------------------
    */

    if ($currentUserId <= 0) {

        $errors[] =
            'Unable to determine the logged-in user. Please log in again.';

    } else {

        try {

            if (
                !document_user_exists(
                    $conn,
                    $currentUserId
                )
            ) {

                $errors[] =
                    'The logged-in user does not exist.';
            }

        } catch (Throwable $exception) {

            $errors[] =
                $exception->getMessage();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ASSIGNED USER
    |--------------------------------------------------------------------------
    */

    if (
        $assignedUserId !== false
        &&
        $assignedUserId !== null
        &&
        (int) $assignedUserId > 0
    ) {

        try {

            if (
                !document_user_exists(
                    $conn,
                    (int) $assignedUserId
                )
            ) {

                $errors[] =
                    'The selected assigned staff does not exist.';
            } elseif (
                !$currentDepartmentId
                || !document_user_belongs_to_department(
                    $conn,
                    (int) $assignedUserId,
                    (int) $currentDepartmentId
                )
            ) {
                $errors[] =
                    'Assigned Staff must belong to the selected Current Department.';
            }

        } catch (Throwable $exception) {

            $errors[] =
                $exception->getMessage();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FILE VALIDATION
    |--------------------------------------------------------------------------
    */

    $uploadedFile =
        $_FILES['document_file']
        ?? null;

    $hasFile = (
        is_array($uploadedFile)
        &&
        (
            $uploadedFile['error']
            ??
            UPLOAD_ERR_NO_FILE
        ) !== UPLOAD_ERR_NO_FILE
    );

    if ($hasFile) {

        $uploadError =
            (int) (
                $uploadedFile['error']
                ??
                UPLOAD_ERR_NO_FILE
            );

        if (
            $uploadError !== UPLOAD_ERR_OK
        ) {

            $errors[] =
                'File upload failed.';
        }

        $fileSize =
            (int) (
                $uploadedFile['size']
                ?? 0
            );

        if (
            $fileSize <= 0
        ) {

            $errors[] =
                'Uploaded file is empty.';

        } elseif (
            $fileSize > $maxFileSize
        ) {

            $errors[] =
                'File size must not exceed 10 MB.';
        }

        $originalName =
            basename(
                (string) (
                    $uploadedFile['name']
                    ?? ''
                )
            );

        $extension =
            strtolower(
                pathinfo(
                    $originalName,
                    PATHINFO_EXTENSION
                )
            );

        if (
            !in_array(
                $extension,
                $allowedExtensions,
                true
            )
        ) {

            $errors[] =
                'Only PDF, DOC and DOCX files are allowed.';
        }

        $tmpName =
            (string) (
                $uploadedFile['tmp_name']
                ?? ''
            );

        if (
            $uploadError === UPLOAD_ERR_OK
            &&
            !is_uploaded_file($tmpName)
        ) {

            $errors[] =
                'Invalid uploaded file.';
        }

        if (
            $uploadError === UPLOAD_ERR_OK
            &&
            is_uploaded_file($tmpName)
        ) {

            $finfo =
                new finfo(
                    FILEINFO_MIME_TYPE
                );

            $mimeType =
                $finfo->file($tmpName);

            if (
                !isset($allowedFileTypes[$extension])
                || $mimeType !== $allowedFileTypes[$extension]
            ) {

                $errors[] =
                    'Invalid file type detected.';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE DOCUMENT
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $destination = '';

        try {

            $conn->begin_transaction();

            $receivedStatusId =
                get_document_status_id(
                    $conn,
                    'Received'
                );

            if (!$receivedStatusId) {

                throw new RuntimeException(
                    'The "Received" status does not exist.'
                );
            }

            if ($statusId && $statusId > 0) {
                $receivedStatusId = $statusId;
            }

            $formattedDate =
                str_replace(
                    'T',
                    ' ',
                    $dateReceived
                ) . ':00';

            $formattedDueDate = null;

            if ($dateDue !== '') {

                $formattedDueDate = $dateDue;
            }

            $assignedUserValue =
                (
                    $assignedUserId !== false
                    &&
                    $assignedUserId !== null
                    &&
                    (int) $assignedUserId > 0
                )
                    ? (int) $assignedUserId
                    : null;

            /*
            |--------------------------------------------------------------------------
            | INSERT DOCUMENT
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            | Correct bind_param types:
            |
            | ss
            | iiiiii
            | sssss
            |
            | 13 variables total.
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                INSERT INTO documents
                (
                    tracking_no,
                    title,
                    description,
                    document_type_id,
                    current_status_id,
                    origin_department_id,
                    origin_department_other,
                    current_department_id,
                    created_by_user_id,
                    current_assignee_user_id,
                    priority_level,
                    confidentiality_level,
                    external_reference_no,
                    date_received,
                    date_due,
                    created_at,
                    updated_at
                )
                VALUES
                (
                    '',
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    CURRENT_TIMESTAMP,
                    CURRENT_TIMESTAMP
                )
            ");

            if (!$stmt) {

                throw new RuntimeException(
                    'Unable to prepare document insert.'
                );
            }

            /*
            | 13 variables
            |
            | s = title
            | s = description
            | i = document type
            | i = status
            | i = origin department
            | i = current department
            | i = created by
            | i = assigned user
            | s = priority
            | s = confidentiality
            | s = external reference
            | s = date received
            | s = due date
            */

            $priorityDb = document_priority_to_db($priority);
            $confidentialityDb = document_confidentiality_to_db($confidentiality);

            $stmt->bind_param(
                'ssiiisiiisssss',
                $title,
                $description,
                $documentTypeId,
                $receivedStatusId,
                $originDepartmentId,
                $originDepartmentOther,
                $currentDepartmentId,
                $currentUserId,
                $assignedUserValue,
                $priorityDb,
                $confidentialityDb,
                $externalReference,
                $formattedDate,
                $formattedDueDate
            );

            if (!$stmt->execute()) {

                throw new RuntimeException(
                    'Unable to insert document: ' .
                    $stmt->error
                );
            }

            $documentId =
                (int) $stmt->insert_id;

            $stmt->close();

            if ($documentId <= 0) {

                throw new RuntimeException(
                    'Document was not created.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | TRACKING NUMBER
            |--------------------------------------------------------------------------
            */

            $year =
                date(
                    'Y',
                    strtotime(
                        $formattedDate
                    )
                );

            $trackingNumber =
                'CLO-' .
                $year .
                '-' .
                str_pad(
                    (string) $documentId,
                    6,
                    '0',
                    STR_PAD_LEFT
                );

            $stmt = $conn->prepare("
                UPDATE documents
                SET
                    tracking_no = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE document_id = ?
            ");

            if (!$stmt) {

                throw new RuntimeException(
                    'Unable to prepare tracking number update.'
                );
            }

            $stmt->bind_param(
                'si',
                $trackingNumber,
                $documentId
            );

            if (!$stmt->execute()) {

                throw new RuntimeException(
                    'Unable to save tracking number.'
                );
            }

            $stmt->close();

            /*
            |--------------------------------------------------------------------------
            | FILE UPLOAD
            |--------------------------------------------------------------------------
            */

            if ($hasFile) {

                $originalFilename =
                    basename(
                        (string) (
                            $uploadedFile['name']
                        )
                    );

                $extension =
                    strtolower(
                        pathinfo(
                            $originalFilename,
                            PATHINFO_EXTENSION
                        )
                    );

                $storedFilename = attachment_storage_filename(
                    $originalFilename,
                    $uploadDir
                );

                $destination =
                    $uploadDir .
                    $storedFilename;

                if (
                    !move_uploaded_file(
                        $uploadedFile['tmp_name'],
                        $destination
                    )
                ) {

                    throw new RuntimeException(
                        'Unable to save uploaded file.'
                    );
                }

                $finfo =
                    new finfo(
                        FILEINFO_MIME_TYPE
                    );

                $detectedMime =
                    $finfo->file(
                        $destination
                    );

                $mimeType =
                    is_string($detectedMime)
                    &&
                    $detectedMime !== ''
                        ? $detectedMime
                        : 'application/octet-stream';

                $savedFileSize =
                    filesize(
                        $destination
                    );

                if (
                    $savedFileSize === false
                ) {

                    throw new RuntimeException(
                        'Unable to determine uploaded file size.'
                    );
                }

                $checksumSha256 =
                    hash_file(
                        'sha256',
                        $destination
                    );

                if (
                    $checksumSha256 === false
                ) {

                    throw new RuntimeException(
                        'Unable to calculate file checksum.'
                    );
                }

                $relativePath =
                    'uploads/' .
                    $storedFilename;

                $isPublic = 0;

                $stmt = $conn->prepare("
                    INSERT INTO attachments
                    (
                        document_id,
                        uploaded_by_user_id,
                        file_name,
                        stored_name,
                        file_path,
                        mime_type,
                        file_size_bytes,
                        checksum_sha256,
                        is_public,
                        uploaded_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        CURRENT_TIMESTAMP
                    )
                ");

                if (!$stmt) {

                    throw new RuntimeException(
                        'Unable to prepare attachment insert.'
                    );
                }

                $stmt->bind_param(
                    'iissssisi',
                    $documentId,
                    $currentUserId,
                    $originalFilename,
                    $originalFilename,
                    $relativePath,
                    $mimeType,
                    $savedFileSize,
                    $checksumSha256,
                    $isPublic
                );

                if (!$stmt->execute()) {

                    throw new RuntimeException(
                        'Unable to save attachment: ' .
                        $stmt->error
                    );
                }

                $stmt->close();
            }

            if ($assignedUserValue !== null) {
                document_assignment_set_receipt($conn, $documentId, $assignedUserValue, $currentUserId);
            }

            $conn->commit();

            audit_log_create(
                $currentUserId,
                'DOCUMENT',
                $documentId,
                'CREATED',
                null,
                [
                    'tracking_no' => $trackingNumber,
                    'title' => $title,
                    'current_assignee_user_id' => $assignedUserValue,
                    'current_status_id' => $receivedStatusId,
                ]
            );

            if ($assignedUserValue !== null) {
                audit_log_create(
                    $currentUserId,
                    'DOCUMENT',
                    $documentId,
                    'ASSIGNED',
                    null,
                    ['assigned_to_user_id' => $assignedUserValue]
                );
            }

            document_redirect(
                'success',
                'Document received successfully. Tracking Number: ' .
                $trackingNumber
            );

        } catch (Throwable $exception) {

            $conn->rollback();

            if (
                $destination !== ''
                &&
                is_file($destination)
            ) {

                @unlink($destination);
            }

            $errors[] =
                'Unable to save document: ' .
                $exception->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| ARCHIVE DOCUMENT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['archive_document'])
    &&
    empty($errors)
) {

    $documentId =
        filter_input(
            INPUT_POST,
            'document_id',
            FILTER_VALIDATE_INT
        );

    if (!$documentId) {

        $errors[] =
            'Invalid document.';

    } else {

        try {

            if ($currentUserId <= 0) {

                throw new RuntimeException(
                    'Unable to determine the logged-in user.'
                );
            }

            $archivedStatusId =
                get_document_status_id(
                    $conn,
                    'Archived'
                );

            if (!$archivedStatusId) {

                throw new RuntimeException(
                    'The "Archived" status does not exist in document_status.'
                );
            }

            $document =
                get_document_by_id(
                    $conn,
                    (int) $documentId,
                    $statusPrimaryKey
                );

            if (!$document) {

                throw new RuntimeException(
                    'Document not found.'
                );
            }

            if (
                !auth_has_any_role(['ADMIN', 'INCOMING'])
                && (int) ($document['current_assignee_user_id'] ?? 0) !== $currentUserId
            ) {
                throw new RuntimeException(
                    'You can only archive documents assigned to you.'
                );
            }

            $currentStatus =
                strtolower(
                    trim(
                        (string) (
                            $document['status_name']
                            ?? ''
                        )
                    )
                );

            if ($currentStatus === 'archived') {

                throw new RuntimeException(
                    'This document is already archived.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | SOFT ARCHIVE
            |--------------------------------------------------------------------------
            | The document remains in the documents table.
            | Only its status changes to Archived.
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                UPDATE documents
                SET
                    current_status_id = ?,
                    archived_at = CURRENT_TIMESTAMP,
                    archived_by_user_id = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE document_id = ?
                AND current_status_id <> ?
            ");

            if (!$stmt) {

                throw new RuntimeException(
                    'Unable to prepare archive query.'
                );
            }

            $stmt->bind_param(
                'iiii',
                $archivedStatusId,
                $currentUserId,
                $documentId,
                $archivedStatusId
            );

            if (!$stmt->execute()) {

                throw new RuntimeException(
                    'Unable to archive document: ' .
                    $stmt->error
                );
            }

            $affectedRows =
                $stmt->affected_rows;

            $stmt->close();

            if ($affectedRows !== 1) {

                throw new RuntimeException(
                    'Document was not archived.'
                );
            }

            document_redirect(
                'success',
                'Document "' .
                ($document['tracking_no'] ?? '') .
                '" was archived successfully.',
                [
                    'view' => 'archived'
                ]
            );

        } catch (Throwable $exception) {

            $errors[] =
                'Unable to archive document: ' .
                $exception->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| UPDATE CONFIDENTIALITY ONLY
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update_confidentiality'])
    && !$canManageDocuments
    && empty($errors)
) {
    $documentId = filter_input(INPUT_POST, 'document_id', FILTER_VALIDATE_INT);
    $confidentiality = trim((string) ($_POST['confidentiality_level'] ?? ''));

    if (!$documentId || !in_array($confidentiality, ['Normal', 'Confidential', 'Highly Confidential'], true)) {
        $errors[] = 'Invalid document or confidentiality level.';
    } else {
        try {
            $document = get_document_by_id($conn, (int) $documentId, 'document_status_id');
            if (!$document) {
                throw new RuntimeException('Document not found.');
            }

            if (
                !auth_is_role('ADMIN')
                && (int) ($document['current_assignee_user_id'] ?? 0) !== $currentUserId
            ) {
                throw new RuntimeException('Only the assigned user can change confidentiality.');
            }

            $confidentialityDb = document_confidentiality_to_db($confidentiality);
            $stmt = $conn->prepare(
                'UPDATE documents
                 SET confidentiality_level = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE document_id = ?'
            );
            $stmt->bind_param('si', $confidentialityDb, $documentId);
            $stmt->execute();
            $stmt->close();

            audit_log_create(
                $currentUserId,
                'DOCUMENT',
                (int) $documentId,
                'CONFIDENTIALITY_UPDATED',
                ['confidentiality_level' => (string) ($document['confidentiality_level'] ?? '')],
                ['confidentiality_level' => $confidentialityDb]
            );

            document_redirect('success', 'Document confidentiality updated.');
        } catch (Throwable $exception) {
            $errors[] = 'Unable to update confidentiality: ' . $exception->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| EDIT DOCUMENT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['update_document'])
    &&
    empty($errors)
) {

    $documentId =
        filter_input(
            INPUT_POST,
            'document_id',
            FILTER_VALIDATE_INT
        );

    $title =
        trim(
            (string) (
                $_POST['title'] ?? ''
            )
        );

    $description =
        trim(
            (string) (
                $_POST['description'] ?? ''
            )
        );

    $documentTypeId =
        filter_input(
            INPUT_POST,
            'document_type_id',
            FILTER_VALIDATE_INT
        );

    $priority =
        trim(
            (string) (
                $_POST['priority_level']
                ?? 'Normal'
            )
        );

    $confidentiality =
        trim(
            (string) (
                $_POST['confidentiality_level']
                ?? 'Normal'
            )
        );

    $externalReference =
        document_normalize_reference(
            (string) (
                $_POST['external_reference_no']
                ?? ''
            )
        );

    $dateDue =
        trim(
            (string) (
                $_POST['date_due'] ?? ''
            )
        );

    $assignedUserId =
        filter_input(
            INPUT_POST,
            'current_assignee_user_id',
            FILTER_VALIDATE_INT
        );

    $currentStatusId =
        filter_input(
            INPUT_POST,
            'current_status_id',
            FILTER_VALIDATE_INT
        );

    $slipTrackingNo = trim((string) ($_POST['tracking_no'] ?? ''));
    $slipRefNo = document_normalize_reference(
        trim((string) ($_POST['ref_no'] ?? ''))
    );
    if (
        !array_key_exists('external_reference_no', $_POST)
        || ($externalReference === '' && $slipRefNo !== '')
    ) {
        $externalReference = document_normalize_reference($slipRefNo);
    }
    if (array_key_exists('external_reference_no', $_POST)) {
        $slipRefNo = $externalReference;
    }
    $slipFrom = trim((string) ($_POST['from'] ?? ''));
    $slipDateReceived = trim((string) ($_POST['date_received'] ?? ''));
    $slipRemarks = trim((string) ($_POST['remarks'] ?? ''));
    $slipAssignedTo = trim((string) ($_POST['assigned_to'] ?? ''));
    $slipActionAppropriate = !empty($_POST['action_appropriate_action']);
    $slipActionReview = !empty($_POST['action_review']);
    $slipActionCommentReply = !empty($_POST['action_comment_reply']);
    $slipActionReference = !empty($_POST['action_reference']);
    $slipActionCountersign = !empty($_POST['action_countersign']);
    $slipActionOthers = !empty($_POST['action_others']);
    $slipTaskDraft = !empty($_POST['task_draft']);
    $slipTaskDiscuss = !empty($_POST['task_discuss']);
    $slipTaskAttend = !empty($_POST['task_attend']);
    $slipTaskFacilitate = !empty($_POST['task_facilitate']);
    $slipTaskReview = !empty($_POST['task_review']);
    $slipTaskFile = !empty($_POST['task_file']);
    $slipTaskRelease = !empty($_POST['task_release']);
    $slipTaskNote = !empty($_POST['task_note']);
    $slipTaskInvestigation = !empty($_POST['task_investigation']);
    $slipTaskDisseminate = !empty($_POST['task_disseminate']);
    $slipTaskOthers = !empty($_POST['task_others']);
    $slipTaskEndorse = !empty($_POST['task_endorse']);
    $slipOutputLetter = !empty($_POST['output_letter']);
    $slipOutputMemo = !empty($_POST['output_memo']);
    $slipOutputInspection = !empty($_POST['output_inspection']);
    $slipOutputMeeting = !empty($_POST['output_meeting']);
    $slipOutputContract = !empty($_POST['output_contract']);
    $slipOutputInvestigation = !empty($_POST['output_investigation']);
    $slipOutputAffidavit = !empty($_POST['output_affidavit']);
    $slipOutputOthers = !empty($_POST['output_others']);

    $editErrors = [];
    $document = null;

    if (!$documentId) {

        $editErrors[] =
            'Invalid document.';
    } else {
        try {
            $document = get_document_by_id(
                $conn,
                (int) $documentId,
                'document_status_id'
            );

            if (!$document) {
                throw new RuntimeException('Document not found.');
            }

            $canEditAsRoutedRecipient = document_user_is_latest_route_recipient(
                (int) $documentId,
                $currentUserId
            );
            $canEditThisDocument = $canManageDocuments
                || (int) ($document['current_assignee_user_id'] ?? 0) === $currentUserId
                || $canEditAsRoutedRecipient;

            if (!$canEditThisDocument) {
                throw new RuntimeException('You can only edit documents assigned to you.');
            }

            if (!$canManageDocuments) {
                $assignedUserId = isset($document['current_assignee_user_id'])
                    ? (int) $document['current_assignee_user_id']
                    : null;
            }

            if (!array_key_exists('external_reference_no', $_POST)) {
                $externalReference = (string) ($document['external_reference_no'] ?? '');
            }
        } catch (Throwable $exception) {
            $editErrors[] = 'Unable to verify document edit access: ' . $exception->getMessage();
        }
    }

    if ($title === '') {

        $editErrors[] =
            'Document title is required.';

    } elseif (
        mb_strlen($title) > 255
    ) {

        $editErrors[] =
            'Document title must not exceed 255 characters.';
    }

    if (
        mb_strlen($description) > 2000
    ) {

        $editErrors[] =
            'Description must not exceed 2000 characters.';
    }

    if (!$documentTypeId) {

        $editErrors[] =
            'Document type is required.';
    }

    if (!$currentStatusId) {

        $editErrors[] =
            'Document status is required.';
    }

    if (
        !in_array(
            $priority,
            [
                'Low',
                'Medium',
                'Normal',
                'High',
                'Urgent'
            ],
            true
        )
    ) {

        $editErrors[] =
            'Invalid priority.';
    }

    if (
        !in_array(
            $confidentiality,
            [
                'Normal',
                'Confidential',
                'Highly Confidential'
            ],
            true
        )
    ) {

        $editErrors[] =
            'Invalid confidentiality level.';
    }

    if (
        $dateDue !== ''
        &&
        !document_valid_date(
            $dateDue
        )
    ) {

        $editErrors[] =
            'Invalid due date.';
    }

    if (
        $assignedUserId !== false
        &&
        $assignedUserId !== null
        &&
        (int) $assignedUserId > 0
    ) {

        try {

            if (
                !document_user_exists(
                    $conn,
                    (int) $assignedUserId
                )
            ) {

                $editErrors[] =
                    'The selected assigned staff does not exist.';
            }

        } catch (Throwable $exception) {

            $editErrors[] =
                $exception->getMessage();
        }
    }

    if (empty($editErrors)) {

        try {
            if (!$document) {
                throw new RuntimeException('Document not found.');
            }

            $previousAssigneeId = isset($document['current_assignee_user_id'])
                ? (int) $document['current_assignee_user_id']
                : null;

            $formattedDueDate = null;

            if ($dateDue !== '') {

                $formattedDueDate = $dateDue;
            }

            $assignedUserValue =
                (
                    $assignedUserId !== false
                    &&
                    $assignedUserId !== null
                    &&
                    (int) $assignedUserId > 0
                )
                    ? (int) $assignedUserId
                    : null;

            $validStatusIds = array_map(
                static function (array $status): int {
                    return (int) ($status['status_id'] ?? 0);
                },
                $statuses
            );

            if (
                !in_array(
                    (int) $currentStatusId,
                    $validStatusIds,
                    true
                )
            ) {

                throw new RuntimeException(
                    'The selected status is invalid.'
                );
            }

            $updateSql = "
                UPDATE documents
                SET
                    title = ?,
                    description = ?,
                    document_type_id = ?,
                    current_assignee_user_id = ?,
                    current_status_id = ?,
                    priority_level = ?,
                    confidentiality_level = ?,
                    external_reference_no = ?,
                    date_due = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE document_id = ?
            ";

            if (!$canManageDocuments) {
                if ($canEditAsRoutedRecipient) {
                    $updateSql .= '
                        AND (
                            current_assignee_user_id = ?
                            OR EXISTS (
                                SELECT 1
                                FROM document_routes latest_route
                                WHERE latest_route.document_id = documents.document_id
                                  AND latest_route.routed_to_user_id = ?
                                  AND latest_route.document_route_id = (
                                      SELECT route_order.document_route_id
                                      FROM document_routes route_order
                                      WHERE route_order.document_id = documents.document_id
                                      ORDER BY route_order.routed_at DESC, route_order.document_route_id DESC
                                      LIMIT 1
                                  )
                            )
                        )';
                } else {
                    $updateSql .= ' AND current_assignee_user_id = ?';
                }
            }

            $stmt = $conn->prepare($updateSql);

            if (!$stmt) {

                throw new RuntimeException(
                    'Unable to prepare update query.'
                );
            }

            $priorityDb = document_priority_to_db($priority);
            $confidentialityDb = document_confidentiality_to_db($confidentiality);

            if ($canManageDocuments) {
                $stmt->bind_param(
                    'ssiiissssi',
                    $title,
                    $description,
                    $documentTypeId,
                    $assignedUserValue,
                    $currentStatusId,
                    $priorityDb,
                    $confidentialityDb,
                    $externalReference,
                    $formattedDueDate,
                    $documentId
                );
            } elseif ($canEditAsRoutedRecipient) {
                $stmt->bind_param(
                    'ssiiissssiii',
                    $title,
                    $description,
                    $documentTypeId,
                    $assignedUserValue,
                    $currentStatusId,
                    $priorityDb,
                    $confidentialityDb,
                    $externalReference,
                    $formattedDueDate,
                    $documentId,
                    $currentUserId,
                    $currentUserId
                );
            } else {
                $stmt->bind_param(
                    'ssiiissssii',
                    $title,
                    $description,
                    $documentTypeId,
                    $assignedUserValue,
                    $currentStatusId,
                    $priorityDb,
                    $confidentialityDb,
                    $externalReference,
                    $formattedDueDate,
                    $documentId,
                    $currentUserId
                );
            }

            if (!$stmt->execute()) {

                throw new RuntimeException(
                    'Unable to update document: ' .
                    $stmt->error
                );
            }

            $updatedRows = $stmt->affected_rows;
            $stmt->close();

            if (!$canManageDocuments && $updatedRows === 0) {
                $ownershipStmt = $conn->prepare(
                    'SELECT current_assignee_user_id FROM documents WHERE document_id = ? LIMIT 1'
                );

                if (!$ownershipStmt) {
                    throw new RuntimeException('Unable to verify document assignment after saving.');
                }

                $ownershipStmt->bind_param('i', $documentId);
                $ownershipStmt->execute();
                $ownershipResult = $ownershipStmt->get_result();
                $ownership = $ownershipResult->fetch_assoc();
                $ownershipStmt->close();

                if (
                    (int) ($ownership['current_assignee_user_id'] ?? 0) !== $currentUserId
                    && !$canEditAsRoutedRecipient
                ) {
                    throw new RuntimeException('This document is no longer assigned to you.');
                }
            }

            if ($canManageDocuments) {
                $_SESSION[document_tracking_slip_override_key((int) $documentId)] = [
                    'tracking_no' => $slipTrackingNo,
                    'ref_no' => $slipRefNo,
                    'from' => $slipFrom,
                    'date_received' => $slipDateReceived,
                    'remarks' => $slipRemarks,
                    'assigned_to' => $slipAssignedTo,
                    'action_appropriate_action' => $slipActionAppropriate,
                    'action_review' => $slipActionReview,
                    'action_comment_reply' => $slipActionCommentReply,
                    'action_reference' => $slipActionReference,
                    'action_countersign' => $slipActionCountersign,
                    'action_others' => $slipActionOthers,
                    'task_draft' => $slipTaskDraft,
                    'task_discuss' => $slipTaskDiscuss,
                    'task_attend' => $slipTaskAttend,
                    'task_facilitate' => $slipTaskFacilitate,
                    'task_review' => $slipTaskReview,
                    'task_file' => $slipTaskFile,
                    'task_release' => $slipTaskRelease,
                    'task_note' => $slipTaskNote,
                    'task_investigation' => $slipTaskInvestigation,
                    'task_disseminate' => $slipTaskDisseminate,
                    'task_others' => $slipTaskOthers,
                    'task_endorse' => $slipTaskEndorse,
                    'output_letter' => $slipOutputLetter,
                    'output_memo' => $slipOutputMemo,
                    'output_inspection' => $slipOutputInspection,
                    'output_meeting' => $slipOutputMeeting,
                    'output_contract' => $slipOutputContract,
                    'output_investigation' => $slipOutputInvestigation,
                    'output_affidavit' => $slipOutputAffidavit,
                    'output_others' => $slipOutputOthers,
                ];
            }

            $oldDocumentSnapshot = [
                'title' => (string) ($document['title'] ?? ''),
                'description' => (string) ($document['description'] ?? ''),
                'document_type_id' => (int) ($document['document_type_id'] ?? 0),
                'current_department_id' => (int) ($document['current_department_id'] ?? 0),
                'current_assignee_user_id' => isset($document['current_assignee_user_id']) ? (int) $document['current_assignee_user_id'] : null,
                'current_status_id' => (int) ($document['current_status_id'] ?? 0),
                'priority_level' => (string) ($document['priority_level'] ?? ''),
                'confidentiality_level' => (string) ($document['confidentiality_level'] ?? ''),
                'external_reference_no' => (string) ($document['external_reference_no'] ?? ''),
                'date_due' => (string) ($document['date_due'] ?? ''),
            ];

            $newDocumentSnapshot = [
                'title' => $title,
                'description' => $description,
                'document_type_id' => (int) $documentTypeId,
                'current_department_id' => (int) ($document['current_department_id'] ?? 0),
                'current_assignee_user_id' => $assignedUserValue,
                'current_status_id' => (int) $currentStatusId,
                'priority_level' => $priorityDb,
                'confidentiality_level' => $confidentialityDb,
                'external_reference_no' => $externalReference,
                'date_due' => $formattedDueDate,
            ];

            audit_log_create(
                $currentUserId,
                'DOCUMENT',
                $documentId,
                'UPDATED',
                $oldDocumentSnapshot,
                $newDocumentSnapshot
            );

            if ($previousAssigneeId !== $assignedUserValue) {
                document_assignment_set_receipt($conn, (int) $documentId, $assignedUserValue, $currentUserId);

                if ($assignedUserValue !== null) {
                    audit_log_create(
                        $currentUserId,
                        'DOCUMENT',
                        (int) $documentId,
                        'ASSIGNED',
                        ['previous_assignee_user_id' => $previousAssigneeId],
                        ['assigned_to_user_id' => $assignedUserValue]
                    );
                }
            }

            document_redirect(
                'success',
                'Document updated successfully.'
            );

        } catch (Throwable $exception) {

            $editErrors[] =
                'Unable to update document: ' .
                $exception->getMessage();
        }
    }

    if (!empty($editErrors)) {

        $errors =
            array_merge(
                $errors,
                $editErrors
            );
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['replace_document_file'])
    && $canUploadDocumentFiles
) {

    if (!document_verify_csrf()) {
        $errors[] = 'Invalid request token. Please refresh the page and try again.';
    }

    $documentId = filter_input(
        INPUT_POST,
        'document_id',
        FILTER_VALIDATE_INT
    );

    $replacementFile = $_FILES['replacement_file'] ?? null;

    if (!$documentId) {
        $errors[] = 'Invalid document selected.';
    } else {
        $documentStmt = $conn->prepare(
            'SELECT document_id, confidentiality_level, created_by_user_id, current_assignee_user_id
             FROM documents
             WHERE document_id = ?
             LIMIT 1'
        );

        if (!$documentStmt) {
            $errors[] = 'Unable to verify the selected document.';
        } else {
            $documentStmt->bind_param('i', $documentId);
            $documentStmt->execute();
            $uploadDocument = $documentStmt->get_result()->fetch_assoc();
            $documentStmt->close();

            if (!$uploadDocument) {
                $errors[] = 'Selected document was not found.';
            } elseif (!document_user_can_upload_file_version($uploadDocument, $currentUser, $conn)) {
                $errors[] = 'Only the assigned user, latest routed recipient, or a user with granted document access can upload a file version.';
            }
        }
    }

    if (!is_array($replacementFile) || !isset($replacementFile['tmp_name']) || !is_uploaded_file($replacementFile['tmp_name'])) {
        $errors[] = 'Please upload a file to replace the current version.';
    }

    if (is_array($replacementFile) && (int) ($replacementFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed.';
    }

    if (is_array($replacementFile)) {
        $replacementSize = (int) ($replacementFile['size'] ?? 0);
        if ($replacementSize <= 0 || $replacementSize > $maxFileSize) {
            $errors[] = 'Replacement file must be between 1 byte and 10 MB.';
        }

        $replacementName = basename((string) ($replacementFile['name'] ?? ''));
        $replacementExtension = strtolower(pathinfo($replacementName, PATHINFO_EXTENSION));
        if (!in_array($replacementExtension, $allowedExtensions, true)) {
            $errors[] = 'Only PDF, DOC and DOCX files are allowed.';
        }

        $replacementTmpName = (string) ($replacementFile['tmp_name'] ?? '');
        if (
            ($replacementFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
            && is_uploaded_file($replacementTmpName)
        ) {
            $replacementFinfo = new finfo(FILEINFO_MIME_TYPE);
            $replacementMime = $replacementFinfo->file($replacementTmpName);
            if (
                !isset($allowedFileTypes[$replacementExtension])
                || $replacementMime !== $allowedFileTypes[$replacementExtension]
            ) {
                $errors[] = 'Invalid file type detected.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $originalFilename = basename((string) ($replacementFile['name'] ?? ''));

            if ($originalFilename === '') {
                throw new RuntimeException('Uploaded file name is missing.');
            }

            $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
            $storedFilename = attachment_storage_filename(
                $originalFilename,
                $uploadDir
            );
            $destination = $uploadDir . $storedFilename;

            if (!move_uploaded_file($replacementFile['tmp_name'], $destination)) {
                throw new RuntimeException('Unable to upload the updated file.');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $detectedMime = $finfo->file($destination);
            $mimeType = is_string($detectedMime) && $detectedMime !== '' ? $detectedMime : 'application/octet-stream';

            $savedFileSize = filesize($destination);
            if ($savedFileSize === false) {
                throw new RuntimeException('Unable to determine the uploaded file size.');
            }

            $checksumSha256 = hash_file('sha256', $destination);
            if ($checksumSha256 === false) {
                throw new RuntimeException('Unable to calculate the uploaded file checksum.');
            }

            $relativePath = 'uploads/' . $storedFilename;

            $stmt = $conn->prepare("
                INSERT INTO attachments
                (
                    document_id,
                    uploaded_by_user_id,
                    file_name,
                    stored_name,
                    file_path,
                    mime_type,
                    file_size_bytes,
                    checksum_sha256,
                    is_public,
                    uploaded_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    0,
                    CURRENT_TIMESTAMP
                )
            ");

            if (!$stmt) {
                throw new RuntimeException('Unable to prepare attachment insert.');
            }

            $stmt->bind_param(
                'iissssis',
                $documentId,
                $currentUserId,
                $originalFilename,
                $originalFilename,
                $relativePath,
                $mimeType,
                $savedFileSize,
                $checksumSha256
            );

            if (!$stmt->execute()) {
                throw new RuntimeException('Unable to save updated attachment: ' . $stmt->error);
            }

            $stmt->close();

            document_redirect(
                'success',
                'Updated file uploaded successfully.',
                ['file_history' => $documentId]
            );

        } catch (Throwable $exception) {
            if (isset($destination) && is_file($destination)) {
                @unlink($destination);
            }

            $errors[] = 'Unable to replace file: ' . $exception->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| ARCHIVE VIEW FILTER
|--------------------------------------------------------------------------
|
| active   = non archived
| archived = archived only
| all      = everything
|--------------------------------------------------------------------------
*/

$view =
    strtolower(
        trim(
            (string) (
                $_GET['view'] ?? 'active'
            )
        )
    );

if (
    !in_array(
        $view,
        [
            'active',
            'archived',
            'pending',
            'all'
        ],
        true
    )
) {

    $view = 'active';
}

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search =
    trim(
        (string) (
            $_GET['search'] ?? ''
        )
    );

$documents = [];

/*
|--------------------------------------------------------------------------
| DOCUMENT LIST QUERY
|--------------------------------------------------------------------------
*/

$where = [];

$params = [];

$types = '';

/*
|--------------------------------------------------------------------------
| ARCHIVE FILTER
|--------------------------------------------------------------------------
*/

if ($view === 'active') {

    $where[] = "
        LOWER(ds.status_name) <> 'archived'
    ";

} elseif ($view === 'archived') {

    $where[] = "
        LOWER(ds.status_name) = 'archived'
    ";

} elseif ($view === 'pending') {

    $where[] = "
        LOWER(COALESCE(ds.status_name, '')) IN ('received', 'under review', 'routed')
    ";
}

/*
|--------------------------------------------------------------------------
| SEARCH FILTER
|--------------------------------------------------------------------------
*/

if ($search !== '') {
    $searchPattern = '%' . str_replace(
        ['\\', '%', '_'],
        ['\\\\', '\\%', '\\_'],
        $search
    ) . '%';

    $where[] = "(" .
        "LOWER(d.tracking_no) LIKE LOWER(?) ESCAPE '\\\\' OR " .
        "LOWER(d.title) LIKE LOWER(?) ESCAPE '\\\\' OR " .
        "LOWER(d.description) LIKE LOWER(?) ESCAPE '\\\\' OR " .
        "LOWER(COALESCE(d.external_reference_no, '')) LIKE LOWER(?) ESCAPE '\\\\' OR " .
        "LOWER(COALESCE(dt.type_name, '')) LIKE LOWER(?) ESCAPE '\\\\' OR " .
        "LOWER(COALESCE(od.department_name, '')) LIKE LOWER(?) ESCAPE '\\\\' OR " .
        "LOWER(COALESCE(cd.department_name, '')) LIKE LOWER(?) ESCAPE '\\\\' OR " .
        "LOWER(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) LIKE LOWER(?) ESCAPE '\\\\'" .
        ")";

    $params = array_fill(0, 8, $searchPattern);
    $types = str_repeat('s', count($params));
}

/*
|--------------------------------------------------------------------------
| BUILD WHERE
|--------------------------------------------------------------------------
*/

$whereSql = '';

if (!empty($where)) {

    $whereSql =
        'WHERE ' .
        implode(
            ' AND ',
            $where
        );
}

/*
|--------------------------------------------------------------------------
| DOCUMENT QUERY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        d.*,
        dar.accepted_at AS assignment_accepted_at,

        dt.type_name,

        ds.status_name,

        COALESCE(
            NULLIF(d.origin_department_other, ''),
            od.department_name
        ) AS origin_department_name,

        cd.department_name
            AS current_department_name,

        CONCAT(
            COALESCE(u.first_name, ''),
            ' ',
            COALESCE(u.last_name, '')
        ) AS assigned_staff_name

    FROM documents d

    LEFT JOIN document_types dt
        ON dt.document_type_id =
           d.document_type_id

    LEFT JOIN document_status ds
        ON ds.`$statusPrimaryKey` =
           d.current_status_id

    LEFT JOIN departments od
        ON od.department_id =
           d.origin_department_id

    LEFT JOIN departments cd
        ON cd.department_id =
           d.current_department_id

    LEFT JOIN users u
        ON u.user_id =
           d.current_assignee_user_id

    LEFT JOIN document_assignment_receipts dar
        ON dar.document_id = d.document_id
       AND dar.user_id = d.current_assignee_user_id

    $whereSql

    ORDER BY
        d.document_id DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    $errors[] =
        'Unable to prepare document list query.';
} else {

    if (!empty($params)) {

        $stmt->bind_param(
            $types,
            ...$params
        );
    }

    if (!$stmt->execute()) {

        $errors[] =
            'Unable to load document records.';
    } else {

        $result =
            $stmt->get_result();

        $documents =
            $result->fetch_all(
                MYSQLI_ASSOC
            );
    }

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

$messageType =
    (string) (
        $_GET['message_type'] ?? ''
    );

$message =
    (string) (
        $_GET['message'] ?? ''
    );

/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$activeCount = 0;
$archivedCount = 0;
$pendingCount = 0;
$totalCount = 0;

$countSql = "
    SELECT
        LOWER(COALESCE(ds.status_name, '')) AS normalized_status,
        COUNT(*) AS total
    FROM documents d
    LEFT JOIN document_status ds
        ON ds.`$statusPrimaryKey` =
           d.current_status_id
    GROUP BY
        LOWER(COALESCE(ds.status_name, ''))
";

$countResult =
    $conn->query($countSql);

if ($countResult) {

    while (
        $countRow =
        $countResult->fetch_assoc()
    ) {

        $count =
            (int) (
                $countRow['total']
                ?? 0
            );

        $totalCount += $count;

        if (
            ($countRow['normalized_status'] ?? '')
            === 'archived'
        ) {

            $archivedCount += $count;

        } elseif (
            in_array(
                $countRow['normalized_status'] ?? '',
                ['received', 'under review', 'routed'],
                true
            )
        ) {

            $pendingCount += $count;
            $activeCount += $count;

        } else {

            $activeCount += $count;
        }
    }

    $countResult->free();
}

/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__, 2) . '/includes/header.php';

?>

<div class="container-fluid">

    <!-- PAGE HEADER -->

    <div class="page-header bg-white rounded-3 shadow-sm p-4 mb-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

            <div>

                <h3 class="mb-1">

                    <i class="bi bi-folder2-open text-primary"></i>

                    Document Management

                </h3>

                <div class="text-muted">

                    Receive, track, edit, archive and manage documents.

                </div>

            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <?php if ($canManageDocuments): ?>
                    <button
                        type="button"
                        class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#receiveDocumentModal"
                    >

                        <i class="bi bi-file-earmark-plus"></i>

                        New Document

                    </button>
                <?php endif; ?>

                <?php if (auth_is_role('INCOMING')): ?>
                    <a class="btn btn-outline-success" href="<?= e(app_url('import_documents.php')) ?>">
                        <i class="bi bi-file-earmark-spreadsheet"></i>
                        Import Excel
                    </a>
                <?php endif; ?>
            </div>

        </div>

    </div>

    <!-- ALERTS -->

    <?php if (!empty($errors)): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <strong>
                Please correct the following:
            </strong>

            <ul class="mb-0 mt-2">

                <?php foreach ($errors as $error): ?>

                    <li>
                        <?= e($error) ?>
                    </li>

                <?php endforeach; ?>

            </ul>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>

    <?php if ($message !== ''): ?>

        <div
            class="alert alert-<?= $messageType === 'success'
                ? 'success'
                : 'danger' ?> alert-dismissible fade show"
        >

            <i class="bi bi-info-circle me-1"></i>

            <?= e($message) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>

    <!-- ARCHIVE SUMMARY -->

    <div class="row g-3 mb-4">

        <div class="col-12 col-md-3">

            <a
                href="document_management.php?view=active"
                class="text-decoration-none"
            >

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <div class="text-muted small">
                                    Active Documents
                                </div>

                                <div class="fs-3 fw-bold text-primary">
                                    <?= $activeCount ?>
                                </div>

                            </div>

                            <div class="summary-icon text-primary">

                                <i class="bi bi-files"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

        <div class="col-12 col-md-3">

            <a
                href="document_management.php?view=pending"
                class="text-decoration-none"
            >

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <div class="text-muted small">
                                    Pending Documents
                                </div>

                                <div class="fs-3 fw-bold text-info">
                                    <?= $pendingCount ?>
                                </div>

                            </div>

                            <div class="summary-icon text-info">

                                <i class="bi bi-hourglass-split"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

        <div class="col-12 col-md-3">

            <a
                href="document_management.php?view=archived"
                class="text-decoration-none"
            >

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <div class="text-muted small">
                                    Archived Documents
                                </div>

                                <div class="fs-3 fw-bold text-warning">
                                    <?= $archivedCount ?>
                                </div>

                            </div>

                            <div class="summary-icon text-warning">

                                <i class="bi bi-archive"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

        <div class="col-12 col-md-3">

            <a
                href="document_management.php?view=all"
                class="text-decoration-none"
            >

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <div class="text-muted small">
                                    All Documents
                                </div>

                                <div class="fs-3 fw-bold text-dark">
                                    <?= $totalCount ?>
                                </div>

                            </div>

                            <div class="summary-icon text-secondary">

                                <i class="bi bi-collection"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    </div>

    <!-- SEARCH -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <form
                method="GET"
                class="row g-2"
                id="documentSearchForm"
            >

                <input
                    type="hidden"
                    name="view"
                    value="<?= e($view) ?>"
                >

                <div class="col-12 col-md-10">

                    <input
                        type="search"
                        name="search"
                        class="form-control"
                        id="documentSearchInput"
                        placeholder="Search tracking number, title, description or reference number..."
                        value="<?= e($search) ?>"
                        autocomplete="off"
                    >

                </div>

                <div class="col-12 col-md-2 d-grid">

                    <button
                        type="submit"
                        class="btn btn-outline-primary"
                    >

                        <i class="bi bi-search"></i>

                        Search

                    </button>

                </div>

            </form>

        </div>

    </div>

<script src="<?php echo e(app_url('assets/js/features/documents/document-management-form.js')); ?>"></script>

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body py-3">

            <div class="d-flex flex-wrap align-items-center gap-2">

                <span class="text-muted me-2">

                    <i class="bi bi-filter"></i>

                    View:

                </span>

                <a
                    href="document_management.php?view=active<?= $search !== '' ? '&search=' . urlencode($search) : '' ?>"
                    class="btn btn-sm <?= $view === 'active'
                        ? 'btn-primary'
                        : 'btn-outline-primary' ?>"
                >

                    <i class="bi bi-files"></i>

                    Active

                    <span class="badge bg-light text-dark ms-1">
                        <?= $activeCount ?>
                    </span>

                </a>

                <a
                    href="document_management.php?view=pending<?= $search !== '' ? '&search=' . urlencode($search) : '' ?>"
                    class="btn btn-sm <?= $view === 'pending'
                        ? 'btn-info'
                        : 'btn-outline-info' ?>"
                >

                    <i class="bi bi-hourglass-split"></i>

                    Pending

                    <span class="badge bg-light text-dark ms-1">
                        <?= $pendingCount ?>
                    </span>

                </a>

                <a
                    href="document_management.php?view=archived<?= $search !== '' ? '&search=' . urlencode($search) : '' ?>"
                    class="btn btn-sm <?= $view === 'archived'
                        ? 'btn-warning'
                        : 'btn-outline-warning' ?>"
                >

                    <i class="bi bi-archive"></i>

                    Archived

                    <span class="badge bg-light text-dark ms-1">
                        <?= $archivedCount ?>
                    </span>

                </a>

                <a
                    href="document_management.php?view=all<?= $search !== '' ? '&search=' . urlencode($search) : '' ?>"
                    class="btn btn-sm <?= $view === 'all'
                        ? 'btn-secondary'
                        : 'btn-outline-secondary' ?>"
                >

                    <i class="bi bi-collection"></i>

                    All

                    <span class="badge bg-light text-dark ms-1">
                        <?= $totalCount ?>
                    </span>

                </a>

            </div>

        </div>

    </div>

    <!-- DOCUMENT TABLE -->

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

                <div>

                    <strong>

                        <?php if ($view === 'archived'): ?>

                            <i class="bi bi-archive text-warning"></i>

                            Archived Documents

                        <?php elseif ($view === 'pending'): ?>

                            <i class="bi bi-hourglass-split text-info"></i>

                            Pending Documents

                        <?php elseif ($view === 'all'): ?>

                            <i class="bi bi-collection text-secondary"></i>

                            All Document Records

                        <?php else: ?>

                            <i class="bi bi-files text-primary"></i>

                            Active Documents

                        <?php endif; ?>

                    </strong>

                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">

                    <?php if ($canPrintTrackingSlips): ?>
                        <div class="form-check m-0">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="select-all-tracking-slips"
                                aria-label="Select all tracking slips"
                            >
                        </div>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary"
                            id="print-selected-tracking-slips"
                            data-print-url="<?= e(app_url('output_tracking_slip_form.php')) ?>"
                        >
                            <i class="bi bi-printer"></i>
                            Print Selected
                        </button>
                    <?php endif; ?>

                    <span class="badge bg-primary">

                        <?= count($documents) ?>

                        Records

                    </span>

                </div>

            </div>

        </div>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>

                        <?php if ($canPrintTrackingSlips): ?>
                            <th class="text-center" style="width: 42px;">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="select-all-tracking-slip-header"
                                    aria-label="Select all slips"
                                >
                            </th>
                        <?php endif; ?>

                        <th>
                            Tracking Number
                        </th>

                        <th>
                            Document
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Date Received
                        </th>

                        <th>
                            Priority
                        </th>

                        <th>
                            Due Date
                        </th>

                        <th class="text-end">
                            Actions
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (empty($documents)): ?>

                    <tr>

                        <td
                            colspan="9"
                            class="text-center py-5 text-muted"
                        >

                            <?php if ($view === 'archived'): ?>

                                <i class="bi bi-archive fs-1 d-block mb-2"></i>

                                No archived documents found.

                            <?php else: ?>

                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>

                                No documents found.

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($documents as $document): ?>

                        <?php
                        $documentId =
                            (int) (
                                $document['document_id']
                                ?? 0
                            );

                        $priorityValue =
                            document_priority_from_db(
                                $document['priority_level']
                                ?? 'MEDIUM'
                            );

                        $confidentialityValue = document_confidentiality_from_db(
                            $document['confidentiality_level'] ?? null
                        );

                        $priorityClass =
                            match ($priorityValue) {

                                'Urgent' =>
                                    'danger',

                                'High' =>
                                    'warning',

                                'Low' =>
                                    'secondary',

                                default =>
                                    'primary'
                            };

                        $status =
                            $document['status_name']
                            ?? 'Unknown';

                        $statusClass =
                            strtolower($status) === 'archived'
                                ? 'warning'
                                : 'secondary';

                        $isArchived =
                            strtolower($status) === 'archived';

                        ?>

                        <tr
                            class="<?= $isArchived
                                ? 'table-warning-subtle'
                                : '' ?>"
                        >

                            <?php if ($canPrintTrackingSlips): ?>
                                <td class="text-center">
                                    <input
                                        class="form-check-input tracking-slip-select"
                                        type="checkbox"
                                        value="<?= $documentId ?>"
                                        aria-label="Select document for tracking slip print"
                                    >
                                </td>
                            <?php endif; ?>

                            <td>

                                <strong class="text-primary">

                                    <?= e(
                                        $document['tracking_no']
                                        ?? ''
                                    ) ?>

                                </strong>

                                <div class="small text-muted">

                                    ID:
                                    <?= $documentId ?>

                                </div>

                            </td>

                            <td>

                                <strong>

                                    <?= e(
                                        $document['title']
                                        ?? ''
                                    ) ?>

                                </strong>

                                <?php if (
                                    !empty(
                                        $document[
                                            'external_reference_no'
                                        ]
                                    )
                                ): ?>

                                    <div class="small text-muted">

                                        Ref:
                                        <?= e(
                                            $document[
                                                'external_reference_no'
                                            ]
                                        ) ?>

                                    </div>

                                <?php endif; ?>

                                <?php if (
                                    !empty(
                                        $document[
                                            'assigned_staff_name'
                                        ]
                                    )
                                ): ?>

                                    <div class="small text-muted">

                                        <i class="bi bi-person"></i>

                                        <?= e(
                                            trim(
                                                $document[
                                                    'assigned_staff_name'
                                                ]
                                            )
                                        ) ?>

                                    </div>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?= e(
                                    $document['type_name']
                                    ?? '-'
                                ) ?>

                            </td>

                            <td>

                                <span
                                    class="badge text-bg-<?= e(
                                        $statusClass
                                    ) ?>"
                                >

                                    <?php if ($isArchived): ?>

                                        <i class="bi bi-archive"></i>

                                    <?php endif; ?>

                                    <?= e($status) ?>

                                </span>

                            </td>

                            <td>

                                <?php if (
                                    !empty(
                                        $document['date_received']
                                    )
                                ): ?>

                                    <?= e(
                                        date(
                                            'M d, Y h:i A',
                                            strtotime(
                                                $document[
                                                    'date_received'
                                                ]
                                            )
                                        )
                                    ) ?>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <td>

                                <span
                                    class="badge text-bg-<?= e(
                                        $priorityClass
                                    ) ?>"
                                >

                                    <?= e(
                                        $priorityValue
                                    ) ?>

                                </span>

                            </td>

                            <td>

                                <?php if (
                                    !empty(
                                        $document['date_due']
                                    )
                                ): ?>

                                    <?= e(
                                        date(
                                            'M d, Y',
                                            strtotime(
                                                $document['date_due']
                                            )
                                        )
                                    ) ?>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <td class="text-end">

                                <?php
                                    $canEditConfidentiality = auth_is_role('ADMIN')
                                        || (int) ($document['current_assignee_user_id'] ?? 0) === $currentUserId;
                                    $canEditAsRoutedRecipient = document_user_is_latest_route_recipient(
                                        $documentId,
                                        $currentUserId
                                    );
                                    $canEditDocument = $canManageDocuments
                                        || (int) ($document['current_assignee_user_id'] ?? 0) === $currentUserId
                                        || $canEditAsRoutedRecipient;
                                ?>

                                <div class="document-actions" role="group" aria-label="Document actions">

                                    <?php if (
                                        (int) ($document['current_assignee_user_id'] ?? 0) === $currentUserId
                                        && empty($document['assignment_accepted_at'])
                                    ): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="document_id" value="<?= $documentId ?>">
                                            <button type="submit" name="accept_assignment" class="btn btn-sm btn-success action-button" title="Accept assigned document">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- FILE HISTORY -->

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-info action-button"
                                        data-file-history-target="documentFiles<?= $documentId ?>"
                                        data-document-id="<?= $documentId ?>"
                                        data-document-tracking="<?= e($document['tracking_no'] ?? '') ?>"
                                        data-access-request-url="<?= e(app_url('request_document_access.php')) ?>"
                                        data-access-request-token="<?= e(csrf_token('document_access_request')) ?>"
                                        onclick="return openFileHistoryModal('documentFiles<?= $documentId ?>');"
                                        title="Document file history"
                                        aria-label="View file history"
                                    >

                                        <i class="bi bi-paperclip"></i>

                                    </button>

                                    <?php if ($canPrintTrackingSlips): ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-success action-button"
                                            title="Print tracking slip"
                                            aria-label="Print tracking slip"
                                            data-tracking-slip-print-url="<?= e(app_url('output_tracking_slip_form.php?document_id=' . urlencode((string) $documentId) . '&generate=1&autoprint=1')) ?>"
                                        >
                                            <i class="bi bi-printer"></i>
                                        </button>
                                    <?php endif; ?>

                                    <!-- EDIT -->

                                    <?php if ($canEditDocument): ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary action-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editDocument<?= $documentId ?>"
                                            title="<?= $canManageDocuments ? 'Edit document' : 'Edit assigned document' ?>"
                                            aria-label="Edit document"
                                        >

                                            <i class="bi bi-pencil"></i>

                                        </button>
                                    <?php if (!$canEditDocument && $canEditConfidentiality): ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary action-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editConfidentiality<?= $documentId ?>"
                                            title="Edit confidentiality only"
                                            aria-label="Edit document confidentiality"
                                        >
                                            <i class="bi bi-shield-lock"></i>
                                        </button>
                                    <?php endif; ?>

                                    <!-- ARCHIVE -->

                                    <?php
                                        $canArchiveThisDocument = auth_has_any_role(['ADMIN', 'INCOMING'])
                                            || (int) ($document['current_assignee_user_id'] ?? 0) === $currentUserId;
                                    ?>
                                    <?php if ($canArchiveThisDocument && !$isArchived): ?>

                                        <form
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Archive this document? This will move the document to Archived status.');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($csrfToken) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="document_id"
                                                value="<?= $documentId ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="archive_document"
                                                class="btn btn-sm btn-outline-warning action-button"
                                                title="Archive"
                                                aria-label="Archive document"
                                            >

                                                <i class="bi bi-archive"></i>

                                            </button>

                                        </form>

                                    <?php elseif ($canArchiveThisDocument): ?>

                                        <span
                                            class="btn btn-sm btn-warning disabled action-button"
                                            title="Already archived"
                                            aria-label="Document already archived"
                                        >

                                            <i class="bi bi-archive-fill"></i>

                                        </span>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                        <!-- FILE HISTORY MODAL -->

                        <template
                            id="documentFiles<?= $documentId ?>"
                        >

                            <div
                                class="modal fade"
                                tabindex="-1"
                                aria-hidden="true"
                            >

                                <div class="modal-dialog modal-xl modal-dialog-scrollable">

                                    <div class="modal-content">

                                        <div class="modal-header">

                                            <h5 class="modal-title">
                                                <i class="bi bi-paperclip text-info"></i>
                                                File History - <?= e($document['tracking_no'] ?? '') ?>
                                            </h5>

                                            <button
                                                type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"
                                            ></button>

                                        </div>

                                        <div class="modal-body">

                                            <?php
                                            $canUploadThisVersion = $canUploadDocumentFiles
                                                && document_user_can_upload_file_version($document, $currentUser, $conn);
                                            $canViewDocumentContent = document_user_can_view_content(
                                                $document,
                                                $currentUser,
                                                $conn
                                            );
                                            $hasPendingAccessRequest = !$canViewDocumentContent
                                                && document_user_has_access_request(
                                                    $conn,
                                                    $documentId,
                                                    $currentUserId
                                                );
                                            ?>
                                        <?php if (!$canViewDocumentContent): ?>
                                            <div class="alert alert-warning mb-0">
                                                <i class="bi bi-shield-lock"></i>
                                                You do not currently have access to this document's files.
                                                <?php if ($hasPendingAccessRequest): ?>
                                                    Your access request is pending review.
                                                <?php else: ?>
                                                    Submit an access request to view the File History.
                                                    <form
                                                        method="POST"
                                                        action="<?= e(app_url('request_document_access.php')) ?>"
                                                        class="mt-3"
                                                    >
                                                        <?= csrf_field('document_access_request') ?>
                                                        <input
                                                            type="hidden"
                                                            name="document_id"
                                                            value="<?= $documentId ?>"
                                                        >
                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-outline-warning"
                                                        >
                                                            <i class="bi bi-shield-lock"></i>
                                                            Request Access
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($canUploadThisVersion): ?>
                                        <form
                                            method="POST"
                                            enctype="multipart/form-data"
                                            class="border rounded p-3 mb-4 bg-light"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($csrfToken) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="document_id"
                                                value="<?= $documentId ?>"
                                            >

                                            <div class="row g-3 align-items-end">

                                                <div class="col-md-9">

                                                    <label class="form-label">
                                                        Upload updated file version
                                                    </label>

                                                    <input
                                                        type="file"
                                                        name="replacement_file"
                                                        class="form-control"
                                                        accept=".pdf,.doc,.docx"
                                                        required
                                                    >

                                                </div>

                                                <div class="col-md-3 d-grid">

                                                    <button
                                                        type="submit"
                                                        name="replace_document_file"
                                                        class="btn btn-primary"
                                                    >
                                                        <i class="bi bi-upload"></i>
                                                        Upload New Version
                                                    </button>

                                                </div>

                                            </div>

                                        </form>
                                        <?php endif; ?>

                                        <?php if ($canViewDocumentContent): ?>
                                        <?php $documentAttachments = get_document_attachments($conn, $documentId); ?>

                                        <?php if (empty($documentAttachments)): ?>

                                            <div class="text-muted text-center py-4">
                                                <i class="bi bi-file-earmark-text fs-1 d-block mb-2"></i>
                                                No uploaded files for this document yet.
                                            </div>

                                        <?php else: ?>

                                            <div class="input-group mb-3">

                                                <span class="input-group-text">
                                                    <i class="bi bi-search"></i>
                                                </span>

                                                <input
                                                    type="search"
                                                    class="form-control file-history-search"
                                                    data-list-id="fileHistoryList<?= $documentId ?>"
                                                    placeholder="Type file name to filter..."
                                                    aria-label="Search uploaded files"
                                                >

                                            </div>

                                            <div class="list-group file-history-list" id="fileHistoryList<?= $documentId ?>">

                                                <?php foreach ($documentAttachments as $attachment): ?>

                                                    <?php
                                                    $attachmentId = (int) ($attachment['attachment_id'] ?? 0);
                                                    $attachmentPath = trim((string) ($attachment['file_path'] ?? ''));
                                                    $attachmentUrl = $attachmentId > 0 ? app_url('attachment.php?id=' . $attachmentId) : '#';
                                                    $attachmentDownloadUrl = $attachmentId > 0 ? app_url('attachment.php?id=' . $attachmentId . '&download=1') : '#';
                                                    $attachmentFileName = strtolower((string) ($attachment['file_name'] ?? ''));
                                                    $canDeleteAttachment =
                                                        $canManageDocuments
                                                        || (int) ($attachment['uploaded_by_user_id'] ?? 0) === $currentUserId
                                                        || $currentUserId === (int) ($document['current_assignee_user_id'] ?? 0)
                                                        || document_user_is_latest_route_recipient(
                                                            $documentId,
                                                            $currentUserId
                                                        );
                                                    ?>

                                                    <div
                                                        class="list-group-item file-history-item"
                                                        data-file-name="<?= e($attachmentFileName) ?>"
                                                    >

                                                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">

                                                            <div>

                                                                <div class="fw-semibold">
                                                                    <?= e($attachment['file_name'] ?? 'Untitled file') ?>
                                                                </div>

                                                                <div class="small text-muted">
                                                                    Uploaded: <?= e(date('M d, Y h:i A', strtotime($attachment['uploaded_at'] ?? 'now'))) ?>
                                                                    <?php if (!empty($attachment['uploaded_by_name'])): ?>
                                                                        by <?= e($attachment['uploaded_by_name']) ?>
                                                                    <?php endif; ?>
                                                                </div>

                                                                <div class="small text-muted">
                                                                    Size: <?= e($attachment['file_size_bytes'] ? number_format((int) $attachment['file_size_bytes'] / 1024, 1) . ' KB' : '0 KB') ?>
                                                                </div>

                                                            </div>

                                                            <div class="file-history-actions d-flex flex-wrap align-items-center gap-2">

                                                                <?php if ($canViewDocumentContent): ?>

                                                                <a
                                                                    href="<?= e($attachmentUrl) ?>"
                                                                    class="btn btn-sm btn-outline-primary file-history-action-button"
                                                                    data-document-preview
                                                                    data-preview-url="<?= e($attachmentUrl) ?>"
                                                                    data-preview-name="<?= e($attachment['file_name'] ?? 'Document') ?>"
                                                                    data-preview-mime="<?= e($attachment['mime_type'] ?? '') ?>"
                                                                >
                                                                    <i class="bi bi-eye"></i>
                                                                    View
                                                                </a>

                                                                <a
                                                                    href="<?= e($attachmentDownloadUrl) ?>"
                                                                    download="<?= e($attachment['file_name'] ?? 'download') ?>"
                                                                    class="btn btn-sm btn-outline-secondary file-history-action-button"
                                                                >
                                                                    <i class="bi bi-download"></i>
                                                                    Download
                                                                </a>

                                                                <?php if ($canDeleteAttachment): ?>
                                                                <form
                                                                    method="POST"
                                                                    class="d-inline-flex m-0"
                                                                    onsubmit="return confirm('Delete this uploaded file permanently?');"
                                                                >

                                                                    <input
                                                                        type="hidden"
                                                                        name="csrf_token"
                                                                        value="<?= e($csrfToken) ?>"
                                                                    >

                                                                    <input
                                                                        type="hidden"
                                                                        name="document_id"
                                                                        value="<?= $documentId ?>"
                                                                    >

                                                                    <input
                                                                        type="hidden"
                                                                        name="attachment_id"
                                                                        value="<?= $attachmentId ?>"
                                                                    >

                                                                    <button
                                                                        type="submit"
                                                                        name="delete_attachment"
                                                                        class="btn btn-sm btn-outline-danger file-history-action-button"
                                                                        title="Delete uploaded file"
                                                                        aria-label="Delete uploaded file"
                                                                    >
                                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                                        Delete
                                                                    </button>

                                                                </form>
                                                                <?php endif; ?>

                                                                <?php elseif ($hasPendingAccessRequest): ?>

                                                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                                                        <i class="bi bi-hourglass-split"></i>
                                                                        Access Requested
                                                                    </button>

                                                                <?php else: ?>

                                                                    <form method="POST" action="<?= e(app_url('request_document_access.php')) ?>" class="d-inline">
                                                                        <?= csrf_field('document_access_request') ?>
                                                                        <input type="hidden" name="document_id" value="<?= $documentId ?>">
                                                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                                                            <i class="bi bi-shield-lock"></i>
                                                                            Request Access
                                                                        </button>
                                                                    </form>

                                                                <?php endif; ?>

                                                            </div>

                                                        </div>

                                                    </div>

                                                <?php endforeach; ?>

                                            </div>

                                            <div
                                                class="file-history-no-results text-muted text-center py-4"
                                                style="display:none;"
                                            >
                                                <i class="bi bi-file-earmark-x fs-1 d-block mb-2"></i>
                                                No files match your search.
                                            </div>

                                        <?php endif; ?>
                                        <?php endif; ?>

                                        </div>

                                        <div class="modal-footer">

                                            <button
                                                type="button"
                                                class="btn btn-secondary"
                                                data-bs-dismiss="modal"
                                            >
                                                Close
                                            </button>

                                        </div>

                                    </div>

                                </div>

                            </div>
                        </template>

                        <?php require dirname(__DIR__, 2) . '/views/document-management-edit-modal.php'; ?>
                        <?php elseif ($canEditConfidentiality): ?>
                        <div
                            class="modal fade"
                            id="editConfidentiality<?= $documentId ?>"
                            tabindex="-1"
                            aria-hidden="true"
                        >
                            <div class="modal-dialog modal-dialog-scrollable">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="document_id" value="<?= $documentId ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Confidentiality</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="mb-3"><?= e((string) ($document['title'] ?? 'Untitled document')) ?></p>
                                            <label class="form-label" for="confidentiality<?= $documentId ?>">Confidentiality</label>
                                            <select class="form-select" id="confidentiality<?= $documentId ?>" name="confidentiality_level" required>
                                                <?php foreach (['Normal', 'Confidential', 'Highly Confidential'] as $level): ?>
                                                    <option value="<?= e($level) ?>" <?= ($confidentialityValue === $level) ? 'selected' : '' ?>><?= e($level) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="update_confidentiality" class="btn btn-primary">
                                                <i class="bi bi-save"></i>
                                                Save Confidentiality
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- RECEIVE DOCUMENT MODAL -->

<div
    class="modal fade"
    id="receiveDocumentModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-xl receive-document-dialog">

        <div class="modal-content">

            <form
                method="POST"
                enctype="multipart/form-data"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($csrfToken) ?>"
                >

                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="bi bi-file-earmark-plus text-primary"></i>

                        New Document

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>

                <div class="modal-body">

                    <div class="alert alert-info">

                        <i class="bi bi-info-circle"></i>

                        The tracking number will be generated
                        automatically after saving.

                    </div>

                    <div class="row">

                        <div class="col-12 col-md-6 mb-3">

                            <label class="form-label">

                                Date & Time Received

                                <span class="text-danger">*</span>

                            </label>

                            <input
                                type="datetime-local"
                                name="date_received"
                                class="form-control"
                                value="<?= date(
                                    'Y-m-d\TH:i'
                                ) ?>"
                                required
                            >

                        </div>

                        <div class="col-12 col-md-6 mb-3">

                            <label class="form-label">

                                Document Type

                                <span class="text-danger">*</span>

                            </label>

                            <select
                                name="document_type_id"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    Select document type
                                </option>

                                <?php foreach (
                                    $documentTypes
                                    as $type
                                ): ?>

                                    <option
                                        value="<?= (int) $type['document_type_id'] ?>"
                                    >

                                        <?= e(
                                            $type['type_name']
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-12 mb-3">

                            <label class="form-label">

                                Document Title

                                <span class="text-danger">*</span>

                            </label>

                            <input
                                type="text"
                                name="title"
                                class="form-control"
                                maxlength="255"
                                required
                            >

                        </div>

                        <div class="col-12 mb-3">

                            <label class="form-label">
                                Description
                                <span class="text-danger">*</span>
                            </label>

                            <textarea
                                name="description"
                                class="form-control"
                                rows="3"
                                maxlength="2000"
                                required
                            ></textarea>

                        </div>

                        <div class="col-12 col-md-6 mb-3">

                            <label class="form-label">

                                Original Department

                                <span class="text-danger">*</span>

                            </label>

                            <select
                                name="origin_department_id"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    Select department
                                </option>

                                <?php foreach (
                                    $departments
                                    as $department
                                ): ?>

                                    <option
                                        value="<?= (int) $department['department_id'] ?>"
                                    >

                                        <?= e(
                                            $department[
                                                'department_name'
                                            ]
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                                <option value="other">
                                    Others
                                </option>

                            </select>

                            <input
                                type="text"
                                name="origin_department_other"
                                class="form-control mt-2 d-none"
                                maxlength="255"
                                placeholder="Enter the person's name or document source"
                                aria-label="Other document source"
                            >

                        </div>

                        <div class="col-12 col-md-6 mb-3">

                            <label class="form-label">

                                Current Department

                                <span class="text-danger">*</span>

                            </label>

                            <select
                                name="current_department_id"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    Select department
                                </option>

                                <?php foreach (
                                    $departments
                                    as $department
                                ): ?>

                                    <option
                                        value="<?= (int) $department['department_id'] ?>"
                                    >

                                        <?= e(
                                            $department[
                                                'department_name'
                                            ]
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-12 col-md-6 mb-3">

                            <label class="form-label">
                                Assigned Staff
                            </label>

                            <select
                                name="current_assignee_user_id"
                                class="form-select"
                            >

                                <option value="">
                                    Unassigned
                                </option>

                                <?php foreach (
                                    $users
                                    as $staff
                                ): ?>

                                    <?php

                                    $staffName =
                                        trim(
                                            (
                                                $staff[
                                                    'first_name'
                                                ]
                                                ?? ''
                                            )
                                            .
                                            ' ' .
                                            (
                                                $staff[
                                                    'last_name'
                                                ]
                                                ?? ''
                                            )
                                        );

                                    if (
                                        $staffName === ''
                                    ) {

                                        $staffName =
                                            $staff[
                                                'username'
                                            ]
                                            ??
                                            '';
                                    }

                                    ?>

                                    <option
                                        value="<?= (int) $staff['user_id'] ?>"
                                        data-department-id="<?= (int) ($staff['department_id'] ?? 0) ?>"
                                    >

                                        <?= e(
                                            $staffName
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-12 col-md-6 mb-3">

                            <label class="form-label">
                                Status
                            </label>

                            <select
                                name="current_status_id"
                                class="form-select"
                            >

                                <option value="">
                                    Auto (Received)
                                </option>

                                <?php foreach (
                                    $statuses
                                    as $status
                                ): ?>

                                    <option
                                        value="<?= (int) $status['status_id'] ?>"
                                    >

                                        <?= e(
                                            $status['status_name']
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-12 col-md-3 mb-3">

                            <label class="form-label">
                                Priority
                            </label>

                            <select
                                name="priority_level"
                                class="form-select"
                                required
                            >

                                <option value="Medium">
                                    Medium
                                </option>

                                <option value="Low">
                                    Low
                                </option>

                                <option value="High">
                                    High
                                </option>

                                <option value="Urgent">
                                    Urgent
                                </option>

                            </select>

                        </div>

                        <div class="col-12 col-md-3 mb-3">

                            <label class="form-label">
                                Confidentiality
                            </label>

                            <select
                                name="confidentiality_level"
                                class="form-select"
                                required
                            >

                                <option value="Normal">
                                    Normal
                                </option>

                                <option value="Confidential">
                                    Confidential
                                </option>

                                <option value="Highly Confidential">
                                    Highly Confidential
                                </option>

                            </select>

                        </div>

                        <div class="col-12 col-md-6 mb-3">

                            <label class="form-label">
                                External Reference No.
                            </label>

                            <input
                                type="text"
                                name="external_reference_no"
                                class="form-control"
                                maxlength="100"
                                placeholder="Enter number only (CLO- is automatic)"
                            >

                        </div>

                        <div class="col-12 col-md-6 mb-3">

                            <label class="form-label">
                                Due Date
                            </label>

                            <input
                                type="date"
                                name="date_due"
                                class="form-control"
                            >

                        </div>

                        <div class="col-12 mb-3">

                            <label class="form-label">
                                Upload Supporting Document
                            </label>

                            <input
                                type="file"
                                name="document_file"
                                class="form-control receive-document-file-input"
                                accept=".pdf,.doc,.docx"
                                aria-label="Upload document"
                            >

                            <div class="form-text">

                                Optional attachment.
                                Allowed files: PDF, DOC, DOCX.
                                Maximum size: 10 MB.

                            </div>

                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >

                        Cancel

                    </button>

                    <button
                        type="submit"
                        name="receive_document"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-check-lg"></i>

                        Receive Document

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script src="<?php echo e(app_url('assets/js/features/documents/document-management-print.js')); ?>?v=20261007b"></script>
<script src="<?php echo e(app_url('assets/js/features/documents/document-management-init.js')); ?>?v=20261007k"></script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
