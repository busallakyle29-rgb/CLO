<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';
auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    exit('Invalid request.');
}

$csrfValid = csrf_validate('device_login_decision', $_POST['csrf_token'] ?? null);
if (!$csrfValid) {
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $expectedOrigin = rtrim(app_url(), '/');
    $sameOrigin = $origin !== ''
        ? rtrim($origin, '/') === $expectedOrigin
        : ($referer !== '' && str_starts_with($referer, $expectedOrigin . '/'));

    if (!$sameOrigin) {
        http_response_code(403);
        exit('Your approval session expired. Refresh the page and try again.');
    }
}

$ok = auth_decide_device_login_request(
    (int) ($_POST['request_id'] ?? 0),
    (int) (auth_user()['user_id'] ?? 0),
    (string) ($_POST['decision'] ?? '')
);

if (!$ok) {
    flash_set(
        'error',
        'This login request has expired or was already handled. Please request a new login approval.'
    );
    redirect_to('dashboard.php');
}

redirect_to('dashboard.php');
