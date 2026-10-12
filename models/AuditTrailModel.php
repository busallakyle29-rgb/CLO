<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| AUDIT TRAIL MODEL
|--------------------------------------------------------------------------
|
| Uses the application's existing MySQLi database helper.
|
| Database table:
| audit_logs
|
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

if (!function_exists('db_connection')) {
    require_once __DIR__ . '/../config/database.php';
}

/**
 * Return the application's MySQLi connection.
 */
function audit_db(): mysqli
{
    return db_connection();
}

/*
|--------------------------------------------------------------------------
| GET AUDIT LOG LIST
|--------------------------------------------------------------------------
|
| Supported filters:
| - search
| - action
| - entity_type
| - date_from
| - date_to
| - page
| - per_page
|
|--------------------------------------------------------------------------
*/

function audit_log_get_list(array $filters = []): array
{
    $conn = audit_db();

    $search = trim(
        (string) ($filters['search'] ?? '')
    );

    $action = trim(
        (string) ($filters['action'] ?? '')
    );

    $entityType = trim(
        (string) ($filters['entity_type'] ?? '')
    );

    $dateFrom = trim(
        (string) ($filters['date_from'] ?? '')
    );

    $dateTo = trim(
        (string) ($filters['date_to'] ?? '')
    );

    $userId = $filters['user_id'] ?? null;
    $userId = $userId !== null ? (int) $userId : null;

    $page = max(
        1,
        (int) ($filters['page'] ?? 1)
    );

    $perPage = max(
        1,
        min(
            100,
            (int) ($filters['per_page'] ?? 20)
        )
    );

    /*
    |--------------------------------------------------------------------------
    | WHERE
    |--------------------------------------------------------------------------
    */

    $where = [];
    $params = [];
    $types = '';

    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    if ($search !== '') {

        $where[] = "
            (
                CONCAT(
                    COALESCE(u.first_name, ''),
                    ' ',
                    COALESCE(u.last_name, '')
                ) LIKE ?
                OR COALESCE(u.email, '') LIKE ?
                OR al.action LIKE ?
                OR al.entity_type LIKE ?
                OR CAST(al.entity_id AS CHAR) LIKE ?
                OR COALESCE(al.ip_address, '') LIKE ?
                OR COALESCE(al.user_agent, '') LIKE ?
                OR COALESCE(al.old_values, '') LIKE ?
                OR COALESCE(al.new_values, '') LIKE ?
            )
        ";

        $searchValue = '%' . $search . '%';

        for ($i = 0; $i < 9; $i++) {
            $params[] = $searchValue;
            $types .= 's';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ACTION
    |--------------------------------------------------------------------------
    */

    if ($action !== '') {

        $where[] = 'al.action = ?';

        $params[] = $action;

        $types .= 's';
    }

    $excludedAction = trim(
        (string) ($filters['exclude_action'] ?? '')
    );

    if ($excludedAction !== '') {
        $where[] = 'al.action <> ?';
        $params[] = $excludedAction;
        $types .= 's';
    }

    /*
    |--------------------------------------------------------------------------
    | ENTITY TYPE
    |--------------------------------------------------------------------------
    */

    if ($entityType !== '') {

        $where[] = 'al.entity_type = ?';

        $params[] = $entityType;

        $types .= 's';
    }

    /*
    |--------------------------------------------------------------------------
    | DATE FROM
    |--------------------------------------------------------------------------
    */

    if ($dateFrom !== '') {

        $where[] = 'al.created_at >= ?';

        $params[] = $dateFrom . ' 00:00:00';

        $types .= 's';
    }

    /*
    |--------------------------------------------------------------------------
    | DATE TO
    |--------------------------------------------------------------------------
    */

    if ($dateTo !== '') {

        $where[] = 'al.created_at <= ?';

        $params[] = $dateTo . ' 23:59:59';

        $types .= 's';
    }

    if ($userId !== null && $userId > 0) {
        $where[] = 'al.user_id = ?';
        $params[] = $userId;
        $types .= 'i';
    }

    /*
    |--------------------------------------------------------------------------
    | WHERE SQL
    |--------------------------------------------------------------------------
    */

    $whereSql = '';

    if ($where !== []) {

        $whereSql =
            ' WHERE '
            . implode(
                ' AND ',
                $where
            );
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL COUNT
    |--------------------------------------------------------------------------
    */

    $countSql = "
        SELECT COUNT(*) AS total
        FROM audit_logs al
        LEFT JOIN users u
            ON u.user_id = al.user_id
        {$whereSql}
    ";

    $countStmt = $conn->prepare($countSql);

    if (!$countStmt) {
        throw new RuntimeException(
            'Failed to prepare audit log count query: '
            . $conn->error
        );
    }

    audit_bind_params(
        $countStmt,
        $types,
        $params
    );

    $countStmt->execute();

    $countResult = $countStmt->get_result();

    $countRow =
        $countResult
            ? $countResult->fetch_assoc()
            : null;

    $total = (int) (
        $countRow['total']
        ?? 0
    );

    $countStmt->close();

    /*
    |--------------------------------------------------------------------------
    | PAGINATION
    |--------------------------------------------------------------------------
    */

    $totalPages = max(
        1,
        (int) ceil(
            $total / $perPage
        )
    );

    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $offset =
        ($page - 1)
        * $perPage;

    /*
    |--------------------------------------------------------------------------
    | GET ROWS
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT

            al.audit_log_id,

            al.user_id,

            al.entity_type,

            al.entity_id,

            al.action,

            al.old_values,

            al.new_values,

            al.ip_address,

            al.user_agent,

            al.created_at,

            u.first_name,

            u.last_name,

            u.email,

            d.title AS entity_title

        FROM audit_logs al

        LEFT JOIN users u
            ON u.user_id = al.user_id

        LEFT JOIN documents d
            ON d.document_id = al.entity_id
           AND LOWER(COALESCE(al.entity_type, '')) = 'document'

        {$whereSql}

        ORDER BY
            al.created_at DESC,
            al.audit_log_id DESC

        LIMIT ?
        OFFSET ?
    ";

    /*
    |--------------------------------------------------------------------------
    | PAGINATION PARAMETERS
    |--------------------------------------------------------------------------
    */

    $rowParams = $params;
    $rowTypes = $types . 'ii';

    $rowParams[] = $perPage;
    $rowParams[] = $offset;

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException(
            'Failed to prepare audit log query: '
            . $conn->error
        );
    }

    audit_bind_params(
        $stmt,
        $rowTypes,
        $rowParams
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $rows = [];

    if ($result) {

        $rows =
            $result->fetch_all(
                MYSQLI_ASSOC
            );
    }

    $stmt->close();

    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
    ];
}

/*
|--------------------------------------------------------------------------
| GET AVAILABLE ACTIONS
|--------------------------------------------------------------------------
*/

function audit_log_get_actions(): array
{
    $conn = audit_db();

    $sql = "
        SELECT DISTINCT action
        FROM audit_logs
        WHERE action IS NOT NULL
          AND action <> ''
        ORDER BY action ASC
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException(
            'Failed to prepare audit actions query: '
            . $conn->error
        );
    }

    $stmt->execute();

    $result = $stmt->get_result();

    $actions = [];

    if ($result) {

        while ($row = $result->fetch_assoc()) {

            $value =
                trim(
                    (string) (
                        $row['action']
                        ?? ''
                    )
                );

            if ($value !== '') {
                $actions[] = $value;
            }
        }
    }

    $stmt->close();

    return $actions;
}

/*
|--------------------------------------------------------------------------
| GET ENTITY TYPES
|--------------------------------------------------------------------------
*/

function audit_log_get_entity_types(): array
{
    $conn = audit_db();

    $sql = "
        SELECT DISTINCT entity_type
        FROM audit_logs
        WHERE entity_type IS NOT NULL
          AND entity_type <> ''
        ORDER BY entity_type ASC
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException(
            'Failed to prepare audit entity type query: '
            . $conn->error
        );
    }

    $stmt->execute();

    $result = $stmt->get_result();

    $types = [];

    if ($result) {

        while ($row = $result->fetch_assoc()) {

            $value =
                trim(
                    (string) (
                        $row['entity_type']
                        ?? ''
                    )
                );

            if ($value !== '') {
                $types[] = $value;
            }
        }
    }

    $stmt->close();

    return $types;
}

