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
        echo json_encode([
            'success' => false,
            'message' => 'Your session expired. Please refresh the page and try again.',
        ]);
        exit;
    }

    if (!user_record_message_presence($currentUserId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unable to update online status.']);
        exit;
    }

    echo json_encode(['success' => true, 'csrf_token' => csrf_token('message_send')], JSON_THROW_ON_ERROR);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'success' => true,
        'online_user_ids' => user_get_online_message_user_ids($currentUserId),
    ], JSON_THROW_ON_ERROR);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
