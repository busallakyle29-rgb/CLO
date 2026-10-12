<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/models/DashboardModel.php';
require_once dirname(__DIR__, 2) . '/models/AuditTrailModel.php';
require_once dirname(__DIR__, 2) . '/functions/document_access.php';

auth_require_page('reports.php');

$user = auth_user();

/*
|--------------------------------------------------------------------------
| Report Settings
|--------------------------------------------------------------------------
*/

$allowedPeriods = [
    'daily',
    'weekly',
    'monthly',
    'semester',
    'yearly',
];

$period = strtolower(
    trim(
        (string) (
            $_GET['period']
            ?? 'monthly'
        )
    )
);

if (!in_array($period, $allowedPeriods, true)) {
    $period = 'monthly';
}

/*
|--------------------------------------------------------------------------
| Selected Date
|--------------------------------------------------------------------------
*/

$selectedDate =
    (string) (
        $_GET['date']
        ?? date('Y-m-d')
    );

$dateObject = DateTime::createFromFormat(
    'Y-m-d',
    $selectedDate
);

if (
    !$dateObject
    ||
    $dateObject->format('Y-m-d') !== $selectedDate
) {
    $selectedDate = date('Y-m-d');

    $dateObject = new DateTime(
        $selectedDate
    );
}

/*
|--------------------------------------------------------------------------
| Report Date Range
|--------------------------------------------------------------------------
*/

$rangeStart = clone $dateObject;
$rangeEnd = clone $dateObject;

switch ($period) {

    case 'daily':

        $rangeStart->setTime(
            0,
            0,
            0
        );

        $rangeEnd->setTime(
            23,
            59,
            59
        );

        $periodLabel = 'Daily Report';

        break;

    case 'weekly':

        /*
         * Monday to Sunday
         */

        $dayOfWeek =
            (int) $rangeStart->format('N');

        $rangeStart->modify(
            '-' . ($dayOfWeek - 1) . ' days'
        );

        $rangeStart->setTime(
            0,
            0,
            0
        );

        $rangeEnd = clone $rangeStart;

        $rangeEnd->modify('+6 days');

        $rangeEnd->setTime(
            23,
            59,
            59
        );

        $periodLabel = 'Weekly Report';

        break;

    case 'yearly':

        $year =
            (int) $rangeStart->format('Y');

        $rangeStart = new DateTime(
            $year . '-01-01 00:00:00'
        );

        $rangeEnd = new DateTime(
            $year . '-12-31 23:59:59'
        );

        $periodLabel = 'Yearly Report';

        break;

    case 'semester':

        $year =
            (int) $rangeStart->format('Y');

        $month =
            (int) $rangeStart->format('m');

        $semesterStartMonth = $month <= 6 ? 1 : 7;
        $semesterEndMonth = $month <= 6 ? 6 : 12;

        $rangeStart = new DateTime(
            sprintf(
                '%04d-%02d-01 00:00:00',
                $year,
                $semesterStartMonth
            )
        );

        $rangeEnd = new DateTime(
            sprintf(
                '%04d-%02d-01 23:59:59',
                $year,
                $semesterEndMonth
            )
        );

        $rangeEnd->modify('last day of this month');
        $rangeEnd->setTime(23, 59, 59);

        $periodLabel = 'Semester Report';

        break;

    case 'monthly':

    default:

        $year =
            (int) $rangeStart->format('Y');

        $month =
            (int) $rangeStart->format('m');

        $rangeStart = new DateTime(
            sprintf(
                '%04d-%02d-01 00:00:00',
                $year,
                $month
            )
        );

        $rangeEnd = clone $rangeStart;

        $rangeEnd->modify(
            'last day of this month'
        );

        $rangeEnd->setTime(
            23,
            59,
            59
        );

        $periodLabel = 'Monthly Report';

        break;
}

/*
|--------------------------------------------------------------------------
| Report Date Display
|--------------------------------------------------------------------------
*/

if ($period === 'daily') {

    $dateRangeLabel =
        $rangeStart->format('F j, Y');

} else {

    $dateRangeLabel =
        $rangeStart->format('M j, Y')
        .
        ' - '
        .
        $rangeEnd->format('M j, Y');

}

