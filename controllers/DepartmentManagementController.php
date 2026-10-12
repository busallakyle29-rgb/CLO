<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/DepartmentManagementModel.php';

function dm_validate_department_payload(array $post): array
{
    $errors = [];

    $departmentCode = strtoupper(trim((string) ($post['department_code'] ?? '')));
    $departmentName = trim((string) ($post['department_name'] ?? ''));
    $description = trim((string) ($post['description'] ?? ''));
    $isActive = isset($post['is_active']) && (string) $post['is_active'] === '0' ? 0 : 1;

    if ($departmentCode === '' || $departmentName === '') {
        $errors[] = 'Department code and name are required.';
    }

    if (strlen($departmentCode) > 20) {
        $errors[] = 'Department code must not exceed 20 characters.';
    }

    if (strlen($departmentName) > 120) {
        $errors[] = 'Department name must not exceed 120 characters.';
    }

    if (strlen($description) > 255) {
        $errors[] = 'Description must not exceed 255 characters.';
    }

    return [
        'errors' => $errors,
        'data' => [
            'department_code' => $departmentCode,
            'department_name' => $departmentName,
            'description' => $description,
            'is_active' => $isActive,
        ],
    ];
}

function controller_dm_add_department(array $post): array
{
    if (!csrf_validate('dm_add_department_form', $post['csrf_token'] ?? null)) {
        return ['errors' => ['Invalid request token. Please refresh and try again.']];
    }

    $validated = dm_validate_department_payload($post);

    if ($validated['errors'] !== []) {
        return ['errors' => $validated['errors']];
    }

    try {
        dm_create_department($validated['data']);
        return ['success' => 'Department added successfully.'];
    } catch (Throwable $e) {
        return ['errors' => ['Unable to add department. Department code or name may already exist.']];
    }
}

function controller_dm_edit_department(array $post): array
{
    if (!csrf_validate('dm_edit_department_form', $post['csrf_token'] ?? null)) {
        return ['errors' => ['Invalid request token. Please refresh and try again.']];
    }

    $departmentId = (int) ($post['department_id'] ?? 0);

    if ($departmentId <= 0) {
        return ['errors' => ['Invalid department selected.']];
    }

    $validated = dm_validate_department_payload($post);

    if ($validated['errors'] !== []) {
        return ['errors' => $validated['errors']];
    }

    try {
        dm_update_department($departmentId, $validated['data']);
        return ['success' => 'Department updated successfully.'];
    } catch (Throwable $e) {
        return ['errors' => ['Unable to update department. Department code or name may already exist.']];
    }
}

function controller_dm_delete_department(array $post): array
{
    if (!csrf_validate('dm_delete_department_form', $post['csrf_token'] ?? null)) {
        return ['errors' => ['Invalid request token. Please refresh and try again.']];
    }

    $departmentId = (int) ($post['department_id'] ?? 0);

    if ($departmentId <= 0) {
        return ['errors' => ['Invalid department selected.']];
    }

    try {
        dm_delete_department($departmentId);
        return ['success' => 'Department deleted successfully.'];
    } catch (Throwable $e) {
        return ['errors' => ['Unable to delete department. It may be used by existing users or documents.']];
    }
}
