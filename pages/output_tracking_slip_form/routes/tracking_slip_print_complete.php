<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/functions/security.php';
require_once dirname(__DIR__, 3) . '/functions/auth.php';
require_once dirname(__DIR__, 3) . '/models/AuditTrailModel.php';

start_secure_session();
auth_require_login();

header('Content-Type: application/json; charset=utf-8');

if (!auth_is_role('INCOMING') || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

$payload = json_decode((string) file_get_contents('php://input'), true);
$documentIds = is_array($payload['document_ids'] ?? null)
    ? $payload['document_ids']
    : [];
$token = trim((string) ($payload['token'] ?? ''));

if ($token === '' || $documentIds === []) {
    http_response_code(400);
    echo json_encode(['success' => false]);
    exit;
}

$loggedTokens = $_SESSION['tracking_slip_print_tokens'] ?? [];
if (!is_array($loggedTokens)) {
    $loggedTokens = [];
}

if (isset($loggedTokens[$token])) {
    echo json_encode(['success' => true]);
    exit;
}

$currentUser = auth_user();
$currentUserId = (int) ($currentUser['user_id'] ?? 0);
$loggedCount = 0;

foreach ($documentIds as $documentId) {
    $documentId = filter_var($documentId, FILTER_VALIDATE_INT);
    if ($documentId === false || $documentId <= 0) {
        continue;
    }

    $stmt = db_execute(
        'SELECT document_id, tracking_no, title FROM documents WHERE document_id = ? LIMIT 1',
        'i',
        [(int) $documentId]
    );
    $document = db_fetch_one_assoc($stmt);
    $stmt->close();

    if (!$document) {
        continue;
    }

    audit_log_create(
        $currentUserId,
        'DOCUMENT',
        (int) $document['document_id'],
        'PRINT_INITIATED',
        null,
        [
            'source' => 'TRACKING_SLIP',
            'tracking_no' => (string) ($document['tracking_no'] ?? ''),
            'document_title' => (string) ($document['title'] ?? ''),
        ]
    );
    $loggedCount++;
}

$loggedTokens[$token] = time();
$_SESSION['tracking_slip_print_tokens'] = array_slice($loggedTokens, -100, 100, true);

echo json_encode(['success' => $loggedCount > 0]);
