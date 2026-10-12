<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function um_get_users(): array
{
    $sql = 'SELECT u.user_id, u.employee_no, u.username, u.email, u.first_name, u.last_name, u.is_active,
                   u.role_id, u.department_id, u.created_at,
                   r.role_name, d.department_name
            FROM users u
            INNER JOIN roles r ON r.role_id = u.role_id
            INNER JOIN departments d ON d.department_id = u.department_id
            ORDER BY u.created_at DESC, u.user_id DESC';

    $stmt = db_execute($sql);
    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows;
}

function um_get_roles(): array
{
    $stmt = db_execute('SELECT role_id, role_name FROM roles WHERE is_active = 1 ORDER BY role_name ASC');
    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows;
}

function um_get_departments(): array
{
    $stmt = db_execute('SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name ASC');
    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows;
}

function um_create_user(array $data): int
{
    $sql = 'INSERT INTO users (
                employee_no, username, email, password_hash, first_name, last_name, role_id, department_id, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = db_execute(
        $sql,
        'ssssssiii',
        [
            $data['employee_no'],
            $data['username'],
            $data['email'],
            $data['password_hash'],
            $data['first_name'],
            $data['last_name'],
            (int) $data['role_id'],
            (int) $data['department_id'],
            (int) $data['is_active'],
        ]
    );

    $newId = (int) $stmt->insert_id;
    $stmt->close();

    return $newId;
}

function um_update_user(int $userId, array $data): void
{
    if (!empty($data['password_hash'])) {
        $sql = 'UPDATE users
                SET employee_no = ?, username = ?, email = ?, first_name = ?, last_name = ?, role_id = ?, department_id = ?, is_active = ?, password_hash = ?
                WHERE user_id = ?';
        $stmt = db_execute(
            $sql,
            'sssssiiisi',
            [
                $data['employee_no'],
                $data['username'],
                $data['email'],
                $data['first_name'],
                $data['last_name'],
                (int) $data['role_id'],
                (int) $data['department_id'],
                (int) $data['is_active'],
                $data['password_hash'],
                $userId,
            ]
        );
        $stmt->close();
        return;
    }

    $sql = 'UPDATE users
            SET employee_no = ?, username = ?, email = ?, first_name = ?, last_name = ?, role_id = ?, department_id = ?, is_active = ?
            WHERE user_id = ?';
    $stmt = db_execute(
        $sql,
        'sssssiiii',
        [
            $data['employee_no'],
            $data['username'],
            $data['email'],
            $data['first_name'],
            $data['last_name'],
            (int) $data['role_id'],
            (int) $data['department_id'],
            (int) $data['is_active'],
            $userId,
        ]
    );
    $stmt->close();
}

function um_delete_user(int $userId): void
{
    $stmt = db_execute('DELETE FROM users WHERE user_id = ?', 'i', [$userId]);
    $stmt->close();
}

function um_set_user_active(int $userId, int $isActive): void
{
    $stmt = db_execute('UPDATE users SET is_active = ? WHERE user_id = ?', 'ii', [$isActive, $userId]);
    $stmt->close();

    if ($isActive === 0) {
        $stmt = db_execute('DELETE FROM remember_tokens WHERE user_id = ?', 'i', [$userId]);
        $stmt->close();
    }
}