/*
|--------------------------------------------------------------------------
| Get Dashboard Data
|--------------------------------------------------------------------------
|
| We use the existing DashboardModel so this page works with the
| current project structure you already have.
|
*/

$summary = dashboard_get_summary_counts();

$reportUserId = auth_is_role('ADMIN')
    ? null
    : (int) ($user['user_id'] ?? 0);

$reportDocuments = dashboard_get_report_documents(
    $rangeStart->format('Y-m-d H:i:s'),
    $rangeEnd->format('Y-m-d H:i:s'),
    $reportUserId
);
$turnaroundStats = dashboard_get_report_turnaround(
    $rangeStart->format('Y-m-d H:i:s'),
    $rangeEnd->format('Y-m-d H:i:s'),
    $reportUserId
);
$printHistory = audit_log_get_print_history(
    $rangeStart->format('Y-m-d H:i:s'),
    $rangeEnd->format('Y-m-d H:i:s'),
    $reportUserId
);
$routeUserScope = '';
$routeParams = [
    $rangeStart->format('Y-m-d H:i:s'),
    $rangeEnd->format('Y-m-d H:i:s'),
];
$routeTypes = 'ss';

if ($reportUserId !== null && $reportUserId > 0) {
    $routeUserScope = '
        AND (
            dr.routed_by_user_id = ?
            OR dr.routed_to_user_id = ?
            OR d.created_by_user_id = ?
            OR d.current_assignee_user_id = ?
        )';
    array_push($routeParams, $reportUserId, $reportUserId, $reportUserId, $reportUserId);
    $routeTypes .= 'iiii';
}

$routeStmt = db_execute(
    '
    SELECT
        dr.document_route_id,
        dr.document_id,
        dr.routed_at,
        dr.external_recipient_name,
        d.tracking_no,
        d.title,
        d.confidentiality_level,
        d.created_by_user_id,
        d.current_assignee_user_id,
        fd.department_name AS from_department_name,
        td.department_name AS to_department_name,
        COALESCE(NULLIF(CONCAT_WS(" ", ru.first_name, ru.last_name), ""), ru.username, "Unknown") AS routed_by_name,
        COALESCE(NULLIF(CONCAT_WS(" ", tu.first_name, tu.last_name), ""), tu.username, NULLIF(dr.external_recipient_name, ""), "External recipient") AS routed_to_name
    FROM document_routes dr
    INNER JOIN documents d
        ON d.document_id = dr.document_id
    LEFT JOIN departments fd
        ON fd.department_id = dr.from_department_id
    LEFT JOIN departments td
        ON td.department_id = dr.to_department_id
    LEFT JOIN users ru
        ON ru.user_id = dr.routed_by_user_id
    LEFT JOIN users tu
        ON tu.user_id = dr.routed_to_user_id
    WHERE dr.from_department_id IS NOT NULL
      AND dr.from_department_id <> dr.to_department_id
      AND dr.routed_at >= ?
      AND dr.routed_at <= ?
    ' . $routeUserScope . '
    ORDER BY dr.routed_at DESC, dr.document_route_id DESC
    ',
    $routeTypes,
    $routeParams
);
$crossDepartmentRoutes = db_fetch_all_assoc($routeStmt);
$routeStmt->close();
$conn = db_connection();
$crossDepartmentRoutes = array_values(array_filter(
    $crossDepartmentRoutes,
    static fn (array $route): bool => document_user_can_view_content($route, $user, $conn)
));

/*
|--------------------------------------------------------------------------
| Report Statistics
|--------------------------------------------------------------------------
*/

$totalDocuments =
    count($reportDocuments);

$approvedCount = 0;
$pendingCount = 0;
$rejectedCount = 0;
$otherCount = 0;

$typeCounts = [];
$statusCounts = [];

