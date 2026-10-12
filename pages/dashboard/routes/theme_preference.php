<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (!csrf_validate('theme_preference', $_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit;
}

$currentUser = auth_user();
$currentUserId = (int) ($currentUser['user_id'] ?? 0);
$requestedTheme = normalize_theme_preference((string) ($_POST['theme'] ?? 'light'));

if ($currentUserId <= 0) {
    http_response_code(400);
    exit;
}

if (!user_set_theme_preference($currentUserId, $requestedTheme)) {
    http_response_code(500);
    exit;
}

$_SESSION['auth_user']['theme_preference'] = $requestedTheme;

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'theme' => $requestedTheme,
], JSON_THROW_ON_ERROR);
