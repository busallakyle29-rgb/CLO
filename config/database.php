<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/**
 * Shared connection storage used by helper functions.
 */
function &db_connection_store(): ?mysqli
{
    static $connection = null;
    return $connection;
}

/**
 * Opens and reuses a single MySQLi connection.
 */
function db_connection(): mysqli
{
    $connection = &db_connection_store();

    if ($connection instanceof mysqli) {
        try {
            if ($connection->ping()) {
                return $connection;
            }
        } catch (mysqli_sql_exception $e) {
            db_disconnect();
        }
    }

    $config = get_database_config();

    try {
        $connection = mysqli_init();

        if (!$connection) {
            throw new RuntimeException('Unable to initialize database connection.');
        }

        $connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, $config['connect_timeout']);
        $connection->real_connect(
            $config['host'],
            $config['user'],
            $config['pass'],
            $config['name'],
            $config['port']
        );
        $connection->set_charset($config['charset']);

        ensure_auth_tables_exist($connection);

        return $connection;
    } catch (Throwable $e) {
        error_log('Database connection failed: ' . $e->getMessage());

        if (is_production()) {
            throw new RuntimeException('A database error occurred. Please try again later.');
        }

        throw new RuntimeException('Database connection failed: ' . $e->getMessage());
    }
}

/**
 * Closes the active MySQLi connection, if any.
 */
function db_disconnect(): void
{
    $connection = &db_connection_store();

    if ($connection instanceof mysqli) {
        $connection->close();
        $connection = null;
    }
}

/**
 * Ensures the auth-related tables exist before the app uses them.
 */
