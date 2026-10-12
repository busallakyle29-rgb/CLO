<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Returns a count of access requests captured in the existing audit trail.
 *
 * This intentionally works with the current schema and does not invent a
 * dedicated access-request table that is not present in the application.
 */
function dashboard_get_pending_access_requests(?int $userId = null): int
{
    $sql = "SELECT COUNT(*) AS total
            FROM audit_logs al
            WHERE al.action IN (
                'ACCESS_REQUEST',
                'REQUEST_ACCESS',
                'REQUEST_EDIT_ACCESS',
                'EDIT_ACCESS_REQUEST'
            )";

    $sql .= "
            AND NOT EXISTS (
                SELECT 1
                FROM document_access_grants dag
                WHERE dag.document_id = al.entity_id
                  AND dag.user_id = al.user_id
            )";

        $sql .= "
                        AND NOT EXISTS (
                                SELECT 1
                                FROM audit_logs status_log
                                WHERE status_log.entity_type = al.entity_type
                                    AND status_log.entity_id = al.entity_id
                                    AND status_log.action IN ('ACCESS_REQUEST_CANCELLED', 'ACCESS_REVOKED', 'ACCESS_REQUEST_REJECTED')
                                    AND JSON_UNQUOTE(JSON_EXTRACT(status_log.new_values, '$.request_id')) = CAST(al.audit_log_id AS CHAR)
                        )";

    $params = [];
    $types = '';

    if ($userId !== null && $userId > 0) {
        $sql .= "
            AND (
                al.user_id = ?
                OR EXISTS (
                    SELECT 1
                    FROM documents d
                    WHERE d.document_id = al.entity_id
                      AND (
                          d.current_assignee_user_id = ?
                      )
                )
            )";

        $params = [$userId, $userId];
        $types = 'ii';
    }

    $stmt = db_execute($sql, $types, $params);
    $row = db_fetch_one_assoc($stmt) ?? [];
    $stmt->close();

    return (int) ($row['total'] ?? 0);
}

/**
 * Returns top-level KPI counts used by the dashboard.
 *
 * @param int|null $userId Logged-in user ID.
 */
