<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| DOCUMENT TRACKING MODEL
|--------------------------------------------------------------------------
| Functions:
|
| - Search document by tracking number
| - Get current status
| - Get current office
| - Get date received
| - Get complete routing timeline
|--------------------------------------------------------------------------
*/

/**
 * Find document using exact tracking number.
 *
 * IMPORTANT:
 * The documents table stores the current status using
 * documents.current_status_id.
 *
 * The document_status table uses document_status_id
 * as its primary key.
 */
function tracking_find_document(
    string $trackingNumber,
    bool $publicOnly = true
): ?array {

    $visibilitySql = $publicOnly
        ? " AND d.confidentiality_level = 'PUBLIC'"
        : '';

    $stmt = db_execute(
        '
        SELECT
            d.document_id,
            d.tracking_no,
            d.title,
            d.description,

            d.current_status_id,
            d.current_department_id,
            d.current_assignee_user_id,

            d.date_received,
            d.date_due,
            d.updated_at,
            d.priority_level,
            d.confidentiality_level,

            ds.status_name,

            dep.department_name
                AS current_department_name,

            u.first_name
                AS assignee_first_name,

            u.last_name
                AS assignee_last_name,

            u.username
                AS assignee_username

        FROM documents d

        LEFT JOIN document_status ds
            ON ds.document_status_id =
               d.current_status_id

        LEFT JOIN departments dep
            ON dep.department_id =
               d.current_department_id

        LEFT JOIN users u
            ON u.user_id =
               d.current_assignee_user_id

        WHERE
            d.tracking_no = ?
            ' . $visibilitySql . '

        LIMIT 1
        ',
        's',
        [$trackingNumber]
    );

    $row = db_fetch_one_assoc($stmt);

    $stmt->close();

    return $row ?: null;
}

/**
 * Find a document using a combined search phrase.
 * Supports tracking number, document title/description, and uploaded file name.
 */
function tracking_find_document_by_query(
    string $query,
    bool $publicOnly = true
): ?array
{
    $value = trim($query);

    if ($value === '') {
        return null;
    }

    $like = '%' . $value . '%';
    $exact = $value;
    $visibilitySql = $publicOnly
        ? " AND d.confidentiality_level = 'PUBLIC'"
        : '';

    $stmt = db_execute(
        '
        SELECT
            d.document_id,
            d.tracking_no,
            d.title,
            d.description,
            d.current_status_id,
            d.current_department_id,
            d.current_assignee_user_id,
            d.date_received,
            d.date_due,
            d.updated_at,
            d.priority_level,
            d.confidentiality_level,
            ds.status_name,
            dep.department_name AS current_department_name,
            u.first_name AS assignee_first_name,
            u.last_name AS assignee_last_name,
            u.username AS assignee_username
        FROM documents d
        LEFT JOIN document_status ds
            ON ds.document_status_id = d.current_status_id
        LEFT JOIN departments dep
            ON dep.department_id = d.current_department_id
        LEFT JOIN users u
            ON u.user_id = d.current_assignee_user_id
        WHERE
            (
                d.tracking_no = ?
                OR d.title LIKE ?
                OR d.description LIKE ?
                OR EXISTS (
                SELECT 1
                FROM attachments a
                WHERE a.document_id = d.document_id
                  AND a.file_name LIKE ?
                )
            )
            ' . $visibilitySql . '
        ORDER BY
            CASE
                WHEN d.tracking_no = ? THEN 0
                WHEN d.title LIKE ? THEN 1
                WHEN d.description LIKE ? THEN 2
                ELSE 3
            END,
            d.document_id DESC
        LIMIT 1
        ',
        'sssssss',
        [$exact, $like, $like, $like, $exact, $like, $like]
    );

    if (!$stmt) {
        return null;
    }

    $row = db_fetch_one_assoc($stmt);
    $stmt->close();

    return $row ?: null;
}

/**
 * Suggest documents while typing in tracking search fields.
 *
 * @return list<array{tracking_no: string, title: string, status_name: string}>
 */
function tracking_suggest_documents(
    string $query,
    string $field = 'all',
    int $limit = 8,
    bool $publicOnly = true
): array {

    $query = trim($query);

    if ($query === '') {
        return [];
    }

    $limit = max(1, min(20, $limit));
    $like = '%' . $query . '%';
    $prefix = $query . '%';
    $limitSql = (string) $limit;
    $visibilitySql = $publicOnly
        ? " AND d.confidentiality_level = 'PUBLIC'"
        : '';

    if ($field === 'tracking_no') {
        $stmt = db_execute(
            '
            SELECT
                d.tracking_no,
                d.title,
                COALESCE(ds.status_name, \'\') AS status_name
            FROM documents d
            LEFT JOIN document_status ds
                ON ds.document_status_id = d.current_status_id
                        WHERE d.tracking_no LIKE ?
                        ' . $visibilitySql . '
            ORDER BY
                CASE WHEN d.tracking_no LIKE ? THEN 0 ELSE 1 END,
                d.tracking_no ASC
            LIMIT ' . $limitSql,
            'ss',
            [$like, $prefix]
        );
    } else {
        $stmt = db_execute(
            '
            SELECT DISTINCT
                d.tracking_no,
                d.title,
                COALESCE(ds.status_name, \'\') AS status_name
            FROM documents d
            LEFT JOIN document_status ds
                ON ds.document_status_id = d.current_status_id
            LEFT JOIN attachments a
                ON a.document_id = d.document_id
            WHERE
                (
                    d.tracking_no LIKE ?
                    OR d.title LIKE ?
                    OR d.description LIKE ?
                    OR a.file_name LIKE ?
                )
                ' . $visibilitySql . '
            ORDER BY
                CASE
                    WHEN d.tracking_no LIKE ? THEN 0
                    WHEN d.title LIKE ? THEN 1
                    WHEN d.description LIKE ? THEN 2
                    ELSE 3
                END,
                d.document_id DESC
            LIMIT ' . $limitSql,
            'sssssss',
            [$like, $like, $like, $like, $prefix, $prefix, $like]
        );
    }

    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    $suggestions = [];

    foreach ($rows as $row) {
        $suggestions[] = [
            'tracking_no' => (string) ($row['tracking_no'] ?? ''),
            'title' => (string) ($row['title'] ?? ''),
            'status_name' => (string) ($row['status_name'] ?? ''),
        ];
    }

    return $suggestions;
}

/**
 * Get complete routing history for a document.
 */
function tracking_get_timeline(
    int $documentId
): array {

    $stmt = db_execute(
        '
        SELECT
            dr.document_route_id,
            dr.document_id,

            dr.from_department_id,
            dr.to_department_id,

            dr.routed_by_user_id,
            dr.routed_to_user_id,

            dr.route_status,
            dr.remarks,

            dr.routed_at,
            dr.received_at,
            dr.due_at,

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
            dr.routed_at ASC,
            dr.document_route_id ASC
        ',
        'i',
        [$documentId]
    );

    $rows = db_fetch_all_assoc($stmt);

    $stmt->close();

    return $rows;
}
