<?php

declare(strict_types=1);

require_once __DIR__ . '/../functions/theme.php';

$dark = normalize_theme_preference('dark');
if ($dark !== 'dark') {
    fwrite(STDERR, "Expected dark but got {$dark}\n");
    exit(1);
}

$light = normalize_theme_preference('LIGHT');
if ($light !== 'light') {
    fwrite(STDERR, "Expected light but got {$light}\n");
    exit(1);
}

echo "Theme preference validation passed\n";
