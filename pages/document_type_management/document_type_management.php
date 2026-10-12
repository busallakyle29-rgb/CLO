<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/controllers/DocumentTypeManagementController.php';
auth_require_page('document_type_management.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    switch ($action) {
        case 'add_document_type':
            $result = controller_dt_add_document_type($_POST);
            break;
        case 'edit_document_type':
            $result = controller_dt_edit_document_type($_POST);
            break;
        case 'delete_document_type':
            $result = controller_dt_delete_document_type($_POST);
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

    redirect_to('document_type_management.php');
}

$documentTypes = dt_get_document_types();

require dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h1 class="h4 mb-1">
      <i class="bi bi-file-earmark-text text-primary"></i>
      Document Type Management
    </h1>
    <p class="text-muted mb-0">Manage document categories used in receiving and tracking.</p>
  </div>
  <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addDocumentTypeModal">
    <i class="bi bi-plus-circle"></i>
    Add Document Type
  </button>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="px-3">Code</th>
            <th>Type Name</th>
            <th>Description</th>
            <th>Retention Years</th>
            <th>Require Acknowledgment</th>
            <th>Status</th>
            <th class="text-end pe-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($documentTypes === []): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">No document types found.</td>
            </tr>
          <?php endif; ?>

          <?php foreach ($documentTypes as $row): ?>
            <?php
              $isActive = (int) $row['is_active'] === 1;
              $statusLabel = $isActive ? 'Active' : 'Inactive';
              $statusClass = $isActive ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary-emphasis';
              $ackLabel = (int) $row['requires_acknowledgment'] === 1 ? 'Yes' : 'No';
            ?>
            <tr>
              <td class="px-3"><span class="badge bg-primary-subtle text-primary-emphasis"><?php echo e((string) $row['type_code']); ?></span></td>
              <td><?php echo e((string) $row['type_name']); ?></td>
              <td><?php echo e((string) ($row['description'] ?? '')); ?></td>
              <td><?php echo e((string) (((int) ($row['retention_days'] ?? 0)) / 365)); ?> years</td>
              <td><?php echo e($ackLabel); ?></td>
              <td><span class="badge <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span></td>
              <td class="text-end pe-3">
                <div class="btn-group btn-group-sm" role="group">
                  <button
                    type="button"
                    class="btn btn-outline-primary btn-edit-document-type"
                    data-bs-toggle="modal"
                    data-bs-target="#editDocumentTypeModal"
                    data-document-type-id="<?php echo e((string) $row['document_type_id']); ?>"
                    data-type-code="<?php echo e((string) $row['type_code']); ?>"
                    data-type-name="<?php echo e((string) $row['type_name']); ?>"
                    data-description="<?php echo e((string) ($row['description'] ?? '')); ?>"
                    data-retention-years="<?php echo e((string) (((int) ($row['retention_days'] ?? 0)) / 365)); ?>"
                    data-requires-acknowledgment="<?php echo e((string) $row['requires_acknowledgment']); ?>"
                    data-is-active="<?php echo e((string) $row['is_active']); ?>"
                  >
                    Edit
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-danger btn-delete-document-type"
                    data-bs-toggle="modal"
                    data-bs-target="#deleteDocumentTypeModal"
                    data-document-type-id="<?php echo e((string) $row['document_type_id']); ?>"
                    data-type-name="<?php echo e((string) $row['type_name']); ?>"
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

<div class="modal fade" id="addDocumentTypeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="<?php echo e(app_url('document_type_management.php')); ?>">
        <div class="modal-header">
          <h2 class="h5 modal-title mb-0">Add Document Type</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="add_document_type">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('dt_add_document_type_form')); ?>">

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Type Code</label>
              <input type="text" name="type_code" class="form-control" maxlength="20" required>
            </div>
            <div class="col-md-8">
              <label class="form-label">Type Name</label>
              <input type="text" name="type_name" class="form-control" maxlength="100" required>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3" maxlength="255"></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Retention Years</label>
              <input type="number" name="retention_years" class="form-control" min="0" value="0" step="0.1">
            </div>
            <div class="col-md-4">
              <label class="form-label">Requires Acknowledgment</label>
              <select name="requires_acknowledgment" class="form-select">
                <option value="0" selected>No</option>
                <option value="1">Yes</option>
              </select>
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
          <button type="submit" class="btn btn-primary">Save Document Type</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editDocumentTypeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="<?php echo e(app_url('document_type_management.php')); ?>">
        <div class="modal-header">
          <h2 class="h5 modal-title mb-0">Edit Document Type</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="edit_document_type">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('dt_edit_document_type_form')); ?>">
          <input type="hidden" name="document_type_id" id="edit_document_type_id">

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Type Code</label>
              <input type="text" name="type_code" id="edit_type_code" class="form-control" maxlength="20" required>
            </div>
            <div class="col-md-8">
              <label class="form-label">Type Name</label>
              <input type="text" name="type_name" id="edit_type_name" class="form-control" maxlength="100" required>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" id="edit_description" class="form-control" rows="3" maxlength="255"></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Retention Years</label>
              <input type="number" name="retention_years" id="edit_retention_years" class="form-control" min="0" value="0" step="0.1">
            </div>
            <div class="col-md-4">
              <label class="form-label">Requires Acknowledgment</label>
              <select name="requires_acknowledgment" id="edit_requires_acknowledgment" class="form-select">
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
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
          <button type="submit" class="btn btn-primary">Update Document Type</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteDocumentTypeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="<?php echo e(app_url('document_type_management.php')); ?>">
        <div class="modal-header">
          <h2 class="h5 modal-title mb-0">Delete Document Type</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="delete_document_type">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('dt_delete_document_type_form')); ?>">
          <input type="hidden" name="document_type_id" id="delete_document_type_id">
          <p class="mb-0">Are you sure you want to delete <strong id="delete_document_type_name"></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="<?php echo e(app_url('assets/js/features/documents/document-type-management.js')); ?>"></script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
