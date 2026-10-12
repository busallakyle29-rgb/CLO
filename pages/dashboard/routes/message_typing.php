<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

auth_require_login();

header('Content-Type: application/json');
header('Cache-Control: no-store, private');

$currentUser = auth_user();
$currentUserId = (int) ($currentUser['user_id'] ?? 0);

if ($currentUserId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate('message_send', $_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Your session expired. Please refresh the page and try again.']);
        exit;
    }

    $recipientId = filter_input(INPUT_POST, 'recipient_id', FILTER_VALIDATE_INT);
    $recipientId = $recipientId !== false && $recipientId !== null ? (int) $recipientId : 0;
    $isTyping = filter_input(
        INPUT_POST,
        'is_typing',
        FILTER_VALIDATE_BOOLEAN,
        FILTER_NULL_ON_FAILURE
    );

    if ($recipientId <= 0 || $recipientId === $currentUserId || $isTyping === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid conversation request.']);
        exit;
    }

    if (!user_set_message_typing($currentUserId, $recipientId, $isTyping)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unable to update typing status.']);
        exit;
    }

    echo json_encode(['success' => true, 'csrf_token' => csrf_token('message_send')], JSON_THROW_ON_ERROR);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $senderId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
    $senderId = $senderId !== false && $senderId !== null ? (int) $senderId : 0;

    if ($senderId <= 0 || $senderId === $currentUserId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid conversation request.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'is_typing' => user_is_message_typing($senderId, $currentUserId),
    ], JSON_THROW_ON_ERROR);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
