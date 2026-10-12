document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-edit-document-type').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('edit_document_type_id').value =
                button.dataset.documentTypeId || '';
            document.getElementById('edit_type_code').value =
                button.dataset.typeCode || '';
            document.getElementById('edit_type_name').value =
                button.dataset.typeName || '';
            document.getElementById('edit_description').value =
                button.dataset.description || '';
            document.getElementById('edit_retention_years').value =
                button.dataset.retentionYears || '0';
            document.getElementById('edit_requires_acknowledgment').value =
                button.dataset.requiresAcknowledgment || '0';
            document.getElementById('edit_is_active').value =
                button.dataset.isActive || '1';
        });
    });

    document.querySelectorAll('.btn-delete-document-type').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('delete_document_type_id').value =
                button.dataset.documentTypeId || '';
            document.getElementById('delete_document_type_name').textContent =
                button.dataset.typeName || 'this document type';
        });
    });
});
