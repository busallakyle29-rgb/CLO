<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../functions/security.php';

$_SESSION['app_toasts'] = [];
toast_set('Welcome to the office update.', 'success', 4000);
$toasts = toast_get_all();

if (!is_array($toasts) || count($toasts) !== 1) {
    fwrite(STDERR, "Expected one queued toast\n");
    exit(1);
}

if (($toasts[0]['type'] ?? '') !== 'success') {
    fwrite(STDERR, "Expected success toast type\n");
    exit(1);
}

if (($toasts[0]['message'] ?? '') !== 'Welcome to the office update.') {
    fwrite(STDERR, "Expected matching toast message\n");
    exit(1);
}

echo "Toast helper validation passed\n";
