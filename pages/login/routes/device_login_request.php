<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$token = (string) ($_SESSION['_pending_device_login_token'] ?? '');
$request = auth_get_device_login_request($token);
if (!$request) {
    echo json_encode(['status' => 'NONE']);
    exit;
}

if ($request['status'] === 'APPROVED') {
    $user = auth_find_user_by_id((int) $request['user_id']);
    unset($_SESSION['_pending_device_login_token']);
    if ($user && auth_login($user, (bool) $request['remember_me'], true)) {
        echo json_encode(['status' => 'APPROVED']);
        exit;
    }
    echo json_encode(['status' => 'ERROR']);
    exit;
}

if ($request['status'] === 'DENIED' || $request['status'] === 'EXPIRED') {
    unset($_SESSION['_pending_device_login_token']);
}

echo json_encode(['status' => $request['status']]);
