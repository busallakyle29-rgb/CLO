<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit;
}

$currentUser = auth_user();
$currentUserId = (int) ($currentUser['user_id'] ?? 0);
$otherUserId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$otherUserId = $otherUserId !== false && $otherUserId !== null ? (int) $otherUserId : 0;

if ($currentUserId <= 0 || $otherUserId <= 0 || $currentUserId === $otherUserId) {
    http_response_code(400);
    header('Content-Type: application/json');
    header('Cache-Control: no-store, private');
    echo json_encode(['success' => false, 'messages' => []]);
    exit;
}

header('Content-Type: application/json');
$messages = user_get_conversation_messages($currentUserId, $otherUserId, 50);

echo json_encode([
    'success' => true,
    'messages' => $messages,
], JSON_THROW_ON_ERROR);
