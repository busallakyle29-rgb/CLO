<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

auth_require_page('all_documents.php');

$statusBadgeClass = static function (string $status): string {
    $value = strtolower(trim($status));

    if ($value === '' ) {
        return 'bg-secondary-subtle text-secondary-emphasis';
    }

    if (str_contains($value, 'approved') || str_contains($value, 'archive') || str_contains($value, 'completed')) {
        return 'bg-success-subtle text-success-emphasis';
    }

    if (str_contains($value, 'review') || str_contains($value, 'route') || str_contains($value, 'in progress')) {
        return 'bg-primary-subtle text-primary-emphasis';
    }

    if (str_contains($value, 'reject') || str_contains($value, 'urgent') || str_contains($value, 'return')) {
        return 'bg-danger-subtle text-danger-emphasis';
    }

    if (str_contains($value, 'receive') || str_contains($value, 'pending') || str_contains($value, 'waiting')) {
        return 'bg-warning-subtle text-warning-emphasis';
    }

    return 'bg-secondary-subtle text-secondary-emphasis';
};

$searchTerm = trim((string) ($_GET['search'] ?? ''));
$statusFilter = strtoupper(trim((string) ($_GET['status'] ?? '')));
$allowedStatusFilters = ['APPROVED'];
if (!in_array($statusFilter, $allowedStatusFilters, true)) {
    $statusFilter = '';
}

$where = [];
$params = [];
$types = '';

$where[] = 'd.archived_at IS NULL';

if ($statusFilter !== '') {
    $where[] = 'ds.status_code = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

if ($searchTerm !== '') {
    $where[] = "
        (
            d.tracking_no LIKE ?
            OR d.title LIKE ?
            OR d.description LIKE ?
            OR dt.type_name LIKE ?
            OR ds.status_name LIKE ?
        )
    ";

    $searchPattern = '%' . $searchTerm . '%';
    array_push($params, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern);
    $types .= 'sssss';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "
    SELECT
        d.document_id,
        d.tracking_no,
        d.title,
        d.description,
        d.date_received,
        dt.type_name,
        ds.status_name,
        cd.department_name AS current_department_name,
        CONCAT(
            COALESCE(u.first_name, ''),
            ' ',
            COALESCE(u.last_name, '')
        ) AS assigned_staff_name
    FROM documents d
    LEFT JOIN document_types dt
        ON dt.document_type_id = d.document_type_id
    LEFT JOIN document_status ds
        ON ds.document_status_id = d.current_status_id
    LEFT JOIN departments cd
        ON cd.department_id = d.current_department_id
    LEFT JOIN users u
        ON u.user_id = d.current_assignee_user_id
    {$whereSql}
    ORDER BY d.document_id DESC
";

$stmt = db_execute($sql, $types, $params);
$documents = db_fetch_all_assoc($stmt);
$stmt->close();

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">

    <div class="page-header bg-white rounded-3 shadow-sm p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h3 class="mb-1">
                    <i class="bi bi-folder2-open text-primary"></i>
                    <?php echo $statusFilter === 'APPROVED' ? 'Approved Documents' : 'All Documents'; ?>
                </h3>
                <div class="text-muted">
                    View all documents in the system for authorized users only.
                </div>
            </div>

            <span class="badge bg-primary text-white" style="font-size: 1rem; padding: 0.5rem 1rem;">
                <?php echo e((string) count($documents)); ?> Records
            </span>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="get" action="<?php echo e(app_url('all_documents.php')); ?>" class="row g-3 align-items-end">
                <div class="col-md-10">
                    <label for="documentSearch" class="form-label fw-semibold">Search Documents</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input
                            type="text"
                            id="documentSearch"
                            name="search"
                            class="form-control form-control-lg"
                            value="<?php echo e($searchTerm); ?>"
                            placeholder="Search by tracking number, title, description, type, or status"
                            autocomplete="off"
                        >
                        <?php if ($statusFilter !== ''): ?>
                            <input type="hidden" name="status" value="<?php echo e($statusFilter); ?>">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i>
                        Search
                    </button>
                </div>
            </form>

            <?php if ($statusFilter !== ''): ?>
                <a class="btn btn-sm btn-outline-secondary mt-2" href="<?php echo e(app_url('all_documents.php')); ?>">
                    Clear status filter
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <strong>
                <i class="bi bi-list-ul text-primary"></i>
                Document Record List
            </strong>
            <?php if ($searchTerm !== ''): ?>
                <a href="<?php echo e(app_url('all_documents.php')); ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i>
                    Clear
                </a>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tracking No.</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Department</th>
                        <th>Assigned To</th>
                        <th>Date Received</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documents)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No documents found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($documents as $document): ?>
                            <?php
                            $statusName = (string) ($document['status_name'] ?? 'N/A');
                            $typeName = (string) ($document['type_name'] ?? 'N/A');
                            $assignedName = trim((string) ($document['assigned_staff_name'] ?? '')) !== ''
                                ? (string) $document['assigned_staff_name']
                                : 'Unassigned';
                            $departmentName = trim((string) ($document['current_department_name'] ?? '')) !== ''
                                ? (string) $document['current_department_name']
                                : 'N/A';
                            $dateReceived = !empty($document['date_received'])
                                ? date('M d, Y', strtotime((string) $document['date_received']))
                                : '—';
                            ?>
                            <tr>
                                <td class="fw-semibold text-primary">
                                    <?php echo e((string) ($document['tracking_no'] ?? '')); ?>
                                </td>
                                <td>
                                    <div><?php echo e((string) ($document['title'] ?? '')); ?></div>
                                    <small class="text-muted">
                                        <?php echo e((string) mb_substr((string) ($document['description'] ?? ''), 0, 60)); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?php echo e($typeName); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo e(($statusBadgeClass)($statusName)); ?>">
                                        <?php echo e($statusName); ?>
                                    </span>
                                </td>
                                <td><?php echo e($departmentName); ?></td>
                                <td><?php echo e($assignedName); ?></td>
                                <td><?php echo e($dateReceived); ?></td>
                                <td class="text-end pe-3">
                                    <a
                                        href="<?php echo e(app_url('document_tracking.php?tracking_no=' . urlencode((string) ($document['tracking_no'] ?? '')))); ?>"
                                        class="btn btn-sm btn-outline-primary"
                                        title="View Document"
                                    >
                                        <i class="bi bi-eye"></i>
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
