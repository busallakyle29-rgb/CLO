<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function routing_get_status_id(mysqli $conn, string $statusName): int
{
    $result = $conn->query('SHOW COLUMNS FROM document_status');
    if (!$result) {
        throw new RuntimeException('Unable to inspect document statuses.');
    }

    $primaryKey = null;
    while ($column = $result->fetch_assoc()) {
        if (($column['Field'] ?? '') === 'document_status_id') {
            $primaryKey = 'document_status_id';
            break;
        }
        if (($column['Field'] ?? '') === 'status_id') {
            $primaryKey = 'status_id';
        }
    }
    $result->free();

    if ($primaryKey === null) {
        throw new RuntimeException('Document status key is unavailable.');
    }

    $stmt = $conn->prepare(
        "SELECT `$primaryKey` FROM document_status WHERE LOWER(status_name) = LOWER(?) LIMIT 1"
    );
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare document status lookup.');
    }
    $stmt->bind_param('s', $statusName);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $statusId = (int) ($row[$primaryKey] ?? 0);
    if ($statusId <= 0) {
        throw new RuntimeException('Document status "' . $statusName . '" does not exist.');
    }
    return $statusId;
}

function routing_set_document_status(
    mysqli $conn,
    int $documentId,
    string $statusName
): void {
    $statusId = routing_get_status_id($conn, $statusName);
    $stmt = $conn->prepare(
        'UPDATE documents SET current_status_id = ?, updated_at = CURRENT_TIMESTAMP WHERE document_id = ?'
    );
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare document status update.');
    }
    $stmt->bind_param('ii', $statusId, $documentId);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Unable to update document status: ' . $error);
    }
    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| DOCUMENT ROUTING MODEL
|--------------------------------------------------------------------------
| Uses existing table:
|
| document_routes
|
| Columns:
| document_route_id
| document_id
| from_department_id
| to_department_id
| routed_by_user_id
| routed_to_user_id
| route_status
| remarks
| routed_at
| received_at
| due_at
|--------------------------------------------------------------------------
*/

/**
 * Get current user with department information.
 */
function routing_find_user(int $userId): ?array
{
    $sql = '
        SELECT
            u.user_id,
            u.username,
            u.first_name,
            u.last_name,
            u.department_id,
            d.department_name,
            u.is_active
        FROM users u
        LEFT JOIN departments d
            ON d.department_id = u.department_id
        WHERE u.user_id = ?
        LIMIT 1
    ';

    $stmt = db_execute(
        $sql,
        'i',
        [$userId]
    );

    $row = db_fetch_one_assoc($stmt);

    $stmt->close();

    return $row ?: null;
}

/**
 * Get all active departments.
 */
function routing_get_departments(): array
{
    $stmt = db_execute(
        '
        SELECT
            department_id,
            department_code,
            department_name
        FROM departments
        WHERE is_active = 1
        ORDER BY department_name ASC
        '
    );

    $rows = db_fetch_all_assoc($stmt);

    $stmt->close();

    return $rows;
}

function routing_get_department_id_by_code(string $departmentCode): int
{
    $stmt = db_execute(
        'SELECT department_id FROM departments WHERE department_code = ? AND is_active = 1 LIMIT 1',
        's',
        [$departmentCode]
    );

    $row = db_fetch_one_assoc($stmt);
    $stmt->close();

    return (int) ($row['department_id'] ?? 0);
}

/**
 * Get active users belonging to a department.
 */
function routing_get_users_by_department(
    int $departmentId
): array {
    $stmt = db_execute(
        '
        SELECT
            user_id,
            username,
            first_name,
            last_name,
            department_id
        FROM users
        WHERE
            is_active = 1
            AND department_id = ?
        ORDER BY
            first_name ASC,
            last_name ASC,
            username ASC
        ',
        'i',
        [$departmentId]
    );

    $rows = db_fetch_all_assoc($stmt);

    $stmt->close();

    return $rows;
}

