<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function profile_get_user(int $userId): ?array
{
    $stmt = db_execute(
        "
        SELECT
            u.user_id,
            u.employee_no,
            u.username,
            u.email,
            u.first_name,
            u.last_name,
            u.profile_picture,
            u.role_id,
            u.department_id,
            u.is_active,
            u.password_hash,
            u.last_login_at,
            u.created_at,
            u.updated_at,
            r.role_name,
            d.department_name
        FROM users u
        LEFT JOIN roles r ON r.role_id = u.role_id
        LEFT JOIN departments d ON d.department_id = u.department_id
        WHERE u.user_id = ?
        LIMIT 1
        ",
        'i',
        [$userId]
    );

    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows[0] ?? null;
}

function profile_update_user(int $userId, array $data): void
{
    $stmt = db_execute(
        "
        UPDATE users
        SET
            first_name = ?,
            last_name = ?,
            email = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE user_id = ?
        ",
        'sssi',
        [
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $userId,
        ]
    );
    $stmt->close();
}

function profile_update_password(int $userId, string $passwordHash): void
{
    $stmt = db_execute(
        "
        UPDATE users
        SET
            password_hash = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE user_id = ?
        ",
        'si',
        [$passwordHash, $userId]
    );
    $stmt->close();
}

function profile_update_security_answers(int $userId, array $answers): void
{
    $stmt = db_execute(
        "
        INSERT INTO user_security
            (
                user_id,
                security_answer_color_hash,
                security_answer_food_hash,
                security_answer_birthday_hash
            )
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            security_answer_color_hash = VALUES(security_answer_color_hash),
            security_answer_food_hash = VALUES(security_answer_food_hash),
            security_answer_birthday_hash = VALUES(security_answer_birthday_hash)
        ",
        'isss',
        [
            $userId,
            password_hash($answers['color'], PASSWORD_DEFAULT),
            password_hash($answers['food'], PASSWORD_DEFAULT),
            password_hash($answers['birthday'], PASSWORD_DEFAULT),
        ]
    );
    $stmt->close();
}

function profile_update_picture(int $userId, string $profilePicture): void
{
    $stmt = db_execute(
        "
        UPDATE users
        SET
            profile_picture = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE user_id = ?
        ",
        'si',
        [$profilePicture, $userId]
    );
    $stmt->close();
}

function profile_remove_picture(int $userId): void
{
    $stmt = db_execute(
        "
        UPDATE users
        SET
            profile_picture = NULL,
            updated_at = CURRENT_TIMESTAMP
        WHERE user_id = ?
        ",
        'i',
        [$userId]
    );
    $stmt->close();
}

function profile_verify_password(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}
