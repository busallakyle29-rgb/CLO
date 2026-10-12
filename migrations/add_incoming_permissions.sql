-- Add the Incoming role and its document workflow permissions.
INSERT INTO roles (role_name, description, is_active)
VALUES ('INCOMING', 'Incoming personnel for receiving, editing, and assigning documents', 1)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    is_active = 1;

INSERT INTO page_access (page_name, role_name, is_allowed)
VALUES
    ('document_management.php', 'INCOMING', 1),
    ('document_routing.php', 'INCOMING', 1)
ON DUPLICATE KEY UPDATE
    is_allowed = VALUES(is_allowed);
