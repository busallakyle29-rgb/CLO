-- Add a dedicated role for sending documents to other departments.
INSERT INTO roles (role_name, description, is_active)
VALUES ('OUTGOING', 'Outgoing personnel authorized to send documents to other departments', 1)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    is_active = 1;

UPDATE page_access
SET is_allowed = 0
WHERE role_name = 'OUTGOING';

INSERT INTO page_access (page_name, role_name, is_allowed)
VALUES
    ('dashboard.php', 'OUTGOING', 1),
    ('all_documents.php', 'OUTGOING', 1),
    ('document_management.php', 'OUTGOING', 1),
    ('document_routing.php', 'OUTGOING', 1),
    ('document_tracking.php', 'OUTGOING', 1),
    ('pending_documents.php', 'OUTGOING', 1),
    ('pending_access_requests.php', 'OUTGOING', 1),
    ('reports.php', 'OUTGOING', 1),
    ('archive_documents.php', 'OUTGOING', 1)
ON DUPLICATE KEY UPDATE
    is_allowed = VALUES(is_allowed);

INSERT INTO page_access (page_name, role_name, is_allowed)
VALUES
    ('pending_access_requests.php', 'ADMIN', 1),
    ('pending_access_requests.php', 'CITY_ATTORNEY', 1),
    ('pending_access_requests.php', 'LEGAL_STAFF', 1),
    ('pending_access_requests.php', 'RECORDS_OFFICER', 1),
    ('pending_access_requests.php', 'DOCUMENT_VIEWER', 1),
    ('pending_access_requests.php', 'INCOMING', 1)
ON DUPLICATE KEY UPDATE
    is_allowed = VALUES(is_allowed);
