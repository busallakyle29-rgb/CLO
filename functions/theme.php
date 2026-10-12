<?php

declare(strict_types=1);

function normalize_theme_preference(?string $theme): string
{
    $normalized = strtolower(trim((string) $theme));

    return $normalized === 'dark' ? 'dark' : 'light';
}

function user_get_theme_preference(int $userId): string
{
    if ($userId <= 0) {
        return 'light';
    }

    $stmt = db_execute(
        'SELECT theme_preference
         FROM users
         WHERE user_id = ?
         LIMIT 1',
        'i',
        [$userId]
    );

    $row = db_fetch_one_assoc($stmt);
    $stmt->close();

    if ($row === null) {
        return 'light';
    }

    return normalize_theme_preference((string) ($row['theme_preference'] ?? 'light'));
}

function user_set_theme_preference(int $userId, string $theme): bool
{
    if ($userId <= 0) {
        return false;
    }

    $normalizedTheme = normalize_theme_preference($theme);

    $stmt = db_execute(
        'UPDATE users
         SET theme_preference = ?, updated_at = CURRENT_TIMESTAMP
         WHERE user_id = ?',
        'si',
        [$normalizedTheme, $userId]
    );

    $updated = $stmt->affected_rows >= 0;
    $stmt->close();

    return $updated;
}
