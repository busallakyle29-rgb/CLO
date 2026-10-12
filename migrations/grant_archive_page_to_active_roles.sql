INSERT INTO page_access (page_name, role_name, is_allowed)
SELECT 'archive_documents.php', role_name, 1
FROM roles
WHERE is_active = 1
ON DUPLICATE KEY UPDATE
    is_allowed = VALUES(is_allowed);
