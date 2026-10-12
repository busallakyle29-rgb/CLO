<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This migration must be run from the command line.\n");
}

require_once __DIR__ . '/../config/database.php';

$conn = null;

try {
    $conn = db_connection();
    $conn->begin_transaction();

    $sourceStmt = db_execute(
        'SELECT
            user_id,
            security_answer_color_hash,
            security_answer_food_hash,
            security_answer_birthday_hash,
            failed_reset_attempts,
            reset_locked_until,
            reset_attempt_window_started
         FROM users'
    );

    $sourceRows = db_fetch_all_assoc($sourceStmt);
    $sourceStmt->close();

    foreach ($sourceRows as $row) {
        $securityStmt = db_execute(
        'INSERT INTO user_security
            (
                user_id,
                security_answer_color_hash,
                security_answer_food_hash,
                security_answer_birthday_hash,
                failed_reset_attempts,
                reset_locked_until,
                reset_attempt_window_started
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            security_answer_color_hash = VALUES(security_answer_color_hash),
            security_answer_food_hash = VALUES(security_answer_food_hash),
            security_answer_birthday_hash = VALUES(security_answer_birthday_hash),
            failed_reset_attempts = VALUES(failed_reset_attempts),
            reset_locked_until = VALUES(reset_locked_until),
            reset_attempt_window_started = VALUES(reset_attempt_window_started)',
            'isssiss',
            [
                (int) ($row['user_id'] ?? 0),
                $row['security_answer_color_hash'] ?? null,
                $row['security_answer_food_hash'] ?? null,
                $row['security_answer_birthday_hash'] ?? null,
                (int) ($row['failed_reset_attempts'] ?? 0),
                $row['reset_locked_until'] ?? null,
                $row['reset_attempt_window_started'] ?? null,
            ]
        );
        $securityStmt->close();
    }

    $userCountStmt = db_execute('SELECT COUNT(*) AS total FROM users');
    $securityCountStmt = db_execute('SELECT COUNT(*) AS total FROM user_security');
    $missingStmt = db_execute(
        'SELECT COUNT(*) AS total
         FROM users u
         LEFT JOIN user_security us ON us.user_id = u.user_id
         WHERE us.user_id IS NULL'
    );

    $userCount = (int) (db_fetch_one_assoc($userCountStmt)['total'] ?? 0);
    $securityCount = (int) (db_fetch_one_assoc($securityCountStmt)['total'] ?? 0);
    $missingCount = (int) (db_fetch_one_assoc($missingStmt)['total'] ?? 0);

    $userCountStmt->close();
    $securityCountStmt->close();
    $missingStmt->close();

    if ($userCount !== $securityCount || $missingCount !== 0) {
        throw new RuntimeException(
            'Verification failed: users=' . $userCount
            . ', user_security=' . $securityCount
            . ', missing=' . $missingCount
        );
    }

    $conn->commit();

    echo "Migration completed successfully.\n";
    echo "Users: {$userCount}\n";
    echo "User security rows: {$securityCount}\n";
    echo "Missing security rows: 0\n";
} catch (Throwable $exception) {
    if ($conn instanceof mysqli) {
        $conn->rollback();
    }

    fwrite(STDERR, "Migration failed: " . $exception->getMessage() . "\n");
    exit(1);
}
