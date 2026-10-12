<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/controllers/DepartmentManagementController.php';
auth_require_page('department_management.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    switch ($action) {
        case 'add_department':
            $result = controller_dm_add_department($_POST);
            break;
        case 'edit_department':
            $result = controller_dm_edit_department($_POST);
            break;
        case 'delete_department':
            $result = controller_dm_delete_department($_POST);
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

    redirect_to('department_management.php');
}

$departments = dm_get_departments();

// Get statistics
$totalDepartments = count($departments);
$activeDepartments = count(array_filter($departments, fn($d) => (int) $d['is_active'] === 1));
$totalPersonnel = 0;
$totalDocuments = 0;

// Calculate totals from departments (you may need to query the database for actual counts)
foreach ($departments as $dept) {
    // These would ideally come from the database
    // For now using placeholder values from the database if available
    $totalPersonnel += (int) ($dept['staff_count'] ?? 0);
    $totalDocuments += (int) ($dept['document_count'] ?? 0);
}

require dirname(__DIR__, 2) . '/includes/header.php';
?>
<!-- =====================================================
     HEADER WITH ADD BUTTON
====================================================== -->

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h1 class="h4 mb-1">
      <i class="bi bi-building text-primary"></i>
      Department Management
    </h1>
    <p class="text-muted mb-0">Manage divisions, offices, and their assigned functions</p>
  </div>
  <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
    <i class="bi bi-plus-circle"></i>
    Add Department
  </button>
</div>

<!-- =====================================================
     STATISTICS CARDS
====================================================== -->

<div class="row g-3 mb-4">

  <!-- Total Departments -->
  <div class="col-md-6 col-lg-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-primary bg-opacity-10 rounded-3 p-3">
          <i class="bi bi-building text-primary fs-4"></i>
        </div>
        <div>
          <div class="text-muted small fw-semibold">TOTAL DEPARTMENTS</div>
          <div class="h4 mb-0"><?php echo e((string) $totalDepartments); ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Active Divisions -->
  <div class="col-md-6 col-lg-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-success bg-opacity-10 rounded-3 p-3">
          <i class="bi bi-check-circle text-success fs-4"></i>
        </div>
        <div>
          <div class="text-muted small fw-semibold">ACTIVE DIVISIONS</div>
          <div class="h4 mb-0"><?php echo e((string) $activeDepartments); ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Total Personnel -->
  <div class="col-md-6 col-lg-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-info bg-opacity-10 rounded-3 p-3">
          <i class="bi bi-people text-info fs-4"></i>
        </div>
        <div>
          <div class="text-muted small fw-semibold">TOTAL PERSONNEL</div>
          <div class="h4 mb-0"><?php echo e((string) $totalPersonnel); ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Total Documents -->
  <div class="col-md-6 col-lg-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-warning bg-opacity-10 rounded-3 p-3">
          <i class="bi bi-file-earmark-text text-warning fs-4"></i>
        </div>
        <div>
          <div class="text-muted small fw-semibold">TOTAL DOCUMENTS</div>
          <div class="h4 mb-0"><?php echo e((string) $totalDocuments); ?></div>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- =====================================================
     SEARCH & FILTER
====================================================== -->

<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-9">
        <input
          type="search"
          class="form-control"
          id="departmentSearch"
          placeholder="Search by name, code, head..."
          autocomplete="off"
        >
      </div>
      <div class="col-md-3">
        <select class="form-select" id="statusFilter">
          <option value="">All Status</option>
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
      </div>
    </div>
  </div>
</div>

<!-- =====================================================
     DEPARTMENT TABLE
====================================================== -->