/**
 * Get documents available for routing.
 */
function routing_get_documents(
    string $search = '',
    ?int $assignedUserId = null
): array {
    $assignmentSql = '';
    $assignmentTypes = '';
    $assignmentParams = [];

    if ($assignedUserId !== null && $assignedUserId > 0) {
        $assignmentSql = ' AND (
            d.current_assignee_user_id = ?
            OR EXISTS (
                SELECT 1
                FROM document_routes latest_route
                WHERE latest_route.document_id = d.document_id
                    AND latest_route.routed_to_user_id = ?
                    AND latest_route.document_route_id = (
                        SELECT route_order.document_route_id
                        FROM document_routes route_order
                        WHERE route_order.document_id = d.document_id
                        ORDER BY route_order.routed_at DESC, route_order.document_route_id DESC
                        LIMIT 1
                    )
            )
        )';
        $assignmentTypes = 'ii';
        $assignmentParams = [$assignedUserId, $assignedUserId];
    }

    $baseSql = '
        SELECT
            d.document_id,
            d.tracking_no,
            d.title,
            d.external_reference_no,
            d.description,
            d.current_department_id,
            d.current_assignee_user_id,
            dar.accepted_at AS assignment_accepted_at,
            d.date_received,
            (
                SELECT latest_route.received_at
                FROM document_routes latest_route
                WHERE latest_route.document_id = d.document_id
                  AND latest_route.received_at IS NOT NULL
                ORDER BY latest_route.routed_at DESC, latest_route.document_route_id DESC
                LIMIT 1
            ) AS latest_received_at,
            cd.department_name AS current_department_name,
            u.first_name AS assignee_first_name,
            u.last_name AS assignee_last_name,
            u.username AS assignee_username
        FROM documents d
        LEFT JOIN departments cd
            ON cd.department_id = d.current_department_id
        LEFT JOIN users u
            ON u.user_id = d.current_assignee_user_id
        LEFT JOIN document_assignment_receipts dar
            ON dar.document_id = d.document_id
           AND dar.user_id = d.current_assignee_user_id
    ';

    if ($search !== '') {
        $term = '%' . $search . '%';
        $stmt = db_execute(
            $baseSql . '
            WHERE (
                d.tracking_no LIKE ?
                OR d.title LIKE ?
                OR d.description LIKE ?
                OR d.external_reference_no LIKE ?
            )' . $assignmentSql . '
            ORDER BY d.document_id DESC',
            'ssss' . $assignmentTypes,
            array_merge([$term, $term, $term, $term], $assignmentParams)
        );
    } else {
        $stmt = db_execute(
            $baseSql . '
            WHERE 1 = 1' . $assignmentSql . '
            ORDER BY d.document_id DESC',
            $assignmentTypes,
            $assignmentParams
        );
    }

    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows;
}

/**
 * Find one document available for routing.
 */
function routing_find_document(int $documentId): ?array
{
    $stmt = db_execute(
        '
        SELECT
            d.document_id,
            d.tracking_no,
            d.title,
            d.external_reference_no,
            d.description,
            d.current_department_id,
            d.current_assignee_user_id,
            d.date_received,
            (
                SELECT latest_route.received_at
                FROM document_routes latest_route
                WHERE latest_route.document_id = d.document_id
                  AND latest_route.received_at IS NOT NULL
                ORDER BY latest_route.routed_at DESC, latest_route.document_route_id DESC
                LIMIT 1
            ) AS latest_received_at,
            cd.department_name AS current_department_name
        FROM documents d
        LEFT JOIN departments cd
            ON cd.department_id = d.current_department_id
        WHERE d.document_id = ?
        LIMIT 1
        ',
        'i',
        [$documentId]
    );

    $row = db_fetch_one_assoc($stmt);
    $stmt->close();

    return $row ?: null;
}

/**
 * Create a new routing record.
 *
 * New routes are created with PENDING status.
 */