/*
|--------------------------------------------------------------------------
| CREATE AUDIT LOG
|--------------------------------------------------------------------------
|
| Use this whenever an action needs to be recorded.
|
| Example:
|
| audit_log_create(
|     5,
|     'DOCUMENT',
|     10,
|     'UPDATE',
|     ['status' => 'PENDING'],
|     ['status' => 'APPROVED']
| );
|
|--------------------------------------------------------------------------
*/

function audit_log_create(
    ?int $userId,
    string $entityType,
    ?int $entityId,
    string $action,
    ?array $oldValues = null,
    ?array $newValues = null
): int {

    $conn = audit_db();

    $entityType = trim($entityType);
    $action = trim($action);

    $oldValues = audit_resolve_snapshot_values($oldValues);
    $newValues = audit_resolve_snapshot_values($newValues);

    /*
    |--------------------------------------------------------------------------
    | IP ADDRESS
    |--------------------------------------------------------------------------
    */

    $ipAddress =
        $_SERVER['REMOTE_ADDR']
        ?? null;

    if ($ipAddress !== null) {

        $ipAddress =
            substr(
                (string) $ipAddress,
                0,
                45
            );
    }

    /*
    |--------------------------------------------------------------------------
    | USER AGENT
    |--------------------------------------------------------------------------
    */

    $userAgent =
        $_SERVER['HTTP_USER_AGENT']
        ?? null;

    if ($userAgent !== null) {

        $userAgent =
            substr(
                (string) $userAgent,
                0,
                255
            );
    }

    /*
    |--------------------------------------------------------------------------
    | JSON VALUES
    |--------------------------------------------------------------------------
    */

    $oldJson = null;
    $newJson = null;

    if ($oldValues !== null) {

        $oldJson =
            json_encode(
                $oldValues,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            );

        if ($oldJson === false) {
            $oldJson = null;
        }
    }

    if ($newValues !== null) {

        $newJson =
            json_encode(
                $newValues,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            );

        if ($newJson === false) {
            $newJson = null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT
    |--------------------------------------------------------------------------
    */

    $sql = "
        INSERT INTO audit_logs
        (
            user_id,
            entity_type,
            entity_id,
            action,
            old_values,
            new_values,
            ip_address,
            user_agent,
            created_at
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
            NOW()
        )
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        throw new RuntimeException(
            'Failed to prepare audit log insert: '
            . $conn->error
        );
    }

    $stmt->bind_param(
        'isisssss',
        $userId,
        $entityType,
        $entityId,
        $action,
        $oldJson,
        $newJson,
        $ipAddress,
        $userAgent
    );

    $stmt->execute();

    $auditLogId =
        (int) $stmt->insert_id;

    $stmt->close();

    return $auditLogId;
}

function audit_log_get_print_history(
    string $dateFrom,
    string $dateTo,
    ?int $userId = null
): array {
    $sql = '
        SELECT
            al.audit_log_id,
            al.entity_id AS document_id,
            al.created_at,
            d.tracking_no,
            d.title AS document_title,
            COALESCE(NULLIF(CONCAT_WS(" ", u.first_name, u.last_name), ""), u.username, "System") AS printed_by,
            JSON_UNQUOTE(JSON_EXTRACT(al.new_values, "$.source")) AS print_source
        FROM audit_logs al
        LEFT JOIN documents d
            ON d.document_id = al.entity_id
        LEFT JOIN users u
            ON u.user_id = al.user_id
        WHERE al.entity_type = ?
          AND al.action = ?
          AND al.created_at >= ?
          AND al.created_at <= ?';

    $params = ['DOCUMENT', 'PRINT_INITIATED', $dateFrom, $dateTo];
    $types = 'ssss';

    if ($userId !== null) {
        $sql .= ' AND al.user_id = ?';
        $params[] = $userId;
        $types .= 'i';
    }

    $sql .= ' ORDER BY al.created_at DESC, al.audit_log_id DESC';

    $stmt = db_execute($sql, $types, $params);
    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows;
}

/*
|--------------------------------------------------------------------------
| BIND PARAMETER HELPER
|--------------------------------------------------------------------------
*/

function audit_bind_params(
    mysqli_stmt $stmt,
    string $types,
    array $params
): void {

    if ($types === '') {

        if ($params !== []) {

            throw new InvalidArgumentException(
                'Parameters were provided without parameter types.'
            );
        }

        return;
    }

    if (
        strlen($types)
        !== count($params)
    ) {

        throw new InvalidArgumentException(
            'Parameter type count does not match parameter count.'
        );
    }

    $bindValues = [$types];

    foreach ($params as $index => $value) {

        $bindValues[] =
            &$params[$index];
    }

    $stmt->bind_param(
        ...$bindValues
    );
}

/**
 * Replace foreign-key IDs in audit snapshots with readable database values.
 * This also supports formatting older snapshots when they are displayed.
 */
function audit_resolve_snapshot_values(?array $values): ?array
{
    if ($values === null) {
        return null;
    }

    $lookups = [
        'attachment_id' => [
            'table' => 'attachments',
            'id_column' => 'attachment_id',
            'value_column' => 'file_name',
        ],
        'document_id' => [
            'table' => 'documents',
            'id_column' => 'document_id',
            'value_column' => 'title',
        ],
        'user_id' => [
            'table' => 'users',
            'id_column' => 'user_id',
            'value_sql' => "TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')))"
        ],
        'current_assignee_user_id' => [
            'table' => 'users',
            'id_column' => 'user_id',
            'value_sql' => "TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')))"
        ],
        'assigned_user_id' => [
            'table' => 'users',
            'id_column' => 'user_id',
            'value_sql' => "TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')))"
        ],
        'assignee_user_id' => [
            'table' => 'users',
            'id_column' => 'user_id',
            'value_sql' => "TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')))"
        ],
        'received_by_user_id' => [
            'table' => 'users',
            'id_column' => 'user_id',
            'value_sql' => "TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')))"
        ],
        'department_id' => [
            'table' => 'departments',
            'id_column' => 'department_id',
            'value_column' => 'department_name',
        ],
        'current_department_id' => [
            'table' => 'departments',
            'id_column' => 'department_id',
            'value_column' => 'department_name',
        ],
        'origin_department_id' => [
            'table' => 'departments',
            'id_column' => 'department_id',
            'value_column' => 'department_name',
        ],
        'document_type_id' => [
            'table' => 'document_types',
            'id_column' => 'document_type_id',
            'value_column' => 'type_name',
        ],
        'status_id' => [
            'table' => 'document_status',
            'id_column' => 'document_status_id',
            'value_column' => 'status_name',
        ],
        'current_status_id' => [
            'table' => 'document_status',
            'id_column' => 'document_status_id',
            'value_column' => 'status_name',
        ],
    ];

    foreach ($values as $key => $value) {
        $normalizedKey = strtolower((string) $key);
        $lookup = $lookups[$normalizedKey] ?? null;

        if ($lookup === null || $value === null || !is_numeric($value) || (int) $value <= 0) {
            continue;
        }

        $valueExpression = $lookup['value_sql'] ?? $lookup['value_column'];
        $stmt = db_execute(
            'SELECT ' . $valueExpression . ' AS display_value FROM ' . $lookup['table'] . ' WHERE ' . $lookup['id_column'] . ' = ? LIMIT 1',
            'i',
            [(int) $value]
        );
        $row = db_fetch_one_assoc($stmt);
        $stmt->close();

        $displayValue = trim((string) ($row['display_value'] ?? ''));
        if ($displayValue !== '') {
            $values[$key] = $displayValue;
        }
    }

    return $values;
}