function ensure_auth_tables_exist(?mysqli $conn = null): void
{
    if ($conn === null) {
        return;
    }

    $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS remember_tokens (
  token_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  selector CHAR(24) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  last_used_at DATETIME NULL,
  CONSTRAINT fk_remember_tokens_user FOREIGN KEY (user_id)
    REFERENCES users (user_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT uq_remember_tokens_selector UNIQUE (selector),
  INDEX idx_remember_tokens_user (user_id),
  INDEX idx_remember_tokens_expires_at (expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
  reset_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  selector CHAR(24) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id)
    REFERENCES users (user_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT uq_password_resets_selector UNIQUE (selector),
  INDEX idx_password_resets_user (user_id),
  INDEX idx_password_resets_expires_at (expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
  audit_log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  entity_type VARCHAR(100) NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id)
    REFERENCES users (user_id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  INDEX idx_audit_logs_user (user_id),
  INDEX idx_audit_logs_entity (entity_type, entity_id),
  INDEX idx_audit_logs_action (action),
  INDEX idx_audit_logs_created_at (created_at)
) ENGINE=InnoDB;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL AFTER last_name,
    ADD COLUMN IF NOT EXISTS theme_preference ENUM('light', 'dark') NOT NULL DEFAULT 'light' AFTER department_id;

CREATE TABLE IF NOT EXISTS user_security (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    security_answer_color_hash VARCHAR(255) NULL,
    security_answer_food_hash VARCHAR(255) NULL,
    security_answer_birthday_hash VARCHAR(255) NULL,
    failed_reset_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    reset_locked_until DATETIME NULL,
    reset_attempt_window_started DATETIME NULL,
    CONSTRAINT fk_user_security_user FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS active_user_sessions (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    session_token_hash CHAR(64) NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_active_user_sessions_user FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_device_requests (
    request_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    request_token_hash CHAR(64) NOT NULL UNIQUE,
    remember_me TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('PENDING', 'APPROVED', 'DENIED', 'EXPIRED') NOT NULL DEFAULT 'PENDING',
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    decided_at DATETIME NULL,
    CONSTRAINT fk_login_device_requests_user FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_login_device_requests_user_status (user_id, status),
    INDEX idx_login_device_requests_expires_at (expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_messages (
    message_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id BIGINT UNSIGNED NOT NULL,
    recipient_id BIGINT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,
    CONSTRAINT fk_user_messages_sender FOREIGN KEY (sender_id)
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_user_messages_recipient FOREIGN KEY (recipient_id)
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_user_messages_sender (sender_id),
    INDEX idx_user_messages_recipient (recipient_id),
    INDEX idx_user_messages_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_message_deletions (
    message_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    deleted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id, user_id),
    INDEX idx_user_message_deletions_user (user_id, deleted_at),
    CONSTRAINT fk_user_message_deletions_message FOREIGN KEY (message_id)
        REFERENCES user_messages (message_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_user_message_deletions_user FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_message_presence (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_message_presence_user FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_user_message_presence_last_seen (last_seen_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notification_reads (
    user_id BIGINT UNSIGNED NOT NULL,
    audit_log_id BIGINT UNSIGNED NOT NULL,
    read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, audit_log_id),
    CONSTRAINT fk_notification_reads_user FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_notification_reads_audit_log FOREIGN KEY (audit_log_id)
        REFERENCES audit_logs (audit_log_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_notification_reads_audit_log (audit_log_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notification_dismissals (
    user_id BIGINT UNSIGNED NOT NULL,
    notification_key VARCHAR(120) NOT NULL,
    dismissed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, notification_key),
    CONSTRAINT fk_notification_dismissals_user FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS page_access (
    page_name VARCHAR(120) NOT NULL,
    role_name VARCHAR(50) NOT NULL,
    is_allowed TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (page_name, role_name),
    INDEX idx_page_access_page (page_name),
    INDEX idx_page_access_role (role_name)
) ENGINE=InnoDB;
SQL;

    if (!$conn->multi_query($sql)) {
        throw new RuntimeException('Failed to initialize authentication tables: ' . $conn->error);
    }

    do {
        $result = $conn->store_result();
        if ($result instanceof mysqli_result) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());

    if ($conn->errno !== 0) {
        throw new RuntimeException('Authentication table initialization failed: ' . $conn->error);
    }

    $sessionIdColumn = $conn->query(
        "SHOW COLUMNS FROM active_user_sessions LIKE 'session_id'"
    );
    if (!$sessionIdColumn || $sessionIdColumn->num_rows === 0) {
        $conn->query(
            'ALTER TABLE active_user_sessions
             DROP PRIMARY KEY,
             ADD COLUMN session_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
             ADD INDEX idx_active_user_sessions_user (user_id)'
        );
    }
    if ($sessionIdColumn instanceof mysqli_result) {
        $sessionIdColumn->free();
    }
}

/**
 * Prepares and executes a parameterized statement.
 */
function db_execute(string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $conn = db_connection();
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException('Failed to prepare SQL statement.');
    }

    db_bind_params($stmt, $types, $params);
    $stmt->execute();

    return $stmt;
}

/**
 * Returns all rows from a statement result as associative arrays.
 */
function db_fetch_all_assoc(mysqli_stmt $stmt): array
{
    $result = $stmt->get_result();

    if ($result === false) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Returns a single row from a statement result as an associative array.
 */
function db_fetch_one_assoc(mysqli_stmt $stmt): ?array
{
    $result = $stmt->get_result();

    if ($result === false) {
        return null;
    }

    $row = $result->fetch_assoc();

    return $row === null ? null : $row;
}

/**
 * Binds parameters to a prepared statement.
 */
function db_bind_params(mysqli_stmt $stmt, string $types, array $params): void
{
    if ($types === '') {
        if ($params !== []) {
            throw new InvalidArgumentException('Parameter types are required when params are provided.');
        }

        return;
    }

    if (strlen($types) !== count($params)) {
        throw new InvalidArgumentException('Parameter type count must match parameter count.');
    }

    $bindValues = [$types];

    foreach ($params as $index => $value) {
        $bindValues[] = &$params[$index];
    }

    $stmt->bind_param(...$bindValues);
}