<div class="card border-0 shadow-sm" id="departmentsTable">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="px-3">Code</th>
            <th>Name</th>
            <th>Description</th>
            <th>Head</th>
            <th>Staff</th>
            <th>Documents</th>
            <th>Status</th>
            <th class="text-end pe-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($departments)): ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-4">No departments found.</td>
            </tr>
          <?php else: ?>

            <?php foreach ($departments as $department): ?>

              <?php
                $deptId = (int) $department['department_id'];
                $deptCode = (string) $department['department_code'];
                $deptName = (string) $department['department_name'];
                $deptDesc = (string) ($department['description'] ?? '');
                $isActive = (int) $department['is_active'] === 1;
                $statusLabel = $isActive ? 'Active' : 'Inactive';
                $statusBadge = $isActive ? 'text-bg-success' : 'text-bg-secondary';
                $staffCount = (int) ($department['staff_count'] ?? 0);
                $docCount = (int) ($department['document_count'] ?? 0);
                $headName = trim((string) ($department['head_name'] ?? ''));
                $headName = $headName !== '' ? $headName : 'Unassigned';
                $headEmail = trim((string) ($department['head_email'] ?? ''));
              ?>

              <tr class="department-table-row" data-active="<?php echo e((string) (int) $isActive); ?>">
                <td class="px-3 fw-semibold"><?php echo e($deptCode); ?></td>
                <td><?php echo e($deptName); ?></td>
                <td class="text-muted">
                  <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?php echo e($deptDesc); ?>">
                    <?php echo e(substr($deptDesc, 0, 50)); ?><?php echo e(strlen($deptDesc) > 50 ? '...' : ''); ?>
                  </span>
                </td>
                <td>
                  <div>
                    <div class="small fw-semibold"><?php echo e($headName); ?></div>
                    <?php if ($headEmail !== ''): ?>
                      <div class="small text-muted"><?php echo e($headEmail); ?></div>
                    <?php endif; ?>
                  </div>
                </td>
                <td><?php echo e((string) $staffCount); ?></td>
                <td><?php echo e((string) $docCount); ?></td>
                <td><span class="badge <?php echo e($statusBadge); ?>"><?php echo e($statusLabel); ?></span></td>
                <td class="text-end pe-3">
                  <div class="btn-group btn-group-sm">
                    <button
                      type="button"
                      class="btn btn-outline-secondary btn-edit-department"
                      data-bs-toggle="modal"
                      data-bs-target="#editDepartmentModal"
                      data-department-id="<?php echo e((string) $deptId); ?>"
                      data-department-code="<?php echo e((string) $deptCode); ?>"
                      data-department-name="<?php echo e((string) $deptName); ?>"
                      data-description="<?php echo e((string) $deptDesc); ?>"
                      data-is-active="<?php echo e((string) (int) $isActive); ?>"
                    >
                      <i class="bi bi-pencil"></i>
                      Edit
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-danger btn-delete-department"
                      data-bs-toggle="modal"
                      data-bs-target="#deleteDepartmentModal"
                      data-department-id="<?php echo e((string) $deptId); ?>"
                      data-department-name="<?php echo e((string) $deptName); ?>"
                    >
                      <i class="bi bi-trash"></i>
                      Delete
                    </button>
                  </div>
                </td>
              </tr>

            <?php endforeach; ?>

          <?php endif; ?>

        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="<?php echo e(app_url('department_management.php')); ?>">
        <div class="modal-header">
          <h2 class="h5 modal-title mb-0">Add Department</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="add_department">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('dm_add_department_form')); ?>">

          <div class="mb-3">
            <label class="form-label">Department Code</label>
            <input type="text" name="department_code" class="form-control" maxlength="20" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Department Name</label>
            <input type="text" name="department_name" class="form-control" maxlength="120" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" maxlength="255"></textarea>
          </div>
          <div>
            <label class="form-label">Status</label>
            <select name="is_active" class="form-select">
              <option value="1" selected>Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Department</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="<?php echo e(app_url('department_management.php')); ?>">
        <div class="modal-header">
          <h2 class="h5 modal-title mb-0">Edit Department</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="edit_department">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('dm_edit_department_form')); ?>">
          <input type="hidden" name="department_id" id="edit_department_id">

          <div class="mb-3">
            <label class="form-label">Department Code</label>
            <input type="text" name="department_code" id="edit_department_code" class="form-control" maxlength="20" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Department Name</label>
            <input type="text" name="department_name" id="edit_department_name" class="form-control" maxlength="120" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" id="edit_description" class="form-control" rows="3" maxlength="255"></textarea>
          </div>
          <div>
            <label class="form-label">Status</label>
            <select name="is_active" id="edit_is_active" class="form-select">
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Department</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteDepartmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="<?php echo e(app_url('department_management.php')); ?>">
        <div class="modal-header">
          <h2 class="h5 modal-title mb-0">Delete Department</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="delete_department">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('dm_delete_department_form')); ?>">
          <input type="hidden" name="department_id" id="delete_department_id">
          <p class="mb-0">Are you sure you want to delete <strong id="delete_department_name"></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="<?php echo e(app_url('assets/js/features/admin/department-management.js')); ?>?v=20260820c"></script>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
