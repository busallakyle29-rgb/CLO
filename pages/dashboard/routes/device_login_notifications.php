<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';
auth_require_login();

header('Content-Type: application/json; charset=utf-8');

$currentUser = auth_user();
$userId = (int) ($currentUser['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['requests' => []]);
    exit;
}

$stmt = db_execute(
    'SELECT request_id, requested_at, u.first_name, u.last_name
     FROM login_device_requests ldr
     INNER JOIN users u ON u.user_id = ldr.user_id
     WHERE ldr.user_id = ?
       AND ldr.status = "PENDING"
       AND ldr.expires_at > NOW()
     ORDER BY ldr.requested_at DESC
     LIMIT 5',
    'i',
    [$userId]
);
$requests = db_fetch_all_assoc($stmt);
$stmt->close();

$isAdmin = auth_is_role('ADMIN');
$pendingApprovals = [];
if ($isAdmin) {
    $stmt = db_execute(
        'SELECT u.first_name, u.last_name, u.username, r.role_name
         FROM users u
         INNER JOIN roles r ON r.role_id = u.role_id
         WHERE u.is_active = 0 AND r.role_name <> "ADMIN"
         ORDER BY u.created_at DESC
         LIMIT 10'
    );
    $pendingApprovals = db_fetch_all_assoc($stmt);
    $stmt->close();
}

$stmt = db_execute(
    'SELECT al.audit_log_id, al.action, al.created_at,
            d.title AS document_title,
            u.first_name AS actor_first_name, u.last_name AS actor_last_name,
            nr.read_at AS notification_read_at
     FROM audit_logs al
     LEFT JOIN documents d ON d.document_id = al.entity_id
     LEFT JOIN document_routes dr
       ON dr.document_route_id = CAST(JSON_UNQUOTE(JSON_EXTRACT(al.new_values, "$.route_id")) AS UNSIGNED)
     LEFT JOIN document_assignment_receipts dar
       ON dar.document_id = d.document_id
      AND dar.user_id = d.current_assignee_user_id
      AND dar.accepted_at IS NULL
     LEFT JOIN notification_reads nr
       ON nr.audit_log_id = al.audit_log_id AND nr.user_id = ?
     LEFT JOIN users u ON u.user_id = al.user_id
     WHERE UPPER(al.entity_type) = "DOCUMENT"
       AND (
          (UPPER(al.action) = "ROUTED" AND dr.routed_to_user_id = ?)
          OR (UPPER(al.action) = "RECEIVED" AND dr.routed_by_user_id = ?)
          OR (UPPER(al.action) = "ACCESS_REQUEST" AND (d.current_assignee_user_id = ? OR ? = 1))
          OR (UPPER(al.action) = "ASSIGNED" AND dar.user_id = ?)
       )
       AND (
          UPPER(al.action) <> "ACCESS_REQUEST"
          OR NOT EXISTS (
             SELECT 1 FROM document_access_grants dag
             WHERE dag.document_id = al.entity_id AND dag.user_id = al.user_id
          )
       )
       AND (
          UPPER(al.action) <> "ACCESS_REQUEST"
          OR NOT EXISTS (
             SELECT 1 FROM audit_logs status_log
             WHERE status_log.entity_type = al.entity_type
               AND status_log.entity_id = al.entity_id
               AND status_log.action IN ("ACCESS_REQUEST_CANCELLED", "ACCESS_REVOKED", "ACCESS_REQUEST_REJECTED")
               AND JSON_UNQUOTE(JSON_EXTRACT(status_log.new_values, "$.request_id")) = CAST(al.audit_log_id AS CHAR)
          )
       )
     GROUP BY al.audit_log_id
     ORDER BY al.created_at DESC
     LIMIT 10',
    'iiiiii',
    [$userId, $userId, $userId, $userId, (int) $isAdmin, $userId]
);
$notifications = db_fetch_all_assoc($stmt);
$stmt->close();

$unreadNotifications = count(array_filter(
    $notifications,
    static fn (array $notification): bool => empty($notification['notification_read_at'])
));

echo json_encode([
    'requests' => $requests,
    'notifications' => $notifications,
    'pending_approvals' => $pendingApprovals,
    'is_admin' => $isAdmin,
    'unread_count' => $unreadNotifications + count($requests) + count($pendingApprovals),
]);
