<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function document_is_restricted(?string $confidentiality): bool
{
    return in_array(
        strtoupper(trim((string) $confidentiality)),
        ['CONFIDENTIAL', 'HIGHLY_CONFIDENTIAL'],
        true
    );
}

function document_user_can_view_content(
    array $document,
    ?array $user,
    ?mysqli $conn = null
): bool {
    if (!$user) {
        return false;
    }

    if (!document_is_restricted($document['confidentiality_level'] ?? null)) {
        return true;
    }

    $userId = (int) ($user['user_id'] ?? 0);
    $documentId = (int) ($document['document_id'] ?? 0);
    $role = strtoupper(trim((string) ($user['role_name'] ?? '')));

    if (
        $role === 'ADMIN'
        || $userId === (int) ($document['created_by_user_id'] ?? 0)
        || $userId === (int) ($document['current_assignee_user_id'] ?? 0)
    ) {
        return true;
    }

    if (!$conn || $documentId <= 0) {
        return false;
    }

    $stmt = $conn->prepare(
        'SELECT grant_id
         FROM document_access_grants
         WHERE document_id = ?
           AND user_id = ?
         LIMIT 1'
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ii', $documentId, $userId);
    $stmt->execute();
    $hasGrant = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $hasGrant;
}

function document_user_can_upload_file_version(
    array $document,
    ?array $user,
    mysqli $conn
): bool {
    $userId = (int) ($user['user_id'] ?? 0);
    $documentId = (int) ($document['document_id'] ?? 0);

    if ($userId <= 0 || $documentId <= 0) {
        return false;
    }

    if ($userId === (int) ($document['current_assignee_user_id'] ?? 0)) {
        return true;
    }

    $routeStmt = $conn->prepare(
        'SELECT routed_to_user_id
         FROM document_routes
         WHERE document_id = ?
         ORDER BY routed_at DESC, document_route_id DESC
         LIMIT 1'
    );

    if (!$routeStmt) {
        return false;
    }

    $routeStmt->bind_param('i', $documentId);
    $routeStmt->execute();
    $latestRoute = $routeStmt->get_result()->fetch_assoc();
    $routeStmt->close();

    if ((int) ($latestRoute['routed_to_user_id'] ?? 0) === $userId) {
        return true;
    }

    $stmt = $conn->prepare(
        'SELECT grant_id
         FROM document_access_grants
         WHERE document_id = ?
           AND user_id = ?
         LIMIT 1'
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ii', $documentId, $userId);
    $stmt->execute();
    $hasGrant = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $hasGrant;
}

function document_user_can_access_attachment(
    array $attachment,
    ?array $user,
    mysqli $conn
): bool {
    $isPublic = (int) ($attachment['is_public'] ?? 0) === 1
        && strtoupper(trim((string) ($attachment['confidentiality_level'] ?? ''))) === 'PUBLIC';

    if ($isPublic) {
        return true;
    }

    if (!$user) {
        return false;
    }

    if (document_is_restricted($attachment['confidentiality_level'] ?? null)) {
        return document_user_can_view_content($attachment, $user, $conn);
    }

    $userId = (int) ($user['user_id'] ?? 0);
    $departmentId = (int) ($user['department_id'] ?? 0);

    if ($userId <= 0) {
        return false;
    }

    if (
        strtoupper(trim((string) ($user['role_name'] ?? ''))) === 'ADMIN'
        || $userId === (int) ($attachment['created_by_user_id'] ?? 0)
        || $userId === (int) ($attachment['current_assignee_user_id'] ?? 0)
        || ($departmentId > 0 && in_array($departmentId, [
            (int) ($attachment['origin_department_id'] ?? 0),
            (int) ($attachment['current_department_id'] ?? 0),
        ], true))
    ) {
        return true;
    }

    $documentId = (int) ($attachment['document_id'] ?? 0);
    if ($documentId <= 0) {
        return false;
    }

    $stmt = $conn->prepare(
        'SELECT document_route_id
         FROM document_routes
         WHERE document_id = ?
           AND (routed_by_user_id = ? OR routed_to_user_id = ?
                OR (? > 0 AND (from_department_id = ? OR to_department_id = ?)))
         LIMIT 1'
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('iiiiii', $documentId, $userId, $userId, $departmentId, $departmentId, $departmentId);
    $stmt->execute();
    $hasRouteAccess = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($hasRouteAccess) {
        return true;
    }

    $stmt = $conn->prepare(
        'SELECT grant_id
         FROM document_access_grants
         WHERE document_id = ? AND user_id = ?
         LIMIT 1'
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ii', $documentId, $userId);
    $stmt->execute();
    $hasGrant = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $hasGrant;
}

function document_user_has_access_request(
    mysqli $conn,
    int $documentId,
    int $userId
): bool {
    if ($documentId <= 0 || $userId <= 0) {
        return false;
    }

    $stmt = $conn->prepare(
        'SELECT audit_log_id
         FROM audit_logs
         WHERE entity_type = ?
           AND entity_id = ?
           AND user_id = ?
           AND action = ?
                     AND NOT EXISTS (
                             SELECT 1
                             FROM audit_logs status_log
                             WHERE status_log.entity_type = audit_logs.entity_type
                                 AND status_log.entity_id = audit_logs.entity_id
                                 AND status_log.action IN (\'ACCESS_REQUEST_CANCELLED\', \'ACCESS_REVOKED\', \'ACCESS_REQUEST_REJECTED\')
                                 AND JSON_UNQUOTE(JSON_EXTRACT(status_log.new_values, \'$.request_id\')) = CAST(audit_logs.audit_log_id AS CHAR)
                     )
         LIMIT 1'
    );

    if (!$stmt) {
        return false;
    }

    $entityType = 'DOCUMENT';
    $action = 'ACCESS_REQUEST';
    $stmt->bind_param('siis', $entityType, $documentId, $userId, $action);
    $stmt->execute();
    $hasRequest = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $hasRequest;
}
