CREATE TABLE IF NOT EXISTS document_assignment_receipts (
    document_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    assigned_by_user_id BIGINT UNSIGNED NOT NULL,
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accepted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (document_id),
    KEY idx_assignment_receipts_user_pending (user_id, accepted_at),
    CONSTRAINT fk_assignment_receipts_document
        FOREIGN KEY (document_id) REFERENCES documents (document_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_assignment_receipts_user
        FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_assignment_receipts_assigner
        FOREIGN KEY (assigned_by_user_id) REFERENCES users (user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO document_assignment_receipts
    (document_id, user_id, assigned_by_user_id, assigned_at, accepted_at)
SELECT
    document_id,
    current_assignee_user_id,
    created_by_user_id,
    created_at,
    created_at
FROM documents
WHERE current_assignee_user_id IS NOT NULL
ON DUPLICATE KEY UPDATE
    user_id = VALUES(user_id);
