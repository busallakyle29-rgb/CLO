CREATE TABLE IF NOT EXISTS user_message_presence (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_message_presence_user FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_user_message_presence_last_seen (last_seen_at)
) ENGINE=InnoDB;
