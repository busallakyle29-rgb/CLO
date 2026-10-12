<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Authentication Model
|--------------------------------------------------------------------------
| Handles database-related authentication operations only.
|
| IMPORTANT:
| Session/authentication functions such as:
|   auth_user()
|   auth_is_logged_in()
|   auth_is_role()
|   auth_login()
|   auth_logout()
|   auth_require_login()
|   auth_require_role()
|
| belong in:
| functions/auth.php
|--------------------------------------------------------------------------
*/

/**
 * Find a user by username or email.
 */
function auth_find_user_by_login(string $login): ?array
{
    $sql = 'SELECT
                u.user_id,
                u.employee_no,
                u.username,
                u.email,
                u.password_hash,
                u.first_name,
                u.last_name,
                u.profile_picture,
                u.theme_preference,
                u.is_active,
                us.security_answer_color_hash,
                us.security_answer_food_hash,
                us.security_answer_birthday_hash,
                us.failed_reset_attempts,
                us.reset_locked_until,
                us.reset_attempt_window_started,
                r.role_name,
                d.department_name
            FROM users u
            INNER JOIN roles r
                ON r.role_id = u.role_id
            INNER JOIN departments d
                ON d.department_id = u.department_id
            LEFT JOIN user_security us
                ON us.user_id = u.user_id
            WHERE u.username = ?
               OR u.email = ?
            LIMIT 1';

    $stmt = db_execute(
        $sql,
        'ss',
        [$login, $login]
    );

    $row = db_fetch_one_assoc($stmt);

    $stmt->close();

    return $row;
}

/**
 * Find a user by user ID.
 */
function auth_find_user_by_id(int $userId): ?array
{
    $sql = 'SELECT
                u.user_id,
                u.employee_no,
                u.username,
                u.email,
                u.password_hash,
                u.first_name,
                u.last_name,
                u.profile_picture,
                u.theme_preference,
                u.is_active,
                us.security_answer_color_hash,
                us.security_answer_food_hash,
                us.security_answer_birthday_hash,
                us.failed_reset_attempts,
                us.reset_locked_until,
                us.reset_attempt_window_started,
                r.role_name,
                d.department_name
            FROM users u
            INNER JOIN roles r
                ON r.role_id = u.role_id
            INNER JOIN departments d
                ON d.department_id = u.department_id
            LEFT JOIN user_security us
                ON us.user_id = u.user_id
            WHERE u.user_id = ?
            LIMIT 1';

    $stmt = db_execute(
        $sql,
        'i',
        [$userId]
    );

    $row = db_fetch_one_assoc($stmt);

    $stmt->close();

    return $row;
}

/**
 * Get all active departments.
 */
function auth_get_departments(): array
{
    $stmt = db_execute(
        'SELECT
            department_id,
            department_name
         FROM departments
         WHERE is_active = 1
         ORDER BY department_name'
    );

    $rows = db_fetch_all_assoc($stmt);

    $stmt->close();

    return $rows;
}

function auth_get_clo_department(): ?array
{
    $stmt = db_execute(
        'SELECT department_id, department_name
         FROM departments
         WHERE department_code = ?
           AND is_active = 1
         LIMIT 1',
        's',
        ['CLO']
    );

    $department = db_fetch_one_assoc($stmt);
    $stmt->close();

    return $department ?: null;
}

/**
 * Get roles available for staff registration.
 */
function auth_get_registerable_roles(): array
{
    $stmt = db_execute(
        'SELECT role_id, role_name
         FROM roles
         WHERE is_active = 1
           AND role_name <> ?
         ORDER BY role_name ASC',
        's',
        ['ADMIN']
    );

    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows;
}

/**
 * Normalize an employee number by keeping the CLO prefix and ensuring the suffix is numeric.
 */
function auth_normalize_employee_no(?string $value): string
{
    $raw = trim((string) $value);
    $raw = strtoupper($raw);

    if ($raw === '') {
        return '';
    }

    if (stripos($raw, 'CLO') === 0) {
        $raw = substr($raw, 3);
    }

    $raw = preg_replace('/[^0-9]/', '', $raw);

    if ($raw === '') {
        return '';
    }

    return 'CLO-' . $raw;
}

/**
 * Find existing users that conflict with registration identifiers.
 */
function auth_find_registration_conflicts(
    string $employeeNo,
    string $username,
    string $email
): array {
    $stmt = db_execute(
        'SELECT employee_no, username, email
         FROM users
         WHERE employee_no = ?
            OR username = ?
            OR email = ?',
        'sss',
        [$employeeNo, $username, $email]
    );

    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    $conflicts = [];

    foreach ($rows as $row) {
        if (strcasecmp((string) ($row['employee_no'] ?? ''), $employeeNo) === 0) {
            $conflicts['employee_no'] = true;
        }

        if (strcasecmp((string) ($row['username'] ?? ''), $username) === 0) {
            $conflicts['username'] = true;
        }

        if (strcasecmp((string) ($row['email'] ?? ''), $email) === 0) {
            $conflicts['email'] = true;
        }
    }

    return $conflicts;
}

