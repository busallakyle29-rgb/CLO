<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/DocumentTypeManagementModel.php';

function dt_validate_document_type_payload(array $post): array
{
    $errors = [];

    $typeCode = strtoupper(trim((string) ($post['type_code'] ?? '')));
    $typeName = trim((string) ($post['type_name'] ?? ''));
    $description = trim((string) ($post['description'] ?? ''));
    $retentionYears = (float) ($post['retention_years'] ?? 0);
    $retentionDays = (int) ($retentionYears * 365);
    $requiresAcknowledgment = (string) ($post['requires_acknowledgment'] ?? '0') === '1' ? 1 : 0;
    $isActive = isset($post['is_active']) && (string) $post['is_active'] === '0' ? 0 : 1;

    if ($typeCode === '' || $typeName === '') {
        $errors[] = 'Document type code and name are required.';
    }

    if (strlen($typeCode) > 20) {
        $errors[] = 'Document type code must not exceed 20 characters.';
    }

    if (strlen($typeName) > 100) {
        $errors[] = 'Document type name must not exceed 100 characters.';
    }

    if (strlen($description) > 255) {
        $errors[] = 'Description must not exceed 255 characters.';
    }

    if ($retentionYears < 0) {
        $errors[] = 'Retention years must be zero or greater.';
    }

    return [
        'errors' => $errors,
        'data' => [
            'type_code' => $typeCode,
            'type_name' => $typeName,
            'description' => $description,
            'retention_days' => $retentionDays,
            'requires_acknowledgment' => $requiresAcknowledgment,
            'is_active' => $isActive,
        ],
    ];
}

function controller_dt_add_document_type(array $post): array
{
    if (!csrf_validate('dt_add_document_type_form', $post['csrf_token'] ?? null)) {
        return ['errors' => ['Invalid request token. Please refresh and try again.']];
    }

    $validated = dt_validate_document_type_payload($post);

    if ($validated['errors'] !== []) {
        return ['errors' => $validated['errors']];
    }

    try {
        dt_create_document_type($validated['data']);
        return ['success' => 'Document type added successfully.'];
    } catch (Throwable $e) {
        return ['errors' => ['Unable to add document type. Code or name may already exist.']];
    }
}

function controller_dt_edit_document_type(array $post): array
{
    if (!csrf_validate('dt_edit_document_type_form', $post['csrf_token'] ?? null)) {
        return ['errors' => ['Invalid request token. Please refresh and try again.']];
    }

    $documentTypeId = (int) ($post['document_type_id'] ?? 0);

    if ($documentTypeId <= 0) {
        return ['errors' => ['Invalid document type selected.']];
    }

    $validated = dt_validate_document_type_payload($post);

    if ($validated['errors'] !== []) {
        return ['errors' => $validated['errors']];
    }

    try {
        dt_update_document_type($documentTypeId, $validated['data']);
        return ['success' => 'Document type updated successfully.'];
    } catch (Throwable $e) {
        return ['errors' => ['Unable to update document type. Code or name may already exist.']];
    }
}

function controller_dt_delete_document_type(array $post): array
{
    if (!csrf_validate('dt_delete_document_type_form', $post['csrf_token'] ?? null)) {
        return ['errors' => ['Invalid request token. Please refresh and try again.']];
    }

    $documentTypeId = (int) ($post['document_type_id'] ?? 0);

    if ($documentTypeId <= 0) {
        return ['errors' => ['Invalid document type selected.']];
    }

    try {
        dt_delete_document_type($documentTypeId);
        return ['success' => 'Document type deleted successfully.'];
    } catch (Throwable $e) {
        return ['errors' => ['Unable to delete document type. It may be used by existing documents.']];
    }
}
