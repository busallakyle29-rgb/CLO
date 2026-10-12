<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/functions/security.php';
require_once dirname(__DIR__, 2) . '/functions/theme.php';
require_once dirname(__DIR__, 2) . '/functions/auth.php';
require_once dirname(__DIR__, 2) . '/models/AuditTrailModel.php';

start_secure_session();
auth_require_page('pending_access_requests.php');

$currentUser = auth_user();
$currentUserId = (int) ($currentUser['user_id'] ?? 0);
$currentRole = strtoupper(trim((string) ($currentUser['role_name'] ?? '')));
$message = '';
$messageType = 'success';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && (
        isset($_POST['accept_access_request'])
        || isset($_POST['cancel_access_request'])
        || isset($_POST['reject_access_request'])
        || isset($_POST['revoke_access'])
    )
) {
    try {
        csrf_require('pending_access_request');

        $requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
        $requestId = $requestId !== false && $requestId !== null ? (int) $requestId : 0;

        $requestStmt = db_execute(
            'SELECT
                al.audit_log_id,
                al.user_id AS requester_id,
                d.document_id,
                d.title,
                d.current_assignee_user_id,
                d.created_by_user_id
             FROM audit_logs al
             INNER JOIN documents d
                 ON d.document_id = al.entity_id
             WHERE al.audit_log_id = ?
               AND UPPER(al.entity_type) = \'DOCUMENT\'
               AND UPPER(al.action) = \'ACCESS_REQUEST\'
             LIMIT 1',
            'i',
            [$requestId]
        );
        $request = db_fetch_one_assoc($requestStmt);
        $requestStmt->close();

        $isRequester = $request && $currentUserId === (int) ($request['requester_id'] ?? 0);
        $isAdmin = auth_is_role('ADMIN');
        $canApprove = $request
            && $currentUserId === (int) ($request['current_assignee_user_id'] ?? 0);

        if (isset($_POST['reject_access_request'])) {
            if (!$request || !$canApprove) {
                throw new RuntimeException('You are not allowed to reject this access request.');
            }

            audit_log_create(
                $currentUserId,
                'DOCUMENT',
                (int) $request['document_id'],
                'ACCESS_REQUEST_REJECTED',
                null,
                ['request_id' => $requestId, 'rejected_by_user_id' => $currentUserId]
            );

            $message = 'Access request rejected.';
        } elseif (isset($_POST['cancel_access_request'])) {
            if (!$request || (!$isRequester && !$isAdmin)) {
                throw new RuntimeException('You are not allowed to cancel this access request.');
            }

            db_execute(
                'DELETE FROM document_access_grants WHERE document_id = ? AND user_id = ?',
                'ii',
                [(int) $request['document_id'], (int) $request['requester_id']]
            )->close();

            audit_log_create(
                $currentUserId,
                'DOCUMENT',
                (int) $request['document_id'],
                'ACCESS_REQUEST_CANCELLED',
                null,
                ['request_id' => $requestId, 'cancelled_by_user_id' => $currentUserId]
            );

            $message = 'Access request cancelled.';
        } elseif (isset($_POST['revoke_access'])) {
            if (!$request || !$canApprove) {
                throw new RuntimeException('You are not allowed to revoke this document access.');
            }

            db_execute(
                'DELETE FROM document_access_grants WHERE document_id = ? AND user_id = ?',
                'ii',
                [(int) $request['document_id'], (int) $request['requester_id']]
            )->close();

            audit_log_create(
                $currentUserId,
                'DOCUMENT',
                (int) $request['document_id'],
                'ACCESS_REVOKED',
                null,
                ['request_id' => $requestId, 'revoked_user_id' => (int) $request['requester_id']]
            );

            $message = 'Document access revoked.';
        } elseif (!$request || !$canApprove) {
            throw new RuntimeException('You are not allowed to approve this access request.');
        } else {
            db_execute(
            'INSERT INTO document_access_grants
                (document_id, user_id, granted_by_user_id)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                granted_by_user_id = VALUES(granted_by_user_id),
                granted_at = CURRENT_TIMESTAMP',
            'iii',
            [
                (int) $request['document_id'],
                (int) $request['requester_id'],
                $currentUserId,
            ]
            )->close();

        audit_log_create(
            $currentUserId,
            'DOCUMENT',
            (int) $request['document_id'],
            'ACCESS_GRANTED',
            null,
            [
                'granted_user_id' => (int) $request['requester_id'],
                'granted_by_user_id' => $currentUserId,
            ]
        );

            $message = 'Access granted for ' . (string) ($request['title'] ?? 'the document') . '.';
        }
    } catch (Throwable $exception) {
        $message = $exception->getMessage();
        $messageType = 'danger';
    }
}