function routing_create(
    int $documentId,
    int $fromDepartmentId,
    int $toDepartmentId,
    int $routedByUserId,
    ?int $routedToUserId,
    ?string $externalRecipientName,
    string $remarks,
    ?string $dueAt
): int {

    $routeStatus = 'PENDING';

    $dueAtSql = $dueAt === null
        ? 'NULL'
        : '?';

    $params = [
        $documentId,
        $fromDepartmentId,
        $toDepartmentId,
        $routedByUserId,
        $routedToUserId,
        $externalRecipientName,
        $routeStatus,
        $remarks,
    ];

    $types = 'iiiiisss';

    if ($dueAt !== null) {
        $params[] = $dueAt;
        $types .= 's';
    }

    $stmt = db_execute(
        '
        INSERT INTO document_routes
        (
            document_id,
            from_department_id,
            to_department_id,
            routed_by_user_id,
            routed_to_user_id,
            external_recipient_name,
            route_status,
            remarks,
            routed_at,
            received_at,
            due_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            NOW(),
            NULL,
            ' . $dueAtSql . '
        )
        ',
        $types,
        $params
    );

    $routeId = (int) $stmt->insert_id;

    $stmt->close();

    return $routeId;
}

/**
 * Update current document location.
 */
function routing_is_latest_recipient(
    int $documentId,
    int $userId
): bool {
    $stmt = db_execute(
        '
        SELECT routed_to_user_id
        FROM document_routes
        WHERE document_id = ?
        ORDER BY routed_at DESC, document_route_id DESC
        LIMIT 1
        ',
        'i',
        [$documentId]
    );

    $row = db_fetch_one_assoc($stmt);
    $stmt->close();

    return (int) ($row['routed_to_user_id'] ?? 0) === $userId;
}

/**
 * Update current document location.
 */
function routing_update_document_location(
    int $documentId,
    int $departmentId,
    ?int $assignedUserId
): void {

    $stmt = db_execute(
        '
        UPDATE documents
        SET
            current_department_id = ?,
            current_assignee_user_id = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE
            document_id = ?
        ',
        'iii',
        [
            $departmentId,
            $assignedUserId,
            $documentId
        ]
    );

    $stmt->close();
}

/**
 * Find one route.
 */
function routing_find_route(
    int $routeId
): ?array {

    $stmt = db_execute(
        '
        SELECT
            dr.*,

            d.tracking_no,
            d.title,
            d.external_reference_no,

            fd.department_name
                AS from_department_name,

            td.department_name
                AS to_department_name,

            ru.first_name
                AS routed_by_first_name,

            ru.last_name
                AS routed_by_last_name,

            ru.username
                AS routed_by_username,

            tu.first_name
                AS routed_to_first_name,

            tu.last_name
                AS routed_to_last_name,

            tu.username
                AS routed_to_username

        FROM document_routes dr

        INNER JOIN documents d
            ON d.document_id =
               dr.document_id

        LEFT JOIN departments fd
            ON fd.department_id =
               dr.from_department_id

        LEFT JOIN departments td
            ON td.department_id =
               dr.to_department_id

        LEFT JOIN users ru
            ON ru.user_id =
               dr.routed_by_user_id

        LEFT JOIN users tu
            ON tu.user_id =
               dr.routed_to_user_id

        WHERE
            dr.document_route_id = ?

        LIMIT 1
        ',
        'i',
        [$routeId]
    );

    $row = db_fetch_one_assoc($stmt);

    $stmt->close();

    return $row ?: null;
}

/**
 * Mark route as received.
 *
 * Only PENDING routes can be received.
 */
