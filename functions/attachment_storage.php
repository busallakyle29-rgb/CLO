<?php
declare(strict_types=1);

/**
 * Returns a collision-safe, filesystem-safe filename based on the upload name.
 */
function attachment_storage_filename(
    string $originalFilename,
    string $directory
): string {
    $filename = basename($originalFilename);
    $filename = preg_replace('/[\x00-\x1F<>:"\/\\\\|?*]/u', '_', $filename) ?? '';
    $filename = trim($filename, " .\t\n\r\0\x0B");

    if ($filename === '' || $filename === '.' || $filename === '..') {
        $filename = 'attachment';
    }

    $candidate = $filename;
    $suffix = 1;
    while (file_exists($directory . $candidate)) {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $candidate = $base . '-' . $suffix
            . ($extension !== '' ? '.' . $extension : '');
        $suffix++;
    }

    return $candidate;
}
