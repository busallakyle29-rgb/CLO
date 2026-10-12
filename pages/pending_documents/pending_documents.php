<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/models/PendingDocumentsModel.php';

auth_require_page('pending_documents.php');

$user = auth_user();
$userId = (int) ($user['user_id'] ?? 0);
$userDepartmentId = (int) ($user['department_id'] ?? 0);

$pendingDocuments = [];
$totalPendingCount = 0;

try {
    $totalPendingCount = pending_get_count();
    $pendingDocuments = pending_get_documents(100);
} catch (Throwable $e) {
    error_log('Error fetching pending documents: ' . $e->getMessage());
}

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">

    <!-- PAGE HEADER -->
    <div class="page-header bg-white rounded-3 shadow-sm p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h3 class="mb-1">
                    <i class="bi bi-hourglass-split text-primary"></i>
                    Pending Documents
                </h3>
                <div class="text-muted">
                    View all documents awaiting action or review
                </div>
            </div>
            <span class="badge bg-primary text-white" style="font-size: 1rem; padding: 0.5rem 1rem;">
                <?php echo e((string) $totalPendingCount); ?> Pending
            </span>
        </div>
    </div>

    <!-- STATISTICS -->
    <div class="row g-3 mb-4">
        <?php
        $overduCount = 0;
        $urgentCount = 0;
        $dueSoonCount = 0;

        foreach ($pendingDocuments as $doc) {
            $daysUntilDue = isset($doc['days_until_due'])
                ? (int) $doc['days_until_due']
                : null;
            if ($daysUntilDue !== null && $daysUntilDue < 0) {
                $overduCount++;
            } elseif (strtoupper((string) ($doc['priority_level'] ?? '')) === 'URGENT') {
                $urgentCount++;
            } elseif ($daysUntilDue !== null && $daysUntilDue <= 3) {
                $dueSoonCount++;
            }
        }
        ?>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Overdue</div>
                            <div class="h3 text-danger mb-0"><?php echo e((string) $overduCount); ?></div>
                        </div>
                        <i class="bi bi-exclamation-circle text-danger" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Urgent</div>
                            <div class="h3 text-warning mb-0"><?php echo e((string) $urgentCount); ?></div>
                        </div>
                        <i class="bi bi-lightning-fill text-warning" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Due Soon (3 days)</div>
                            <div class="h3 text-info mb-0"><?php echo e((string) $dueSoonCount); ?></div>
                        </div>
                        <i class="bi bi-clock-history text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- PENDING DOCUMENTS TABLE -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <strong>
                    <i class="bi bi-list-task text-primary"></i>
                    All Pending Documents
                </strong>
                <div class="input-group pending-search">
                    <span class="input-group-text bg-white" id="pending-search-label">
                        <i class="bi bi-search"></i>
                    </span>
                    <input
                        type="search"
                        class="form-control"
                        id="pending-document-search"
                        placeholder="Search pending documents..."
                        aria-label="Search pending documents"
                        aria-describedby="pending-search-label"
                    >
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tracking Number</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Due Date</th>
                        <th>Assigned To</th>
                        <th>Days Until Due</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pendingDocuments)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No pending documents found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pendingDocuments as $doc): ?>
                            <?php
                            $daysUntilDue = isset($doc['days_until_due'])
                                ? (int) $doc['days_until_due']
                                : null;
                            $priority = match (strtoupper((string) ($doc['priority_level'] ?? 'MEDIUM'))) {
                                'LOW' => 'Low',
                                'HIGH' => 'High',
                                'URGENT' => 'Urgent',
                                default => 'Medium',
                            };
                            $priorityClass = match($priority) {
                                'Urgent' => 'bg-danger-subtle text-danger-emphasis',
                                'High' => 'bg-warning-subtle text-warning-emphasis',
                                'Medium' => 'bg-info-subtle text-info-emphasis',
                                default => 'bg-secondary-subtle text-secondary-emphasis',
                            };

                            $dueClass = '';
                            if ($daysUntilDue !== null && $daysUntilDue < 0) {
                                $dueClass = 'text-danger fw-semibold';
                            } elseif ($daysUntilDue !== null && $daysUntilDue <= 3) {
                                $dueClass = 'text-warning fw-semibold';
                            }

                            $dueDisplay = $daysUntilDue === null
                                ? 'No due date'
                                : ($daysUntilDue < 0
                                ? 'Overdue by ' . abs($daysUntilDue) . ' days'
                                : ($daysUntilDue === 0 ? 'Today' : 'In ' . $daysUntilDue . ' days'));
                            ?>
                            <tr data-pending-document-row>
                                <td class="fw-semibold text-primary">
                                    <?php echo e((string) ($doc['tracking_no'] ?? '')); ?>
                                </td>
                                <td>
                                    <div><?php echo e((string) ($doc['title'] ?? '')); ?></div>
                                    <small class="text-muted"><?php echo e((string) mb_substr($doc['description'] ?? '', 0, 50)); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?php echo e((string) ($doc['type_name'] ?? 'N/A')); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo e($priorityClass); ?>">
                                        <?php echo e($priority); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($doc['date_due'])): ?>
                                        <div><?php echo e(date('M d, Y', strtotime((string) $doc['date_due']))); ?></div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo e((string) ($doc['assigned_staff_name'] ?? 'Unassigned')); ?>
                                </td>
                                <td class="<?php echo e($dueClass); ?>">
                                    <?php echo e($dueDisplay); ?>
                                </td>
                                <td class="text-end pe-3">
                                    <a
                                        href="<?php echo e(app_url('document_tracking.php?tracking_no=' . urlencode((string) $doc['tracking_no']))); ?>"
                                        class="btn btn-sm btn-outline-primary"
                                        title="View Document"
                                    >
                                        <i class="bi bi-eye"></i>
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="pending-no-search-results" class="d-none">
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-search fs-1 d-block mb-2"></i>
                                No pending documents match your search.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="<?php echo e(app_url('assets/js/features/documents/pending-documents.js')); ?>?v=20260907"></script>

<?php
require dirname(__DIR__, 2) . '/includes/footer.php';
?>
