<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (!csrf_validate('message_send', $_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Your session expired. Please refresh the page and try again.']);
    exit;
}

$currentUser = auth_user();
$currentUserId = (int) ($currentUser['user_id'] ?? 0);
$messageId = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
$messageId = $messageId !== false && $messageId !== null ? (int) $messageId : 0;
$otherUserId = filter_input(INPUT_POST, 'other_user_id', FILTER_VALIDATE_INT);
$otherUserId = $otherUserId !== false && $otherUserId !== null ? (int) $otherUserId : 0;

if ($currentUserId <= 0 || ($messageId <= 0 && $otherUserId <= 0)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid message request.']);
    exit;
}

header('Content-Type: application/json');
$readCount = 0;
if ($otherUserId > 0) {
    $readCount = user_mark_conversation_read($currentUserId, $otherUserId);
} else {
    $readCount = user_mark_message_read($currentUserId, $messageId) ? 1 : 0;
}

if ($readCount > 0 || ($otherUserId <= 0 && $messageId > 0)) {
    echo json_encode([
        'success' => true,
        'read_count' => $readCount,
        'csrf_token' => csrf_token('message_send'),
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unable to mark the message as read.']);
