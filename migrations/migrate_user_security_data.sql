-- Copy-only migration. This does not alter or drop users columns.

USE clo_document_tracking;

INSERT INTO user_security (
    user_id,
    security_answer_color_hash,
    security_answer_food_hash,
    security_answer_birthday_hash,
    failed_reset_attempts,
    reset_locked_until,
    reset_attempt_window_started
)
SELECT
    user_id,
    security_answer_color_hash,
    security_answer_food_hash,
    security_answer_birthday_hash,
    failed_reset_attempts,
    reset_locked_until,
    reset_attempt_window_started
FROM users
ON DUPLICATE KEY UPDATE
    security_answer_color_hash = VALUES(security_answer_color_hash),
    security_answer_food_hash = VALUES(security_answer_food_hash),
    security_answer_birthday_hash = VALUES(security_answer_birthday_hash),
    failed_reset_attempts = VALUES(failed_reset_attempts),
    reset_locked_until = VALUES(reset_locked_until),
    reset_attempt_window_started = VALUES(reset_attempt_window_started);

SELECT
    (SELECT COUNT(*) FROM users) AS users_count,
    (SELECT COUNT(*) FROM user_security) AS user_security_count,
    (
        SELECT COUNT(*)
        FROM users u
        LEFT JOIN user_security us ON us.user_id = u.user_id
        WHERE us.user_id IS NULL
    ) AS missing_security_rows;