function dashboard_get_summary_counts(?int $userId = null): array
{
    /*
    |--------------------------------------------------------------------------
    | GLOBAL DOCUMENT SUMMARY
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT
                COUNT(*) AS total_documents,

                SUM(
                    CASE
                        WHEN ds.status_code IN (
                            'RECEIVED',
                            'PENDING',
                            'PENDING_REVIEW',
                            'UNDER_REVIEW',
                            'ROUTED',
                            'FOR_REVISION'
                        )
                        THEN 1
                        ELSE 0
                    END
                ) AS pending_documents,

                SUM(
                    CASE
                        WHEN ds.status_code IN (
                            'UNDER_REVIEW',
                            'ROUTED',
                            'FOR_REVISION'
                        )
                        THEN 1
                        ELSE 0
                    END
                ) AS in_progress_documents,

                SUM(
                    CASE
                        WHEN ds.status_code IN (
                            'APPROVED',
                            'COMPLETED',
                            'ARCHIVED',
                            'RELEASED'
                        )
                        THEN 1
                        ELSE 0
                    END
                ) AS completed_documents,

                SUM(
                    CASE
                        WHEN ds.status_code = 'APPROVED'
                        THEN 1
                        ELSE 0
                    END
                ) AS approved_documents,

                SUM(
                    CASE
                        WHEN DATE(d.date_received) = CURDATE()
                        THEN 1
                        ELSE 0
                    END
                ) AS todays_documents,

                SUM(
                    CASE
                        WHEN COALESCE(ds.is_final, 0) = 0
                         AND (
                            UPPER(COALESCE(d.priority_level, '')) = 'URGENT'
                            OR (
                                d.date_due IS NOT NULL
                                AND DATE(d.date_due) < CURDATE()
                            )
                         )
                        THEN 1
                        ELSE 0
                    END
                ) AS urgent_overdue

            FROM documents d

            INNER JOIN document_status ds
                ON ds.document_status_id = d.current_status_id

            WHERE d.archived_at IS NULL";

    $stmt = db_execute($sql);

    $summary = db_fetch_one_assoc($stmt) ?? [];

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | RETURNED DOCUMENTS
    |--------------------------------------------------------------------------
    */

    $routeSql = "SELECT
                    COUNT(DISTINCT document_id) AS returned_documents

                 FROM document_routes

                 WHERE UPPER(route_status) = 'RETURNED'";

    $routeStmt = db_execute($routeSql);

    $routeSummary = db_fetch_one_assoc($routeStmt) ?? [];

    $routeStmt->close();

    /*
    |--------------------------------------------------------------------------
    | FOR MY ACTION
    |--------------------------------------------------------------------------
    |
    | Uses current assignee.
    |
    */

    $forMyAction = 0;

    if ($userId !== null && $userId > 0) {

        $myActionSql = "SELECT
                            COUNT(*) AS total

                        FROM documents d

                        INNER JOIN document_status ds
                            ON ds.document_status_id =
                               d.current_status_id

                        WHERE d.archived_at IS NULL

                        AND d.current_assignee_user_id = ?

                        AND COALESCE(ds.is_final, 0) = 0";

        $myActionStmt = db_execute(
            $myActionSql,
            'i',
            [$userId]
        );

        $myActionRow =
            db_fetch_one_assoc($myActionStmt) ?? [];

        $myActionStmt->close();

        $forMyAction = (int) (
            $myActionRow['total'] ?? 0
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PENDING ACCESS REQUESTS
    |--------------------------------------------------------------------------
    |
    | Uses the audit log, which is the only access-request tracking data
    | currently represented in the live schema.
    |
    */

    $pendingAccessRequests = dashboard_get_pending_access_requests($userId);

    /*
    |--------------------------------------------------------------------------
    | DOCUMENTS OUTSIDE CLO
    |--------------------------------------------------------------------------
    |
    | Department ID 1 is the CLO department according to the current
    | project configuration.
    |
    */

    $documentsOutsideClo = 0;

    $outsideSql = "SELECT
                       COUNT(*) AS total

                   FROM documents d

                   WHERE d.archived_at IS NULL

                   AND d.current_department_id IS NOT NULL

                   AND d.current_department_id <> 1";

    $outsideStmt = db_execute($outsideSql);

    $outsideRow =
        db_fetch_one_assoc($outsideStmt) ?? [];

    $outsideStmt->close();

    $documentsOutsideClo = (int) (
        $outsideRow['total'] ?? 0
    );

    return [
        'total_documents' => (int) (
            $summary['total_documents'] ?? 0
        ),

        'pending_documents' => (int) (
            $summary['pending_documents'] ?? 0
        ),

        'in_progress_documents' => (int) (
            $summary['in_progress_documents'] ?? 0
        ),

        'completed_documents' => (int) (
            $summary['completed_documents'] ?? 0
        ),

        'approved_documents' => (int) (
            $summary['approved_documents'] ?? 0
        ),

        'urgent_overdue' => (int) (
            $summary['urgent_overdue'] ?? 0
        ),

        'returned_documents' => (int) (
            $routeSummary['returned_documents'] ?? 0
        ),

        'todays_documents' => (int) (
            $summary['todays_documents'] ?? 0
        ),

        'for_my_action' => $forMyAction,

        'pending_access_requests' => $pendingAccessRequests,

        'documents_outside_clo' => $documentsOutsideClo,
    ];
}

/**
 * Returns overdue and soon-due documents assigned to the current user.
 *
 * @return list<array<string, mixed>>
 */
function dashboard_get_assigned_deadline_reminders(
    int $userId,
    int $upcomingDays = 3,
    int $limit = 8
): array {
    if ($userId <= 0) {
        return [];
    }

    $upcomingDays = max(0, min(30, $upcomingDays));
    $limit = max(1, min(25, $limit));

    $stmt = db_execute(
        "SELECT
            d.document_id,
            d.tracking_no,
            d.title,
            d.date_due,
            d.priority_level,
            ds.status_name,
            DATEDIFF(DATE(d.date_due), CURDATE()) AS days_until_due
         FROM documents d
         LEFT JOIN document_status ds
            ON ds.document_status_id = d.current_status_id
         WHERE d.current_assignee_user_id = ?
           AND d.archived_at IS NULL
           AND d.date_due IS NOT NULL
           AND DATE(d.date_due) <= DATE_ADD(CURDATE(), INTERVAL {$upcomingDays} DAY)
           AND COALESCE(ds.is_final, 0) = 0
         ORDER BY DATE(d.date_due) ASC, d.document_id ASC
         LIMIT {$limit}",
        'i',
        [$userId]
    );

    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows;
}

/**
 * Returns overdue, unfinished documents for administrator follow-up.
 *
 * @return list<array<string, mixed>>
 */
function dashboard_get_overdue_escalations(int $limit = 8): array
{
     $limit = max(1, min(25, $limit));

     $stmt = db_execute(
          "SELECT
                d.document_id,
                d.tracking_no,
                d.title,
                d.date_due,
                DATEDIFF(CURDATE(), DATE(d.date_due)) AS days_overdue,
                u.first_name AS assignee_first_name,
                u.last_name AS assignee_last_name,
                dep.department_name
            FROM documents d
            INNER JOIN document_status ds
                ON ds.document_status_id = d.current_status_id
            LEFT JOIN users u
                ON u.user_id = d.current_assignee_user_id
            LEFT JOIN departments dep
                ON dep.department_id = d.current_department_id
            WHERE d.archived_at IS NULL
              AND d.date_due IS NOT NULL
              AND DATE(d.date_due) < CURDATE()
              AND COALESCE(ds.is_final, 0) = 0
            ORDER BY DATE(d.date_due) ASC, d.document_id ASC
            LIMIT {$limit}"
     );

     $rows = db_fetch_all_assoc($stmt);
     $stmt->close();

     return $rows;
}

/**
 * Returns status distribution for the dashboard.
 */
function dashboard_get_status_breakdown(): array
{
    $sql = "SELECT
                ds.status_name,
                COUNT(d.document_id) AS total

            FROM document_status ds

            LEFT JOIN documents d
                ON d.current_status_id =
                   ds.document_status_id

            AND d.archived_at IS NULL

            WHERE ds.is_active = 1

            GROUP BY
                ds.document_status_id,
                ds.status_name,
                ds.sort_order

            ORDER BY
                ds.sort_order ASC";

    $stmt = db_execute($sql);

    $rows = db_fetch_all_assoc($stmt);

    $stmt->close();

    $labels = [];
    $totals = [];

    foreach ($rows as $row) {

        $labels[] = (string) (
            $row['status_name'] ?? ''
        );

        $totals[] = (int) (
            $row['total'] ?? 0
        );
    }

    return [
        'labels' => $labels,
        'totals' => $totals,
    ];
}

/**
 * Returns monthly received vs. resolved counts.
 */
function dashboard_get_monthly_trend(
    int $months = 8
): array {

    $months = max(
        3,
        min(12, $months)
    );

    $startDate = date(
        'Y-m-01',
        strtotime(
            '-' . ($months - 1) . ' months'
        )
    );

    /*
    |--------------------------------------------------------------------------
    | RECEIVED
    |--------------------------------------------------------------------------
    */

    $receivedSql = "SELECT
                        DATE_FORMAT(
                            date_received,
                            '%Y-%m'
                        ) AS ym,

                        COUNT(*) AS total

                    FROM documents

                    WHERE archived_at IS NULL

                    AND date_received >= ?

                    GROUP BY ym

                    ORDER BY ym ASC";

    $receivedStmt = db_execute(
        $receivedSql,
        's',
        [$startDate]
    );

    $receivedRows =
        db_fetch_all_assoc($receivedStmt);

    $receivedStmt->close();

    /*
    |--------------------------------------------------------------------------
    | RESOLVED
    |--------------------------------------------------------------------------
    */

    $resolvedSql = "SELECT
                        DATE_FORMAT(
                            dh.changed_at,
                            '%Y-%m'
                        ) AS ym,

                        COUNT(DISTINCT dh.document_id)
                            AS total

                    FROM document_history dh

                    INNER JOIN documents d
                        ON d.document_id =
                           dh.document_id

                    INNER JOIN document_status ds
                        ON ds.document_status_id =
                           dh.document_status_id

                    WHERE d.archived_at IS NULL

                    AND ds.is_final = 1

                    AND dh.changed_at >= ?

                    GROUP BY ym

                    ORDER BY ym ASC";

    $resolvedStmt = db_execute(
        $resolvedSql,
        's',
        [$startDate . ' 00:00:00']
    );

    $resolvedRows =
        db_fetch_all_assoc($resolvedStmt);

    $resolvedStmt->close();

    $receivedMap = [];
    $resolvedMap = [];

    foreach ($receivedRows as $row) {

        $receivedMap[
            (string) $row['ym']
        ] = (int) $row['total'];
    }

    foreach ($resolvedRows as $row) {

        $resolvedMap[
            (string) $row['ym']
        ] = (int) $row['total'];
    }

    $labels = [];
    $received = [];
    $resolved = [];

    for (
        $i = $months - 1;
        $i >= 0;
        $i--
    ) {

        $monthKey = date(
            'Y-m',
            strtotime(
                '-' . $i . ' months'
            )
        );

        $labels[] = date(
            'M',
            strtotime(
                $monthKey . '-01'
            )
        );

        $received[] =
            $receivedMap[$monthKey] ?? 0;

        $resolved[] =
            $resolvedMap[$monthKey] ?? 0;
    }

    return [
        'labels' => $labels,
        'received' => $received,
        'resolved' => $resolved,
    ];
}

/**
 * Returns document type distribution.
 */
function dashboard_get_type_distribution(
    int $limit = 5
): array {

    $limit = max(
        3,
        min(10, $limit)
    );

    $sql = "SELECT
                dt.type_name,
                COUNT(d.document_id) AS total

            FROM document_types dt

            LEFT JOIN documents d
                ON d.document_type_id =
                   dt.document_type_id

            AND d.archived_at IS NULL

            WHERE dt.is_active = 1

            GROUP BY
                dt.document_type_id,
                dt.type_name

            ORDER BY
                total DESC,
                dt.type_name ASC

            LIMIT " . (int) $limit;

    $stmt = db_execute($sql);

    $rows = db_fetch_all_assoc($stmt);

    $stmt->close();

    $labels = [];
    $totals = [];

    foreach ($rows as $row) {

        $labels[] = (string) (
            $row['type_name'] ?? ''
        );

        $totals[] = (int) (
            $row['total'] ?? 0
        );
    }

    return [
        'labels' => $labels,
        'totals' => $totals,
    ];
}

/**
 * Returns current-week document counts per day.
 */
function dashboard_get_weekly_activity(): array
{
    $sql = "SELECT
                WEEKDAY(date_received) AS wd,
                COUNT(*) AS total

            FROM documents

            WHERE archived_at IS NULL

            AND YEARWEEK(
                date_received,
                1
            ) = YEARWEEK(
                CURDATE(),
                1
            )

            GROUP BY wd

            ORDER BY wd ASC";

    $stmt = db_execute($sql);

    $rows = db_fetch_all_assoc($stmt);

    $stmt->close();

    $map = [
        0 => 0,
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0,
        6 => 0,
    ];

    foreach ($rows as $row) {

        $day = (int) (
            $row['wd'] ?? -1
        );

        if (
            array_key_exists(
                $day,
                $map
            )
        ) {

            $map[$day] = (int) (
                $row['total'] ?? 0
            );
        }
    }

    return [
        'labels' => [
            'Mon',
            'Tue',
            'Wed',
            'Thu',
            'Fri',
            'Sat',
            'Sun',
        ],

        'totals' => array_values($map),
    ];
}

/**
 * Returns recent document history activity.
 */
function dashboard_get_recent_activity(
    int $limit = 6
): array {

    $limit = max(
        3,
        min(20, $limit)
    );

    $sql = "SELECT
                dh.changed_at,
                dh.change_note,
                ds.status_name,
                d.tracking_no,
                u.first_name,
                u.last_name

            FROM document_history dh

            INNER JOIN documents d
                ON d.document_id =
                   dh.document_id

            INNER JOIN document_status ds
                ON ds.document_status_id =
                   dh.document_status_id

            LEFT JOIN users u
                ON u.user_id =
                   dh.changed_by_user_id

            WHERE d.archived_at IS NULL

            ORDER BY
                dh.changed_at DESC

            LIMIT " . (int) $limit;

    $stmt = db_execute($sql);

    $rows = db_fetch_all_assoc($stmt);

    $stmt->close();

    return $rows;
}

/**
 * Returns recent documents.
 *
 * If a user ID is supplied, the query prioritizes documents
 * created by or currently assigned to that user.
 */
function dashboard_get_recent_documents(
    int $limit = 6,
    ?int $userId = null
): array {

    $limit = max(
        3,
        min(20, $limit)
    );

    $whereSql = "WHERE d.archived_at IS NULL";

    $params = [];
    $types = '';

    if (
        $userId !== null
        && $userId > 0
    ) {

        $whereSql .= "
            AND (
                d.created_by_user_id = ?
                OR d.current_assignee_user_id = ?
            )
        ";

        $params = [
            $userId,
            $userId,
        ];

        $types = 'ii';
    }

    $sql = "SELECT
                d.document_id,
                d.tracking_no,
                d.title,
                d.date_received,

                dt.type_name,

                ds.status_name,

                au.first_name AS assignee_first_name,
                au.last_name AS assignee_last_name,

                dep.department_name AS current_department_name

            FROM documents d

            INNER JOIN document_types dt
                ON dt.document_type_id =
                   d.document_type_id

            INNER JOIN document_status ds
                ON ds.document_status_id =
                   d.current_status_id

            LEFT JOIN users au
                ON au.user_id =
                   d.current_assignee_user_id

            LEFT JOIN departments dep
                ON dep.department_id =
                   d.current_department_id

            " . $whereSql . "

            ORDER BY
                d.created_at DESC

            LIMIT " . (int) $limit;

    $stmt = db_execute(
        $sql,
        $types,
        $params
    );

    $rows = db_fetch_all_assoc($stmt);

    $stmt->close();

    return $rows;
}

/**
 * Returns non-archived documents received within a report period.
 *
 * @return list<array<string, mixed>>
 */
function dashboard_get_report_documents(
    string $dateFrom,
    string $dateTo,
    ?int $userId = null
): array {
    $userFilter = '';
    $params = [$dateFrom, $dateTo];
    $types = 'ss';

    if ($userId !== null && $userId > 0) {
        $userFilter = '
            AND (
                d.created_by_user_id = ?
                OR d.current_assignee_user_id = ?
            )';
        $params[] = $userId;
        $params[] = $userId;
        $types .= 'ii';
    }

    $stmt = db_execute(
        "SELECT
            d.document_id,
            d.tracking_no,
            d.title,
            d.date_received,
            dt.type_name,
            ds.status_name,
            au.first_name AS assignee_first_name,
            au.last_name AS assignee_last_name,
            dep.department_name AS current_department_name
         FROM documents d
         INNER JOIN document_types dt
            ON dt.document_type_id = d.document_type_id
         INNER JOIN document_status ds
            ON ds.document_status_id = d.current_status_id
         LEFT JOIN users au
            ON au.user_id = d.current_assignee_user_id
         LEFT JOIN departments dep
            ON dep.department_id = d.current_department_id
         WHERE d.archived_at IS NULL
           AND d.date_received >= ?
           AND d.date_received <= ?{$userFilter}
         ORDER BY d.created_at DESC",
        $types,
        $params
    );

    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows;
}

/**
 * Returns completion timing for non-archived documents received in a period.
 *
 * @return array<string, mixed>
 */
function dashboard_get_report_turnaround(
    string $dateFrom,
    string $dateTo,
    ?int $userId = null
): array {
    $userFilter = '';
    $params = [$dateFrom, $dateTo];
    $types = 'ss';

    if ($userId !== null && $userId > 0) {
        $userFilter = ' AND (d.created_by_user_id = ? OR d.current_assignee_user_id = ?)';
        $params[] = $userId;
        $params[] = $userId;
        $types .= 'ii';
    }

    $stmt = db_execute(
        "SELECT
            COUNT(*) AS completed_count,
            ROUND(AVG(TIMESTAMPDIFF(HOUR, d.date_received, completed.completed_at)), 1) AS average_hours
         FROM documents d
         INNER JOIN (
            SELECT dh.document_id, MIN(dh.changed_at) AS completed_at
            FROM document_history dh
            INNER JOIN document_status ds
                ON ds.document_status_id = dh.document_status_id
            WHERE ds.is_final = 1
            GROUP BY dh.document_id
         ) completed
            ON completed.document_id = d.document_id
         WHERE d.archived_at IS NULL
           AND d.date_received >= ?
           AND d.date_received <= ?{$userFilter}",
        $types,
        $params
    );

    $row = db_fetch_one_assoc($stmt) ?? [];
    $stmt->close();

    return $row;
}

/**
 * Returns confidentiality classification counts.
 *
 * This uses the existing documents.confidentiality_level field.
 */
function dashboard_get_confidentiality_summary(): array
{
    $sql = "SELECT
                classifications.label,
                COUNT(d.document_id) AS total
            FROM (
                SELECT 'Normal' AS label
                UNION ALL SELECT 'Confidential'
                UNION ALL SELECT 'Highly Confidential'
            ) classifications
            LEFT JOIN documents d
                ON d.archived_at IS NULL
               AND CASE
                    WHEN UPPER(TRIM(COALESCE(d.confidentiality_level, ''))) = 'CONFIDENTIAL'
                        THEN 'Confidential'
                    WHEN UPPER(TRIM(COALESCE(d.confidentiality_level, ''))) IN ('HIGHLY_CONFIDENTIAL', 'RESTRICTED')
                        THEN 'Highly Confidential'
                    ELSE 'Normal'
               END = classifications.label
            GROUP BY classifications.label
            ORDER BY FIELD(classifications.label, 'Normal', 'Confidential', 'Highly Confidential')";

    $stmt = db_execute($sql);

    $rows = db_fetch_all_assoc($stmt);

    $stmt->close();

    $result = [];

    foreach ($rows as $row) {

        $result[] = [
            'label' => (string) (
                $row['label'] ?? 'Normal'
            ),

            'total' => (int) (
                $row['total'] ?? 0
            ),
        ];
    }

    return $result;
}