foreach ($reportDocuments as $document) {

    $status =
        trim(
            (string) (
                $document['status_name']
                ?? 'Unknown'
            )
        );

    $type =
        trim(
            (string) (
                $document['type_name']
                ?? 'Unknown'
            )
        );

    if ($status === '') {
        $status = 'Unknown';
    }

    if ($type === '') {
        $type = 'Unknown';
    }

    /*
     * Status statistics
     */

    $statusLower =
        strtolower($status);

    if (
        strpos(
            $statusLower,
            'approve'
        ) !== false
        ||
        strpos(
            $statusLower,
            'archive'
        ) !== false
    ) {

        $approvedCount++;

    } elseif (
        strpos(
            $statusLower,
            'pending'
        ) !== false
        ||
        strpos(
            $statusLower,
            'review'
        ) !== false
    ) {

        $pendingCount++;

    } elseif (
        strpos(
            $statusLower,
            'reject'
        ) !== false
    ) {

        $rejectedCount++;

    } else {

        $otherCount++;

    }

    /*
     * Type statistics
     */

    if (!isset($typeCounts[$type])) {
        $typeCounts[$type] = 0;
    }

    $typeCounts[$type]++;

    /*
     * Status statistics
     */

    if (!isset($statusCounts[$status])) {
        $statusCounts[$status] = 0;
    }

    $statusCounts[$status]++;
}

/*
|--------------------------------------------------------------------------
| Sort statistics
|--------------------------------------------------------------------------
*/

arsort($typeCounts);
arsort($statusCounts);

/*
|--------------------------------------------------------------------------
| User Display
|--------------------------------------------------------------------------
*/

$userName =
    trim(
        (string) (
            $user['first_name']
            ?? ''
        )
        .
        ' '
        .
        (string) (
            $user['last_name']
            ?? ''
        )
    );

if ($userName === '') {
    $userName = 'System User';
}

$generatedAt =
    date('F j, Y g:i A');

/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

require dirname(__DIR__, 2) . '/includes/header.php';

?>

<div class="report-page">

    <!-- PAGE HEADER -->

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 mb-3">

        <div>
