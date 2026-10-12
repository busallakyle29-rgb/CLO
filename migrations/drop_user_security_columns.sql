-- Run only after the user_security migration and application verification succeed.
-- This script is intentionally separate from runtime schema initialization.

USE clo_document_tracking;

ALTER TABLE users
    DROP COLUMN security_answer_color_hash,
    DROP COLUMN security_answer_food_hash,
    DROP COLUMN security_answer_birthday_hash,
    DROP COLUMN failed_reset_attempts,
    DROP COLUMN reset_locked_until,
    DROP COLUMN reset_attempt_window_started;
