<?php
declare(strict_types=1);

if (!function_exists('normalize_theme_preference')) {
    require_once __DIR__ . '/../functions/theme.php';
}

if (!function_exists('user_get_message_recipients')) {
    require_once __DIR__ . '/../functions/messages.php';
}

$appConfig = get_app_config();
$currentUser = auth_user();
$currentUserId = (int) ($currentUser['user_id'] ?? 0);
$themePreference = normalize_theme_preference((string) ($currentUser['theme_preference'] ?? 'light'));
$messageRecipients = $currentUserId > 0 ? user_get_message_recipients($currentUserId) : [];
$recentMessages = $currentUserId > 0 ? user_get_recent_messages_for_user($currentUserId, 8) : [];
$unreadMessageCount = $currentUserId > 0 ? user_count_unread_messages($currentUserId) : 0;
$requestPath = parse_url(
    (string) ($_SERVER['REQUEST_URI'] ?? ''),
    PHP_URL_PATH
);
$currentPage = basename(
    is_string($requestPath) && $requestPath !== ''
        ? $requestPath
        : (string) ($_SERVER['PHP_SELF'] ?? '')
);

$isActive = static function (array $pages) use ($currentPage): string {
    return in_array($currentPage, $pages, true) ? 'is-active' : '';
};
?>
<!doctype html>
<html
    lang="en"
    data-theme="<?php echo e($themePreference); ?>"
    data-theme-user-id="<?php echo e((string) ($currentUser['user_id'] ?? 0)); ?>"
    data-theme-preference-endpoint="<?php echo e(app_url('pages/dashboard/routes/theme_preference.php')); ?>"
    data-theme-csrf="<?php echo e(csrf_token('theme_preference')); ?>"
>

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <script src="<?php echo e(app_url('assets/js/features/global/dark-mode.js')); ?>?v=20261009a"></script>

    <title>
        <?php echo e($appConfig['name']); ?>
    </title>

    <link
        rel="icon"
        type="image/png"
        href="<?php echo e(app_url('assets/logo/CLO_LOGO.png')); ?>"
    >

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <?php
    $pageCss = '';
    if (isset($currentPage) && is_string($currentPage)) {
        $pageCssMap = [
            'dashboard.php' => 'dashboard/dashboard.css',
            'audit_trail.php' => 'reports/audit-trail.css',
            'reports.php' => 'reports/reports.css',
            'system_settings.php' => 'admin/system-settings.css',
            'document_tracking.php' => 'documents/document-tracking.css',
            'document_management.php' => 'documents/document-management.css',
            'document_routing.php' => 'documents/document-routing.css',
            'archive_documents.php' => 'documents/archive-documents.css',
            'department_management.php' => 'admin/department-management.css',
            'user_profile.php' => 'profile/user-profile.css',
            'pending_documents.php' => 'documents/pending-documents.css',
        ];
        $pageCss = $pageCssMap[$currentPage] ?? '';
    }
    ?>

    <?php if ($pageCss !== ''): ?>
        <link rel="stylesheet" href="<?php echo e(app_url('assets/css/features/' . $pageCss)); ?>?v=20261012d">
    <?php endif; ?>

    <link rel="stylesheet" href="<?php echo e(app_url('assets/css/features/global/app-layout.css')); ?>?v=20261012j">
    <link rel="stylesheet" href="<?php echo e(app_url('assets/css/features/global/scrollbar.css')); ?>?v=20261006b">
    <link rel="stylesheet" href="<?php echo e(app_url('assets/css/features/global/dark-mode.css')); ?>?v=20261012j">
    <link rel="stylesheet" href="<?php echo e(app_url('assets/css/features/global/notifications.css')); ?>?v=20261012m">
    <link rel="stylesheet" href="<?php echo e(app_url('assets/css/features/global/page-ui-enhancements.css')); ?>?v=20261006a">

</head>

<body class="bg-light">

