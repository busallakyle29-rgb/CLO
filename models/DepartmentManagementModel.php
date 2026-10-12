<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function dm_get_departments(): array
{
    $sql = 'SELECT
                d.department_id,
                d.department_code,
                d.department_name,
                d.description,
                d.is_active,
                d.created_at,
                (
                    SELECT COUNT(*)
                    FROM users u
                    WHERE u.department_id = d.department_id
                ) AS staff_count,
                (
                    SELECT COUNT(*)
                    FROM documents doc
                    WHERE doc.origin_department_id = d.department_id
                       OR doc.current_department_id = d.department_id
                ) AS document_count,
                (
                    SELECT CONCAT(u.first_name, \' \', u.last_name)
                    FROM users u
                    INNER JOIN roles r ON r.role_id = u.role_id
                    WHERE u.department_id = d.department_id
                      AND u.is_active = 1
                    ORDER BY CASE WHEN UPPER(r.role_name) IN (\'ADMIN\', \'ATTORNEY\') THEN 0 ELSE 1 END, u.user_id ASC
                    LIMIT 1
                ) AS head_name,
                (
                    SELECT u.email
                    FROM users u
                    INNER JOIN roles r ON r.role_id = u.role_id
                    WHERE u.department_id = d.department_id
                      AND u.is_active = 1
                    ORDER BY CASE WHEN UPPER(r.role_name) IN (\'ADMIN\', \'ATTORNEY\') THEN 0 ELSE 1 END, u.user_id ASC
                    LIMIT 1
                ) AS head_email
            FROM departments d
            ORDER BY d.department_name ASC';

    $stmt = db_execute($sql);
    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows;
}

function dm_create_department(array $data): int
{
    $sql = 'INSERT INTO departments (department_code, department_name, description, is_active)
            VALUES (?, ?, ?, ?)';

    $stmt = db_execute(
        $sql,
        'sssi',
        [
            $data['department_code'],
            $data['department_name'],
            $data['description'],
            (int) $data['is_active'],
        ]
    );

    $id = (int) $stmt->insert_id;
    $stmt->close();

    return $id;
}

function dm_update_department(int $departmentId, array $data): void
{
    $sql = 'UPDATE departments
            SET department_code = ?, department_name = ?, description = ?, is_active = ?
            WHERE department_id = ?';

    $stmt = db_execute(
        $sql,
        'sssii',
        [
            $data['department_code'],
            $data['department_name'],
            $data['description'],
            (int) $data['is_active'],
            $departmentId,
        ]
    );
    $stmt->close();
}

function dm_delete_department(int $departmentId): void
{
    $stmt = db_execute('DELETE FROM departments WHERE department_id = ?', 'i', [$departmentId]);
    $stmt->close();
}
