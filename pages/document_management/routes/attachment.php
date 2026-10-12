<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/functions/auth.php';
require_once dirname(__DIR__, 3) . '/functions/document_access.php';

function attachment_remove_directory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $entries = scandir($directory);
    if ($entries === false) {
        return;
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $path = $directory . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($path)) {
            attachment_remove_directory($path);
        } else {
            @unlink($path);
        }
    }

    @rmdir($directory);
}

function attachment_windows_quote(string $value): string
{
    return '"' . str_replace('"', '\"', $value) . '"';
}

if (function_exists('start_secure_session')) {
    start_secure_session();
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$attachmentId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$attachmentId) {
    http_response_code(400);
    exit('Invalid attachment.');
}

try {
    $conn = db_connection();
} catch (Throwable $exception) {
    http_response_code(500);
    exit('Database connection failed.');
}

$stmt = $conn->prepare('
    SELECT
        a.document_id,
        a.file_path,
        a.file_name,
        a.mime_type,
        a.is_public,
        d.confidentiality_level,
        d.created_by_user_id,
        d.current_assignee_user_id,
        d.origin_department_id,
        d.current_department_id
    FROM attachments a
    INNER JOIN documents d
        ON d.document_id = a.document_id
    WHERE a.attachment_id = ?
    LIMIT 1
');

if (!$stmt) {
    http_response_code(500);
    exit('Unable to prepare attachment query.');
}

$stmt->bind_param('i', $attachmentId);
$stmt->execute();
$attachment = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$attachment) {
    http_response_code(404);
    exit('Attachment not found.');
}

if (
    (int) ($attachment['is_public'] ?? 0) !== 1
    || strtoupper((string) ($attachment['confidentiality_level'] ?? '')) !== 'PUBLIC'
) {
    if (function_exists('auth_require_login')) {
        auth_require_login();
    }
}

$currentUser = auth_user();

if (!document_user_can_access_attachment($attachment, $currentUser, $conn)) {
    $conn->close();
    http_response_code(403);
    exit('Access denied. You are not authorized to view this attachment.');
}

$conn->close();

$relativePath = ltrim((string) ($attachment['file_path'] ?? ''), '/\\');
$filePath = dirname(__DIR__, 3) . '/' . $relativePath;
$uploadsRoot = realpath(dirname(__DIR__, 3) . '/uploads');
$realFilePath = realpath($filePath);

if (
    $uploadsRoot === false
    || $realFilePath === false
    || !is_file($realFilePath)
    || !str_starts_with($realFilePath, $uploadsRoot . DIRECTORY_SEPARATOR)
) {
    http_response_code(404);
    exit('Attachment file not found.');
}

$fileName = basename((string) ($attachment['file_name'] ?? 'download'));
$fileName = $fileName !== '' ? $fileName : 'download';
$asciiFileName = preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName) ?: 'download';
$mimeType = trim((string) ($attachment['mime_type'] ?? 'application/octet-stream'));
$extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
$isWordDocument = in_array(
    $extension,
    ['doc', 'docx'],
    true
);
$isPreviewRequest = isset($_GET['preview']) && (string) $_GET['preview'] === '1';
$temporaryPreviewDirectory = null;

if ($isPreviewRequest && $isWordDocument) {
    $temporaryPreviewDirectory = sys_get_temp_dir()
        . DIRECTORY_SEPARATOR
        . 'clo-preview-' . bin2hex(random_bytes(12));

    if (!mkdir($temporaryPreviewDirectory, 0700, true)) {
        http_response_code(500);
        exit('Unable to create document preview workspace.');
    }

    $profileDirectory = $temporaryPreviewDirectory . DIRECTORY_SEPARATOR . 'profile';
    if (!mkdir($profileDirectory, 0700, true)) {
        attachment_remove_directory($temporaryPreviewDirectory);
        http_response_code(500);
        exit('Unable to create document preview profile.');
    }

    $sofficePath = 'C:\Program Files\LibreOffice\program\soffice.com';
    $profileUri = 'file:///' . str_replace('\\', '/', $profileDirectory);
    $command = implode(' ', [
        attachment_windows_quote($sofficePath),
        '--headless',
        '--convert-to', 'pdf',
        '--outdir', attachment_windows_quote($temporaryPreviewDirectory),
        attachment_windows_quote('-env:UserInstallation=' . $profileUri),
        attachment_windows_quote($realFilePath),
    ]);
    $process = proc_open(
        $command,
        [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes
    );

    if (!is_resource($process)) {
        http_response_code(500);
        exit('Unable to start document preview conversion.');
    }

    $conversionOutput = stream_get_contents($pipes[1]);
    $conversionError = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    $previewPath = $temporaryPreviewDirectory
        . DIRECTORY_SEPARATOR
        . pathinfo($fileName, PATHINFO_FILENAME) . '.pdf';
    if (!is_file($previewPath)) {
        $generatedPreviews = glob(
            $temporaryPreviewDirectory . DIRECTORY_SEPARATOR . '*.pdf'
        );
        if (is_array($generatedPreviews) && count($generatedPreviews) === 1) {
            $previewPath = $generatedPreviews[0];
        }
    }

    if ($exitCode !== 0 || !is_file($previewPath)) {
        attachment_remove_directory($temporaryPreviewDirectory);

        error_log(
            'Document preview conversion failed (exit '
            . $exitCode
            . '): '
            . trim($conversionError)
            . ' stdout: '
            . trim($conversionOutput ?? '')
        );
        http_response_code(500);
        exit('Unable to convert this document for preview.');
    }

    $realFilePath = $previewPath;
    $fileName = pathinfo($fileName, PATHINFO_FILENAME) . '.pdf';
    $asciiFileName = preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName) ?: 'preview.pdf';
    $mimeType = 'application/pdf';
}

$disposition = isset($_GET['download']) ? 'attachment' : 'inline';

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . (string) filesize($realFilePath));
header(
    'Content-Disposition: ' . $disposition
    . '; filename="' . addcslashes($asciiFileName, '\\"') . '"'
    . "; filename*=UTF-8''" . rawurlencode($fileName)
);
header('X-Content-Type-Options: nosniff');

$fileHandle = fopen($realFilePath, 'rb');
if ($fileHandle === false) {
    http_response_code(500);
    exit('Unable to read attachment.');
}

while (!feof($fileHandle)) {
    echo fread($fileHandle, 8192);
}

fclose($fileHandle);

if ($temporaryPreviewDirectory !== null) {
    attachment_remove_directory($temporaryPreviewDirectory);
}

exit;
