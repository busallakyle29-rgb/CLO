<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/models/DashboardModel.php';

auth_require_page('dashboard.php');

$user = auth_user();

$canManageDocuments = auth_can_manage_documents()
    && auth_can_access_page('document_management.php');
$canRouteDocuments = auth_can_access_page('document_routing.php');
$canViewPendingDocuments = auth_can_access_page('pending_documents.php');
$canViewReports = auth_can_access_page('reports.php');
$canViewArchive = auth_can_access_page('archive_documents.php');
$canViewDrafts = file_exists(dirname(__DIR__, 2) . '/my_drafts.php')
    && auth_can_access_page('my_drafts.php');
$hasQuickActions = $canManageDocuments
    || $canRouteDocuments
    || $canViewPendingDocuments
    || $canViewReports
    || $canViewArchive
    || $canViewDrafts;

$userId = isset($user['user_id']) ? (int) $user['user_id'] : 0;

/*
|--------------------------------------------------------------------------
| DASHBOARD DATA
|--------------------------------------------------------------------------
*/

$summary = dashboard_get_summary_counts($userId);
$deadlineReminders = dashboard_get_assigned_deadline_reminders($userId);
$overdueEscalations = auth_is_role('ADMIN')
    ? dashboard_get_overdue_escalations()
    : [];
$monthlyTrend = dashboard_get_monthly_trend(8);
$typeDistribution = dashboard_get_type_distribution(5);
$weeklyActivity = dashboard_get_weekly_activity();
$recentActivity = dashboard_get_recent_activity(6);
$recentDocuments = dashboard_get_recent_documents(6, $userId);

$confidentialitySummary = dashboard_get_confidentiality_summary();

$todayLabel = date('l, F j, Y');

/*
|--------------------------------------------------------------------------
| STATUS BADGE CLASS
|--------------------------------------------------------------------------
*/

$statusClass = static function (string $status): string {
    $value = strtolower(trim($status));

    if (
        strpos($value, 'approve') !== false ||
        strpos($value, 'archive') !== false ||
        strpos($value, 'complete') !== false
    ) {
        return 'bg-success-subtle text-success-emphasis';
    }

    if (
        strpos($value, 'review') !== false ||
        strpos($value, 'route') !== false ||
        strpos($value, 'release') !== false
    ) {
        return 'bg-primary-subtle text-primary-emphasis';
    }

    if (
        strpos($value, 'reject') !== false ||
        strpos($value, 'urgent') !== false ||
        strpos($value, 'return') !== false ||
        strpos($value, 'overdue') !== false
    ) {
        return 'bg-danger-subtle text-danger-emphasis';
    }

    if (
        strpos($value, 'receive') !== false ||
        strpos($value, 'pending') !== false
    ) {
        return 'bg-warning-subtle text-warning-emphasis';
    }

    return 'bg-secondary-subtle text-secondary-emphasis';
};

/*
|--------------------------------------------------------------------------
| DASHBOARD CHART DATA
|--------------------------------------------------------------------------
|
| Data is passed through HTML instead of inline JavaScript.
|
*/

$dashboardChartData = [
    'trend' => [
        'labels' => $monthlyTrend['labels'] ?? [],
        'received' => $monthlyTrend['received'] ?? [],
        'resolved' => $monthlyTrend['resolved'] ?? [],
    ],
    'types' => [
        'labels' => $typeDistribution['labels'] ?? [],
        'totals' => $typeDistribution['totals'] ?? [],
    ],
    'weekly' => [
        'labels' => $weeklyActivity['labels'] ?? [],
        'totals' => $weeklyActivity['totals'] ?? [],
    ],
];

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<link
    rel="stylesheet"
    href="<?php echo e(app_url('assets/css/features/dashboard/dashboard.css')); ?>"
>

<div
    id="dashboardData"
    class="d-none"
    data-chart-data="<?php echo e(
        json_encode(
            $dashboardChartData,
            JSON_HEX_TAG |
            JSON_HEX_AMP |
            JSON_HEX_APOS |
            JSON_HEX_QUOT
        )
    ); ?>"