/**
 * Get the list of pages available for manual permission assignment.
 */
function auth_get_page_access_list(): array
{
    return [
        'dashboard.php',
        'all_documents.php',
        'document_management.php',
        'document_routing.php',
        'document_tracking.php',
        'pending_documents.php',
        'reports.php',
        'archive_documents.php',
        'admin.php',
        'document_type_management.php',
        'department_management.php',
        'audit_trail.php',
        'system_settings.php',
    ];
}

/**
 * Get the explicit page access overrides currently saved in the DB.
 */
function auth_get_page_access_rows(): array
{
    try {
        $stmt = db_execute(
            'SELECT page_name, role_name, is_allowed
             FROM page_access
             ORDER BY page_name ASC, role_name ASC'
        );
    } catch (Throwable $e) {
        return [];
    }

    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows;
}

/**
 * Get the currently allowed roles for a given page.
 */
function auth_get_page_allowed_roles(string $pageName): array
{
    $rows = auth_get_page_access_rows();
    $normalizedPage = strtolower(trim($pageName));
    $allowed = [];

    foreach ($rows as $row) {
        $rowPage = strtolower(trim((string) ($row['page_name'] ?? '')));

        if ($rowPage !== $normalizedPage) {
            continue;
        }

        $roleName = strtoupper(trim((string) ($row['role_name'] ?? '')));
        $isAllowed = (int) ($row['is_allowed'] ?? 1);

        if ($roleName === '*' && $isAllowed === 0) {
            return [];
        }

        if ($roleName !== '*' && $isAllowed === 1) {
            $allowed[] = $roleName;
        }
    }

    return $allowed;
}

/**
 * Save page permission matrix selected from the admin UI.
 */
function auth_save_page_permissions(array $selectedPermissions): void
{
    $pageList = auth_get_page_access_list();
    $stmt = db_execute('DELETE FROM page_access');
    $stmt->close();

    foreach ($pageList as $pageName) {
        $selectedRoles = [];

        if (isset($selectedPermissions[$pageName]) && is_array($selectedPermissions[$pageName])) {
            foreach ($selectedPermissions[$pageName] as $roleName) {
                $roleName = trim((string) $roleName);
                if ($roleName !== '') {
                    $selectedRoles[] = strtoupper($roleName);
                }
            }
        }

        if ($selectedRoles === []) {
            $adminRole = 'ADMIN';
            $allowStmt = db_execute(
                'INSERT INTO page_access (page_name, role_name, is_allowed) VALUES (?, ?, ?)',
                'ssi',
                [$pageName, $adminRole, 1]
            );
            $allowStmt->close();
            continue;
        }

        foreach ($selectedRoles as $roleName) {
            $allowStmt = db_execute(
                'INSERT INTO page_access (page_name, role_name, is_allowed) VALUES (?, ?, ?)',
                'ssi',
                [$pageName, $roleName, 1]
            );
            $allowStmt->close();
        }
    }
}

/**
 * Create a general user account.
 */
