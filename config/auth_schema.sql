-- Authentication extension tables for CLO Document Tracking System
-- Run this after clo_document_tracking.sql

USE clo_document_tracking;

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
