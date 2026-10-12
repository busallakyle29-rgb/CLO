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
$recipientId = filter_input(INPUT_POST, 'recipient_id', FILTER_VALIDATE_INT);
$recipientId = $recipientId !== false && $recipientId !== null ? (int) $recipientId : 0;
$messageText = message_normalize_text((string) ($_POST['message'] ?? ''));

if ($currentUserId <= 0 || $recipientId <= 0 || $currentUserId === $recipientId || $messageText === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid message request.']);
    exit;
}

if (!user_send_message($currentUserId, $recipientId, $messageText)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unable to send the message.']);
    exit;
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => $messageText,
    'recipient_id' => $recipientId,
    'csrf_token' => csrf_token('message_send'),
], JSON_THROW_ON_ERROR);