></div>

<div class="container-fluid dashboard-page">

    <!-- =========================================================
         DASHBOARD HEADER
    ========================================================== -->

    <div class="row g-3">

        <div class="col-12">

            <div
                class="dash-header d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-2"
            >

                <div>

                    <h1 class="h3 mb-1">
                        Dashboard Overview
                    </h1>

                    <div class="dash-sub">

                        <?php echo e($todayLabel); ?>

                        <span class="dash-divider">|</span>

                        City Legal Office, Panabo City

                    </div>

                </div>

            </div>

        </div>

        <?php if ($deadlineReminders !== []): ?>
            <div class="col-12">
                <div class="panel-card">
                    <div class="panel-heading">
                        <div>
                            <p class="panel-title mb-1">
                                <i class="bi bi-alarm text-warning me-1"></i>
                                Due Date Reminders
                            </p>
                            <p class="panel-sub mb-0">Documents assigned to you that are overdue or due within 3 days</p>
                        </div>
                        <span class="badge text-bg-warning"><?= count($deadlineReminders) ?> listed</span>
                    </div>
                    <div class="mt-2">
                        <?php foreach ($deadlineReminders as $reminder): ?>
                            <?php
                                $daysUntilDue = (int) ($reminder['days_until_due'] ?? 0);
                                if ($daysUntilDue < 0) {
                                    $dueLabel = 'Overdue by ' . abs($daysUntilDue) . ' day(s)';
                                    $dueClass = 'text-danger';
                                } elseif ($daysUntilDue === 0) {
                                    $dueLabel = 'Due today';
                                    $dueClass = 'text-warning-emphasis';
                                } else {
                                    $dueLabel = 'Due in ' . $daysUntilDue . ' day(s)';
                                    $dueClass = 'text-muted';
                                }
                            ?>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom py-2">
                                <div class="min-width-0">
                                    <?php if (auth_can_access_page('document_management.php')): ?>
                                        <a class="fw-semibold text-decoration-none" href="<?= e(app_url('document_management.php?search=' . urlencode((string) ($reminder['tracking_no'] ?? '')))) ?>">
                                            <?= e((string) ($reminder['title'] ?? 'Untitled document')) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="fw-semibold"><?= e((string) ($reminder['title'] ?? 'Untitled document')) ?></span>
                                    <?php endif; ?>
                                    <small class="text-muted d-block"><?= e((string) ($reminder['tracking_no'] ?? '')) ?> · <?= e((string) ($reminder['status_name'] ?? '')) ?></small>
                                </div>
                                <div class="text-end">
                                    <strong class="<?= e($dueClass) ?>"><?= e($dueLabel) ?></strong>
                                    <small class="text-muted d-block"><?= e(date('M d, Y', strtotime((string) $reminder['date_due']))) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($overdueEscalations !== []): ?>
            <div class="col-12">
                <div class="panel-card">
                    <div class="panel-heading">
                        <div>
                            <p class="panel-title mb-1">
                                <i class="bi bi-exclamation-triangle text-danger me-1"></i>
                                Overdue Escalations
                            </p>
                            <p class="panel-sub mb-0">Unfinished documents past their due date</p>
                        </div>
                        <span class="badge text-bg-danger"><?= count($overdueEscalations) ?> listed</span>
                    </div>
                    <div class="mt-2">
                        <?php foreach ($overdueEscalations as $escalation): ?>
                            <?php
                                $assigneeName = trim(
                                    (string) ($escalation['assignee_first_name'] ?? '')
                                    . ' '
                                    . (string) ($escalation['assignee_last_name'] ?? '')
                                );
                            ?>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom py-2">
                                <div class="min-width-0">
                                    <?php if (auth_can_access_page('document_management.php')): ?>
                                        <a class="fw-semibold text-decoration-none" href="<?= e(app_url('document_management.php?search=' . urlencode((string) ($escalation['tracking_no'] ?? '')))) ?>">
                                            <?= e((string) ($escalation['title'] ?? 'Untitled document')) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="fw-semibold"><?= e((string) ($escalation['title'] ?? 'Untitled document')) ?></span>
                                    <?php endif; ?>
                                    <small class="text-muted d-block">
                                        <?= e((string) ($escalation['tracking_no'] ?? '')) ?> ·
                                        <?= e((string) ($escalation['department_name'] ?? 'Unassigned department')) ?> ·
                                        <?= e($assigneeName !== '' ? $assigneeName : 'Unassigned') ?>
                                    </small>
                                </div>
                                <strong class="text-danger">
                                    <?= e((string) ($escalation['days_overdue'] ?? 0)) ?> day(s) overdue
                                </strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- =====================================================
             KPI 1 - TOTAL DOCUMENTS
        ====================================================== -->

        <?php if ($canManageDocuments || auth_can_access_page('all_documents.php')): ?>
        <div class="col-12 col-md-6 col-xl-2">

            <a
                class="kpi-card h-100 d-block text-decoration-none"
                href="<?php echo e(app_url(
                    auth_can_access_page('all_documents.php')
                        ? 'all_documents.php'
                        : 'document_management.php'
                )); ?>"
            >

                <div
                    class="d-flex justify-content-between align-items-start"
                >

                    <div>

                        <div class="kpi-title">
                            Total Documents
                        </div>

                        <div class="kpi-value">
                            <?php echo e(
                                (string) ($summary['total_documents'] ?? 0)
                            ); ?>
                        </div>

                        <div class="kpi-meta kpi-meta-success">
                            <i class="bi bi-files"></i>
                            Active monitoring
                        </div>

                    </div>

                    <div class="kpi-icon kpi-icon-blue">
                        <i class="bi bi-files"></i>
                    </div>

                </div>

            </a>

        </div>
        <?php endif; ?>

        <!-- =====================================================
             KPI 2 - FOR MY ACTION
        ====================================================== -->

        <div class="col-12 col-md-6 col-xl-2">

            <a
                class="kpi-card h-100 d-block text-decoration-none"
                href="<?php echo e(app_url('pending_documents.php')); ?>"
            >

                <div
                    class="d-flex justify-content-between align-items-start"
                >

                    <div>

                        <div class="kpi-title">
                            For My Action
                        </div>

                        <div class="kpi-value">
                            <?php echo e(
                                (string) ($summary['for_my_action'] ?? 0)
                            ); ?>
                        </div>

                        <div class="kpi-meta kpi-meta-warning">
                            <i class="bi bi-lightning-charge"></i>
                            Requires action
                        </div>

                    </div>

                    <div class="kpi-icon kpi-icon-orange">
                        <i class="bi bi-clock-history"></i>
                    </div>

                </div>

            </a>

        </div>

        <!-- =====================================================
             KPI 3 - CURRENTLY APPROVED
        ====================================================== -->

        <div class="col-12 col-md-6 col-xl-2">

            <a
                class="kpi-card h-100 d-block text-decoration-none"
                href="<?php echo e(app_url('all_documents.php?status=APPROVED')); ?>"
            >

                <div
                    class="d-flex justify-content-between align-items-start"
                >

                    <div>

                        <div class="kpi-title">
                            Approved Documents
                        </div>

                        <div class="kpi-value">
                            <?php echo e(
                                (string) ($summary['approved_documents'] ?? 0)
                            ); ?>
                        </div>

                        <div class="kpi-meta kpi-meta-success">
                            <i class="bi bi-check2-circle"></i>
                            Currently approved
                        </div>

                    </div>

                    <div class="kpi-icon kpi-icon-green">
                        <i class="bi bi-patch-check"></i>
                    </div>

                </div>

            </a>

        </div>

        <!-- =====================================================
             KPI 4 - URGENT / OVERDUE
        ====================================================== -->

        <div class="col-12 col-md-6 col-xl-2">

            <a
                class="kpi-card h-100 d-block text-decoration-none"
                href="<?php echo e(app_url('pending_documents.php')); ?>"
            >

                <div
                    class="d-flex justify-content-between align-items-start"
                >

                    <div>

                        <div class="kpi-title">
                            Urgent / Overdue
                        </div>

                        <div class="kpi-value">
                            <?php echo e(
                                (string) ($summary['urgent_overdue'] ?? 0)
                            ); ?>
                        </div>

                        <div class="kpi-meta kpi-meta-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            Needs immediate response
                        </div>

                    </div>

                    <div class="kpi-icon kpi-icon-red">
                        <i class="bi bi-exclamation-octagon"></i>
                    </div>

                </div>

            </a>

        </div>

        <!-- =====================================================
             KPI 5 - PENDING ACCESS REQUESTS
        ====================================================== -->

        <div class="col-12 col-md-6 col-xl-2">

            <a
                class="kpi-card h-100 d-block text-decoration-none"
                href="<?php echo e(app_url('pending_access_requests.php')); ?>"
            >

                <div
                    class="d-flex justify-content-between align-items-start"
                >

                    <div>

                        <div class="kpi-title">
                            Pending Access Requests
                        </div>

                        <div class="kpi-value">
                            <?php echo e(
                                (string) ($summary['pending_access_requests'] ?? 0)
                            ); ?>
                        </div>

                        <div class="kpi-meta kpi-meta-info">
                            <i class="bi bi-shield-lock"></i>
                            Awaiting review
                        </div>

                    </div>

                    <div class="kpi-icon kpi-icon-purple">
                        <i class="bi bi-key"></i>
                    </div>

                </div>

            </a>

        </div>

        <!-- =====================================================
             DOCUMENTS OUTSIDE CLO
        ====================================================== -->

        <div class="col-12 col-md-6 col-xl-2">

            <a
                class="kpi-card h-100 d-block text-decoration-none"
                href="<?php echo e(app_url('document_tracking.php')); ?>"
            >

                <div
                    class="d-flex justify-content-between align-items-start"
                >

                    <div>

                        <div class="kpi-title">
                            Documents Outside CLO
                        </div>

                        <div class="kpi-value">
                            <?php echo e(
                                (string) ($summary['documents_outside_clo'] ?? 0)
                            ); ?>
                        </div>

                        <div class="kpi-meta kpi-meta-info">
                            <i class="bi bi-globe2"></i>
                            Inter-office flow
                        </div>

                    </div>

                    <div class="kpi-icon kpi-icon-cyan">
                        <i class="bi bi-building"></i>
                    </div>

                </div>

            </a>

        </div>

        <!-- =====================================================
             DOCUMENT VOLUME TREND
        ====================================================== -->

        <div class="col-12 col-xl-8">

            <div class="panel-card h-100">

                <div class="panel-heading">

                    <div>

                        <p class="panel-title mb-1">
                            Document Volume Trend
                        </p>

                        <p class="panel-sub mb-0">
                            Monthly received vs. resolved
                        </p>

                    </div>

                </div>

                <div class="chart-container chart-volume">
                    <canvas
                        id="volumeTrendChart"
                        role="img"
                        aria-label="Document volume trend chart"
                    ></canvas>
                </div>

            </div>

        </div>

        <!-- =====================================================
             DOCUMENTS BY TYPE
        ====================================================== -->

        <div class="col-xl-4">

            <div class="panel-card h-100">

                <div class="panel-heading">

                    <div>

                        <p class="panel-title mb-1">
                            Documents by Type
                        </p>

                        <p class="panel-sub mb-0">
                            Active document distribution
                        </p>

                    </div>

                </div>

                <div class="chart-container chart-type">
                    <canvas
                        id="typeDistributionChart"
                        role="img"
                        aria-label="Documents by type chart"
                    ></canvas>
                </div>

                <div class="type-list">

                    <?php if ($typeDistribution['labels'] !== []): ?>

                        <?php foreach (
                            $typeDistribution['labels']
                            as $idx => $label
                        ): ?>

                            <div class="type-row">

                                <span class="text-truncate">
                                    <?php echo e($label); ?>
                                </span>

                                <strong>
                                    <?php echo e(
                                        (string) (
                                            $typeDistribution['totals'][$idx]
                                            ?? 0
                                        )
                                    ); ?>
                                </strong>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="empty-mini">
                            No document type data available.
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

        <!-- =====================================================
             WEEKLY ACTIVITY
        ====================================================== -->

        <div class="col-lg-4">

            <div class="panel-card h-100">

                <div class="panel-heading">

                    <div>

                        <p class="panel-title mb-1">
                            Weekly Activity
                        </p>

                        <p class="panel-sub mb-0">
                            Documents received this week
                        </p>

                    </div>

                </div>

                <div class="chart-container chart-weekly">
                    <canvas
                        id="weeklyActivityChart"
                        role="img"
                        aria-label="Weekly activity chart"
                    ></canvas>
                </div>

            </div>

        </div>

        <!-- =====================================================
             CONFIDENTIALITY SUMMARY
        ====================================================== -->

        <div class="col-lg-4">

            <div class="panel-card h-100">

                <div class="panel-heading">

                    <div>

                        <p class="panel-title mb-1">
                            Confidentiality Summary
                        </p>

                        <p class="panel-sub mb-0">
                            Current document classification
                        </p>

                    </div>

                </div>

                <div class="confidentiality-list">

                    <?php foreach (
                        $confidentialitySummary
                        as $item
                    ): ?>

                        <div class="confidentiality-row">

                            <div class="d-flex align-items-center gap-2">

                                <span
                                    class="classification-dot classification-<?php echo e(
                                        strtolower(
                                            preg_replace(
                                                '/[^a-z0-9]+/i',
                                                '-',
                                                (string) $item['label']
                                            )
                                        )
                                    ); ?>"
                                ></span>

                                <span>
                                    <?php echo e(
                                        (string) $item['label']
                                    ); ?>
                                </span>

                            </div>

                            <strong>
                                <?php echo e(
                                    (string) $item['total']
                                ); ?>
                            </strong>

                        </div>

                    <?php endforeach; ?>

                    <?php if ($confidentialitySummary === []): ?>

                        <div class="empty-mini">
                            No confidentiality data available.
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

        <!-- =====================================================
             QUICK ACTIONS
        ====================================================== -->

           <?php if ($hasQuickActions): ?>
        <div class="col-lg-4">

            <div class="panel-card h-100">

                <div class="panel-heading">

                    <div>

                        <p class="panel-title mb-1">
                            Quick Actions
                        </p>

                        <p class="panel-sub mb-0">
                            Frequently used operations
                        </p>

                    </div>

                </div>

                <div class="quick-grid">

                    <?php if ($canManageDocuments): ?>

                        <a
                            class="quick-item"
                            href="<?php echo e(
                                app_url('document_management.php')
                            ); ?>"
                        >

                            <span class="q-icon q-icon-blue">
                                <i class="bi bi-file-earmark-plus"></i>
                            </span>

                            <span>New Document</span>

                        </a>

                        <a
                            class="quick-item"
                            href="<?php echo e(
                                app_url('document_management.php')
                            ); ?>"
                        >

                            <span class="q-icon q-icon-light-blue">
                                <i class="bi bi-upload"></i>
                            </span>

                            <span>Upload</span>

                        </a>

                    <?php endif; ?>

                    <?php if ($canRouteDocuments): ?>
                        <a
                            class="quick-item"
                            href="<?php echo e(
                                app_url('document_routing.php')
                            ); ?>"
                        >

                            <span class="q-icon q-icon-indigo">
                                <i class="bi bi-send"></i>
                            </span>

                            <span>Forward</span>

                        </a>
                    <?php endif; ?>

                    <?php if ($canViewPendingDocuments): ?>
                    <a
                        class="quick-item"
                        href="<?php echo e(
                            app_url('pending_documents.php')
                        ); ?>"
                    >

                        <span class="q-icon q-icon-orange">
                            <i class="bi bi-hourglass-split"></i>
                        </span>

                        <span>Pending</span>

                    </a>
                    <?php endif; ?>

                    <?php if ($canViewDrafts): ?>
                        <a
                            class="quick-item"
                            href="<?php echo e(
                                app_url('my_drafts.php')
                            ); ?>"
                        >

                            <span class="q-icon q-icon-yellow">
                                <i class="bi bi-pencil-square"></i>
                            </span>

                            <span>My Drafts</span>

                        </a>
                    <?php endif; ?>

                    <?php if ($canViewReports): ?>
                    <a
                        class="quick-item"
                        href="<?php echo e(
                            app_url('reports.php')
                        ); ?>"
                    >

                        <span class="q-icon q-icon-green">
                            <i class="bi bi-bar-chart-line"></i>
                        </span>

                        <span>Reports</span>

                    </a>
                    <?php endif; ?>

                    <?php if ($canViewArchive): ?>
                    <a
                        class="quick-item"
                        href="<?php echo e(
                            app_url('archive_documents.php')
                        ); ?>"
                    >

                        <span class="q-icon q-icon-gray">
                            <i class="bi bi-archive"></i>
                        </span>

                        <span>Archive</span>

                    </a>
                    <?php endif; ?>

                </div>

            </div>

        </div>
        <?php endif; ?>

        <!-- =====================================================
             RECENT ACTIVITY
        ====================================================== -->

        <div class="col-lg-12">

            <div class="panel-card h-100">

                <div
                    class="d-flex justify-content-between align-items-center mb-1"
                >

                    <div>

                        <p class="panel-title mb-1">
                            Recent Activity
                        </p>

                        <p class="panel-sub mb-0">
                            Latest document actions
                        </p>

                    </div>

                    <a
                        href="<?php echo e(
                            app_url('document_tracking.php')
                        ); ?>"
                        class="small text-decoration-none"
                    >
                        View all
                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>

                <ul class="activity-list mt-3">

                    <?php foreach (
                        $recentActivity
                        as $activity
                    ): ?>

                        <?php

                        $statusName = (string) (
                            $activity['status_name']
                            ?? 'Updated'
                        );

                        $actor = trim(
                            (string) (
                                $activity['first_name']
                                ?? ''
                            )
                            . ' '
                            .
                            (string) (
                                $activity['last_name']
                                ?? ''
                            )
                        );

                        $trackingNo = (string) (
                            $activity['tracking_no']
                            ?? ''
                        );

                        $note = (string) (
                            $activity['change_note']
                            ?? 'Updated document status.'
                        );

                        $changedAt = (string) (
                            $activity['changed_at']
                            ?? ''
                        );

                        ?>

                        <li class="activity-item">

                            <span
                                class="activity-dot <?php echo e(
                                    $statusClass($statusName)
                                ); ?>"
                            >
                                <i class="bi bi-check2"></i>
                            </span>

                            <div class="activity-content">

                                <div class="small">

                                    <strong>
                                        <?php echo e(
                                            $actor !== ''
                                                ? $actor
                                                : 'System'
                                        ); ?>
                                    </strong>

                                    <span>
                                        <?php echo e($note); ?>
                                    </span>

                                    <?php if ($trackingNo !== ''): ?>

                                        <span class="text-primary fw-semibold">
                                            <?php echo e($trackingNo); ?>
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <?php if ($changedAt !== ''): ?>

                                    <div class="activity-time">

                                        <?php echo e(
                                            date(
                                                'M j, Y, g:i A',
                                                strtotime($changedAt)
                                            )
                                        ); ?>

                                    </div>

                                <?php endif; ?>

                            </div>

                        </li>

                    <?php endforeach; ?>

                    <?php if ($recentActivity === []): ?>

                        <li class="small text-muted py-3">
                            <i class="bi bi-info-circle me-1"></i>
                            No recent activity found.
                        </li>

                    <?php endif; ?>

                </ul>

            </div>

        </div>

        <!-- =====================================================
             RECENT DOCUMENTS
        ====================================================== -->

        <div class="col-12">

            <div class="recent-table">

                <div
                    class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 p-3 border-bottom"
                >

                    <div>

                        <h2 class="h5 mb-0">
                            Recent Documents
                        </h2>

                        <small
                            id="documentFilterInfo"
                            class="text-muted"
                        ></small>

                    </div>

                    <a
                        href="<?php echo e(
                            app_url(
                                $canManageDocuments
                                    ? 'document_management.php'
                                    : 'document_tracking.php'
                            )
                        ); ?>"
                        class="small text-decoration-none"
                    >
                        View all
                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>

                <div class="table-responsive">

                    <table
                        class="table table-hover align-middle mb-0"
                        id="recentDocumentsTable"
                    >

                        <thead>

                            <tr>

                                <th class="ps-3">
                                    Tracking No.
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

                        <tbody id="recentDocumentsBody">

                            <?php foreach (
                                $recentDocuments
                                as $doc
                            ): ?>

                                <?php

                                $assignedTo = trim(
                                    (string) (
                                        $doc['assignee_first_name']
                                        ?? ''
                                    )
                                    . ' '
                                    .
                                    (string) (
                                        $doc['assignee_last_name']
                                        ?? ''
                                    )
                                );

                                $docStatus = (string) (
                                    $doc['status_name']
                                    ?? 'Unknown'
                                );

                                $docType = (string) (
                                    $doc['type_name']
                                    ?? ''
                                );

                                $docTitle = (string) (
                                    $doc['title']
                                    ?? ''
                                );

                                $trackingNo = (string) (
                                    $doc['tracking_no']
                                    ?? ''
                                );

                                $dateReceived = (string) (
                                    $doc['date_received']
                                    ?? ''
                                );

                                ?>

                                <tr
                                    data-type="<?php echo e(
                                        strtolower(
                                            trim($docType)
                                        )
                                    ); ?>"
                                    data-status="<?php echo e(
                                        strtolower(
                                            trim($docStatus)
                                        )
                                    ); ?>"
                                >

                                    <td class="ps-3">

                                        <span class="tracking-number">
                                            <?php echo e(
                                                $trackingNo !== ''
                                                    ? $trackingNo
                                                    : '—'
                                            ); ?>
                                        </span>

                                    </td>

                                    <td>

                                        <div class="document-title-cell">

                                            <?php echo e(
                                                $docTitle !== ''
                                                    ? $docTitle
                                                    : 'Untitled Document'
                                            ); ?>

                                        </div>

                                    </td>

                                    <td>

                                        <span class="badge bg-light text-dark border">
                                            <?php echo e(
                                                $docType !== ''
                                                    ? $docType
                                                    : 'Unspecified'
                                            ); ?>
                                        </span>

                                    </td>

                                    <td>

                                        <span
                                            class="badge <?php echo e(
                                                $statusClass($docStatus)
                                            ); ?>"
                                        >
                                            <?php echo e($docStatus); ?>
                                        </span>

                                    </td>

                                    <td>

                                        <?php echo e(
                                            $assignedTo !== ''
                                                ? $assignedTo
                                                : 'Unassigned'
                                        ); ?>

                                    </td>

                                    <td class="pe-3 text-muted">

                                        <?php if ($dateReceived !== ''): ?>

                                            <?php echo e(
                                                date(
                                                    'M j, Y',
                                                    strtotime($dateReceived)
                                                )
                                            ); ?>

                                        <?php else: ?>

                                            —

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            <tr
                                id="noDocumentResults"
                                class="d-none"
                            >

                                <td
                                    colspan="6"
                                    class="text-center text-muted py-4"
                                >

                                    <i
                                        class="bi bi-search fs-4 d-block mb-2"
                                    ></i>

                                    No documents match your search or filter.

                                </td>

                            </tr>

                            <?php if ($recentDocuments === []): ?>

                                <tr id="noDocumentsAvailable">

                                    <td
                                        colspan="6"
                                        class="text-center text-muted py-4"
                                    >

                                        <i
                                            class="bi bi-folder2-open fs-4 d-block mb-2"
                                        ></i>

                                        No recent documents found.

                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"
></script>

<script
    src="<?php echo e(app_url('assets/js/features/dashboard/dashboard.js')); ?>?v=20261012a"
    defer
></script>

<?php
require dirname(__DIR__, 2) . '/includes/footer.php';
?>