if (auth_is_role('ADMIN')) {
    $whereSql = ' AND 1 = 1 ';
    $params = [];
    $types = '';
} else {
    $whereSql = '
        AND (
            d.current_assignee_user_id = ?
            OR al.user_id = ?
        )
    ';
    $params = [$currentUserId, $currentUserId];
    $types = 'ii';
}

$stmt = db_execute(
    'SELECT
        al.audit_log_id,
        al.user_id AS requester_id,
        al.entity_id AS document_id,
        al.created_at AS requested_at,
        d.tracking_no,
        d.title,
        d.confidentiality_level,
        d.current_assignee_user_id,
        CASE
            WHEN NOT EXISTS (
                SELECT 1
                FROM audit_logs newer_request
                WHERE newer_request.entity_type = al.entity_type
                  AND newer_request.entity_id = al.entity_id
                  AND newer_request.user_id = al.user_id
                  AND newer_request.action = \'ACCESS_REQUEST\'
                  AND newer_request.audit_log_id > al.audit_log_id
            ) THEN dag.granted_at
            ELSE NULL
        END AS granted_at,
        CASE
            WHEN NOT EXISTS (
                SELECT 1
                FROM audit_logs newer_request
                WHERE newer_request.entity_type = al.entity_type
                  AND newer_request.entity_id = al.entity_id
                  AND newer_request.user_id = al.user_id
                  AND newer_request.action = \'ACCESS_REQUEST\'
                  AND newer_request.audit_log_id > al.audit_log_id
            ) THEN 1
            ELSE 0
        END AS is_latest_request,
        (
            SELECT status_log.action
            FROM audit_logs status_log
            WHERE status_log.entity_type = al.entity_type
              AND status_log.entity_id = al.entity_id
              AND status_log.action IN (
                  \'ACCESS_REQUEST_REJECTED\',
                  \'ACCESS_REQUEST_CANCELLED\',
                  \'ACCESS_REVOKED\'
              )
              AND JSON_UNQUOTE(JSON_EXTRACT(status_log.new_values, \'$.request_id\')) = CAST(al.audit_log_id AS CHAR)
            ORDER BY status_log.created_at DESC, status_log.audit_log_id DESC
            LIMIT 1
        ) AS request_status,
        (
            SELECT status_log.created_at
            FROM audit_logs status_log
            WHERE status_log.entity_type = al.entity_type
              AND status_log.entity_id = al.entity_id
              AND status_log.action IN (
                  \'ACCESS_REQUEST_REJECTED\',
                  \'ACCESS_REQUEST_CANCELLED\',
                  \'ACCESS_REVOKED\'
              )
              AND JSON_UNQUOTE(JSON_EXTRACT(status_log.new_values, \'$.request_id\')) = CAST(al.audit_log_id AS CHAR)
            ORDER BY status_log.created_at DESC, status_log.audit_log_id DESC
            LIMIT 1
        ) AS status_at,
        CONCAT(COALESCE(requester.first_name, \'\'), \' \', COALESCE(requester.last_name, \'\')) AS requester_name,
        requester.username AS requester_username,
        CONCAT(COALESCE(assignee.first_name, \'\'), \' \', COALESCE(assignee.last_name, \'\')) AS assignee_name
     FROM audit_logs al
     INNER JOIN documents d
         ON d.document_id = al.entity_id
     LEFT JOIN users requester
         ON requester.user_id = al.user_id
     LEFT JOIN users assignee
         ON assignee.user_id = d.current_assignee_user_id
     LEFT JOIN document_access_grants dag
         ON dag.document_id = d.document_id
        AND dag.user_id = al.user_id
     WHERE UPPER(al.entity_type) = \'DOCUMENT\'
       AND UPPER(al.action) = \'ACCESS_REQUEST\'
       ' . $whereSql . '
     ORDER BY al.created_at DESC',
    $types,
    $params
);

$requests = db_fetch_all_assoc($stmt);
$stmt->close();

require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="page-header bg-white rounded-3 shadow-sm p-4 mb-4">
        <h3 class="mb-1">
            <i class="bi bi-shield-lock text-primary"></i>
            Access Requests
        </h3>
        <div class="text-muted">
            Review current and completed requests for confidential documents.
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= e($messageType) ?> alert-dismissible fade show">
            <?= e($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="page-card bg-white rounded-3 shadow-sm p-4">
        <?php if ($requests === []): ?>
            <div class="text-muted text-center py-5">
                <i class="bi bi-shield-check fs-1 d-block mb-2"></i>
                No access requests found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Confidentiality</th>
                            <th>Requested By</th>
                            <th>Assigned To</th>
                            <th>Requested</th>
                            <th>Status</th>
                            <th>Decision Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>
                                    <strong><?= e((string) ($request['title'] ?? 'Untitled document')) ?></strong>
                                    <small class="text-muted d-block"><?= e((string) ($request['tracking_no'] ?? '')) ?></small>
                                </td>
                                <td><?= e((string) ($request['confidentiality_level'] ?? '')) ?></td>
                                <td><?= e(trim((string) ($request['requester_name'] ?? '')) ?: (string) ($request['requester_username'] ?? 'Unknown user')) ?></td>
                                <td><?= e(trim((string) ($request['assignee_name'] ?? '')) ?: 'Unassigned') ?></td>
                                <td><?= e(date('M d, Y h:i A', strtotime((string) ($request['requested_at'] ?? 'now')))) ?></td>
                                <td>
                                    <?php if (($request['request_status'] ?? '') === 'ACCESS_REVOKED'): ?>
                                        <span class="badge text-bg-secondary">Revoked</span>
                                    <?php elseif (($request['request_status'] ?? '') === 'ACCESS_REQUEST_CANCELLED'): ?>
                                        <span class="badge text-bg-secondary">Cancelled</span>
                                    <?php elseif (($request['request_status'] ?? '') === 'ACCESS_REQUEST_REJECTED'): ?>
                                        <span class="badge text-bg-danger">Rejected</span>
                                    <?php elseif ((int) ($request['is_latest_request'] ?? 0) !== 1): ?>
                                        <span class="badge text-bg-secondary">Superseded</span>
                                    <?php elseif (!empty($request['granted_at'])): ?>
                                        <span class="badge text-bg-success">Accepted</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-warning">Awaiting review</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($request['status_at'])): ?>
                                        <?= e(date('M d, Y h:i A', strtotime((string) $request['status_at']))) ?>
                                    <?php elseif (!empty($request['granted_at'])): ?>
                                        <?= e(date('M d, Y h:i A', strtotime((string) $request['granted_at']))) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $canApproveRequest = $currentUserId === (int) ($request['current_assignee_user_id'] ?? 0);
                                        $isOwnRequest = $currentUserId === (int) ($request['requester_id'] ?? 0);
                                        $isLatestRequest = (int) ($request['is_latest_request'] ?? 0) === 1;
                                    ?>
                                    <?php if ($isLatestRequest && $canApproveRequest && empty($request['granted_at']) && empty($request['request_status'])): ?>
                                        <form method="POST">
                                            <?= csrf_field('pending_access_request') ?>
                                            <input type="hidden" name="request_id" value="<?= (int) ($request['audit_log_id'] ?? 0) ?>">
                                            <button type="submit" name="accept_access_request" class="btn btn-sm btn-success">
                                                <i class="bi bi-check-circle"></i>
                                                Accept
                                            </button>
                                            <button type="submit" name="reject_access_request" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-x-circle"></i>
                                                Reject
                                            </button>
                                        </form>
                                    <?php elseif ($isLatestRequest && $canApproveRequest && !empty($request['granted_at'])): ?>
                                        <form method="POST">
                                            <?= csrf_field('pending_access_request') ?>
                                            <input type="hidden" name="request_id" value="<?= (int) ($request['audit_log_id'] ?? 0) ?>">
                                            <button type="submit" name="revoke_access" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-shield-x"></i>
                                                Revoke Access
                                            </button>
                                        </form>
                                    <?php elseif ($isLatestRequest && empty($request['granted_at']) && empty($request['request_status']) && ($isOwnRequest || auth_is_role('ADMIN'))): ?>
                                        <form method="POST">
                                            <?= csrf_field('pending_access_request') ?>
                                            <input type="hidden" name="request_id" value="<?= (int) ($request['audit_log_id'] ?? 0) ?>">
                                            <button type="submit" name="cancel_access_request" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-x-circle"></i>
                                                Cancel Request
                                            </button>
                                        </form>
                                    <?php elseif (
                                        (int) ($request['is_latest_request'] ?? 0) !== 1
                                        && empty($request['request_status'])
                                    ): ?>
                                        <span class="text-muted small">Superseded</span>
                                    <?php elseif (($request['request_status'] ?? '') === 'ACCESS_REVOKED'): ?>
                                        <span class="text-muted small">Revoked</span>
                                    <?php elseif (($request['request_status'] ?? '') === 'ACCESS_REQUEST_CANCELLED'): ?>
                                        <span class="text-muted small">Cancelled</span>
                                    <?php elseif (($request['request_status'] ?? '') === 'ACCESS_REQUEST_REJECTED'): ?>
                                        <span class="text-muted small">Rejected</span>
                                    <?php elseif (!empty($request['request_status'])): ?>
                                        <span class="text-muted small">Reviewed</span>
                                    <?php else: ?>
                                        <span class="text-muted small">Awaiting reviewer</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
