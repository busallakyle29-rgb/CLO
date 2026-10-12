CREATE TABLE IF NOT EXISTS user_message_typing (
    sender_id BIGINT UNSIGNED NOT NULL,
    recipient_id BIGINT UNSIGNED NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (sender_id, recipient_id),
    INDEX idx_user_message_typing_recipient (recipient_id, updated_at),
    CONSTRAINT fk_user_message_typing_sender FOREIGN KEY (sender_id)
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_user_message_typing_recipient FOREIGN KEY (recipient_id)
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
