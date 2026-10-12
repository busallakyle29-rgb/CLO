<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function document_assignment_set_receipt(
    mysqli $conn,
    int $documentId,
    ?int $assigneeUserId,
    int $assignedByUserId
): void {
    if ($documentId <= 0) {
        throw new InvalidArgumentException('A valid document is required for assignment tracking.');
    }

    if ($assigneeUserId === null || $assigneeUserId <= 0) {
        $stmt = $conn->prepare('DELETE FROM document_assignment_receipts WHERE document_id = ?');
        $stmt->bind_param('i', $documentId);
        $stmt->execute();
        $stmt->close();
        return;
    }

    $stmt = $conn->prepare(
        'INSERT INTO document_assignment_receipts
            (document_id, user_id, assigned_by_user_id, assigned_at, accepted_at)
         VALUES (?, ?, ?, CURRENT_TIMESTAMP, NULL)
         ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            assigned_by_user_id = VALUES(assigned_by_user_id),
            assigned_at = CURRENT_TIMESTAMP,
            accepted_at = NULL'
    );
    $stmt->bind_param('iii', $documentId, $assigneeUserId, $assignedByUserId);
    $stmt->execute();
    $stmt->close();
}
