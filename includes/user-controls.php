<?php
if (!isset($currentUser) || !is_array($currentUser)) {
    return;
}
?>

<link rel="stylesheet" href="<?php echo e(app_url('assets/css/features/global/user-controls-fixed.css')); ?>?v=20261007a">

<!-- USER CONTROLS -->

<div class="upper-bar" id="upperBar">

    <div class="sidebar-user">

    <div class="sidebar-avatar">

        <?php if (!empty($currentUser['profile_picture'])): ?>
            <img
                src="<?php echo e(app_url((string) $currentUser['profile_picture'])); ?>"
                alt="Profile picture"
            >
        <?php else: ?>
            <?php echo e(
                $initials !== ''
                    ? $initials
                    : 'U'
            ); ?>
        <?php endif; ?>

    </div>

    <div class="min-width-0">

        <div class="sidebar-user-name">

            <?php echo e(
                trim(
                    $firstName
                    . ' '
                    . $lastName
                )
            ); ?>

        </div>

        <div class="sidebar-user-role">

            <?php echo e(
                ucwords(
                    strtolower(
                        $displayRole
                    )
                )
            ); ?>

        </div>

    </div>

    <div class="d-flex align-items-center gap-2">

        <button
            type="button"
            class="notification-button theme-toggle-button"
            id="darkModeToggle"
            aria-pressed="false"
            aria-label="Switch to dark mode"
            title="Toggle dark mode"
        >
            <i class="bi bi-moon-stars" aria-hidden="true"></i>
        </button>

        <div class="dropdown notification-wrapper">
            <button
                class="notification-button"
                type="button"
                id="notificationButton"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                title="Document notifications"
            >
                <i class="bi bi-bell"></i>
                <span
                    class="notification-badge"
                    id="notificationBadge"
                    <?php echo $totalNotificationCount > 0 ? '' : 'hidden'; ?>
                ><?php echo e((string) $totalNotificationCount); ?></span>
            </button>

            <div
                class="dropdown-menu dropdown-menu-end notification-menu"
                id="notificationMenu"
                data-notification-read-endpoint="<?php echo e(app_url('notification_read.php')); ?>"
                data-notification-csrf="<?php echo e(csrf_token('notification_read')); ?>"
                data-device-login-endpoint="<?php echo e(app_url('device_login_notifications.php')); ?>"
                data-device-login-decision-endpoint="<?php echo e(app_url('device_login_decision.php')); ?>"
                data-device-login-csrf="<?php echo e(csrf_token('device_login_decision')); ?>"
                data-static-notification-count="<?php echo e((string) ($totalNotificationCount - count($deviceLoginRequests))); ?>"
            >
                <div class="notification-header">
                    <span>Notifications</span>
                </div>

                <?php foreach ($deviceLoginRequests as $deviceRequest): ?>
                    <div
                        class="notification-item is-unread"
                        data-device-login-request-id="<?php echo e((string) $deviceRequest['request_id']); ?>"
                    >
                        <strong>New device login request</strong>
                        <small class="text-muted d-block">
                            <?php echo e(trim((string) ($deviceRequest['first_name'] ?? '') . ' ' . (string) ($deviceRequest['last_name'] ?? 'Unknown user'))); ?>
                            is requesting access.
                        </small>
                        <div class="d-flex gap-2 mt-2">
                            <form method="post" action="<?php echo e(app_url('device_login_decision.php')); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('device_login_decision')); ?>">
                                <input type="hidden" name="request_id" value="<?php echo e((string) $deviceRequest['request_id']); ?>">
                                <input type="hidden" name="decision" value="APPROVED">
                                <button class="btn btn-sm btn-success" type="submit">Allow</button>
                            </form>
                            <form method="post" action="<?php echo e(app_url('device_login_decision.php')); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('device_login_decision')); ?>">
                                <input type="hidden" name="request_id" value="<?php echo e((string) $deviceRequest['request_id']); ?>">
                                <input type="hidden" name="decision" value="DENIED">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Deny</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($userNotificationCount > 0): ?>
                    <?php foreach ($userNotifications as $notification): ?>
                        <?php
                            $notificationActor = trim((string) ($notification['actor_first_name'] ?? '') . ' ' . (string) ($notification['actor_last_name'] ?? ''));
                            if ($notificationActor === '') {
                                $notificationActor = 'System';
                            }

                            $notificationTitle = trim((string) ($notification['document_title'] ?? ''));
                            if ($notificationTitle === '') {
                                $notificationTitle = 'Document';
                            }

                            $notificationAction = strtoupper(trim((string) ($notification['action'] ?? 'UPDATE')));
                            $notificationDate = date('M d, Y', strtotime((string) ($notification['created_at'] ?? 'now')));
                        ?>
                        <?php
                            $notificationActionKey = strtoupper((string) ($notification['action'] ?? ''));
                            $notificationLink = $notificationActionKey === 'ACCESS_REQUEST'
                                ? app_url('pending_access_requests.php')
                                : ($notificationActionKey === 'ASSIGNED'
                                    ? app_url('document_management.php')
                                    : app_url('document_routing.php'));
                        ?>
                        <a
                            class="notification-item <?php echo empty($notification['notification_read_at']) ? 'is-unread' : 'is-read'; ?>"
                            href="<?php echo e($notificationLink); ?>"
                            data-notification-read-id="<?php echo e((string) ($notification['audit_log_id'] ?? 0)); ?>"
                        >
                            <strong><?php echo e($notificationTitle); ?></strong>
                            <small class="text-muted d-block">
                                <?php echo e($notificationActor . ' • ' . $notificationAction . ' • ' . $notificationDate); ?>
                            </small>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="dropdown-item text-muted small">No recent document activity.</div>
                <?php endif; ?>

                <?php if (auth_is_role('ADMIN') && $pendingApprovalCount > 0): ?>
                    <div class="dropdown-divider"></div>
                    <div class="notification-header" style="padding-top:0.25rem;">
                        <span>Approvals</span>
                        <span class="text-danger"><?php echo e((string) $pendingApprovalCount); ?> pending</span>
                    </div>

                    <?php foreach ($pendingApprovals as $pending): ?>
                        <a class="notification-item" href="<?php echo e(app_url('admin.php')); ?>">
                            <strong>
                                <?php echo e(trim((string) ($pending['first_name'] ?? '') . ' ' . (string) ($pending['last_name'] ?? '')) ?: (string) ($pending['username'] ?? 'Unknown user')); ?>
                            </strong>
                            <small class="text-muted d-block">
                                <?php echo e((string) ($pending['role_name'] ?? 'Staff')); ?> • awaiting approval
                            </small>
                        </a>
                    <?php endforeach; ?>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-primary small fw-semibold" href="<?php echo e(app_url('admin.php')); ?>">
                        View all users
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <a
            class="sidebar-logout"
            href="<?php echo e(app_url('user_profile.php')); ?>"
            title="Profile"
        >
            <i class="bi bi-person"></i>
        </a>

        <a
            class="sidebar-logout"
            href="<?php echo e(app_url('logout.php')); ?>"
            title="Logout"
        >
            <i class="bi bi-box-arrow-right"></i>
        </a>

    </div>

    </div>

</div>
