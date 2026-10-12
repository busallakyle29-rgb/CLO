<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function pending_get_documents(int $limit = 50): array
{
    $stmt = db_execute(
        "
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
            d.priority_level,
            d.confidentiality_level,

            ds.status_name,

            dt.type_name,

            dep.department_name AS current_department_name,

            CONCAT(
                COALESCE(u.first_name, ''),
                ' ',
                COALESCE(u.last_name, '')
            ) AS assigned_staff_name,

            DATEDIFF(d.date_due, CURDATE()) AS days_until_due

        FROM documents d

        LEFT JOIN document_status ds
            ON ds.document_status_id = d.current_status_id
        LEFT JOIN document_types dt
            ON dt.document_type_id = d.document_type_id
        LEFT JOIN departments dep
            ON dep.department_id = d.current_department_id
        LEFT JOIN users u
            ON u.user_id = d.current_assignee_user_id

        WHERE d.archived_at IS NULL
          AND ds.status_code IN (
              'RECEIVED',
              'PENDING',
              'PENDING_REVIEW',
              'UNDER_REVIEW',
              'ROUTED',
              'FOR_REVISION'
          )

        ORDER BY
            d.date_due ASC,
            d.priority_level DESC,
            d.document_id DESC

        LIMIT ?
        ",
        'i',
        [$limit]
    );

    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows;
}

function pending_get_count(): int
{
    $stmt = db_execute(
        "
        SELECT COUNT(*) AS total
        FROM documents d
        LEFT JOIN document_status ds
            ON ds.document_status_id = d.current_status_id
        WHERE d.archived_at IS NULL
          AND ds.status_code IN (
              'RECEIVED',
              'PENDING',
              'PENDING_REVIEW',
              'UNDER_REVIEW',
              'ROUTED',
              'FOR_REVISION'
          )
        "
    );

    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return (int) ($rows[0]['total'] ?? 0);
}

function pending_get_by_department(int $departmentId): array
{
    $stmt = db_execute(
        "
        SELECT
            d.document_id,
            d.tracking_no,
            d.title,
            d.description,
            d.current_status_id,
            d.date_received,
            d.date_due,
            d.priority_level,

            ds.status_name,
            dt.type_name,

            CONCAT(
                COALESCE(u.first_name, ''),
                ' ',
                COALESCE(u.last_name, '')
            ) AS assigned_staff_name,

            DATEDIFF(d.date_due, CURDATE()) AS days_until_due

        FROM documents d

        LEFT JOIN document_status ds
            ON ds.document_status_id = d.current_status_id
        LEFT JOIN document_types dt
            ON dt.document_type_id = d.document_type_id
        LEFT JOIN users u
            ON u.user_id = d.current_assignee_user_id

        WHERE d.current_department_id = ?
        AND d.archived_at IS NULL
        AND ds.status_code IN (
            'RECEIVED',
            'PENDING',
            'PENDING_REVIEW',
            'UNDER_REVIEW',
            'ROUTED',
            'FOR_REVISION'
        )

        ORDER BY
            d.date_due ASC,
            d.priority_level DESC,
            d.document_id DESC
        ",
        'i',
        [$departmentId]
    );

    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows;
}