<?php if ($currentUser): ?>

    <?php

    $firstName =
        (string) (
            $currentUser['first_name']
            ?? 'User'
        );

    $lastName =
        (string) (
            $currentUser['last_name']
            ?? ''
        );

    $initials =
        strtoupper(
            substr($firstName, 0, 1)
            .
            substr($lastName, 0, 1)
        );

    $displayRole =
        str_replace(
            '_',
            ' ',
            (string) (
                $currentUser['role_name']
                ?? ''
            )
        );

    $pendingApprovals = [];
    $pendingApprovalCount = 0;
    $userNotifications = [];
    $userNotificationCount = 0;
    $userUnreadNotificationCount = 0;
    $deviceLoginRequests = [];

    if (auth_is_role('ADMIN')) {
        $pendingStmt = db_execute(
            'SELECT
                u.user_id,
                u.first_name,
                u.last_name,
                u.username,
                r.role_name,
                u.created_at
             FROM users u
             INNER JOIN roles r
                 ON r.role_id = u.role_id
                         WHERE u.is_active = 0
                             AND r.role_name <> ?
             ORDER BY u.created_at DESC
             LIMIT 10',
                        's',
                        ['ADMIN']
        );

        $pendingApprovals = db_fetch_all_assoc($pendingStmt);
        $pendingApprovalCount = count($pendingApprovals);
        $pendingStmt->close();
    }

    $currentUserId = (int) ($currentUser['user_id'] ?? 0);
    $isCurrentUserAdmin = auth_is_role('ADMIN');
    if ($currentUserId > 0) {
        $deviceLoginStmt = db_execute(
            'SELECT request_id, requested_at, u.first_name, u.last_name
             FROM login_device_requests ldr
             INNER JOIN users u ON u.user_id = ldr.user_id
             WHERE ldr.user_id = ?
               AND ldr.status = "PENDING"
               AND ldr.expires_at > NOW()
             ORDER BY ldr.requested_at DESC
             LIMIT 5',
            'i',
            [$currentUserId]
        );
        $deviceLoginRequests = db_fetch_all_assoc($deviceLoginStmt);
        $deviceLoginStmt->close();

        $notificationStmt = db_execute(
            'SELECT
                al.audit_log_id,
                al.entity_type,
                al.entity_id,
                al.action,
                al.created_at,
                d.title AS document_title,
                d.tracking_no,
                u.first_name AS actor_first_name,
                u.last_name AS actor_last_name,
                d.current_assignee_user_id,
                d.created_by_user_id,
                dr.routed_to_user_id,
                     dr.routed_by_user_id,
                     nr.read_at AS notification_read_at
             FROM audit_logs al
                 LEFT JOIN documents d
                     ON d.document_id = al.entity_id
                 LEFT JOIN document_routes dr
                     ON dr.document_route_id = CAST(JSON_UNQUOTE(JSON_EXTRACT(al.new_values, "$.route_id")) AS UNSIGNED)
                 LEFT JOIN document_assignment_receipts dar
                     ON dar.document_id = d.document_id
                    AND dar.user_id = d.current_assignee_user_id
                    AND dar.accepted_at IS NULL
                      LEFT JOIN notification_reads nr
                     ON nr.audit_log_id = al.audit_log_id
                    AND nr.user_id = ?
             LEFT JOIN users u
                ON u.user_id = al.user_id
                                 WHERE UPPER(al.entity_type) = ?
                             AND (
                                (UPPER(al.action) = "ROUTED" AND dr.routed_to_user_id = ?)
                                OR (UPPER(al.action) = "RECEIVED" AND dr.routed_by_user_id = ?)
                                OR (
                                    UPPER(al.action) = "ACCESS_REQUEST"
                                    AND (
                                        d.current_assignee_user_id = ?
                                        OR ? = 1
                                    )
                                )
                                OR (UPPER(al.action) = "ASSIGNED" AND dar.user_id = ?)
                             )
                             AND (
                                 UPPER(al.action) <> "ACCESS_REQUEST"
                                 OR NOT EXISTS (
                                     SELECT 1
                                     FROM document_access_grants dag
                                     WHERE dag.document_id = al.entity_id
                                       AND dag.user_id = al.user_id
                                 )
                             )
                             AND (
                                 UPPER(al.action) <> "ACCESS_REQUEST"
                                 OR NOT EXISTS (
                                     SELECT 1
                                     FROM audit_logs status_log
                                     WHERE status_log.entity_type = al.entity_type
                                       AND status_log.entity_id = al.entity_id
                                       AND status_log.action IN ("ACCESS_REQUEST_CANCELLED", "ACCESS_REVOKED", "ACCESS_REQUEST_REJECTED")
                                       AND JSON_UNQUOTE(JSON_EXTRACT(status_log.new_values, \'$.request_id\')) = CAST(al.audit_log_id AS CHAR)
                                 )
                             )
             GROUP BY al.audit_log_id
             ORDER BY al.created_at DESC
             LIMIT 10',
                            'isiiiii',
                            [$currentUserId, 'DOCUMENT', $currentUserId, $currentUserId, $currentUserId, (int) $isCurrentUserAdmin, $currentUserId]
        );

        $userNotifications = db_fetch_all_assoc($notificationStmt);
        $userNotificationCount = count($userNotifications);
        $userUnreadNotificationCount = count(array_filter(
            $userNotifications,
            static fn (array $notification): bool => empty($notification['notification_read_at'])
        ));
        $notificationStmt->close();
    }

    $totalNotificationCount = $userUnreadNotificationCount + $pendingApprovalCount + count($deviceLoginRequests);

    ?>

    <div class="d-flex app-shell">

        <!-- SIDEBAR -->

        <aside
            class="app-sidebar is-collapsed"
            id="appSidebar"
        >

            <button
                type="button"
                class="app-brand sidebar-collapse-toggle"
                id="sidebarCollapseToggle"
                aria-label="Expand navigation"
                aria-expanded="false"
            >

                <div class="logo-dot">
                    <img
                        src="<?php echo e(app_url('assets/logo/CLO_LOGO.png')); ?>"
                        alt="City Legal Office logo"
                    >
                </div>

                <div>

                    <div class="brand-top">
                        City Legal Office
                    </div>

                    <div class="brand-sub">
                        Document Tracking
                    </div>

                </div>

            </button>

            <nav class="sidebar-nav">

                <?php if (auth_can_access_page('dashboard.php')): ?>
                    <a
                        href="<?php echo e(app_url('dashboard.php')); ?>"
                        class="sidebar-item <?php echo e(
                            $isActive(['dashboard.php'])
                        ); ?>"
                    >

                        <i class="bi bi-grid-1x2"></i>

                        <span>Dashboard</span>

                    </a>
                <?php endif; ?>

            </nav>

            <?php if (
                auth_can_access_page('all_documents.php')
                || auth_can_access_page('document_management.php')
                || auth_can_access_page('document_routing.php')
                || auth_can_access_page('document_tracking.php')
                || auth_can_access_page('pending_documents.php')
                || auth_can_access_page('pending_access_requests.php')
                || auth_can_access_page('reports.php')
            ): ?>

                <div class="menu-label">
                    Documents
                </div>

                <nav class="sidebar-nav">

                <?php if (auth_can_access_page('all_documents.php')): ?>

                    <!-- All Documents -->

                    <a
                        href="<?php echo e(
                            app_url('all_documents.php')
                        ); ?>"
                        class="sidebar-item <?php echo e(
                            $isActive([
                                'all_documents.php'
                            ])
                        ); ?>"
                    >

                        <i class="bi bi-folder2-open"></i>

                        <span>All Documents</span>

                    </a>

                <?php endif; ?>

                <?php if (auth_can_access_page('document_management.php')): ?>

                    <!-- Document Management -->

                    <a
                        href="<?php echo e(
                            app_url('document_management.php')
                        ); ?>"
                        class="sidebar-item <?php echo e(
                            $isActive([
                                'document_management.php'
                            ])
                        ); ?>"
                    >

                        <i class="bi bi-clipboard-data"></i>

                        <span>Document Management</span>

                    </a>

                <?php endif; ?>

                <?php if (auth_can_access_page('document_routing.php')): ?>

                    <!-- Document Routing -->

                    <a
                        href="<?php echo e(
                            app_url('document_routing.php')
                        ); ?>"
                        class="sidebar-item <?php echo e(
                            $isActive([
                                'document_routing.php'
                            ])
                        ); ?>"
                    >

                        <i class="bi bi-signpost-2"></i>

                        <span>Document Routing</span>

                    </a>

                <?php endif; ?>

                <!-- Document Tracking -->

                <?php if (auth_can_access_page('document_tracking.php')): ?>
                    <a
                        href="<?php echo e(
                            app_url('document_tracking.php')
                        ); ?>"
                        class="sidebar-item <?php echo e(
                            $isActive([
                                'document_tracking.php'
                            ])
                        ); ?>"
                    >

                        <i class="bi bi-geo-alt"></i>

                        <span>Document Tracking</span>

                    </a>
                <?php endif; ?>

                <!-- Pending Documents -->

                <?php if (auth_can_access_page('pending_documents.php')): ?>
                    <a
                        href="<?php echo e(
                            app_url('pending_documents.php')
                        ); ?>"
                        class="sidebar-item <?php echo e(
                            $isActive([
                                'pending_documents.php'
                            ])
                        ); ?>"
                    >

                        <i class="bi bi-hourglass-split"></i>

                        <span>Pending Documents</span>

                    </a>
                <?php endif; ?>

                <?php if (auth_can_access_page('pending_access_requests.php')): ?>
                    <a
                        href="<?php echo e(app_url('pending_access_requests.php')); ?>"
                        class="sidebar-item <?php echo e($isActive(['pending_access_requests.php'])); ?>"
                    >
                        <i class="bi bi-shield-lock"></i>
                        <span>Access Requests</span>
                    </a>
                <?php endif; ?>

                <!-- Reports -->

                <?php if (auth_can_access_page('reports.php')): ?>
                    <a
                        href="<?php echo e(
                            app_url('reports.php')
                        ); ?>"
                        class="sidebar-item <?php echo e(
                            $isActive([
                                'reports.php'
                            ])
                        ); ?>"
                    >

                        <i class="bi bi-bar-chart-line"></i>

                        <span>Reports</span>

                    </a>
                <?php endif; ?>

                </nav>

            <?php endif; ?>

            <?php if (auth_can_access_page('archive_documents.php')): ?>

                <nav class="sidebar-nav">

                    <a
                        href="<?php echo e(app_url('archive_documents.php')); ?>"
                        class="sidebar-item <?php echo e(
                            $isActive(['archive_documents.php'])
                        ); ?>"
                    >

                        <i class="bi bi-archive"></i>

                        <span>Archive</span>

                    </a>

                </nav>

            <?php endif; ?>

            <?php if (
                auth_can_access_page('admin.php')
                || auth_can_access_page('role_management.php')
                || auth_can_access_page('document_type_management.php')
                || auth_can_access_page('department_management.php')
            ): ?>

                <div class="menu-label">
                    Management
                </div>

                <nav class="sidebar-nav">

                <!-- User Management -->

                <?php if (auth_can_access_page('admin.php')): ?>

                    <a
                        href="<?php echo e(
                            app_url('admin.php')
                        ); ?>"
                        class="sidebar-item <?php echo e(
                            $isActive(['admin.php'])
                        ); ?>"
                    >

                        <i class="bi bi-people"></i>

                        <span>User Management</span>

                    </a>

                <?php endif; ?>

                <?php if (auth_can_access_page('role_management.php')): ?>
                    <a
                        href="<?php echo e(
                            app_url('role_management.php')
                        ); ?>"
                        class="sidebar-item <?php echo e(
                            $isActive(['role_management.php'])
                        ); ?>"
                    >

                        <i class="bi bi-shield-lock"></i>

                        <span>Roles</span>

                    </a>
                <?php endif; ?>

                <!-- Document Type Management -->

                <?php if (auth_can_access_page('document_type_management.php')): ?>

                    <a
                        href="<?php echo e(
                            app_url('document_type_management.php')
                        ); ?>"
                        class="sidebar-item <?php echo e(
                            $isActive(['document_type_management.php'])
                        ); ?>"
                    >

                        <i class="bi bi-file-earmark-text"></i>

                        <span>Document Types</span>

                    </a>

                <?php endif; ?>

                <!-- Department Management -->

                <?php if (auth_can_access_page('department_management.php')): ?>

                    <a
                        href="<?php echo e(
                            app_url('department_management.php')
                        ); ?>"
                        class="sidebar-item <?php echo e(
                            $isActive([
                                'department_management.php'
                            ])
                        ); ?>"
                    >

                        <i class="bi bi-building"></i>

                        <span>Department Management</span>

                    </a>

                <?php endif; ?>

                </nav>

            <?php endif; ?>

            <?php if (auth_can_access_page('audit_trail.php') || auth_can_access_page('system_settings.php')): ?>

                <div class="menu-label">
                    System
                </div>

                <nav class="sidebar-nav">

                    <?php if (auth_can_access_page('audit_trail.php')): ?>
                        <a
                            href="<?php echo e(app_url('audit_trail.php')); ?>"
                            class="sidebar-item <?php echo e(
                                $isActive([
                                    'audit_trail.php'
                                ])
                            ); ?>"
                        >

                            <i class="bi bi-shield-check"></i>

                            <span>Audit Trail</span>

                        </a>
                    <?php endif; ?>

                    <?php if (auth_can_access_page('system_settings.php')): ?>
                        <a
                            href="<?php echo e(
                                app_url('system_settings.php')
                            ); ?>"
                            class="sidebar-item <?php echo e(
                                $isActive([
                                    'system_settings.php'
                                ])
                            ); ?>"
                        >

                            <i class="bi bi-gear"></i>

                            <span>Settings</span>

                        </a>
                    <?php endif; ?>

                </nav>

            <?php endif; ?>

            <div class="sidebar-spacer"></div>

        </aside>

        <!-- MOBILE OVERLAY -->

        <div
            class="mobile-overlay"
            id="mobileSidebarOverlay"
        ></div>

        <!-- MAIN -->

        <main class="app-main">

            <div class="mobile-topbar">

                <button
                    class="mobile-toggle"
                    type="button"
                    id="sidebarToggle"
                    aria-label="Toggle Sidebar"
                    aria-expanded="false"
                >

                    <i class="bi bi-list"></i>

                </button>

            </div>

<?php else: ?>

    <main class="container py-4">

<?php endif; ?>

<?php if ($msg = flash_get('success')): ?>

    <div
        class="alert alert-success alert-dismissible fade show"
        role="alert"
    >

        <?php echo e($msg); ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
            aria-label="Close"
        ></button>

    </div>

<?php endif; ?>

<?php if ($msg = flash_get('error')): ?>

    <div
        class="alert alert-danger alert-dismissible fade show"
        role="alert"
    >

        <?php echo e($msg); ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
            aria-label="Close"
        ></button>

    </div>

<?php endif; ?>

