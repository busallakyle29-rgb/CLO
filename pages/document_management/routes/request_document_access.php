<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/functions/security.php';
require_once dirname(__DIR__, 3) . '/functions/auth.php';
require_once dirname(__DIR__, 3) . '/functions/document_access.php';
require_once dirname(__DIR__, 3) . '/models/AuditTrailModel.php';

start_secure_session();
auth_require_login();

if (!auth_can_access_page('document_management.php')) {
    http_response_code(403);
    exit('Your role cannot request document access from this page.');
}

$currentUser = auth_user();
$currentUserId = (int) ($currentUser['user_id'] ?? 0);
$conn = db_connection();
$documentId = filter_input(INPUT_POST, 'document_id', FILTER_VALIDATE_INT);
$documentId = $documentId !== false && $documentId !== null ? (int) $documentId : 0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $documentId <= 0) {
    http_response_code(400);
    exit('Invalid access request.');
}

try {
    csrf_require('document_access_request');

    $stmt = db_execute(
        'SELECT document_id, confidentiality_level, created_by_user_id, current_assignee_user_id
         FROM documents
         WHERE document_id = ?
         LIMIT 1',
        'i',
        [$documentId]
    );
    $document = db_fetch_one_assoc($stmt);
    $stmt->close();

    if (!$document) {
        http_response_code(404);
        exit('Document not found.');
    }

    if (!document_is_restricted($document['confidentiality_level'] ?? null)) {
        header('Location: ' . app_url('document_management.php?message_type=success&message=' . urlencode('This document does not require restricted access.')));
        exit;
    }

    if (document_user_can_view_content($document, $currentUser, $conn)) {
        header('Location: ' . app_url('document_management.php?message_type=success&message=' . urlencode('You already have access to this document.')));
        exit;
    }

    $duplicateStmt = db_execute(
        'SELECT audit_log_id
         FROM audit_logs
         WHERE user_id = ?
           AND entity_type = ?
           AND entity_id = ?
           AND action = ?
                     AND NOT EXISTS (
                             SELECT 1
                             FROM audit_logs status_log
                             WHERE status_log.entity_type = audit_logs.entity_type
                                 AND status_log.entity_id = audit_logs.entity_id
                                 AND status_log.action IN (\'ACCESS_REQUEST_CANCELLED\', \'ACCESS_REVOKED\', \'ACCESS_REQUEST_REJECTED\')
                                 AND JSON_UNQUOTE(JSON_EXTRACT(status_log.new_values, \'$.request_id\')) = CAST(audit_logs.audit_log_id AS CHAR)
                     )
           AND created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY)
         LIMIT 1',
        'isis',
        [$currentUserId, 'DOCUMENT', $documentId, 'ACCESS_REQUEST']
    );
    $duplicateRequest = db_fetch_one_assoc($duplicateStmt);
    $duplicateStmt->close();

    if (!$duplicateRequest) {
        audit_log_create(
            $currentUserId,
            'DOCUMENT',
            $documentId,
            'ACCESS_REQUEST',
            null,
            [
                'confidentiality_level' => (string) ($document['confidentiality_level'] ?? ''),
                'requested_by_user_id' => $currentUserId,
            ]
        );
    }

    $message = $duplicateRequest
        ? 'You already requested access to this document recently.'
        : 'Access request sent for review.';

    header('Location: ' . app_url('document_management.php?message_type=success&message=' . urlencode($message)));
    exit;
} catch (Throwable $exception) {
    error_log('Document access request failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Unable to submit access request.');
}
