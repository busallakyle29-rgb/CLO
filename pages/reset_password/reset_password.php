<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$errors = [];
$successMessage = null;
$selector = trim((string) ($_GET['selector'] ?? $_POST['selector'] ?? ''));
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

if ($selector === '' || $token === '') {
    $errors[] = 'Missing reset token.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errors === []) {
    $result = controller_reset_password($_POST);

    if (!empty($result['errors'])) {
        $errors = $result['errors'];
    } else {
        $successMessage = (string) ($result['success'] ?? 'Password updated successfully.');
    }
}

require dirname(__DIR__, 2) . '/includes/header.php';
require dirname(__DIR__, 2) . '/views/auth/reset_password_form.php';
require dirname(__DIR__, 2) . '/includes/footer.php';