<?php foreach (toast_get_all() as $toast): ?>
    <?php $toastMessage = (string) ($toast['message'] ?? ''); ?>
    <?php $toastType = (string) ($toast['type'] ?? 'info'); ?>
    <?php if ($toastMessage !== ''): ?>
        <div
            class="app-toast app-toast-<?php echo e($toastType); ?>"
            role="status"
            data-toast-duration="<?php echo e((string) ($toast['duration'] ?? 5000)); ?>"
        >
            <div class="app-toast-icon">
                <?php if ($toastType === 'success'): ?>
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <?php elseif ($toastType === 'warning'): ?>
                    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                <?php elseif ($toastType === 'error'): ?>
                    <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
                <?php else: ?>
                    <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                <?php endif; ?>
            </div>
            <div class="app-toast-text"><?php echo e($toastMessage); ?></div>
            <button type="button" class="app-toast-close" aria-label="Close notification">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php if ($currentUser): ?>
    <div
        class="floating-message-widget"
        aria-live="polite"
        data-message-endpoint="<?php echo e(app_url('pages/dashboard/routes/message_send.php')); ?>"
        data-message-thread-endpoint="<?php echo e(app_url('pages/dashboard/routes/message_thread.php')); ?>"
        data-message-recent-endpoint="<?php echo e(app_url('pages/dashboard/routes/message_recent.php')); ?>"
        data-message-read-endpoint="<?php echo e(app_url('pages/dashboard/routes/message_read.php')); ?>"
        data-message-delete-endpoint="<?php echo e(app_url('pages/dashboard/routes/message_delete.php')); ?>"
        data-message-typing-endpoint="<?php echo e(app_url('pages/dashboard/routes/message_typing.php')); ?>"
        data-message-presence-endpoint="<?php echo e(app_url('pages/dashboard/routes/message_presence.php')); ?>"
        data-message-csrf="<?php echo e(csrf_token('message_send')); ?>"
        data-current-user-id="<?php echo e((string) $currentUserId); ?>"
        data-current-user-avatar="<?php echo e(app_url((string) ($currentUser['profile_picture'] ?? ''))); ?>"
        data-app-base-url="<?php echo e(app_url()); ?>"
    >
        <button
            type="button"
            class="floating-message-button"
            id="floatingMessageButton"
            aria-label="Open messages"
            title="Messages"
        >
            <i class="bi bi-chat-dots-fill" aria-hidden="true"></i>
            <span class="floating-message-badge"><?php echo e((string) $unreadMessageCount); ?></span>
        </button>

        <div class="floating-message-panel" id="floatingMessagePanel" hidden>
            <div class="floating-message-header">
                <span>Inbox</span>
                <div class="floating-message-header-actions">
                    <button type="button" class="floating-message-new" id="floatingMessageNew" aria-label="New message">
                        New message
                    </button>
                    <button type="button" class="floating-message-close" id="floatingMessageClose" aria-label="Close messages">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="floating-message-shell">
                <div class="floating-message-sidebar">
                    <div class="floating-message-search-wrap">
                        <input
                            type="search"
                            id="floatingMessageSearch"
                            class="floating-message-search"
                            placeholder="Search conversations"
                            aria-label="Search conversations"
                        >
                    </div>

                    <form id="floatingMessageNewComposer" class="floating-message-new-composer" hidden>
                        <label class="floating-message-field">
                            <span>To</span>
                            <select id="floatingMessageNewRecipient" name="recipient_id" required>
                                <?php if ($messageRecipients === []): ?>
                                    <option value="">No users available</option>
                                <?php else: ?>
                                    <option value="">Select user</option>
                                    <?php foreach ($messageRecipients as $recipient): ?>
                                        <?php $recipientName = trim((string) (($recipient['first_name'] ?? '') . ' ' . ($recipient['last_name'] ?? ''))); ?>
                                        <?php if ($recipientName === ''): ?>
                                            <?php $recipientName = 'User'; ?>
                                        <?php endif; ?>
                                        <option value="<?php echo e((string) ($recipient['user_id'] ?? '')); ?>" data-display-name="<?php echo e($recipientName); ?>"><?php echo e($recipientName); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </label>

                        <label class="floating-message-field">
                            <span>Message</span>
                            <textarea
                                id="floatingMessageNewInput"
                                name="message"
                                rows="2"
                                maxlength="500"
                                placeholder="Type a message..."
                                required
                            ></textarea>
                        </label>

                        <div class="floating-message-new-actions">
                            <button type="button" class="floating-message-cancel" id="floatingMessageNewCancel">Cancel</button>
                            <button type="submit" class="floating-message-send">Send</button>
                        </div>
                    </form>

                    <div class="floating-message-list">
                        <?php if ($recentMessages === []): ?>
                            <div class="floating-message-item is-empty">
                                <strong>No messages yet</strong>
                                <small>Your conversations will appear here.</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentMessages as $messageRow): ?>
                                <?php
                                    $senderId = (int) ($messageRow['sender_id'] ?? 0);
                                    $recipientId = (int) ($messageRow['recipient_id'] ?? 0);
                                    $otherUserId = $senderId === $currentUserId ? $recipientId : $senderId;
                                    $otherFirstName = trim((string) (($senderId === $currentUserId ? ($messageRow['recipient_first_name'] ?? '') : ($messageRow['sender_first_name'] ?? ''))));
                                    $otherLastName = trim((string) (($senderId === $currentUserId ? ($messageRow['recipient_last_name'] ?? '') : ($messageRow['sender_last_name'] ?? ''))));
                                    $senderDisplay = $otherFirstName !== '' || $otherLastName !== '' ? trim($otherFirstName . ' ' . $otherLastName) : 'User';
                                    $isUnread = ((int) ($messageRow['recipient_id'] ?? 0) === $currentUserId && ($messageRow['read_at'] ?? null) === null);
                                    $avatarFile = ($senderId === $currentUserId)
                                        ? ((string) ($messageRow['recipient_profile_picture'] ?? ''))
                                        : ((string) ($messageRow['sender_profile_picture'] ?? ''));
                                    if ($avatarFile === '') {
                                        $avatarFile = (string) (($senderId === $currentUserId) ? ($messageRow['recipient_profile_picture'] ?? '') : ($messageRow['sender_profile_picture'] ?? ''));
                                    }
                                    $avatarLetters = 'U';
                                    $nameParts = preg_split('/\s+/', $senderDisplay);
                                    $conversationInitials = '';
                                    foreach ($nameParts as $namePart) {
                                        $cleanPart = preg_replace('/[^A-Za-z]/', '', (string) $namePart);
                                        if ($cleanPart !== '') {
                                            $conversationInitials .= strtoupper(mb_substr($cleanPart, 0, 1, 'UTF-8'));
                                        }
                                    }
                                    $avatarLetters = $conversationInitials !== '' ? $conversationInitials : 'U';
                                    $avatarLetters = mb_substr($avatarLetters, 0, 2, 'UTF-8');
                                ?>
                                <?php
                                    $replyTargetId = (int) ($messageRow['sender_id'] ?? 0);
                                    if ($replyTargetId === $currentUserId) {
                                        $replyTargetId = (int) ($messageRow['recipient_id'] ?? 0);
                                    }
                                    $conversationUnreadCount = $replyTargetId > 0 ? user_get_conversation_unread_count($currentUserId, $replyTargetId) : 0;
                                ?>
                                <div
                                    class="floating-message-item<?php echo $isUnread ? ' is-unread' : ''; ?>"
                                    tabindex="0"
                                    role="button"
                                    data-message-id="<?php echo e((string) ($messageRow['message_id'] ?? '')); ?>"
                                    data-message-sender="<?php echo e($senderDisplay); ?>"
                                    data-message-body="<?php echo e((string) ($messageRow['message'] ?? '')); ?>"
                                    data-message-recipient-id="<?php echo e((string) $replyTargetId); ?>"
                                    data-message-time="<?php echo e((string) ($messageRow['created_at'] ?? '')); ?>"
                                    aria-label="Read message from <?php echo e($senderDisplay); ?>"
                                >
                                    <div class="floating-message-item-row">
                                        <div class="floating-message-avatar" aria-hidden="true">
                                            <?php if ($avatarFile !== ''): ?>
                                                <img src="<?php echo e(app_url($avatarFile)); ?>" alt="Profile picture">
                                            <?php else: ?>
                                                <?php echo e($avatarLetters); ?>
                                            <?php endif; ?>
                                            <span class="floating-message-online-indicator" hidden></span>
                                        </div>
                                        <div class="floating-message-item-copy">
                                            <strong><?php echo e($senderDisplay); ?></strong>
                                            <small><?php echo e((string) ($messageRow['message'] ?? '')); ?></small>
                                        </div>
                                        <div class="floating-message-item-meta">
                                            <time class="floating-message-item-time"><?php echo e(message_format_timestamp((string) ($messageRow['created_at'] ?? ''))); ?></time>
                                            <?php if ($conversationUnreadCount > 0): ?>
                                                <span class="floating-message-item-count"><?php echo e((string) $conversationUnreadCount); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="floating-message-detail" id="floatingMessageDetail" hidden>
                    <div class="floating-message-detail-header">
                        <button type="button" class="floating-message-back" id="floatingMessageBack" aria-label="Back to conversations">
                            <i class="bi bi-arrow-left" aria-hidden="true"></i>
                            <span>Back</span>
                        </button>
                        <strong id="floatingMessageDetailSender">Message</strong>
                        <span class="floating-message-online-status" id="floatingMessageOnlineStatus" role="status" hidden>
                            <span class="floating-message-online-indicator" aria-hidden="true"></span>
                            <span>Online</span>
                        </span>
                        <button type="button" class="floating-message-clear" id="floatingMessageClear" hidden>
                            Clear conversation
                        </button>
                    </div>
                    <div class="floating-message-clear-options" id="floatingMessageClearOptions" role="group" aria-label="Choose who to clear the conversation for" hidden>
                        <span>Clear conversation for:</span>
                        <button type="button" class="floating-message-clear-scope" data-clear-scope="me">Just me</button>
                        <button type="button" class="floating-message-clear-scope" data-clear-scope="everyone">Everyone</button>
                        <button type="button" class="floating-message-clear-cancel">Cancel</button>
                    </div>
                    <div id="floatingMessageThread" class="floating-message-thread"></div>
                    <div id="floatingMessageTypingIndicator" class="floating-message-typing" role="status" aria-live="polite" hidden></div>
                    <div id="floatingMessageDetailBody" class="floating-message-thread-empty">Select a conversation to read it.</div>

                    <form id="floatingMessageReplyForm" class="floating-message-reply-form" hidden>
                        <div class="floating-message-field">
                            <div class="floating-message-composer">
                                <textarea
                                    id="floatingMessageReplyInput"
                                    rows="2"
                                    maxlength="500"
                                    placeholder="Write a reply..."
                                    aria-label="Write a reply"
                                    required
                                ></textarea>
                                <button type="submit" class="floating-message-send" aria-label="Send reply">
                                    <i class="bi bi-send-fill" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Bootstrap JS
     IMPORTANT:
     Only load Bootstrap JS here.
     footer.php should NOT load it again.
-->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>

<!-- SIDEBAR MOBILE SCRIPT -->

