<?php
declare(strict_types=1);

/**
 * HTML-escapes output to prevent XSS.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Returns an absolute app URL for a relative path.
 */
function app_url(string $path = ''): string
{
    $config = get_app_config();

    if ($path === '') {
        return $config['base_url'];
    }

    return $config['base_url'] . '/' . ltrim($path, '/');
}

/**
 * Redirects and stops script execution.
 */
function redirect_to(string $path): void
{
    header('Location: ' . app_url($path));
    exit;
}

/**
 * Creates or returns a CSRF token tied to a form name.
 */
function csrf_token(string $form): string
{
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }

    if (!isset($_SESSION['csrf_tokens'][$form])) {
        $_SESSION['csrf_tokens'][$form] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_tokens'][$form];
}

/**
 * Validates a CSRF token without rotating it so repeated AJAX submissions
 * from the same page remain valid while the session is still active.
 */
function csrf_validate(string $form, ?string $token): bool
{
    $stored = $_SESSION['csrf_tokens'][$form] ?? null;

    if (!$stored || !$token || !hash_equals($stored, $token)) {
        return false;
    }

    return true;
}

/**
 * Stores a one-time flash message.
 */
function flash_set(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

/**
 * Reads and consumes a flash message.
 */
function flash_get(string $key): ?string
{
    $message = $_SESSION['flash'][$key] ?? null;

    if ($message !== null) {
        unset($_SESSION['flash'][$key]);
    }

    return $message;
}

/**
 * Queues a toast message for the next page render.
 * Supported types: info, success, warning, error.
 */
function toast_set(string $message, string $type = 'info', int $durationMs = 2000): void
{
    if (!isset($_SESSION['app_toasts'])) {
        $_SESSION['app_toasts'] = [];
    }

    $normalizedType = in_array($type, ['info', 'success', 'warning', 'error'], true)
        ? $type
        : 'info';

    $_SESSION['app_toasts'][] = [
        'message' => trim($message),
        'type' => $normalizedType,
        'duration' => max(2000, $durationMs),
    ];
}

/**
 * Reads and consumes queued toast messages.
 */
function toast_get_all(): array
{
    $toasts = $_SESSION['app_toasts'] ?? [];
    unset($_SESSION['app_toasts']);

    return is_array($toasts) ? $toasts : [];
}

/**
 * Renders a hidden CSRF field for a named form.
 */
function csrf_field(string $form = 'default_form'): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token($form)) . '">';
}

/**
 * Validates the posted CSRF token for a named form.
 */
function csrf_require(string $form = 'default_form'): void
{
    $token = $_POST['csrf_token'] ?? null;

    if (!csrf_validate($form, $token)) {
        throw new RuntimeException('Invalid CSRF token.');
    }
}