<!--
            <div class="text-muted small mb-1">
                CLO &nbsp; &gt; &nbsp; Reports
            </div> -->

            <h1 class="h3 report-title mb-1">
                Reports
            </h1>

            <div class="report-subtitle">
                Generate document activity reports by date received.
            </div>

        </div>

        <div class="d-flex gap-2 no-print">

            <button
                type="button"
                class="btn btn-outline-secondary"
                onclick="window.print()"
            >

                <i class="bi bi-printer me-1"></i>

                Print

            </button>

            <button
                type="button"
                class="btn btn-primary"
                onclick="window.print()"
            >

                <i class="bi bi-file-earmark-pdf me-1"></i>

                Export PDF

            </button>

        </div>

    </div>

    <!-- FILTER -->

    <div class="report-toolbar mb-3 no-print">

        <form
            method="get"
            action="<?php echo e(
                app_url('reports.php')
            ); ?>"
        >

            <div class="row g-3 align-items-end">

                <div class="col-lg-6">

                    <label class="form-label fw-semibold">
                        Report Period
                    </label>

                    <div class="period-buttons d-flex flex-wrap gap-2">

                        <?php foreach ($allowedPeriods as $item): ?>

                            <button
                                type="submit"
                                name="period"
                                value="<?php echo e($item); ?>"
                                class="btn <?php echo $period === $item
                                    ? 'btn-primary'
                                    : 'btn-outline-primary'; ?>"
                            >

                                <?php
                                echo e(
                                    ucfirst(
                                        $item
                                    )
                                );
                                ?>

                            </button>

                        <?php endforeach; ?>

                    </div>

                </div>

                <div class="col-md-6 col-lg-3">

                    <label
                        for="reportDate"
                        class="form-label fw-semibold"
                    >
                        Reference Date
                    </label>

                    <input
                        type="date"
                        class="form-control"
                        id="reportDate"
                        name="date"
                        value="<?php echo e(
                            $selectedDate
                        ); ?>"
                    >

                </div>

                <div class="col-md-6 col-lg-3">

                    <button
                        type="submit"
                        class="btn btn-dark w-100"
                    >

                        <i class="bi bi-funnel me-1"></i>

                        Generate Report

                    </button>

                </div>

            </div>

        </form>

    </div>

    <!-- REPORT HEADER -->

    <div class="report-header mb-3">

        <div class="row align-items-center">

            <div class="col-md-8">

                <div class="small text-uppercase text-muted fw-semibold">
                    City Legal Office
                </div>

                <h2 class="h4 mb-1 report-title">
                    <?php echo e($periodLabel); ?>
                </h2>

                <div class="report-subtitle">
                    Document Tracking System
                </div>

            </div>

            <div class="col-md-4 text-md-end mt-3 mt-md-0">

                <div class="small text-muted">
                    Report Coverage
                </div>

                <div class="fw-semibold text-primary">
                    <?php echo e($dateRangeLabel); ?>
                </div>

                <div class="small text-muted mt-1">
                    Generated <?php echo e($generatedAt); ?>
                </div>

            </div>

        </div>

    </div>

    <!-- STATISTICS -->

    <div class="row g-3 mb-3">

        <div class="col-6 col-xl-3">

            <div class="report-stat">

                <div class="report-stat-label">
                    Total Documents
                </div>

                <div class="report-stat-value">
                    <?php echo e(
                        (string) $totalDocuments
                    ); ?>
                </div>

            </div>

        </div>

        <div class="col-6 col-xl-3">

            <div class="report-stat">

                <div class="report-stat-label">
                    Approved / Archived
                </div>

                <div class="report-stat-value text-success">
                    <?php echo e(
                        (string) $approvedCount
                    ); ?>
                </div>

            </div>

        </div>

        <div class="col-6 col-xl-3">

            <div class="report-stat">

                <div class="report-stat-label">
                    Pending / Review
                </div>

                <div class="report-stat-value text-warning">
                    <?php echo e(
                        (string) $pendingCount
                    ); ?>
                </div>

            </div>

        </div>

        <div class="col-6 col-xl-3">

            <div class="report-stat">

                <div class="report-stat-label">
                    Rejected
                </div>

                <div class="report-stat-value text-danger">
                    <?php echo e(
                        (string) $rejectedCount
                    ); ?>
                </div>

            </div>

        </div>

    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="report-stat">
                <div class="report-stat-label">Average Turnaround</div>
                <div class="report-stat-value">
                    <?php if ($turnaroundStats['average_hours'] !== null): ?>
                        <?= e(number_format((float) $turnaroundStats['average_hours'], 1)) ?> hrs
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </div>
                <div class="small text-muted">
                    <?= e((string) ($turnaroundStats['completed_count'] ?? 0)) ?> completed in this period
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">

        <!-- DOCUMENT TYPE -->

        <div class="col-lg-5">

            <div class="report-card">

                <div class="report-card-header">

                    <h3 class="report-card-title h6">
                        Documents by Type
                    </h3>

                </div>

                <?php if ($typeCounts !== []): ?>

                    <div class="table-responsive">

                        <table class="table table-hover report-table">

                            <thead>

                                <tr>

                                    <th class="ps-3">
                                        Document Type
                                    </th>

                                    <th class="text-end pe-3">
                                        Total
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($typeCounts as $type => $count): ?>

                                <tr>

                                    <td class="ps-3">
                                        <?php echo e($type); ?>
                                    </td>

                                    <td class="text-end pe-3 fw-semibold">
                                        <?php echo e(
                                            (string) $count
                                        ); ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="report-empty">

                        <i class="bi bi-file-earmark-x fs-2 d-block mb-2"></i>

                        No documents found for this period.

                    </div>

                <?php endif; ?>

            </div>

        </div>

        <!-- STATUS -->

        <div class="col-lg-7">

            <div class="report-card">

                <div class="report-card-header">

                    <h3 class="report-card-title h6">
                        Documents by Status
                    </h3>

                </div>

                <?php if ($statusCounts !== []): ?>

                    <div class="table-responsive">

                        <table class="table table-hover report-table">

                            <thead>

                                <tr>

                                    <th class="ps-3">
                                        Status
                                    </th>

                                    <th class="text-end pe-3">
                                        Total
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($statusCounts as $status => $count): ?>

                                <tr>

                                    <td class="ps-3">

                                        <?php

                                        $statusLower =
                                            strtolower(
                                                $status
                                            );

                                        $badgeClass =
                                            'bg-secondary-subtle text-secondary-emphasis';

                                        if (
                                            strpos(
                                                $statusLower,
                                                'approve'
                                            ) !== false
                                            ||
                                            strpos(
                                                $statusLower,
                                                'archive'
                                            ) !== false
                                        ) {

                                            $badgeClass =
                                                'bg-success-subtle text-success-emphasis';

                                        } elseif (
                                            strpos(
                                                $statusLower,
                                                'pending'
                                            ) !== false
                                            ||
                                            strpos(
                                                $statusLower,
                                                'review'
                                            ) !== false
                                        ) {

                                            $badgeClass =
                                                'bg-warning-subtle text-warning-emphasis';

                                        } elseif (
                                            strpos(
                                                $statusLower,
                                                'reject'
                                            ) !== false
                                        ) {

                                            $badgeClass =
                                                'bg-danger-subtle text-danger-emphasis';

                                        }

                                        ?>

                                        <span
                                            class="badge <?php echo e(
                                                $badgeClass
                                            ); ?>"
                                        >
                                            <?php echo e($status); ?>
                                        </span>

                                    </td>

                                    <td class="text-end pe-3 fw-semibold">
                                        <?php echo e(
                                            (string) $count
                                        ); ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="report-empty">

                        <i class="bi bi-bar-chart fs-2 d-block mb-2"></i>

                        No status data available.

                    </div>

                <?php endif; ?>

            </div>

        </div>

        <!-- DOCUMENT LIST -->

        <div class="col-12">

            <div class="report-card">

                <div class="report-card-header d-flex justify-content-between align-items-center">

                    <h3 class="report-card-title h6">
                        Document Details
                    </h3>

                    <span class="small text-muted">
                        <?php echo e(
                            (string) $totalDocuments
                        ); ?>
                        document(s)
                    </span>

                </div>

                <?php if ($reportDocuments !== []): ?>

                    <div class="table-responsive">

                        <table class="table table-hover align-middle report-table">

                            <thead>

                                <tr>

                                    <th class="ps-3">
                                        Document ID
                                    </th>

                                    <th>
                                        Title
                                    </th>

                                    <th>
                                        Type
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Assigned To
                                    </th>

                                    <th class="pe-3">
                                        Date Received
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($reportDocuments as $document): ?>

                                <?php

                                $assignedTo =
                                    trim(
                                        (string) (
                                            $document[
                                                'assignee_first_name'
                                            ]
                                            ?? ''
                                        )
                                        .
                                        ' '
                                        .
                                        (string) (
                                            $document[
                                                'assignee_last_name'
                                            ]
                                            ?? ''
                                        )
                                    );

                                $docStatus =
                                    (string) (
                                        $document[
                                            'status_name'
                                        ]
                                        ?? 'Unknown'
                                    );

                                ?>

                                <tr>

                                    <td class="ps-3 fw-semibold text-primary">

                                        <?php echo e(
                                            (string) (
                                                $document[
                                                    'tracking_no'
                                                ]
                                                ?? ''
                                            )
                                        ); ?>

                                    </td>

                                    <td>

                                        <?php echo e(
                                            (string) (
                                                $document[
                                                    'title'
                                                ]
                                                ?? ''
                                            )
                                        ); ?>

                                    </td>

                                    <td>

                                        <span class="badge bg-light text-dark border">

                                            <?php echo e(
                                                (string) (
                                                    $document[
                                                        'type_name'
                                                    ]
                                                    ?? 'Unknown'
                                                )
                                            ); ?>

                                        </span>

                                    </td>

                                    <td>

                                        <?php echo e(
                                            $docStatus
                                        ); ?>

                                    </td>

                                    <td>

                                        <?php echo e(
                                            $assignedTo !== ''
                                                ? $assignedTo
                                                : 'Unassigned'
                                        ); ?>

                                    </td>

                                    <td class="pe-3 text-muted">

                                        <?php

                                        $displayDate =
                                            (string) (
                                                $document[
                                                    'date_received'
                                                ]
                                                ?? ''
                                            );

                                        echo e(
                                            $displayDate !== ''
                                                ? date(
                                                    'M j, Y',
                                                    strtotime(
                                                        $displayDate
                                                    )
                                                )
                                                : '-'
                                        );

                                        ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="report-empty">

                        <i class="bi bi-file-earmark-text fs-2 d-block mb-2"></i>

                        <div class="fw-semibold">
                            No documents found
                        </div>

                        <div class="small mt-1">
                            There are no documents received within the selected report period.
                        </div>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

    <div class="row g-3 mt-1">

        <div class="col-12">

            <div class="report-card">

                <div class="report-card-header d-flex justify-content-between align-items-center">
                    <h3 class="report-card-title h6">Tracking Slip Print Activity</h3>
                    <span class="small text-muted"><?= e((string) count($printHistory)) ?> print event(s)</span>
                </div>

                <?php if ($printHistory !== []): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle report-table">
                            <thead>
                                <tr>
                                    <th class="ps-3">Tracking Number</th>
                                    <th>Document</th>
                                    <th>Printed By</th>
                                    <th>Source</th>
                                    <th class="pe-3">Date and Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($printHistory as $printEvent): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold text-primary"><?= e((string) ($printEvent['tracking_no'] ?? '')) ?></td>
                                        <td><?= e((string) ($printEvent['document_title'] ?? 'Document')) ?></td>
                                        <td><?= e((string) ($printEvent['printed_by'] ?? 'System')) ?></td>
                                        <td><?= e(ucwords(strtolower(str_replace('_', ' ', (string) ($printEvent['print_source'] ?? 'Tracking Slip'))))) ?></td>
                                        <td class="pe-3 text-muted"><?= e(date('M j, Y g:i A', strtotime((string) $printEvent['created_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="report-empty">No tracking slips were sent to print during this period.</div>
                <?php endif; ?>

            </div>

        </div>

    </div>

    <div class="row g-3 mt-1">

        <div class="col-12">

            <div class="report-card">

                <div class="report-card-header d-flex justify-content-between align-items-center">
                    <h3 class="report-card-title h6">Documents Routed to Other Departments</h3>
                    <span class="small text-muted"><?= e((string) count($crossDepartmentRoutes)) ?> route(s)</span>
                </div>

                <?php if ($crossDepartmentRoutes !== []): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle report-table">
                            <thead>
                                <tr>
                                    <th class="ps-3">Tracking Number</th>
                                    <th>Document</th>
                                    <th>From Department</th>
                                    <th>To Department</th>
                                    <th>Routed By</th>
                                    <th>Routed To</th>
                                    <th class="pe-3">Routed At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($crossDepartmentRoutes as $route): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold text-primary"><?= e((string) ($route['tracking_no'] ?? '')) ?></td>
                                        <td><?= e((string) ($route['title'] ?? 'Document')) ?></td>
                                        <td><?= e((string) ($route['from_department_name'] ?? 'Unknown')) ?></td>
                                        <td><?= e((string) ($route['to_department_name'] ?? 'Unknown')) ?></td>
                                        <td><?= e((string) ($route['routed_by_name'] ?? 'Unknown')) ?></td>
                                        <td><?= e((string) ($route['routed_to_name'] ?? 'External recipient')) ?></td>
                                        <td class="pe-3 text-muted"><?= e(date('M j, Y g:i A', strtotime((string) $route['routed_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="report-empty">No documents were routed to other departments during this period.</div>
                <?php endif; ?>

            </div>

        </div>

    </div>

    <!-- REPORT FOOTER -->

    <div class="mt-4 pt-3 border-top text-muted small no-print">

        <div class="row">

            <div class="col-md-6">
                Generated by:
                <strong>
                    <?php echo e($userName); ?>
                </strong>
            </div>

            <div class="col-md-6 text-md-end">
                City Legal Office &mdash; Document Tracking System
            </div>

        </div>

    </div>

</div>

<script src="<?php echo e(app_url('assets/js/features/reports/reports.js')); ?>"></script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
