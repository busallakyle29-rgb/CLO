<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (!csrf_validate('notification_clear', $_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit;
}

$currentUser = auth_user();
$currentUserId = (int) ($currentUser['user_id'] ?? 0);

if ($currentUserId <= 0) {
    http_response_code(403);
    exit;
}

$keys = [];

foreach ((array) ($_POST['audit_log_ids'] ?? []) as $auditLogId) {
    $auditLogId = (int) $auditLogId;
    if ($auditLogId > 0) {
        $keys[] = 'audit:' . $auditLogId;
    }
}

if (auth_is_role('ADMIN')) {
    foreach ((array) ($_POST['approval_user_ids'] ?? []) as $userId) {
        $userId = (int) $userId;
        if ($userId > 0) {
            $keys[] = 'approval:' . $userId;
        }
    }
}

$keys = array_values(array_unique($keys));

foreach ($keys as $notificationKey) {
    db_execute(
        'INSERT INTO notification_dismissals (user_id, notification_key, dismissed_at)
         VALUES (?, ?, CURRENT_TIMESTAMP)
         ON DUPLICATE KEY UPDATE dismissed_at = CURRENT_TIMESTAMP',
        'is',
        [$currentUserId, $notificationKey]
    )->close();
}

$returnTo = basename((string) ($_POST['return_to'] ?? 'dashboard.php'));
$allowedPages = [
    'dashboard.php',
    'pending_access_requests.php',
    'all_documents.php',
    'document_management.php',
    'document_routing.php',
    'document_tracking.php',
    'pending_documents.php',
    'reports.php',
    'admin.php',
];

if (!in_array($returnTo, $allowedPages, true)) {
    $returnTo = 'dashboard.php';
}

redirect_to($returnTo);
