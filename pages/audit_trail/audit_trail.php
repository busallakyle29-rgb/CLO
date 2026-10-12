<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/models/AuditTrailModel.php';

auth_require_page('audit_trail.php');

$currentUser = auth_user();
$currentUserId = (int) ($currentUser['user_id'] ?? 0);
$isAdmin = auth_is_role('ADMIN');

/*
 * Filters
 */
$search = trim((string) ($_GET['search'] ?? ''));
$action = trim((string) ($_GET['action'] ?? ''));
$entityType = trim((string) ($_GET['entity_type'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));

$page = max(
    1,
    (int) ($_GET['page'] ?? 1)
);

/*
 * Get audit logs.
 */
$result = audit_log_get_list([
    'search' => $search,
    'action' => $action,
    'entity_type' => $isAdmin ? $entityType : 'DOCUMENT',
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'user_id' => $isAdmin ? null : $currentUserId,
    'exclude_action' => auth_is_role('INCOMING')
        ? 'PRINT_INITIATED'
        : null,
    'page' => $page,
    'per_page' => 20,
]);

$logs = is_array($result['rows'] ?? null)
    ? $result['rows']
    : [];

$total = (int) ($result['total'] ?? 0);

$auditPage = max(
    1,
    (int) ($result['page'] ?? $page)
);

$totalPages = max(
    1,
    (int) ($result['total_pages'] ?? 1)
);

$actions = audit_log_get_actions();

if (!is_array($actions)) {
    $actions = [];
}

$entityTypes = audit_log_get_entity_types();

if (!is_array($entityTypes)) {
    $entityTypes = [];
}

/*
 * Build pagination URL.
 */
$buildPageUrl = static function (int $targetPage): string {
    $params = $_GET;
    $params['page'] = $targetPage;

    return '?' . http_build_query($params);
};

/*
 * Build reset URL.
 */
$resetUrl = app_url('audit_trail.php');

function audit_format_value($value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    if (is_string($value)) {
        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $value = audit_resolve_snapshot_values($decoded) ?? [];
        } else {
            return $value;
        }
    }

    if (is_array($value)) {
        $value = audit_resolve_snapshot_values($value) ?? [];
        $lines = [];

        foreach ($value as $key => $item) {
            $lines[] = audit_format_key($key, $item);
        }

        return $lines === [] ? '{}' : implode(PHP_EOL, $lines);
    }

    return (string) $value;
}

function audit_format_key(string $key, $value): string
{
    $labelMap = [
        'attachment_id' => 'File',
        'document_id' => 'Document',
        'user_id' => 'User',
        'current_assignee_user_id' => 'Current Assignee',
        'assigned_user_id' => 'Assigned User',
        'assignee_user_id' => 'Assignee',
        'department_id' => 'Department',
        'current_department_id' => 'Current Department',
        'origin_department_id' => 'Origin Department',
        'status_id' => 'Status',
        'current_status_id' => 'Current Status',
        'document_type_id' => 'Document Type',
        'file_name' => 'File Name',
        'file_path' => 'File Path',
        'mime_type' => 'MIME Type',
        'priority_level' => 'Priority Level',
        'confidentiality_level' => 'Confidentiality Level',
        'title' => 'Title',
        'description' => 'Description',
        'tracking_no' => 'Tracking Number',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ];

    $normalizedKey = strtolower(trim($key));
    $label = $labelMap[$normalizedKey] ?? ucfirst(str_replace('_', ' ', $key));

    return $label . ': ' . audit_format_scalar($value);
}