<script src="<?php echo app_url('assets/js/features/global/header-init.js'); ?>?v=20261006"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toasts = document.querySelectorAll('.app-toast');

        toasts.forEach(function (toast) {
            const closeButton = toast.querySelector('.app-toast-close');
            const duration = Number(toast.dataset.toastDuration || 5000);

            const hideToast = function () {
                toast.classList.add('is-hidden');
                setTimeout(function () {
                    toast.remove();
                }, 220);
            };

            if (closeButton) {
                closeButton.addEventListener('click', hideToast);
            }

            window.setTimeout(hideToast, duration);
        });

        const messageWidget = document.querySelector('.floating-message-widget');
        const recentMessagesEndpoint = messageWidget ? (messageWidget.dataset.messageRecentEndpoint || '') : '';
        const messageDeleteEndpoint = messageWidget ? (messageWidget.dataset.messageDeleteEndpoint || '') : '';
        const messageTypingEndpoint = messageWidget ? (messageWidget.dataset.messageTypingEndpoint || '') : '';
        const messagePresenceEndpoint = messageWidget ? (messageWidget.dataset.messagePresenceEndpoint || '') : '';
        let lastKnownUnreadCount = Number(document.querySelector('.floating-message-badge')?.textContent || '0');
        let activeConversationUserId = null;
        let activeThreadStateSignature = '';
        let messagePollInProgress = false;
        let threadLoadSequence = 0;
        let typingHeartbeatTimer = null;
        let typingStopTimer = null;
        let typingTargetId = null;
        let typingPollInProgress = false;

        const escapeHtml = function (value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        const renderRecentMessageList = function (messages) {
            const list = document.querySelector('.floating-message-list');
            if (!list) {
                return;
            }

            const previousScrollTop = list.scrollTop;
            list.style.opacity = '0.68';
            requestAnimationFrame(function () {
                list.innerHTML = '';
                if (!Array.isArray(messages) || messages.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'floating-message-item is-empty';
                    empty.innerHTML = '<strong>No messages yet</strong><small>Your conversations will appear here.</small>';
                    list.appendChild(empty);
                    list.style.opacity = '1';
                    return;
                }

                messages.forEach(function (message) {
                    const senderId = Number(message.sender_id || 0);
                    const recipientId = Number(message.recipient_id || 0);
                    const otherUserId = senderId === currentUserId ? recipientId : senderId;
                    const otherFirstName = (senderId === currentUserId ? (message.recipient_first_name || '') : (message.sender_first_name || '')).trim();
                    const otherLastName = (senderId === currentUserId ? (message.recipient_last_name || '') : (message.sender_last_name || '')).trim();
                    const senderDisplay = [otherFirstName, otherLastName].filter(Boolean).join(' ') || 'User';
                    const text = String(message.message || '');
                    const conversationUnreadCount = Number(message.conversation_unread_count || 0);
                    const isUnread = conversationUnreadCount > 0 || (recipientId === currentUserId && !message.read_at);
                    const item = document.createElement('div');
                    item.className = 'floating-message-item' + (isUnread ? ' is-unread' : '');
                    item.setAttribute('tabindex', '0');
                    item.setAttribute('role', 'button');
                    item.dataset.messageId = message.message_id || '';
                    item.dataset.messageSender = senderDisplay;
                    item.dataset.messageBody = text;
                    item.dataset.messageRecipientId = String(otherUserId || '');
                    item.dataset.messageTime = message.created_at || new Date().toISOString();
                    item.classList.toggle('is-active', Number(otherUserId) === Number(activeConversationUserId));

                    const avatarText = senderDisplay.split(/\s+/).filter(Boolean).slice(0, 2).map(function (part) {
                        return part.charAt(0).toUpperCase();
                    }).join('') || 'U';
                    const avatarMarkup = avatarText + '<span class="floating-message-online-indicator"' + (messageOnlineUserIds.has(otherUserId) ? '' : ' hidden') + '></span>';
                    const countMarkup = conversationUnreadCount > 0 ? '<span class="floating-message-item-count">' + conversationUnreadCount + '</span>' : '';
                    const safeSenderDisplay = escapeHtml(senderDisplay);
                    const safeText = escapeHtml(text);

                    item.innerHTML = '<div class="floating-message-item-row"><div class="floating-message-avatar" aria-hidden="true">' + avatarMarkup + '</div><div class="floating-message-item-copy"><strong>' + safeSenderDisplay + '</strong><small>' + safeText + '</small></div><div class="floating-message-item-meta"><time class="floating-message-item-time">' + formatThreadTime(message.created_at || new Date().toISOString()) + '</time>' + countMarkup + '</div></div>';
                    if (messageSearch && messageSearch.value.trim() && !item.textContent.toLowerCase().includes(messageSearch.value.trim().toLowerCase())) {
                        item.hidden = true;
                    }
                    item.addEventListener('click', function () {
                        openMessage(item);
                    });
                    list.appendChild(item);
                });

                list.scrollTop = previousScrollTop;
                requestAnimationFrame(function () {
                    list.style.opacity = '1';
                });
            });
        };

        const showMessageNotification = function (sender, text) {
            const badge = document.querySelector('.floating-message-badge');
            const previewText = (text || '').trim();
            const senderName = sender && sender.trim() ? sender.trim() : 'New message';
            const popup = document.createElement('div');
            popup.className = 'message-popup-toast';
            popup.setAttribute('role', 'status');
            popup.innerHTML = '<div class="message-popup-avatar">' + escapeHtml(senderName.charAt(0).toUpperCase() || 'M') + '</div><div class="message-popup-content"><div class="message-popup-title">' + escapeHtml(senderName) + '</div><div class="message-popup-preview">' + (previewText ? escapeHtml(previewText) : 'You have a new message.') + '</div></div><button type="button" class="message-popup-close" aria-label="Close message"><i class="bi bi-x-lg" aria-hidden="true"></i></button>';
            document.body.appendChild(popup);

            const closeButton = popup.querySelector('.message-popup-close');
            if (closeButton) {
                closeButton.addEventListener('click', function () {
                    popup.classList.add('is-hidden');
                    setTimeout(function () {
                        popup.remove();
                    }, 220);
                });
            }

            setTimeout(function () {
                if (document.body.contains(popup)) {
                    popup.classList.add('is-hidden');
                    setTimeout(function () {
                        popup.remove();
                    }, 200);
                }
            }, 1800);

            if (badge) {
                badge.textContent = String(Math.max(0, Number(badge.textContent || '0')));
            }
        };

        const isActiveConversationVisible = function () {
            const messagePanel = document.getElementById('floatingMessagePanel');
            const messageDetail = document.getElementById('floatingMessageDetail');
            return Boolean(
                activeConversationUserId
                && messagePanel
                && !messagePanel.hasAttribute('hidden')
                && messageDetail
                && !messageDetail.hidden
            );
        };

        const pollIncomingMessages = function () {
            if (!recentMessagesEndpoint || document.visibilityState !== 'visible') {
                return;
            }
            if (messagePollInProgress) {
                window.setTimeout(pollIncomingMessages, 250);
                return;
            }

            messagePollInProgress = true;
            fetch(recentMessagesEndpoint, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (response) {
                if (!response.ok) {
                    return null;
                }

                return response.json().catch(function () { return null; });
            })
            .then(function (result) {
                if (!result || !result.success) {
                    return;
                }

                const unreadCount = Number(result.unread_count || 0);
                const badge = document.querySelector('.floating-message-badge');
                if (badge) {
                    badge.textContent = String(unreadCount);
                }

                if (Array.isArray(result.messages)) {
                    const messages = result.messages;
                    const latestMessage = result.messages[0];
                    const activePartnerId = Number(activeConversationUserId || 0);
                    const activeLatestMessage = activePartnerId > 0
                        ? messages.find(function (message) {
                            const senderId = Number(message.sender_id || 0);
                            const recipientId = Number(message.recipient_id || 0);
                            return (senderId === activePartnerId && recipientId === currentUserId)
                                || (senderId === currentUserId && recipientId === activePartnerId);
                        })
                        : null;
                    const activeConversationVisible = isActiveConversationVisible();

                    if (latestMessage) {
                        const senderId = Number(latestMessage.sender_id || 0);
                        const recipientId = Number(latestMessage.recipient_id || 0);
                        const currentIncoming = senderId !== currentUserId && recipientId === currentUserId;

                        if (currentIncoming && unreadCount > lastKnownUnreadCount
                            && !(activeConversationVisible && senderId === activePartnerId)) {
                            const senderLabel = (latestMessage.sender_first_name || 'User').trim();
                            showMessageNotification(senderLabel, String(latestMessage.message || '').trim());
                        }
                    }

                    if (activePartnerId > 0 && activeConversationVisible) {
                        loadThread(activePartnerId, { silent: true });
                    }

                    if (activeConversationVisible && activeLatestMessage
                        && Number(activeLatestMessage.sender_id) === activePartnerId
                        && Number(activeLatestMessage.recipient_id) === currentUserId
                        && !activeLatestMessage.read_at && readEndpoint && messageCsrfToken) {
                        fetch(readEndpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: new URLSearchParams({
                                csrf_token: messageCsrfToken,
                                other_user_id: String(activePartnerId)
                            }).toString()
                        })
                        .then(function (response) {
                            return response.ok ? response.json() : null;
                        })
                        .then(function (readResult) {
                            if (!readResult || !readResult.success) {
                                return;
                            }
                            if (readResult.csrf_token) {
                                refreshMessageCsrf(readResult.csrf_token);
                            }
                            const currentUnreadCount = Math.max(0, Number(badge ? badge.textContent : unreadCount) - Number(readResult.read_count || 0));
                            if (badge) {
                                badge.textContent = String(currentUnreadCount);
                            }
                            lastKnownUnreadCount = currentUnreadCount;
                            const activeItem = document.querySelector('.floating-message-item[data-message-recipient-id="' + String(activePartnerId) + '"]');
                            if (activeItem) {
                                activeItem.classList.remove('is-unread');
                                const unreadIndicator = activeItem.querySelector('.floating-message-item-count');
                                if (unreadIndicator) {
                                    unreadIndicator.remove();
                                }
                            }
                        })
                        .catch(function () {
                            // The unread count is reconciled on the next poll.
                        });
                    }

                    renderRecentMessageList(messages);
                    if (activeConversationVisible && activePartnerId > 0) {
                        const activeItem = document.querySelector('.floating-message-item[data-message-recipient-id="' + String(activePartnerId) + '"]');
                        if (activeItem) {
                            activeItem.classList.add('is-active');
                        }
                    }
                }

                lastKnownUnreadCount = unreadCount;
            })
            .catch(function () {
                // Ignore polling failures; they are retried on the next tick.
            })
            .finally(function () {
                messagePollInProgress = false;
            });
        };

        if (messageWidget && recentMessagesEndpoint) {
            pollIncomingMessages();
            setInterval(pollIncomingMessages, 5000);

            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) {
                    pollIncomingMessages();
                }
            });

            window.addEventListener('focus', pollIncomingMessages);
        }
        const messageButton = document.getElementById('floatingMessageButton');
        const messagePanel = document.getElementById('floatingMessagePanel');
        const messageClose = document.getElementById('floatingMessageClose');
        const messageNewButton = document.getElementById('floatingMessageNew');
        const messageNewComposer = document.getElementById('floatingMessageNewComposer');
        const messageNewRecipient = document.getElementById('floatingMessageNewRecipient');
        const messageNewInput = document.getElementById('floatingMessageNewInput');
        const messageSearch = document.getElementById('floatingMessageSearch');
        const messageForm = document.getElementById('floatingMessageForm');
        const messageInput = document.getElementById('floatingMessageInput');
        const messageRecipient = document.getElementById('floatingMessageRecipient');
        const messageDetail = document.getElementById('floatingMessageDetail');
        const messageDetailSender = document.getElementById('floatingMessageDetailSender');
        const messageBackButton = document.getElementById('floatingMessageBack');
        const messageClearButton = document.getElementById('floatingMessageClear');
        const messageClearOptions = document.getElementById('floatingMessageClearOptions');
        const messageDetailBody = document.getElementById('floatingMessageDetailBody');
        const messageThread = document.getElementById('floatingMessageThread');
        const messageReplyForm = document.getElementById('floatingMessageReplyForm');
        const messageReplyInput = document.getElementById('floatingMessageReplyInput');
        const messageTypingIndicator = document.getElementById('floatingMessageTypingIndicator');
        const messageList = document.querySelector('.floating-message-list');
        const readEndpoint = messageWidget ? (messageWidget.dataset.messageReadEndpoint || '') : '';
        const threadEndpoint = messageWidget ? (messageWidget.dataset.messageThreadEndpoint || '') : '';
        const typingEndpoint = messageWidget ? (messageWidget.dataset.messageTypingEndpoint || '') : '';
        const appBaseUrl = messageWidget ? (messageWidget.dataset.appBaseUrl || '') : '';
        let messageCsrfToken = messageWidget ? (messageWidget.dataset.messageCsrf || '') : '';
        const currentUserId = Number(messageWidget ? (messageWidget.dataset.currentUserId || '0') : '0');
        const currentUserAvatar = messageWidget ? (messageWidget.dataset.currentUserAvatar || '') : '';
        const messageOnlineUserIds = new Set();
        let messagePresencePollInProgress = false;
        const messageOnlineStatus = document.getElementById('floatingMessageOnlineStatus');

        const refreshMessageCsrf = function (nextToken) {
            if (nextToken && nextToken !== '') {
                messageCsrfToken = nextToken;
                if (messageWidget) {
                    messageWidget.dataset.messageCsrf = nextToken;
                }
            }
        };

        const applyMessageOnlineStatus = function (onlineUserIds) {
            messageOnlineUserIds.clear();
            onlineUserIds.forEach(function (userId) {
                const parsedId = Number(userId);
                if (Number.isInteger(parsedId) && parsedId > 0 && parsedId !== currentUserId) {
                    messageOnlineUserIds.add(parsedId);
                }
            });

            document.querySelectorAll('.floating-message-item[data-message-recipient-id]').forEach(function (item) {
                const userId = Number(item.dataset.messageRecipientId || 0);
                const indicator = item.querySelector('.floating-message-online-indicator');
                if (indicator) {
                    indicator.hidden = !messageOnlineUserIds.has(userId);
                }
            });

            if (messageOnlineStatus) {
                messageOnlineStatus.hidden = !messageOnlineUserIds.has(Number(activeConversationUserId || 0));
            }

            if (messageNewRecipient) {
                Array.from(messageNewRecipient.options).forEach(function (option) {
                    if (!option.value) {
                        return;
                    }
                    const baseName = option.dataset.displayName || option.textContent.replace(/ \(Online\)$/, '');
                    option.dataset.displayName = baseName;
                    option.textContent = messageOnlineUserIds.has(Number(option.value))
                        ? baseName + ' (Online)'
                        : baseName;
                });
            }
        };

        const pollMessagePresence = function () {
            if (!messagePresenceEndpoint || document.visibilityState !== 'visible' || messagePresencePollInProgress) {
                return;
            }

            messagePresencePollInProgress = true;
            fetch(messagePresenceEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({ csrf_token: messageCsrfToken }).toString()
            })
            .then(function (response) {
                return response.json().catch(function () {
                    return { success: false, message: 'Unable to update online status.' };
                }).then(function (result) {
                    if (result && result.csrf_token) {
                        refreshMessageCsrf(result.csrf_token);
                    }
                    if (!response.ok || !result || !result.success) {
                        throw new Error(result && result.message ? result.message : 'Unable to update online status.');
                    }
                    return fetch(messagePresenceEndpoint, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                });
            })
            .then(function (response) {
                if (!response || !response.ok) {
                    throw new Error('Unable to load online status.');
                }
                return response.json();
            })
            .then(function (result) {
                if (!result || !result.success || !Array.isArray(result.online_user_ids)) {
                    throw new Error('The online status response was invalid.');
                }
                applyMessageOnlineStatus(result.online_user_ids);
            })
            .catch(function (error) {
                console.error(error);
            })
            .finally(function () {
                messagePresencePollInProgress = false;
            });
        };

        const getAppAssetUrl = function (path) {
            if (!path) {
                return '';
            }

            try {
                return new URL(String(path), appBaseUrl + '/').href;
            } catch (error) {
                console.error(error);
                return '';
            }
        };

        const setTypingStatus = function (recipientId, isTyping) {
            if (!typingEndpoint || !recipientId) {
                return Promise.resolve();
            }

            return fetch(typingEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    csrf_token: messageCsrfToken,
                    recipient_id: String(recipientId),
                    is_typing: isTyping ? '1' : '0'
                }).toString()
            }).then(function (response) {
                return response.json().catch(function () {
                    return { success: false, message: 'Unable to update typing status.' };
                }).then(function (result) {
                    if (result && result.csrf_token) {
                        refreshMessageCsrf(result.csrf_token);
                    }
                    if (!response.ok || !result || !result.success) {
                        throw new Error(result && result.message ? result.message : 'Unable to update typing status.');
                    }
                });
            });
        };

        const stopTypingStatus = function () {
            if (typingHeartbeatTimer !== null) {
                window.clearInterval(typingHeartbeatTimer);
                typingHeartbeatTimer = null;
            }
            if (typingStopTimer !== null) {
                window.clearTimeout(typingStopTimer);
                typingStopTimer = null;
            }
            if (typingTargetId !== null) {
                const previousTargetId = typingTargetId;
                typingTargetId = null;
                setTypingStatus(previousTargetId, false).catch(function (error) {
                    console.error(error);
                });
            }
        };

        const startTypingStatus = function () {
            const targetId = Number(activeConversationUserId || 0);
            if (!typingEndpoint || targetId <= 0) {
                return;
            }
            if (typingTargetId !== null && typingTargetId !== targetId) {
                stopTypingStatus();
            }
            if (typingTargetId === null) {
                typingTargetId = targetId;
                setTypingStatus(targetId, true).catch(function (error) {
                    console.error(error);
                });
                typingHeartbeatTimer = window.setInterval(function () {
                    if (typingTargetId !== null) {
                        setTypingStatus(typingTargetId, true).catch(function (error) {
                            console.error(error);
                        });
                    }
                }, 2000);
            }
            if (typingStopTimer !== null) {
                window.clearTimeout(typingStopTimer);
            }
            typingStopTimer = window.setTimeout(stopTypingStatus, 4000);
        };

        const pollTypingStatus = function () {
            const partnerId = Number(activeConversationUserId || 0);
            if (!typingEndpoint || partnerId <= 0 || !isActiveConversationVisible() || typingPollInProgress) {
                if (messageTypingIndicator) {
                    messageTypingIndicator.hidden = true;
                }
                return;
            }

            typingPollInProgress = true;
            fetch(typingEndpoint + '?user_id=' + encodeURIComponent(partnerId), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Unable to check typing status.');
                }
                return response.json();
            })
            .then(function (result) {
                if (Number(activeConversationUserId) !== partnerId || !messageTypingIndicator) {
                    return;
                }
                const isTyping = Boolean(result && result.success && result.is_typing);
                messageTypingIndicator.replaceChildren();
                if (isTyping) {
                    const accessibleLabel = document.createElement('span');
                    accessibleLabel.className = 'visually-hidden';
                    accessibleLabel.textContent = (messageDetailSender.textContent.trim() || 'User') + ' is typing';
                    messageTypingIndicator.appendChild(accessibleLabel);
                    const dots = document.createElement('span');
                    dots.className = 'floating-message-typing-dots';
                    dots.setAttribute('aria-hidden', 'true');
                    for (let index = 0; index < 3; index += 1) {
                        const dot = document.createElement('span');
                        dot.className = 'floating-message-typing-dot';
                        dots.appendChild(dot);
                    }
                    messageTypingIndicator.appendChild(dots);
                }
                messageTypingIndicator.hidden = !isTyping;
            })
            .catch(function (error) {
                if (messageTypingIndicator) {
                    messageTypingIndicator.hidden = true;
                }
                console.error(error);
            })
            .finally(function () {
                typingPollInProgress = false;
            });
        };

        if (typingEndpoint) {
            window.setInterval(pollTypingStatus, 1800);
        }

        if (messagePresenceEndpoint) {
            pollMessagePresence();
            window.setInterval(pollMessagePresence, 10000);
            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) {
                    pollMessagePresence();
                }
            });
        }

        const toggleMessagePanel = function () {
            if (!messageButton || !messagePanel) {
                return;
            }

            const shouldShow = messagePanel.hasAttribute('hidden');
            messagePanel.toggleAttribute('hidden', !shouldShow);
            messageButton.hidden = shouldShow;
        };

        const closeMessagePanel = function () {
            if (!messagePanel || !messageButton) {
                return;
            }

            stopTypingStatus();
            if (messageTypingIndicator) {
                messageTypingIndicator.hidden = true;
            }
            messagePanel.setAttribute('hidden', 'hidden');
            messageButton.hidden = false;
        };

        if (messageButton && messagePanel) {
            messageButton.addEventListener('click', function () {
                if (messagePanel.hasAttribute('hidden')) {
                    messagePanel.removeAttribute('hidden');
                    messageButton.hidden = true;
                } else {
                    closeMessagePanel();
                }
            });
        }

        if (messageClose && messagePanel) {
            messageClose.addEventListener('click', function () {
                const sidebar = document.querySelector('.floating-message-sidebar');
                if (sidebar) {
                    sidebar.style.display = '';
                }
                closeMessagePanel();
            });
        }

        if (messageBackButton) {
            messageBackButton.addEventListener('click', function () {
                stopTypingStatus();
                activeConversationUserId = null;
                activeThreadStateSignature = '';
                threadLoadSequence += 1;
                if (messageTypingIndicator) {
                    messageTypingIndicator.hidden = true;
                    messageTypingIndicator.textContent = '';
                }
                if (messageDetail) {
                    messageDetail.hidden = true;
                }
                if (messageOnlineStatus) {
                    messageOnlineStatus.hidden = true;
                }
                if (messageReplyForm) {
                    messageReplyForm.hidden = true;
                }
                if (messageClearButton) {
                    messageClearButton.hidden = true;
                }
                if (messageClearOptions) {
                    messageClearOptions.hidden = true;
                }
                document.querySelectorAll('.floating-message-item.is-active').forEach(function (item) {
                    item.classList.remove('is-active');
                });
                const sidebar = document.querySelector('.floating-message-sidebar');
                if (sidebar) {
                    sidebar.style.display = '';
                }
            });
        }

        if (messageNewButton && messageNewComposer) {
            messageNewButton.addEventListener('click', function () {
                const sidebar = document.querySelector('.floating-message-sidebar');
                if (sidebar) {
                    sidebar.style.display = '';
                }
                messageNewComposer.hidden = !messageNewComposer.hidden;
                if (!messageNewComposer.hidden && messageNewRecipient) {
                    messageNewRecipient.focus();
                }
            });
        }

        const hideComposer = function () {
            if (messageNewComposer) {
                messageNewComposer.hidden = true;
            }
            if (messageNewInput) {
                messageNewInput.value = '';
            }
            if (messageNewRecipient) {
                messageNewRecipient.selectedIndex = 0;
            }
        };

        const messageCancel = document.getElementById('floatingMessageNewCancel');
        if (messageCancel) {
            messageCancel.addEventListener('click', hideComposer);
        }

        if (messageSearch) {
            messageSearch.addEventListener('input', function () {
                const term = messageSearch.value.trim().toLowerCase();
                document.querySelectorAll('.floating-message-item').forEach(function (item) {
                    if (item.classList.contains('is-empty')) {
                        return;
                    }
                    const text = (item.textContent || '').toLowerCase();
                    item.hidden = term !== '' && !text.includes(term);
                });
            });
        }

        const renderThreadBubble = function (text, isOutgoing, senderName, createdAt, messageId, seenBy) {
            if (!messageThread) {
                return;
            }

            const bubbleWrap = document.createElement('div');
            bubbleWrap.className = 'floating-message-bubble-wrap ' + (isOutgoing ? 'is-outgoing' : 'is-incoming');

            const bubble = document.createElement('div');
            bubble.className = 'floating-message-bubble ' + (isOutgoing ? 'is-outgoing' : 'is-incoming');

            if (Number(messageId) > 0) {
                const actions = document.createElement('div');
                actions.className = 'floating-message-bubble-actions';
                if (isOutgoing) {
                    const editButton = document.createElement('button');
                    editButton.type = 'button';
                    editButton.className = 'floating-message-edit-one';
                    editButton.dataset.messageId = String(messageId);
                    editButton.textContent = 'Edit';
                    editButton.setAttribute('aria-label', 'Edit this message');
                    actions.appendChild(editButton);
                }

                const deleteButton = document.createElement('button');
                deleteButton.type = 'button';
                deleteButton.className = 'floating-message-delete-one';
                deleteButton.dataset.messageId = String(messageId);
                deleteButton.textContent = 'Delete';
                deleteButton.setAttribute('aria-label', 'Delete this message');
                actions.appendChild(deleteButton);
                bubble.appendChild(actions);
            }

            const label = document.createElement('div');
            label.className = 'floating-message-bubble-sender';
            label.textContent = senderName || (isOutgoing ? 'You' : 'User');

            const content = document.createElement('div');
            content.className = 'floating-message-bubble-text';
            content.textContent = text;

            const meta = document.createElement('div');
            meta.className = 'floating-message-bubble-meta';
            meta.textContent = createdAt || '';

            bubble.appendChild(label);
            bubble.appendChild(content);
            bubble.appendChild(meta);
            if (seenBy) {
                const seenIndicator = document.createElement('span');
                seenIndicator.className = 'floating-message-seen';
                seenIndicator.setAttribute('role', 'img');
                seenIndicator.setAttribute('aria-label', 'Seen by ' + (seenBy.name || 'recipient'));
                seenIndicator.title = 'Seen by ' + (seenBy.name || 'recipient');
                if (seenBy.avatar) {
                    const avatar = document.createElement('img');
                    avatar.src = seenBy.avatar;
                    avatar.alt = '';
                    seenIndicator.appendChild(avatar);
                } else {
                    seenIndicator.textContent = seenBy.initials || 'U';
                }
                bubble.appendChild(seenIndicator);
            }
            bubbleWrap.appendChild(bubble);
            messageThread.appendChild(bubbleWrap);
        };

        const formatThreadTime = function (value) {
            if (!value) {
                return '';
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return '';
            }

            return date.toLocaleTimeString([], {
                hour: 'numeric',
                minute: '2-digit'
            });
        };

        const upsertConversationItem = function (recipientId, text, senderLabel, createdAtValue) {
            const list = document.querySelector('.floating-message-list');
            if (!list || !recipientId) {
                return null;
            }

            const rawText = (text || '').trim();
            const createdAt = createdAtValue || new Date().toISOString();
            const targetId = String(recipientId);
            const label = (senderLabel && senderLabel.trim() !== '') ? senderLabel.trim() : 'User';
            const safeText = escapeHtml(rawText);
            const safeTextEscaped = escapeHtml(rawText);

            let existing = null;
            list.querySelectorAll('.floating-message-item').forEach(function (item) {
                if (!existing && (item.dataset.messageRecipientId || '') === targetId) {
                    existing = item;
                }
            });

            if (existing) {
                existing.dataset.messageSender = label;
                existing.dataset.messageBody = rawText;
                existing.dataset.messageTime = createdAt;
                existing.dataset.messageRecipientId = targetId;
                existing.classList.remove('is-unread');
                const existingAvatar = label === 'You' && currentUserAvatar ? '<img src="' + currentUserAvatar + '" alt="Profile picture">' : (label === 'You' ? 'Y' : label.slice(0, 2).toUpperCase());
                existing.innerHTML = '<div class="floating-message-item-row"><div class="floating-message-avatar" aria-hidden="true">' + existingAvatar + '<span class="floating-message-online-indicator"' + (messageOnlineUserIds.has(Number(targetId)) ? '' : ' hidden') + '></span></div><div class="floating-message-item-copy"><strong>' + escapeHtml(label) + '</strong><small>' + safeTextEscaped + '</small></div><time class="floating-message-item-time">' + formatThreadTime(createdAt) + '</time></div>';
                list.prepend(existing);
                return existing;
            }

            const item = document.createElement('div');
            item.className = 'floating-message-item';
            item.setAttribute('tabindex', '0');
            item.setAttribute('role', 'button');
            item.dataset.messageSender = label;
            item.dataset.messageBody = rawText;
            item.dataset.messageRecipientId = targetId;
            item.dataset.messageTime = createdAt;

            const avatarMarkup = label === 'You' && currentUserAvatar ? '<img src="' + currentUserAvatar + '" alt="Profile picture">' : (label === 'You' ? 'Y' : (label.slice(0, 2).toUpperCase()));
            item.innerHTML = '<div class="floating-message-item-row"><div class="floating-message-avatar" aria-hidden="true">' + avatarMarkup + '<span class="floating-message-online-indicator"' + (messageOnlineUserIds.has(Number(targetId)) ? '' : ' hidden') + '></span></div><div class="floating-message-item-copy"><strong>' + escapeHtml(label) + '</strong><small>' + safeTextEscaped + '</small></div><time class="floating-message-item-time">' + formatThreadTime(createdAt) + '</time></div>';

            list.prepend(item);
            item.addEventListener('click', function () {
                openMessage(item);
            });
            return item;
        };

        const loadThread = function (partnerId, options) {
            if (!messageThread || !threadEndpoint || !partnerId) {
                return;
            }

            const silentRefresh = Boolean(options && options.silent);
            const wasNearBottom = messageThread.scrollHeight - messageThread.scrollTop - messageThread.clientHeight < 48;
            const requestSequence = ++threadLoadSequence;
            if (!silentRefresh) {
                messageDetailBody.textContent = 'Loading conversation...';
                messageThread.innerHTML = '';
            }

            fetch(threadEndpoint + '?user_id=' + encodeURIComponent(partnerId), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Unable to load the conversation.');
                }

                return response.json();
            })
            .then(function (result) {
                if (requestSequence !== threadLoadSequence
                    || Number(activeConversationUserId) !== Number(partnerId)) {
                    return;
                }
                if (!result || !result.success || !Array.isArray(result.messages)) {
                    if (!silentRefresh) {
                        messageDetailBody.textContent = 'No conversation history available.';
                    }
                    return;
                }

                const stateSignature = result.messages.map(function (message) {
                    return String(message.message_id || '')
                        + ':' + String(message.read_at || '')
                        + ':' + String(message.message || '');
                }).join('|');
                if (silentRefresh && !(options && options.force) && stateSignature === activeThreadStateSignature) {
                    return;
                }

                messageThread.innerHTML = '';
                if (result.messages.length === 0) {
                    messageDetailBody.textContent = 'This conversation has no messages yet.';
                    activeThreadStateSignature = stateSignature;
                    return;
                }

                activeThreadStateSignature = stateSignature;
                let seenByMessageId = 0;
                result.messages.forEach(function (message) {
                    if (Number(message.sender_id) === currentUserId && message.read_at) {
                        seenByMessageId = Number(message.message_id || 0);
                    }
                });
                const lastReadMessage = result.messages.find(function (message) {
                    return Number(message.message_id) === seenByMessageId;
                });
                let seenBy = null;
                if (lastReadMessage) {
                    const seenName = [
                        lastReadMessage.recipient_first_name || '',
                        lastReadMessage.recipient_last_name || ''
                    ].join(' ').trim() || 'recipient';
                    const seenInitials = seenName.split(/\s+/).filter(Boolean).slice(0, 2).map(function (part) {
                        return part.charAt(0).toUpperCase();
                    }).join('') || 'U';
                    seenBy = {
                        name: seenName,
                        initials: seenInitials,
                        avatar: getAppAssetUrl(lastReadMessage.recipient_profile_picture || '')
                    };
                }
                result.messages.forEach(function (message) {
                    const isOutgoing = Number(message.sender_id) === currentUserId;
                    const senderName = isOutgoing ? 'You' : (message.sender_first_name || 'User');
                    const text = message.message || '';
                    renderThreadBubble(
                        text,
                        isOutgoing,
                        senderName,
                        formatThreadTime(message.created_at),
                        message.message_id,
                        Number(message.message_id) === seenByMessageId ? seenBy : null
                    );
                });

                messageDetailBody.textContent = '';
                if (!silentRefresh || wasNearBottom) {
                    messageThread.scrollTop = messageThread.scrollHeight;
                }
            })
            .catch(function () {
                if (!silentRefresh && requestSequence === threadLoadSequence) {
                    messageDetailBody.textContent = 'Unable to load the conversation right now.';
                }
            });
        };

        const openMessage = function (item) {
            if (!item || !messageDetail || !messageDetailSender || !messageDetailBody || !messageThread) {
                return;
            }

            const sender = item.dataset.messageSender || 'Message';
            const body = item.dataset.messageBody || '';
            const messageId = item.dataset.messageId || '';
            const replyRecipientId = item.dataset.messageRecipientId || '';
            if (typingTargetId !== null && Number(typingTargetId) !== Number(replyRecipientId)) {
                stopTypingStatus();
            }
            activeConversationUserId = replyRecipientId ? Number(replyRecipientId) : null;
            activeThreadStateSignature = '';
            if (messageClearOptions) {
                messageClearOptions.hidden = true;
            }
            if (messageOnlineStatus) {
                messageOnlineStatus.hidden = !messageOnlineUserIds.has(Number(activeConversationUserId || 0));
            }
            if (messageTypingIndicator) {
                messageTypingIndicator.hidden = true;
                messageTypingIndicator.textContent = '';
            }

            messageDetailSender.textContent = sender;
            messageDetailBody.textContent = 'Loading conversation...';
            messageThread.innerHTML = '';
            messageDetail.hidden = false;
            if (messagePanel && !messagePanel.hasAttribute('hidden')) {
                const sidebar = document.querySelector('.floating-message-sidebar');
                if (sidebar) {
                    sidebar.style.display = 'none';
                }
                if (messageButton) {
                    messageButton.hidden = true;
                }
            }

            if (messageReplyForm) {
                messageReplyForm.hidden = false;
                messageReplyForm.dataset.replyRecipientId = replyRecipientId;
                if (messageReplyInput) {
                    messageReplyInput.value = '';
                }
                if (messageClearButton) {
                    messageClearButton.hidden = !replyRecipientId;
                }
            }

            document.querySelectorAll('.floating-message-item').forEach(function (listItem) {
                listItem.classList.remove('is-active');
            });
            item.classList.add('is-active');

            if (replyRecipientId) {
                loadThread(replyRecipientId, item);
                pollTypingStatus();
            }

            if (replyRecipientId && readEndpoint && messageCsrfToken) {
                fetch(readEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        csrf_token: messageCsrfToken,
                        other_user_id: String(replyRecipientId),
                        message_id: messageId || ''
                    }).toString()
                }).then(function (response) {
                    if (response.ok) {
                        return response.json().catch(function () { return null; });
                    }

                    return response.json().catch(function () { return null; }).then(function (data) {
                        if (data && data.csrf_token) {
                            refreshMessageCsrf(data.csrf_token);
                        }
                        return Promise.reject(new Error(data && data.message ? data.message : 'Unable to mark the message as read.'));
                    });
                }).then(function (result) {
                    if (result && result.csrf_token) {
                        refreshMessageCsrf(result.csrf_token);
                    }

                    item.classList.remove('is-unread');
                    const badge = document.querySelector('.floating-message-badge');
                    const clearedCount = Number(result && result.read_count ? result.read_count : 0);
                    if (badge) {
                        const nextValue = Math.max(0, Number(badge.textContent || '0') - clearedCount);
                        badge.textContent = String(nextValue);
                        lastKnownUnreadCount = nextValue;
                    }
                }).catch(function (error) {
                    if (error && error.message) {
                        alert(error.message);
                    }
                    // Ignore read-state update failures; content still opens.
                });
            }
        };

        if (messageList) {
            messageList.addEventListener('click', function (event) {
                const item = event.target.closest('.floating-message-item');
                if (!item || item.classList.contains('is-empty')) {
                    return;
                }
                openMessage(item);
            });

            messageList.addEventListener('keydown', function (event) {
                const item = event.target.closest('.floating-message-item');
                if (!item || item.classList.contains('is-empty')) {
                    return;
                }

                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openMessage(item);
                }
            });
        }

        const submitMessageDelete = function (payload) {
            if (!messageDeleteEndpoint) {
                return Promise.reject(new Error('Message deletion is unavailable right now.'));
            }

            payload.set('csrf_token', messageCsrfToken);
            return fetch(messageDeleteEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: payload.toString()
            }).then(function (response) {
                return response.json().catch(function () {
                    return { success: false, message: 'Unable to update the conversation.' };
                }).then(function (result) {
                    if (result && result.csrf_token) {
                        refreshMessageCsrf(result.csrf_token);
                    }
                    if (!response.ok || !result || !result.success) {
                        throw new Error(result && result.message ? result.message : 'Unable to update the conversation.');
                    }
                    const badge = document.querySelector('.floating-message-badge');
                    if (badge && Number.isFinite(Number(result.unread_count))) {
                        badge.textContent = String(Number(result.unread_count));
                        lastKnownUnreadCount = Number(result.unread_count);
                    }
                    return result;
                });
            });
        };

        if (messageThread) {
            messageThread.addEventListener('click', function (event) {
                const editButton = event.target.closest('.floating-message-edit-one');
                if (editButton) {
                    const bubble = editButton.closest('.floating-message-bubble');
                    const content = bubble && bubble.querySelector('.floating-message-bubble-text');
                    if (!bubble || !content || bubble.querySelector('.floating-message-edit-form')) {
                        return;
                    }

                    editButton.hidden = true;
                    const editor = document.createElement('form');
                    editor.className = 'floating-message-edit-form';
                    const textarea = document.createElement('textarea');
                    textarea.className = 'floating-message-edit-input';
                    textarea.maxLength = 500;
                    textarea.required = true;
                    textarea.setAttribute('aria-label', 'Edit message');
                    textarea.value = content.textContent || '';

                    const actions = document.createElement('div');
                    actions.className = 'floating-message-edit-actions';
                    const saveButton = document.createElement('button');
                    saveButton.type = 'submit';
                    saveButton.className = 'floating-message-edit-save';
                    saveButton.textContent = 'Save';
                    const cancelButton = document.createElement('button');
                    cancelButton.type = 'button';
                    cancelButton.className = 'floating-message-edit-cancel';
                    cancelButton.textContent = 'Cancel';
                    actions.append(saveButton, cancelButton);
                    editor.append(textarea, actions);
                    content.hidden = true;
                    content.after(editor);
                    textarea.focus();
                    textarea.setSelectionRange(textarea.value.length, textarea.value.length);
                    return;
                }

                const cancelEdit = event.target.closest('.floating-message-edit-cancel');
                if (cancelEdit) {
                    const bubble = cancelEdit.closest('.floating-message-bubble');
                    const content = bubble && bubble.querySelector('.floating-message-bubble-text');
                    const editor = cancelEdit.closest('.floating-message-edit-form');
                    const editControl = bubble && bubble.querySelector('.floating-message-edit-one');
                    if (content) {
                        content.hidden = false;
                    }
                    if (editor) {
                        editor.remove();
                    }
                    if (editControl) {
                        editControl.hidden = false;
                    }
                    return;
                }

                const deleteButton = event.target.closest('.floating-message-delete-one');
                const deleteScopeButton = event.target.closest('.floating-message-delete-scope');
                const deleteCancelButton = event.target.closest('.floating-message-delete-cancel');
                if (deleteButton) {
                    const bubble = deleteButton.closest('.floating-message-bubble');
                    const actions = deleteButton.closest('.floating-message-bubble-actions');
                    if (!bubble || !actions || actions.querySelector('.floating-message-delete-options')) {
                        return;
                    }

                    const choices = document.createElement('div');
                    choices.className = 'floating-message-delete-options';
                    choices.setAttribute('role', 'group');
                    choices.setAttribute('aria-label', 'Choose who to delete the message for');
                    const justMe = document.createElement('button');
                    justMe.type = 'button';
                    justMe.className = 'floating-message-delete-scope';
                    justMe.dataset.deleteScope = 'me';
                    justMe.textContent = 'Just me';
                    choices.appendChild(justMe);
                    if (bubble.classList.contains('is-outgoing')) {
                        const everyone = document.createElement('button');
                        everyone.type = 'button';
                        everyone.className = 'floating-message-delete-scope';
                        everyone.dataset.deleteScope = 'everyone';
                        everyone.textContent = 'Everyone';
                        choices.appendChild(everyone);
                    }
                    const cancel = document.createElement('button');
                    cancel.type = 'button';
                    cancel.className = 'floating-message-delete-cancel';
                    cancel.textContent = 'Cancel';
                    choices.appendChild(cancel);
                    actions.appendChild(choices);
                    deleteButton.hidden = true;
                    return;
                }

                if (deleteCancelButton) {
                    const actions = deleteCancelButton.closest('.floating-message-bubble-actions');
                    if (actions) {
                        actions.querySelectorAll('.floating-message-delete-options').forEach(function (options) {
                            options.remove();
                        });
                        const trigger = actions.querySelector('.floating-message-delete-one');
                        if (trigger) {
                            trigger.hidden = false;
                        }
                    }
                    return;
                }

                if (!deleteScopeButton) {
                    return;
                }

                const bubble = deleteScopeButton.closest('.floating-message-bubble');
                const actions = deleteScopeButton.closest('.floating-message-bubble-actions');
                const deleteTrigger = actions && actions.querySelector('.floating-message-delete-one');
                const messageId = Number(deleteTrigger && deleteTrigger.dataset.messageId || 0);
                const scope = deleteScopeButton.dataset.deleteScope || '';
                if (messageId <= 0 || !['me', 'everyone'].includes(scope)) {
                    return;
                }
                if (scope === 'everyone' && !window.confirm('Delete this message for both users? This cannot be undone.')) {
                    return;
                }

                deleteScopeButton.disabled = true;
                submitMessageDelete(new URLSearchParams({
                    action: 'delete_message',
                    message_id: String(messageId),
                    scope: scope
                }))
                .then(function () {
                    loadThread(Number(activeConversationUserId || 0), { silent: true, force: true });
                    pollIncomingMessages();
                })
                .catch(function (error) {
                    deleteScopeButton.disabled = false;
                    alert(error && error.message ? error.message : 'Unable to delete the message.');
                });
            });

            messageThread.addEventListener('submit', function (event) {
                const editor = event.target.closest('.floating-message-edit-form');
                if (!editor) {
                    return;
                }
                event.preventDefault();

                const bubble = editor.closest('.floating-message-bubble');
                const editButton = bubble && bubble.querySelector('.floating-message-edit-one');
                const messageId = Number(editButton && editButton.dataset.messageId || 0);
                const textarea = editor.querySelector('.floating-message-edit-input');
                const text = textarea ? textarea.value.trim() : '';
                if (messageId <= 0 || !text) {
                    if (textarea) {
                        textarea.focus();
                    }
                    return;
                }

                const saveButton = editor.querySelector('.floating-message-edit-save');
                if (saveButton) {
                    saveButton.disabled = true;
                }
                submitMessageDelete(new URLSearchParams({
                    action: 'edit_message',
                    message_id: String(messageId),
                    message: text
                }))
                .then(function (result) {
                    const content = bubble && bubble.querySelector('.floating-message-bubble-text');
                    if (content) {
                        content.textContent = result.message;
                        content.hidden = false;
                    }
                    editor.remove();
                    if (editButton) {
                        editButton.hidden = false;
                    }
                    loadThread(Number(activeConversationUserId || 0), { silent: true, force: true });
                    pollIncomingMessages();
                })
                .catch(function (error) {
                    if (saveButton) {
                        saveButton.disabled = false;
                    }
                    alert(error && error.message ? error.message : 'Unable to edit the message.');
                });
            });
        }

        if (messageClearButton) {
            messageClearButton.addEventListener('click', function () {
                if (Number(activeConversationUserId || 0) <= 0 || !messageClearOptions) {
                    return;
                }
                messageClearOptions.hidden = !messageClearOptions.hidden;
            });
        }

        if (messageClearOptions) {
            messageClearOptions.addEventListener('click', function (event) {
                const cancelButton = event.target.closest('.floating-message-clear-cancel');
                if (cancelButton) {
                    messageClearOptions.hidden = true;
                    return;
                }

                const scopeButton = event.target.closest('.floating-message-clear-scope');
                const otherUserId = Number(activeConversationUserId || 0);
                const scope = scopeButton && scopeButton.dataset.clearScope;
                if (!scopeButton || otherUserId <= 0 || !['me', 'everyone'].includes(scope)) {
                    return;
                }
                if (scope === 'everyone' && !window.confirm('Clear the conversation for both users? This cannot be undone.')) {
                    return;
                }

                scopeButton.disabled = true;
                messageClearButton.disabled = true;
                submitMessageDelete(new URLSearchParams({
                    action: 'clear_conversation',
                    other_user_id: String(otherUserId),
                    scope: scope
                }))
                .then(function () {
                    activeConversationUserId = null;
                    messageThread.innerHTML = '';
                    messageDetailBody.textContent = 'Select a conversation to read it.';
                    messageDetail.hidden = true;
                    messageReplyForm.hidden = true;
                    messageClearButton.hidden = true;
                    messageClearOptions.hidden = true;
                    const sidebar = document.querySelector('.floating-message-sidebar');
                    if (sidebar) {
                        sidebar.style.display = '';
                    }
                    pollIncomingMessages();
                })
                .catch(function (error) {
                    alert(error && error.message ? error.message : 'Unable to clear the conversation.');
                })
                .finally(function () {
                    messageClearButton.disabled = false;
                    scopeButton.disabled = false;
                });
            });
        }

        if (messageReplyForm && messageReplyInput) {
            messageReplyInput.addEventListener('input', function () {
                if (messageReplyInput.value.trim() !== '') {
                    startTypingStatus();
                } else {
                    stopTypingStatus();
                }
            });

            messageReplyInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    if (typeof messageReplyForm.requestSubmit === 'function') {
                        messageReplyForm.requestSubmit();
                    } else {
                        messageReplyForm.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                    }
                }
            });

            messageReplyForm.addEventListener('submit', function (event) {
                event.preventDefault();

                if (!messageWidget || !messageReplyInput.value.trim()) {
                    return;
                }

                const activeItem = document.querySelector('.floating-message-item.is-active');
                const replyRecipientId = Number(messageReplyForm.dataset.replyRecipientId || activeItem?.dataset.messageRecipientId || 0);
                const text = messageReplyInput.value.trim();

                if (!replyRecipientId || replyRecipientId === currentUserId || !text) {
                    return;
                }

                stopTypingStatus();
                fetch(messageWidget.dataset.messageEndpoint || '', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        csrf_token: messageCsrfToken,
                        recipient_id: String(replyRecipientId),
                        message: text
                    }).toString()
                })
                .then(function (response) {
                    if (!response.ok) {
                        return response.json().catch(function () {
                            return { success: false, message: 'Unable to reply.' };
                        }).then(function (result) {
                            if (result && result.csrf_token) {
                                refreshMessageCsrf(result.csrf_token);
                            }
                            throw new Error(result && result.message ? result.message : 'Unable to reply.');
                        });
                    }

                    return response.json();
                })
                .then(function (result) {
                    if (result && result.csrf_token) {
                        refreshMessageCsrf(result.csrf_token);
                    }

                    if (!result || !result.success) {
                        throw new Error(result && result.message ? result.message : 'Reply was not sent.');
                    }

                    const activeSender = (activeItem && activeItem.dataset.messageSender) ? activeItem.dataset.messageSender : 'User';
                    upsertConversationItem(replyRecipientId, result.message || text, activeSender, new Date().toISOString());

                    if (messageThread) {
                        renderThreadBubble(result.message || text, true, 'You', formatThreadTime(new Date().toISOString()));
                        messageThread.scrollTop = messageThread.scrollHeight;
                    }

                    messageReplyInput.value = '';
                    messageReplyForm.hidden = false;
                    messageReplyInput.focus();

                    if (activeItem) {
                        openMessage(activeItem);
                    }
                })
                .catch(function (error) {
                    alert(error && error.message ? error.message : 'Unable to send the reply right now.');
                });
            });
        }

        if (messageNewComposer && messageNewRecipient && messageNewInput) {
            messageNewInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    if (typeof messageNewComposer.requestSubmit === 'function') {
                        messageNewComposer.requestSubmit();
                    } else {
                        messageNewComposer.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                    }
                }
            });

            messageNewComposer.addEventListener('submit', function (event) {
                event.preventDefault();

                if (!messageWidget) {
                    return;
                }

                const recipientId = messageNewRecipient.value;
                const text = messageNewInput.value.trim();

                if (!recipientId || !text) {
                    return;
                }

                fetch(messageWidget.dataset.messageEndpoint || '', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        csrf_token: messageCsrfToken,
                        recipient_id: recipientId,
                        message: text
                    }).toString()
                })
                .then(function (response) {
                    if (!response.ok) {
                        return response.json().catch(function () {
                            return { success: false, message: 'Unable to send new message.' };
                        }).then(function (result) {
                            if (result && result.csrf_token) {
                                refreshMessageCsrf(result.csrf_token);
                            }
                            throw new Error(result && result.message ? result.message : 'Unable to send new message.');
                        });
                    }

                    return response.json();
                })
                .then(function (result) {
                    if (result && result.csrf_token) {
                        refreshMessageCsrf(result.csrf_token);
                    }

                    if (!result || !result.success) {
                        throw new Error(result && result.message ? result.message : 'Message was not sent.');
                    }

                    const selectedName = (messageNewRecipient && messageNewRecipient.selectedOptions[0] && messageNewRecipient.selectedOptions[0].textContent)
                        ? messageNewRecipient.selectedOptions[0].textContent.trim()
                        : 'User';
                    upsertConversationItem(recipientId, result.message || text, selectedName, new Date().toISOString());

                    hideComposer();
                    messageNewInput.value = '';
                    messageNewRecipient.selectedIndex = 0;

                    const createdItem = document.querySelector('.floating-message-item[data-message-recipient-id="' + String(recipientId) + '"]');
                    if (createdItem) {
                        openMessage(createdItem);
                    }
                })
                .catch(function () {
                    alert('Unable to send the message right now.');
                });
            });
        }

        if (messageForm && messageWidget) {
            const endpoint = messageWidget.dataset.messageEndpoint || '';

            messageForm.addEventListener('submit', function (event) {
                event.preventDefault();

                if (!endpoint || !messageRecipient || !messageInput) {
                    return;
                }

                const recipientId = messageRecipient.value;
                const text = (messageInput.value || '').trim();

                if (!recipientId || !text) {
                    return;
                }

                const payload = new URLSearchParams({
                    csrf_token: messageCsrfToken,
                    recipient_id: recipientId,
                    message: text
                });

                fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: payload.toString()
                })
                .then(function (response) {
                    if (!response.ok) {
                        return response.json().catch(function () {
                            return { success: false, message: 'Unable to send message.' };
                        }).then(function (result) {
                            if (result && result.csrf_token) {
                                refreshMessageCsrf(result.csrf_token);
                            }
                            throw new Error(result && result.message ? result.message : 'Unable to send message.');
                        });
                    }

                    return response.json();
                })
                .then(function (result) {
                    if (result && result.csrf_token) {
                        refreshMessageCsrf(result.csrf_token);
                    }

                    if (!result || !result.success) {
                        throw new Error(result && result.message ? result.message : 'Message was not sent.');
                    }

                    const selectedName = (messageRecipient && messageRecipient.selectedOptions[0] && messageRecipient.selectedOptions[0].textContent)
                        ? messageRecipient.selectedOptions[0].textContent.trim()
                        : 'User';
                    upsertConversationItem(recipientId, result.message || text, selectedName, new Date().toISOString());

                    messageInput.value = '';
                    messageRecipient.selectedIndex = 0;
                    window.location.reload();
                })
                .catch(function () {
                    alert('Unable to send message right now. Please try again.');
                });
            });
        }
    });
</script>
