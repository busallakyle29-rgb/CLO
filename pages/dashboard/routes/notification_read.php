<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (!csrf_validate('notification_read', $_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit;
}

$currentUser = auth_user();
$currentUserId = (int) ($currentUser['user_id'] ?? 0);
$notificationIds = $_POST['audit_log_ids'] ?? [];

if ($currentUserId <= 0 || !is_array($notificationIds)) {
    http_response_code(400);
    exit;
}

$notificationIds = array_values(array_unique(array_filter(
    array_map('intval', $notificationIds),
    static fn (int $auditLogId): bool => $auditLogId > 0
)));

if ($notificationIds === []) {
    http_response_code(204);
    exit;
}

foreach ($notificationIds as $auditLogId) {
    db_execute(
        'INSERT INTO notification_reads (user_id, audit_log_id, read_at)
         SELECT ?, al.audit_log_id, CURRENT_TIMESTAMP
         FROM audit_logs al
         INNER JOIN documents d
             ON d.document_id = al.entity_id
         LEFT JOIN document_routes dr
             ON dr.document_route_id = CAST(JSON_UNQUOTE(JSON_EXTRACT(al.new_values, "$.route_id")) AS UNSIGNED)
         WHERE al.audit_log_id = ?
           AND UPPER(al.entity_type) = "DOCUMENT"
           AND (
                (UPPER(al.action) = "ROUTED" AND dr.routed_to_user_id = ?)
                OR (UPPER(al.action) = "RECEIVED" AND dr.routed_by_user_id = ?)
                OR (
                    UPPER(al.action) = "ACCESS_REQUEST"
                    AND (
                        d.current_assignee_user_id = ?
                        OR d.created_by_user_id = ?
                        OR ? = 1
                    )
                )
                OR (
                    UPPER(al.action) = "ASSIGNED"
                    AND d.current_assignee_user_id = ?
                    AND EXISTS (
                        SELECT 1
                        FROM document_assignment_receipts dar
                        WHERE dar.document_id = d.document_id
                          AND dar.user_id = ?
                          AND dar.accepted_at IS NULL
                    )
                )
           )
         ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP',
        'iiiiiiiii',
        [
            $currentUserId,
            $auditLogId,
            $currentUserId,
            $currentUserId,
            $currentUserId,
            $currentUserId,
            (int) auth_is_role('ADMIN'),
            $currentUserId,
            $currentUserId,
        ]
    )->close();
}

http_response_code(204);
