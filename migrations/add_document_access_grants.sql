CREATE TABLE document_access_grants (
    grant_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    document_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    granted_by_user_id BIGINT UNSIGNED NOT NULL,
    granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (grant_id),
    UNIQUE KEY uq_document_access_grant (document_id, user_id),
    KEY idx_document_access_grants_user (user_id),
    CONSTRAINT fk_access_grants_document
        FOREIGN KEY (document_id) REFERENCES documents (document_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_access_grants_user
        FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_access_grants_granted_by
        FOREIGN KEY (granted_by_user_id) REFERENCES users (user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