function audit_format_scalar($value): string
{
    if ($value === null) {
        return 'null';
    }

    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    if (is_array($value)) {
        $lines = [];

        foreach ($value as $key => $item) {
            $lines[] = '  ' . audit_format_key((string) $key, $item);
        }

        return $lines === [] ? '{}' : "{\n" . implode(PHP_EOL, $lines) . "\n}";
    }

    if (is_object($value)) {
        return audit_format_value((array) $value);
    }

    return (string) $value;
}

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="audit-page">

    <!-- PAGE HEADER -->

    <div class="page-header bg-white rounded-3 shadow-sm p-4 mb-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

            <div>

                <h3 class="mb-1">

                    <i class="bi bi-shield-check text-primary"></i>

                    Audit Trail

                </h3>

                <div class="text-muted">

                    Monitor user actions and document activities.

                </div>

            </div>

            <div class="d-flex gap-2 no-print">

                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    onclick="window.print()"
                >
                    <i class="bi bi-printer"></i>
                    Print
                </button>

                <a
                    href="<?php echo e($resetUrl); ?>"
                    class="btn btn-outline-primary"
                >
                    <i class="bi bi-arrow-clockwise"></i>
                    Reset
                </a>

            </div>

        </div>

    </div>

    <!-- FILTER -->

    <div class="audit-filter mb-3 no-print">

        <form
            method="get"
            action="<?php echo e(app_url('audit_trail.php')); ?>"
        >

            <div class="row g-3">

                <!-- SEARCH -->

                <div class="col-lg-4">

                    <label class="form-label fw-semibold">
                        Search
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="<?php echo e($search); ?>"
                            placeholder="User, action, document, IP..."
                            autocomplete="off"
                        >

                    </div>

                </div>

                <!-- ACTION -->

                <div class="col-md-6 col-lg-2">

                    <label class="form-label fw-semibold">
                        Action
                    </label>

                    <select
                        name="action"
                        class="form-select"
                    >

                        <option value="">
                            All Actions
                        </option>

                        <?php foreach ($actions as $item): ?>

                            <?php
                            $itemValue = (string) $item;
                            ?>

                            <option
                                value="<?php echo e($itemValue); ?>"
                                <?php echo $action === $itemValue ? 'selected' : ''; ?>
                            >
                                <?php echo e($itemValue); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- ENTITY TYPE -->

                <div class="col-md-6 col-lg-2">

                    <label class="form-label fw-semibold">
                        Document
                    </label>

                    <select
                        name="entity_type"
                        class="form-select"
                    >

                        <option value="">
                            All Types
                        </option>

                        <?php foreach ($entityTypes as $item): ?>

                            <?php
                            $itemValue = (string) $item;
                            ?>

                            <option
                                value="<?php echo e($itemValue); ?>"
                                <?php echo $entityType === $itemValue ? 'selected' : ''; ?>
                            >
                                <?php echo e($itemValue); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- DATE FROM -->

                <div class="col-md-6 col-lg-2">

                    <label class="form-label fw-semibold">
                        Date From
                    </label>

                    <input
                        type="date"
                        name="date_from"
                        class="form-control"
                        value="<?php echo e($dateFrom); ?>"
                    >

                </div>

                <!-- DATE TO -->

                <div class="col-md-6 col-lg-2">

                    <label class="form-label fw-semibold">
                        Date To
                    </label>

                    <input
                        type="date"
                        name="date_to"
                        class="form-control"
                        value="<?php echo e($dateTo); ?>"
                    >

                </div>

            </div>

            <div class="mt-3 d-flex gap-2">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    <i class="bi bi-funnel"></i>
                    Apply Filter
                </button>

                <a
                    href="<?php echo e($resetUrl); ?>"
                    class="btn btn-light border"
                >
                    <i class="bi bi-x-circle"></i>
                    Clear
                </a>

            </div>

        </form>

    </div>

    <!-- TABLE -->

    <div class="audit-table-card">

        <div class="p-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">

            <div>

                <h2 class="h5 mb-1">
                    Audit Logs
                </h2>

                <div class="audit-count">

                    <?php echo e(number_format($total)); ?>

                    record<?php echo $total === 1 ? '' : 's'; ?>
                    found

                </div>

            </div>

            <div class="small text-muted no-print">

                Page
                <?php echo e((string) $auditPage); ?>
                of
                <?php echo e((string) $totalPages); ?>

            </div>

        </div>

        <div class="table-responsive">

            <table class="table table-hover audit-table">

                <thead>

                    <tr>

                        <th class="ps-3">
                            User
                        </th>

                        <th>
                            Action
                        </th>

                        <th>
                            Document
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Time
                        </th>

                        <th class="no-print">
                            Details
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($logs as $log): ?>

                    <?php

                    $userName = trim(
                        (string) ($log['first_name'] ?? '')
                        . ' '
                        . (string) ($log['last_name'] ?? '')
                    );

                    if ($userName === '') {
                        $userName = 'System / Unknown';
                    }

                    $email = trim(
                        (string) ($log['email'] ?? '')
                    );

                    $entityTypeLabel = trim(
                        (string) ($log['entity_type'] ?? 'SYSTEM')
                    );

                    if ($entityTypeLabel === '') {
                        $entityTypeLabel = 'SYSTEM';
                    }

                    $entityId = '';

                    if (
                        isset($log['entity_id'])
                        && $log['entity_id'] !== null
                        && (string) $log['entity_id'] !== ''
                    ) {
                        $entityId = (string) $log['entity_id'];
                    }

                    $entityTitle = trim(
                        (string) ($log['entity_title'] ?? '')
                    );

                    $documentLabel = $entityTypeLabel;

                    if ($entityTitle !== '') {
                        $documentLabel = $entityTitle;
                    } elseif ($entityId !== '') {
                        $documentLabel = $entityTypeLabel . ' #' . $entityId;
                    }

                    $actionValue = strtoupper(
                        trim(
                            (string) ($log['action'] ?? '')
                        )
                    );

                    $actionClass =
                        'bg-secondary-subtle text-secondary-emphasis';

                    if (
                        in_array(
                            $actionValue,
                            [
                                'CREATE',
                                'INSERT',
                                'UPLOAD'
                            ],
                            true
                        )
                    ) {

                        $actionClass =
                            'bg-success-subtle text-success-emphasis';

                    } elseif (
                        in_array(
                            $actionValue,
                            [
                                'UPDATE',
                                'EDIT',
                                'FORWARD',
                                'ROUTE'
                            ],
                            true
                        )
                    ) {

                        $actionClass =
                            'bg-primary-subtle text-primary-emphasis';

                    } elseif (
                        in_array(
                            $actionValue,
                            [
                                'DELETE',
                                'REJECT',
                                'REMOVE'
                            ],
                            true
                        )
                    ) {

                        $actionClass =
                            'bg-danger-subtle text-danger-emphasis';

                    } elseif (
                        in_array(
                            $actionValue,
                            [
                                'LOGIN',
                                'LOGOUT',
                                'VIEW'
                            ],
                            true
                        )
                    ) {

                        $actionClass =
                            'bg-warning-subtle text-warning-emphasis';

                    }

                    $createdAt = strtotime(
                        (string) ($log['created_at'] ?? '')
                    );

                    $auditLogId = (int) (
                        $log['audit_log_id']
                        ?? 0
                    );

                    ?>

                    <tr>

                        <!-- USER -->

                        <td class="ps-3">

                            <div class="audit-user">
                                <?php echo e($userName); ?>
                            </div>

                            <?php if ($email !== ''): ?>

                                <div class="small text-muted">
                                    <?php echo e($email); ?>
                                </div>

                            <?php endif; ?>

                        </td>

                        <!-- ACTION -->

                        <td>

                            <span
                                class="badge audit-action <?php echo e($actionClass); ?>"
                            >

                                <?php echo e(
                                    $actionValue !== ''
                                        ? $actionValue
                                        : 'UNKNOWN'
                                ); ?>

                            </span>

                        </td>

                        <!-- DOCUMENT -->

                        <td>

                            <div class="audit-document">
                                <?php echo e($documentLabel); ?>
                            </div>

                            <?php if ($entityId !== ''): ?>

                                <div class="small text-muted">
                                    ID: <?php echo e($entityId); ?>
                                </div>

                            <?php endif; ?>

                        </td>

                        <!-- DATE -->

                        <td class="audit-time">

                            <?php

                            echo $createdAt !== false
                                ? e(
                                    date(
                                        'M j, Y',
                                        $createdAt
                                    )
                                )
                                : '-';

                            ?>

                        </td>

                        <!-- TIME -->

                        <td class="audit-time">

                            <?php

                            echo $createdAt !== false
                                ? e(
                                    date(
                                        'g:i:s A',
                                        $createdAt
                                    )
                                )
                                : '-';

                            ?>

                        </td>

                        <!-- DETAILS -->

                        <td class="no-print">

                            <?php if ($auditLogId > 0): ?>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#auditDetailsModal<?php echo e((string) $auditLogId); ?>"
                                    title="View Details"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>

                                <!-- DETAILS MODAL -->

                                <div
                                    class="modal fade audit-details-modal"
                                    id="auditDetailsModal<?php echo e((string) $auditLogId); ?>"
                                    tabindex="-1"
                                    aria-hidden="true"
                                >

                                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">

                                        <div class="modal-content">

                                            <div class="modal-header">

                                                <h5 class="modal-title">
                                                    <i class="bi bi-shield-check text-primary"></i>
                                                    Audit Log Details
                                                </h5>

                                                <button
                                                    type="button"
                                                    class="btn-close"
                                                    data-bs-dismiss="modal"
                                                    aria-label="Close"
                                                ></button>

                                            </div>

                                            <div class="modal-body audit-details">

                                                <div class="row g-3 mb-3">

                                                    <div class="col-md-4">

                                                        <strong>
                                                            Audit ID
                                                        </strong>

                                                        <div>
                                                            <?php echo e((string) $auditLogId); ?>
                                                        </div>

                                                    </div>

                                                    <div class="col-md-4">

                                                        <strong>
                                                            User
                                                        </strong>

                                                        <div>
                                                            <?php echo e($userName); ?>
                                                        </div>

                                                    </div>

                                                    <div class="col-md-4">

                                                        <strong>
                                                            Action
                                                        </strong>

                                                        <div>
                                                            <?php echo e(
                                                                $actionValue !== ''
                                                                    ? $actionValue
                                                                    : 'UNKNOWN'
                                                            ); ?>
                                                        </div>

                                                    </div>

                                                    <div class="col-md-4">

                                                        <strong>
                                                            Entity
                                                        </strong>

                                                        <div>
                                                            <?php echo e($documentLabel); ?>
                                                        </div>

                                                    </div>

                                                    <div class="col-md-4">

                                                        <strong>
                                                            Date / Time
                                                        </strong>

                                                        <div>

                                                            <?php

                                                            echo $createdAt !== false
                                                                ? e(
                                                                    date(
                                                                        'M j, Y g:i:s A',
                                                                        $createdAt
                                                                    )
                                                                )
                                                                : '-';

                                                            ?>

                                                        </div>

                                                    </div>

                                                </div>

                                                <?php if (!empty($log['old_values'])): ?>

                                                    <div class="mb-3">

                                                        <div class="fw-semibold mb-1">
                                                            Old Values
                                                        </div>

                                                        <pre><?php echo e(
                                                            audit_format_value($log['old_values'])
                                                        ); ?></pre>

                                                    </div>

                                                <?php endif; ?>

                                                <?php if (!empty($log['new_values'])): ?>

                                                    <div class="mb-3">

                                                        <div class="fw-semibold mb-1">
                                                            New Values
                                                        </div>

                                                        <pre><?php echo e(
                                                            audit_format_value($log['new_values'])
                                                        ); ?></pre>

                                                    </div>

                                                <?php endif; ?>

                                                <?php if (!empty($log['user_agent'])): ?>

                                                    <div>

                                                        <div class="fw-semibold mb-1">
                                                            User Agent
                                                        </div>

                                                        <div class="text-muted small">
                                                            <?php echo e(
                                                                (string) $log['user_agent']
                                                            ); ?>
                                                        </div>

                                                    </div>

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

                            <?php else: ?>

                                <span class="text-muted">
                                    -
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if ($logs === []): ?>

                    <tr>

                        <td
                            colspan="7"
                            class="audit-empty"
                        >

                            <i
                                class="bi bi-shield-x"
                                style="font-size: 2rem;"
                            ></i>

                            <div class="fw-semibold mt-2">
                                No audit logs found
                            </div>

                            <div class="small">
                                Try changing your search or filters.
                            </div>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

        <!-- PAGINATION -->

        <?php if ($totalPages > 1): ?>

            <div class="p-3 border-top no-print">

                <nav aria-label="Audit log pagination">

                    <ul class="pagination justify-content-center mb-0">

                        <!-- PREVIOUS -->

                        <li
                            class="page-item <?php echo $auditPage <= 1 ? 'disabled' : ''; ?>"
                        >

                            <?php if ($auditPage <= 1): ?>

                                <span class="page-link">
                                    Previous
                                </span>

                            <?php else: ?>

                                <a
                                    class="page-link"
                                    href="<?php echo e(
                                        $buildPageUrl(
                                            $auditPage - 1
                                        )
                                    ); ?>"
                                >
                                    Previous
                                </a>

                            <?php endif; ?>

                        </li>

                        <?php

                        $startPage = max(
                            1,
                            $auditPage - 2
                        );

                        $endPage = min(
                            $totalPages,
                            $auditPage + 2
                        );

                        for (
                            $i = $startPage;
                            $i <= $endPage;
                            $i++
                        ):

                        ?>

                            <li
                                class="page-item <?php echo $i === $auditPage ? 'active' : ''; ?>"
                            >

                                <a
                                    class="page-link"
                                    href="<?php echo e(
                                        $buildPageUrl($i)
                                    ); ?>"
                                >
                                    <?php echo e((string) $i); ?>
                                </a>

                            </li>

                        <?php endfor; ?>

                        <!-- NEXT -->

                        <li
                            class="page-item <?php echo $auditPage >= $totalPages ? 'disabled' : ''; ?>"
                        >

                            <?php if ($auditPage >= $totalPages): ?>

                                <span class="page-link">
                                    Next
                                </span>

                            <?php else: ?>

                                <a
                                    class="page-link"
                                    href="<?php echo e(
                                        $buildPageUrl(
                                            $auditPage + 1
                                        )
                                    ); ?>"
                                >
                                    Next
                                </a>

                            <?php endif; ?>

                        </li>

                    </ul>

                </nav>

            </div>

        <?php endif; ?>

    </div>

</div>

<script src="<?php echo e(app_url('assets/js/features/reports/audit-trail.js')); ?>?v=20261007a"></script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