function routing_receive(
    int $routeId,
    ?string $remarks = null
): void {

    $receivedStatus = 'RECEIVED';
    $pendingStatus = 'PENDING';

    if (
        $remarks !== null
        && trim($remarks) !== ''
    ) {

        $cleanRemarks = trim($remarks);

        $stmt = db_execute(
            '
            UPDATE document_routes
            SET
                route_status = ?,
                received_at = NOW(),
                remarks = ?
            WHERE
                document_route_id = ?
                AND route_status = ?
            ',
            'ssis',
            [
                $receivedStatus,
                $cleanRemarks,
                $routeId,
                $pendingStatus
            ]
        );

    } else {

        $stmt = db_execute(
            '
            UPDATE document_routes
            SET
                route_status = ?,
                received_at = NOW()
            WHERE
                document_route_id = ?
                AND route_status = ?
            ',
            'sis',
            [
                $receivedStatus,
                $routeId,
                $pendingStatus
            ]
        );
    }

    $stmt->close();
}

/**
 * Get routing history for one document.
 *
 * Every routing record is preserved in document_routes.
 */
function routing_get_history(
    int $documentId
): array {

    $stmt = db_execute(
        '
        SELECT
            dr.*,

            fd.department_name
                AS from_department_name,

            td.department_name
                AS to_department_name,

            ru.first_name
                AS routed_by_first_name,

            ru.last_name
                AS routed_by_last_name,

            ru.username
                AS routed_by_username,

            tu.first_name
                AS routed_to_first_name,

            tu.last_name
                AS routed_to_last_name,

            tu.username
                AS routed_to_username

        FROM document_routes dr

        LEFT JOIN departments fd
            ON fd.department_id =
               dr.from_department_id

        LEFT JOIN departments td
            ON td.department_id =
               dr.to_department_id

        LEFT JOIN users ru
            ON ru.user_id =
               dr.routed_by_user_id

        LEFT JOIN users tu
            ON tu.user_id =
               dr.routed_to_user_id

        WHERE
            dr.document_id = ?

        ORDER BY
            dr.routed_at DESC,
            dr.document_route_id DESC
        ',
        'i',
        [$documentId]
    );

    $rows = db_fetch_all_assoc($stmt);

    $stmt->close();

    return $rows;
}

/**
 * Get pending routes for a department.
 *
 * Pending means the route has not yet been received.
 */
function routing_get_pending_for_department(
    int $departmentId,
    ?int $userId = null
): array {

    $pendingStatus = 'PENDING';
    $recipientSql = $userId !== null && $userId > 0
        ? ' AND (dr.routed_to_user_id IS NULL OR dr.routed_to_user_id = ?)'
        : '';
    $params = [$departmentId, $pendingStatus];
    $types = 'is';

    if ($recipientSql !== '') {
        $params[] = $userId;
        $types .= 'i';
    }

    $stmt = db_execute(
        '
        SELECT
            dr.*,

            d.external_reference_no,
            d.tracking_no,
            d.title,

            fd.department_name
                AS from_department_name,

            td.department_name
                AS to_department_name,

            ru.first_name
                AS routed_by_first_name,

            ru.last_name
                AS routed_by_last_name,

            ru.username
                AS routed_by_username,

            tu.first_name
                AS routed_to_first_name,

            tu.last_name
                AS routed_to_last_name,

            tu.username
                AS routed_to_username

        FROM document_routes dr

        INNER JOIN documents d
            ON d.document_id =
               dr.document_id

        LEFT JOIN departments fd
            ON fd.department_id =
               dr.from_department_id

        LEFT JOIN departments td
            ON td.department_id =
               dr.to_department_id

        LEFT JOIN users ru
            ON ru.user_id =
               dr.routed_by_user_id

        LEFT JOIN users tu
            ON tu.user_id =
               dr.routed_to_user_id

        WHERE
            dr.to_department_id = ?
            AND dr.route_status = ?
            ' . $recipientSql . '

        ORDER BY
            dr.routed_at DESC,
            dr.document_route_id DESC
        ',
        $types,
        $params
    );

    $rows = db_fetch_all_assoc($stmt);

    $stmt->close();

    return $rows;
}