function auth_create_user_account(array $data): int
{
    $sql = 'INSERT INTO users
            (
                employee_no,
                username,
                email,
                password_hash,
                first_name,
                last_name,
                role_id,
                department_id,
                is_active
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $conn = db_connection();
    $conn->begin_transaction();

    try {
        $stmt = db_execute(
            $sql,
            'ssssssiii',
            [
                (string) $data['employee_no'],
                (string) $data['username'],
                (string) $data['email'],
                (string) $data['password_hash'],
                (string) $data['first_name'],
                (string) $data['last_name'],
                (int) $data['role_id'],
                (int) $data['department_id'],
                (int) ($data['is_active'] ?? 0),
            ]
        );

        $userId = (int) $stmt->insert_id;
        $stmt->close();

        auth_insert_user_security($userId, $data);
        $conn->commit();

        return $userId;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }

}

function auth_insert_user_security(int $userId, array $data): void
{
    $stmt = db_execute(
        'INSERT INTO user_security
            (
                user_id,
                security_answer_color_hash,
                security_answer_food_hash,
                security_answer_birthday_hash
            )
         VALUES (?, ?, ?, ?)',
        'isss',
        [
            $userId,
            $data['security_answer_color_hash'] ?? null,
            $data['security_answer_food_hash'] ?? null,
            $data['security_answer_birthday_hash'] ?? null,
        ]
    );
    $stmt->close();
}

/**
 * Check if an ADMIN user already exists.
 */
function auth_has_admin_user(): bool
{
    $sql = 'SELECT COUNT(*) AS total
            FROM users u
            INNER JOIN roles r
                ON r.role_id = u.role_id
            WHERE r.role_name = ?';

    $stmt = db_execute(
        $sql,
        's',
        ['ADMIN']
    );

    $row = db_fetch_one_assoc($stmt);

    $stmt->close();

    return (int) ($row['total'] ?? 0) > 0;
}

/**
 * Get ADMIN role ID.
 *
 * Creates the ADMIN role if it does not exist.
 */
function auth_get_admin_role_id(): int
{
    $stmt = db_execute(
        'SELECT role_id
         FROM roles
         WHERE role_name = ?
         LIMIT 1',
        's',
        ['ADMIN']
    );

    $row = db_fetch_one_assoc($stmt);

    $stmt->close();

    if ($row) {
        return (int) $row['role_id'];
    }

    $stmt = db_execute(
        'INSERT INTO roles
            (
                role_name,
                description,
                is_active
            )
         VALUES (?, ?, 1)',
        'ss',
        [
            'ADMIN',
            'System administrator'
        ]
    );

    $newId = $stmt->insert_id;

    $stmt->close();

    return (int) $newId;
}

/**
 * Create an ADMIN user.
 */
function auth_create_admin_user(array $data): int
{
    $roleId = auth_get_admin_role_id();

    $sql = 'INSERT INTO users
            (
                employee_no,
                username,
                email,
                password_hash,
                first_name,
                last_name,
                role_id,
                department_id,
                is_active
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)';

    $conn = db_connection();
    $conn->begin_transaction();

    try {
        $stmt = db_execute(
            $sql,
            'ssssssii',
            [
                (string) $data['employee_no'],
                (string) $data['username'],
                (string) $data['email'],
                (string) $data['password_hash'],
                (string) $data['first_name'],
                (string) $data['last_name'],
                $roleId,
                (int) $data['department_id'],
            ]
        );

        $userId = (int) $stmt->insert_id;
        $stmt->close();

        auth_insert_user_security($userId, $data);
        $conn->commit();

        return $userId;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Update user's last login timestamp.
 */
function auth_update_last_login(int $userId): void
{
    $stmt = db_execute(
        'UPDATE users
         SET last_login_at = NOW()
         WHERE user_id = ?',
        'i',
        [$userId]
    );

    $stmt->close();
}

/**
 * Store remember-me token.
 */
function auth_store_remember_token(
    int $userId,
    string $selector,
    string $tokenHash,
    string $expiresAt
): void {
    $stmt = db_execute(
        'INSERT INTO remember_tokens
            (
                user_id,
                selector,
                token_hash,
                expires_at,
                created_at
            )
         VALUES (?, ?, ?, ?, NOW())',
        'isss',
        [
            $userId,
            $selector,
            $tokenHash,
            $expiresAt
        ]
    );

    $stmt->close();
}

/**
 * Find remember-me token.
 */
function auth_find_remember_token(string $selector): ?array
{
    $stmt = db_execute(
        'SELECT
            token_id,
            user_id,
            token_hash,
            expires_at
         FROM remember_tokens
         WHERE selector = ?
         LIMIT 1',
        's',
        [$selector]
    );

    $row = db_fetch_one_assoc($stmt);

    $stmt->close();

    return $row;
}

/**
 * Update remember token last-used timestamp.
 */
function auth_touch_remember_token(int $tokenId): void
{
    $stmt = db_execute(
        'UPDATE remember_tokens
         SET last_used_at = NOW()
         WHERE token_id = ?',
        'i',
        [$tokenId]
    );

    $stmt->close();
}

/**
 * Delete remember token by selector.
 */
function auth_delete_remember_token_by_selector(string $selector): void
{
    $stmt = db_execute(
        'DELETE FROM remember_tokens
         WHERE selector = ?',
        's',
        [$selector]
    );

    $stmt->close();
}

/**
 * Delete all remember tokens belonging to a user.
 */
function auth_delete_remember_tokens_by_user(int $userId): void
{
    $stmt = db_execute(
        'DELETE FROM remember_tokens
         WHERE user_id = ?',
        'i',
        [$userId]
    );

    $stmt->close();
}

/**
 * Store password reset token.
 */
function auth_store_password_reset(
    int $userId,
    string $selector,
    string $tokenHash,
    string $expiresAt
): void {
    $stmt = db_execute(
        'INSERT INTO password_resets
            (
                user_id,
                selector,
                token_hash,
                expires_at,
                created_at
            )
         VALUES (?, ?, ?, ?, NOW())',
        'isss',
        [
            $userId,
            $selector,
            $tokenHash,
            $expiresAt
        ]
    );

    $stmt->close();
}

/**
 * Find valid password reset token.
 */
function auth_find_valid_password_reset(string $selector): ?array
{
    $stmt = db_execute(
        'SELECT
            reset_id,
            user_id,
            token_hash,
            expires_at,
            used_at
         FROM password_resets
         WHERE selector = ?
         LIMIT 1',
        's',
        [$selector]
    );

    $row = db_fetch_one_assoc($stmt);

    $stmt->close();

    if (!$row) {
        return null;
    }

    if ($row['used_at'] !== null) {
        return null;
    }

    if (strtotime((string) $row['expires_at']) < time()) {
        return null;
    }

    return $row;
}

/**
 * Mark password reset token as used.
 */
function auth_mark_password_reset_used(int $resetId): void
{
    $stmt = db_execute(
        'UPDATE password_resets
         SET used_at = NOW()
         WHERE reset_id = ?',
        'i',
        [$resetId]
    );

    $stmt->close();
}

/**
 * Update user password.
 */
function auth_update_user_password(
    int $userId,
    string $passwordHash
): void {
    $stmt = db_execute(
        'UPDATE users
         SET password_hash = ?
         WHERE user_id = ?',
        'si',
        [
            $passwordHash,
            $userId
        ]
    );

    $stmt->close();
}

function auth_normalize_security_answer(string $questionKey, string $answer): string
{
    $value = trim($answer);

    if ($questionKey === 'birthday') {
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : '';
    }

    return strtolower($value);
}

function auth_security_questions(): array
{
    return [
        'color' => 'What is your favorite color?',
        'food' => 'What is your favorite food?',
        'birthday' => 'What is your full birthdate?',
    ];
}

function auth_find_user_for_reset(string $login, string $employeeNo): ?array
{
    $stmt = db_execute(
        'SELECT
            u.user_id,
            u.username,
            u.email,
            u.employee_no,
            u.role_id,
            u.is_active,
                us.security_answer_color_hash,
                us.security_answer_food_hash,
                us.security_answer_birthday_hash,
                us.failed_reset_attempts,
                us.reset_locked_until,
                us.reset_attempt_window_started,
            r.role_name
         FROM users u
         INNER JOIN roles r ON r.role_id = u.role_id
            LEFT JOIN user_security us ON us.user_id = u.user_id
         WHERE (u.username = ? OR u.email = ?)
           AND u.employee_no = ?
         LIMIT 1',
        'sss',
        [$login, $login, $employeeNo]
    );

    $row = db_fetch_one_assoc($stmt);
    $stmt->close();

    return $row;
}

function auth_is_reset_locked(array $user): bool
{
    $lockedUntil = trim((string) ($user['reset_locked_until'] ?? ''));

    return $lockedUntil !== '' && strtotime($lockedUntil) !== false && strtotime($lockedUntil) > time();
}

function auth_increment_reset_failure(int $userId): void
{
    $stmt = db_execute(
        'INSERT INTO user_security
            (user_id, failed_reset_attempts, reset_attempt_window_started, reset_locked_until)
         VALUES (?, 1, NOW(), NULL)
         ON DUPLICATE KEY UPDATE
             failed_reset_attempts = CASE
                WHEN reset_attempt_window_started IS NULL
                     OR reset_attempt_window_started < DATE_SUB(NOW(), INTERVAL 15 MINUTE)
                THEN 1
                ELSE failed_reset_attempts + 1
             END,
             reset_attempt_window_started = CASE
                WHEN reset_attempt_window_started IS NULL
                     OR reset_attempt_window_started < DATE_SUB(NOW(), INTERVAL 15 MINUTE)
                THEN NOW()
                ELSE reset_attempt_window_started
             END,
             reset_locked_until = CASE
                WHEN failed_reset_attempts >= 5
                THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                ELSE reset_locked_until
             END',
        'i',
        [$userId]
    );
    $stmt->close();
}

function auth_reset_reset_failures(int $userId): void
{
    $stmt = db_execute(
        'INSERT INTO user_security
            (user_id, failed_reset_attempts, reset_locked_until, reset_attempt_window_started)
         VALUES (?, 0, NULL, NULL)
         ON DUPLICATE KEY UPDATE
             failed_reset_attempts = 0,
             reset_locked_until = NULL,
             reset_attempt_window_started = NULL',
        'i',
        [$userId]
    );
    $stmt->close();
}

function auth_set_security_answer_hashes(int $userId, array $answers): void
{
    $stmt = db_execute(
        'INSERT INTO user_security
            (user_id, security_answer_color_hash, security_answer_food_hash, security_answer_birthday_hash)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
             security_answer_color_hash = VALUES(security_answer_color_hash),
             security_answer_food_hash = VALUES(security_answer_food_hash),
             security_answer_birthday_hash = VALUES(security_answer_birthday_hash)',
        'isss',
        [
            $userId,
            $answers['color'] ?? null,
            $answers['food'] ?? null,
            $answers['birthday'] ?? null,
        ]
    );
    $stmt->close();
}
