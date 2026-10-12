<?php
declare(strict_types=1);

/**
 * Generate application URL.
 */
function app_url(string $path = ''): string
{
    $config = get_app_config();

    $baseUrl = rtrim(
        (string) ($config['base_url'] ?? ''),
        '/'
    );

    $path = ltrim($path, '/');

    if ($path === '') {
        return $baseUrl;
    }

    return $baseUrl . '/' . $path;
}

/**
 * Store a flash message.
 */
function flash_set(string $key, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['_flash'])) {
        $_SESSION['_flash'] = [];
    }

    $_SESSION['_flash'][$key] = $message;
}

/**
 * Get and remove a flash message.
 */
function flash_get(string $key): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $message = (string) $_SESSION['_flash'][$key];

    unset($_SESSION['_flash'][$key]);

    return $message;
}
