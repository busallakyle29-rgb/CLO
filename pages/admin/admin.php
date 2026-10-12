<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/controllers/UserManagementController.php';
auth_require_role(['ADMIN']);

$currentUser = auth_user();
$currentUserId = (int) ($currentUser['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    switch ($action) {
        case 'add_user':
            $result = controller_um_add_user($_POST);
            break;
        case 'edit_user':
            $result = controller_um_edit_user($_POST, $currentUserId);
            break;
        case 'delete_user':
            $result = controller_um_delete_user($_POST, $currentUserId);
            break;
        case 'toggle_status':
            $result = controller_um_toggle_user_status($_POST, $currentUserId);
            break;
        default:
            $result = ['errors' => ['Unknown action request.']];
            break;
    }

    if (!empty($result['errors'])) {
        flash_set('error', implode(' ', $result['errors']));
    } else {
        flash_set('success', (string) ($result['success'] ?? 'Request completed successfully.'));
    }

    redirect_to('admin.php');
}

$users = um_get_users();
$roles = um_get_roles();
$departments = um_get_departments();

$filterStatus = strtolower(trim((string) ($_GET['status'] ?? 'all')));
if ($filterStatus === 'inactive') {
  $filterStatus = 'pending';
}
if (!in_array($filterStatus, ['all', 'pending', 'active'], true)) {
    $filterStatus = 'all';
}

$filteredUsers = array_values(array_filter($users, static function (array $user) use ($filterStatus): bool {
    $status = (int) ($user['is_active'] ?? 0);

    if ($filterStatus === 'all') {
        return true;
    }

    if ($filterStatus === 'pending') {
        return $status === 0;
    }

    return $status === 1;
}));

require dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h4 mb-1">User Management</h1>
    <p class="text-muted mb-0">Manage user accounts, roles, departments, and account status.</p>
  </div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-3">
    <div class="d-flex flex-wrap align-items-center gap-2">
      <span class="text-muted me-2"><i class="bi bi-funnel"></i> Filter:</span>

      <a href="<?php echo e(app_url('admin.php?status=all')); ?>" class="btn btn-sm <?php echo $filterStatus === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
      <a href="<?php echo e(app_url('admin.php?status=pending')); ?>" class="btn btn-sm <?php echo $filterStatus === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">Pending / Inactive</a>
      <a href="<?php echo e(app_url('admin.php?status=active')); ?>" class="btn btn-sm <?php echo $filterStatus === 'active' ? 'btn-success' : 'btn-outline-success'; ?>">Active</a>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="px-3">Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Department</th>
            <th>Role</th>
            <th>Status</th>
            <th class="text-end pe-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($users === []): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">No users found.</td>
            </tr>
          <?php endif; ?>

          <?php foreach ($filteredUsers as $row): ?>
            <?php
              $fullName = trim((string) $row['first_name'] . ' ' . (string) $row['last_name']);
              $isActive = (int) $row['is_active'] === 1;
              $targetStatus = $isActive ? '0' : '1';
              $statusLabel = $isActive ? 'Active' : 'Pending';
              $statusClass = $isActive ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis';
              $toggleLabel = $isActive ? 'Deactivate' : 'Approve';
            ?>
            <tr>
              <td class="px-3">
                <div class="fw-semibold"><?php echo e($fullName); ?></div>
                <div class="text-muted small"><?php echo e((string) $row['employee_no']); ?></div>
              </td>
              <td><?php echo e((string) $row['username']); ?></td>
              <td><?php echo e((string) $row['email']); ?></td>
              <td><?php echo e((string) $row['department_name']); ?></td>
              <td><span class="badge bg-primary-subtle text-primary-emphasis"><?php echo e((string) $row['role_name']); ?></span></td>
              <td><span class="badge <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span></td>
              <td class="text-end pe-3">
                <div class="btn-group btn-group-sm" role="group">
                  <button
                    type="button"
                    class="btn btn-outline-primary btn-edit-user"
                    data-bs-toggle="modal"
                    data-bs-target="#editUserModal"
                    data-user-id="<?php echo e((string) $row['user_id']); ?>"
                    data-employee-no="<?php echo e((string) $row['employee_no']); ?>"
                    data-username="<?php echo e((string) $row['username']); ?>"
                    data-email="<?php echo e((string) $row['email']); ?>"
                    data-first-name="<?php echo e((string) $row['first_name']); ?>"
                    data-last-name="<?php echo e((string) $row['last_name']); ?>"
                    data-role-id="<?php echo e((string) $row['role_id']); ?>"
                    data-is-current-user="<?php echo (int) $row['user_id'] === $currentUserId ? '1' : '0'; ?>"
                    data-department-id="<?php echo e((string) $row['department_id']); ?>"
                    data-is-active="<?php echo e((string) $row['is_active']); ?>"
                  >
                    Edit
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-warning btn-toggle-user-status"
                    data-bs-toggle="modal"
                    data-bs-target="#toggleStatusModal"
                    data-user-id="<?php echo e((string) $row['user_id']); ?>"
                    data-full-name="<?php echo e($fullName); ?>"
                    data-target-status="<?php echo e($targetStatus); ?>"
                    data-target-label="<?php echo e($toggleLabel); ?>"
                  >
                    <?php echo e($toggleLabel); ?>
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-danger btn-delete-user"
                    data-bs-toggle="modal"
                    data-bs-target="#deleteUserModal"
                    data-user-id="<?php echo e((string) $row['user_id']); ?>"
                    data-full-name="<?php echo e($fullName); ?>"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="<?php echo e(app_url('admin.php')); ?>">
        <div class="modal-header">
          <h2 class="h5 modal-title mb-0">Add User</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="add_user">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('um_add_user_form')); ?>">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Employee No</label>
              <input type="text" name="employee_no" class="form-control" placeholder="CLO-12345" value="">
            </div>
            <div class="col-md-6">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Role</label>
              <select name="role_id" class="form-select" required>
                <option value="">Select role</option>
                <?php foreach ($roles as $role): ?>
                  <option value="<?php echo e((string) $role['role_id']); ?>"><?php echo e((string) $role['role_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Department</label>
              <select name="department_id" class="form-select" required>
                <option value="">Select department</option>
                <?php foreach ($departments as $department): ?>
                  <option value="<?php echo e((string) $department['department_id']); ?>"><?php echo e((string) $department['department_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" minlength="8" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Confirm Password</label>
              <input type="password" name="confirm_password" class="form-control" minlength="8" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="is_active" class="form-select">
                <option value="1" selected>Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="<?php echo e(app_url('admin.php')); ?>">
        <div class="modal-header">
          <h2 class="h5 modal-title mb-0">Edit User</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="edit_user">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('um_edit_user_form')); ?>">
          <input type="hidden" name="user_id" id="edit_user_id">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Employee No</label>
              <input type="text" name="employee_no" id="edit_employee_no" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Username</label>
              <input type="text" name="username" id="edit_username" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Role</label>
              <select name="role_id" id="edit_role_id" class="form-select" required>
                <option value="">Select role</option>
                <?php foreach ($roles as $role): ?>
                  <option value="<?php echo e((string) $role['role_id']); ?>"><?php echo e((string) $role['role_name']); ?></option>
                <?php endforeach; ?>
              </select>
              <input type="hidden" name="role_id" id="edit_role_id_locked" disabled>
              <div class="form-text d-none" id="edit_role_lock_note">You cannot change your own administrator role.</div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Department</label>
              <select name="department_id" id="edit_department_id" class="form-select" required>
                <option value="">Select department</option>
                <?php foreach ($departments as $department): ?>
                  <option value="<?php echo e((string) $department['department_id']); ?>"><?php echo e((string) $department['department_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">New Password (optional)</label>
              <input type="password" name="password" class="form-control" minlength="8">
            </div>
            <div class="col-md-6">
              <label class="form-label">Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control" minlength="8">
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="is_active" id="edit_is_active" class="form-select">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="<?php echo e(app_url('admin.php')); ?>">
        <div class="modal-header">
          <h2 class="h5 modal-title mb-0">Delete User</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="delete_user">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('um_delete_user_form')); ?>">
          <input type="hidden" name="user_id" id="delete_user_id">
          <p class="mb-0">Are you sure you want to delete <strong id="delete_user_name"></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="<?php echo e(app_url('admin.php')); ?>">
        <div class="modal-header">
          <h2 class="h5 modal-title mb-0" id="toggle_status_title">Update User Status</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="toggle_status">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('um_toggle_status_form')); ?>">
          <input type="hidden" name="user_id" id="toggle_user_id">
          <input type="hidden" name="is_active" id="toggle_target_status">
          <p class="mb-0"><span id="toggle_action_label">Update</span> account for <strong id="toggle_user_name"></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning" id="toggle_submit_btn">Confirm</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="<?php echo e(app_url('assets/js/features/admin/admin.js')); ?>?v=20261005a"></script>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
