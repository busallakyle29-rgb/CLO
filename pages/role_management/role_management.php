<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/models/UserManagementModel.php';

auth_require_role(['ADMIN']);

$roles = um_get_roles();
$pageList = auth_get_page_access_list();
$pageAccessMap = auth_page_access_map();

$pagesByRole = [];
foreach ($roles as $role) {
    $roleName = strtoupper(trim((string) ($role['role_name'] ?? '')));
    $pagesByRole[$roleName] = [];

    foreach ($pageAccessMap as $pageName => $allowedRoles) {
        if (in_array($roleName, $allowedRoles, true)) {
            $pagesByRole[$roleName][] = $pageName;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_role_permissions') {
        if (!csrf_validate('role_access_form', $_POST['csrf_token'] ?? null)) {
            flash_set('error', 'Invalid request token. Please refresh and try again.');
            redirect_to('role_management.php');
            exit;
        }

        $roleName = strtoupper(trim((string) ($_POST['role_name'] ?? '')));
        $selectedPages = $_POST['pages'] ?? [];

        if (!is_array($selectedPages)) {
            $selectedPages = [];
        }

        $normalizedPages = [];
        foreach ($selectedPages as $page) {
            $pageName = trim((string) $page);
            if ($pageName !== '') {
                $normalizedPages[] = $pageName;
            }
        }

        if ($roleName !== 'ADMIN' && !in_array('dashboard.php', $normalizedPages, true)) {
            flash_set(
                'error',
                'dashboard.php must be included for role "' . $roleName . '" - otherwise users with this role will be unable to access the system after logging in.'
            );

            redirect_to('role_management.php');
            exit;
        }

        $stmt = db_execute('DELETE FROM page_access WHERE role_name = ?', 's', [$roleName]);
        $stmt->close();

        foreach ($normalizedPages as $pageName) {
            $insert = db_execute(
                'INSERT INTO page_access (page_name, role_name, is_allowed) VALUES (?, ?, ?)',
                'ssi',
                [$pageName, $roleName, 1]
            );
            $insert->close();
        }

        flash_set('success', 'Updated page access for role ' . $roleName . '.');
        redirect_to('role_management.php');
    }
}

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="page-header bg-white rounded-3 shadow-sm p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h3 class="mb-1">
                    <i class="bi bi-shield-lock text-primary"></i>
                    Role Management
                </h3>
                <div class="text-muted">
                    List of roles and the pages each role can access.
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3">Role</th>
                            <th>Pages Allowed</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                            <?php
                                $roleName = strtoupper(trim((string) ($role['role_name'] ?? '')));
                                $allowedPages = $pagesByRole[$roleName] ?? [];
                            ?>
                            <tr>
                                <td class="px-3">
                                    <div class="fw-semibold"><?php echo e((string) ($role['role_name'] ?? '')); ?></div>
                                </td>
                                <td>
                                    <?php if ($allowedPages === []): ?>
                                        <span class="text-muted">No pages assigned</span>
                                    <?php else: ?>
                                        <?php foreach ($allowedPages as $page): ?>
                                            <span class="badge bg-primary-subtle text-primary-emphasis me-1 mb-1"><?php echo e($page); ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editRoleAccessModal"
                                        data-role-name="<?php echo e($roleName); ?>"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editRoleAccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" action="<?php echo e(app_url('role_management.php')); ?>">
                <div class="modal-header">
                    <h2 class="h5 modal-title mb-0">Edit Role Access</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_role_permissions">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('role_access_form')); ?>">
                    <input type="hidden" name="role_name" id="edit_role_name">

                    <div class="row g-3">
                        <?php foreach ($pageList as $pageName): ?>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="pages[]"
                                        value="<?php echo e($pageName); ?>"
                                        id="page_<?php echo e(str_replace('.', '_', $pageName)); ?>"
                                    >
                                    <label class="form-check-label" for="page_<?php echo e(str_replace('.', '_', $pageName)); ?>">
                                        <?php echo e($pageName); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Access</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="application/json" id="role-access-map"><?php
    echo json_encode($pagesByRole, JSON_THROW_ON_ERROR);
?></script>
<script src="<?php echo e(app_url('assets/js/features/admin/role-management.js')); ?>"></script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
