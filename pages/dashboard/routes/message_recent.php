<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    header('Cache-Control: no-store, private');
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$currentUser = auth_user();
$currentUserId = (int) ($currentUser['user_id'] ?? 0);

if ($currentUserId <= 0) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

header('Content-Type: application/json');

$messages = user_get_recent_messages_for_user($currentUserId, 20);
$unreadCount = user_count_unread_messages($currentUserId);

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'unread_count' => $unreadCount,
], JSON_THROW_ON_ERROR);
