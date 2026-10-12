<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

auth_require_login();

header('Content-Type: application/json');
header('Cache-Control: no-store, private');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (!csrf_validate('message_send', $_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Your session expired. Please refresh the page and try again.']);
    exit;
}

$currentUser = auth_user();
$currentUserId = (int) ($currentUser['user_id'] ?? 0);
$action = (string) ($_POST['action'] ?? '');

if ($currentUserId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($action === 'delete_message') {
    $messageId = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
    $messageId = $messageId !== false && $messageId !== null ? (int) $messageId : 0;
    $scope = (string) ($_POST['scope'] ?? 'everyone');

    if ($messageId <= 0 || !in_array($scope, ['me', 'everyone'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid message request.']);
        exit;
    }

    $deleted = $scope === 'me'
        ? user_hide_conversation_message($currentUserId, $messageId)
        : user_delete_conversation_message($currentUserId, $messageId);

    if (!$deleted) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Message was not found or has already been deleted.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'action' => $action,
        'scope' => $scope,
        'unread_count' => user_count_unread_messages($currentUserId),
        'csrf_token' => csrf_token('message_send'),
    ], JSON_THROW_ON_ERROR);
    exit;
}

if ($action === 'edit_message') {
    $messageId = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
    $messageId = $messageId !== false && $messageId !== null ? (int) $messageId : 0;
    $editedMessage = user_edit_message(
        $currentUserId,
        $messageId,
        isset($_POST['message']) ? (string) $_POST['message'] : null
    );

    if ($editedMessage === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Message could not be edited. Only your own non-empty messages can be edited.',
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'action' => $action,
        'message' => $editedMessage,
        'unread_count' => user_count_unread_messages($currentUserId),
        'csrf_token' => csrf_token('message_send'),
    ], JSON_THROW_ON_ERROR);
    exit;
}

if ($action === 'clear_conversation') {
    $otherUserId = filter_input(INPUT_POST, 'other_user_id', FILTER_VALIDATE_INT);
    $otherUserId = $otherUserId !== false && $otherUserId !== null ? (int) $otherUserId : 0;
    $scope = (string) ($_POST['scope'] ?? 'everyone');

    if ($otherUserId <= 0 || $otherUserId === $currentUserId
        || !in_array($scope, ['me', 'everyone'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid conversation request.']);
        exit;
    }

    $deletedCount = $scope === 'me'
        ? user_clear_conversation_for_user($currentUserId, $otherUserId)
        : user_clear_conversation($currentUserId, $otherUserId);

    echo json_encode([
        'success' => true,
        'action' => $action,
        'scope' => $scope,
        'deleted_count' => $deletedCount,
        'unread_count' => user_count_unread_messages($currentUserId),
        'csrf_token' => csrf_token('message_send'),
    ], JSON_THROW_ON_ERROR);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid message action.']);
