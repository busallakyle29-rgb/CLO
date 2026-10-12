<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/UserManagementModel.php';
require_once __DIR__ . '/../models/AuthModel.php';

function um_validate_user_payload(array $post, bool $isEdit = false): array
{
    $errors = [];

    $employeeNo = auth_normalize_employee_no($post['employee_no'] ?? '');
    $username = trim((string) ($post['username'] ?? ''));
    $email = trim((string) ($post['email'] ?? ''));
    $firstName = trim((string) ($post['first_name'] ?? ''));
    $lastName = trim((string) ($post['last_name'] ?? ''));
    $roleId = (int) ($post['role_id'] ?? 0);
    $departmentId = (int) ($post['department_id'] ?? 0);
    $isActive = isset($post['is_active']) && (string) $post['is_active'] === '0' ? 0 : 1;

    $password = (string) ($post['password'] ?? '');
    $confirmPassword = (string) ($post['confirm_password'] ?? '');

    if ($employeeNo === '' || $username === '' || $email === '' || $firstName === '' || $lastName === '' || $roleId <= 0 || $departmentId <= 0) {
        $errors[] = 'All required fields must be completed.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    $passwordHash = '';

    if (!$isEdit || $password !== '' || $confirmPassword !== '') {
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Password and confirmation do not match.';
        }

        if ($errors === []) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        }
    }

    return [
        'errors' => $errors,
        'data' => [
            'employee_no' => $employeeNo,
            'username' => $username,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role_id' => $roleId,
            'department_id' => $departmentId,
            'is_active' => $isActive,
            'password_hash' => $passwordHash,
        ],
    ];
}

function controller_um_add_user(array $post): array
{
    if (!csrf_validate('um_add_user_form', $post['csrf_token'] ?? null)) {
        return ['errors' => ['Invalid request token. Please refresh and try again.']];
    }

    $validated = um_validate_user_payload($post, false);

    if ($validated['errors'] !== []) {
        return ['errors' => $validated['errors']];
    }

    try {
        um_create_user($validated['data']);
        return ['success' => 'User added successfully.'];
    } catch (Throwable $e) {
        return ['errors' => ['Unable to add user. Username, email, or employee no might already exist.']];
    }
}

function controller_um_edit_user(array $post, int $currentUserId): array
{
    if (!csrf_validate('um_edit_user_form', $post['csrf_token'] ?? null)) {
        return ['errors' => ['Invalid request token. Please refresh and try again.']];
    }

    $userId = (int) ($post['user_id'] ?? 0);

    if ($userId <= 0) {
        return ['errors' => ['Invalid user selected.']];
    }

    $validated = um_validate_user_payload($post, true);

    if ($validated['errors'] !== []) {
        return ['errors' => $validated['errors']];
    }

    if ($userId === $currentUserId) {
        $roleStmt = db_execute(
            'SELECT role_id FROM users WHERE user_id = ? LIMIT 1',
            'i',
            [$currentUserId]
        );
        $currentRole = db_fetch_one_assoc($roleStmt);
        $roleStmt->close();

        if (!$currentRole || (int) $validated['data']['role_id'] !== (int) $currentRole['role_id']) {
            return ['errors' => ['You cannot change your own administrator role.']];
        }
    }

    if ($userId === $currentUserId && (int) $validated['data']['is_active'] === 0) {
        return ['errors' => ['You cannot deactivate your own account.']];
    }

    try {
        um_update_user($userId, $validated['data']);
        return ['success' => 'User updated successfully.'];
    } catch (Throwable $e) {
        return ['errors' => ['Unable to update user. Username, email, or employee no might already exist.']];
    }
}

function controller_um_delete_user(array $post, int $currentUserId): array
{
    if (!csrf_validate('um_delete_user_form', $post['csrf_token'] ?? null)) {
        return ['errors' => ['Invalid request token. Please refresh and try again.']];
    }

    $userId = (int) ($post['user_id'] ?? 0);

    if ($userId <= 0) {
        return ['errors' => ['Invalid user selected.']];
    }

    if ($userId === $currentUserId) {
        return ['errors' => ['You cannot delete your own account.']];
    }

    try {
        um_delete_user($userId);
        return ['success' => 'User deleted successfully.'];
    } catch (Throwable $e) {
        return ['errors' => ['Unable to delete user. The account may be referenced by existing records.']];
    }
}

function controller_um_toggle_user_status(array $post, int $currentUserId): array
{
    if (!csrf_validate('um_toggle_status_form', $post['csrf_token'] ?? null)) {
        return ['errors' => ['Invalid request token. Please refresh and try again.']];
    }

    $userId = (int) ($post['user_id'] ?? 0);
    $isActive = (int) ($post['is_active'] ?? 0);

    if ($userId <= 0 || !in_array($isActive, [0, 1], true)) {
        return ['errors' => ['Invalid status request.']];
    }

    if ($userId === $currentUserId && $isActive === 0) {
        return ['errors' => ['You cannot deactivate your own account.']];
    }

    try {
        um_set_user_active($userId, $isActive);
        return ['success' => $isActive === 1 ? 'User approved successfully.' : 'User deactivated successfully.'];
    } catch (Throwable $e) {
        return ['errors' => ['Unable to update user status.']];
    }
}
