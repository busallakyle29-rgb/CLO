(() => {
    const menu = document.getElementById('notificationMenu');
    const badge = document.getElementById('notificationBadge');
    if (!menu || !badge) return;

    const endpoint = menu.dataset.deviceLoginEndpoint;
    const decisionEndpoint = menu.dataset.deviceLoginDecisionEndpoint;
    const csrfToken = menu.dataset.deviceLoginCsrf;
    const staticHeader = menu.querySelector('.notification-header');

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[character]));

    const formatDate = (value) => {
        const date = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(date.getTime())
            ? ''
            : date.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
    };

    const render = (result) => {
        const parts = [staticHeader.outerHTML];
        (result.requests || []).forEach((request) => {
            const name = `${request.first_name || ''} ${request.last_name || ''}`.trim() || 'Unknown user';
            const id = escapeHtml(request.request_id);
            parts.push(`
                <div class="notification-item is-unread" data-device-login-request-id="${id}">
                    <strong>New device login request</strong>
                    <small class="text-muted d-block">${escapeHtml(name)} is requesting access.</small>
                    <div class="d-flex gap-2 mt-2">
                        <form method="post" action="${escapeHtml(decisionEndpoint)}">
                            <input type="hidden" name="csrf_token" value="${escapeHtml(csrfToken)}">
                            <input type="hidden" name="request_id" value="${id}">
                            <input type="hidden" name="decision" value="APPROVED">
                            <button class="btn btn-sm btn-success" type="submit">Allow</button>
                        </form>
                        <form method="post" action="${escapeHtml(decisionEndpoint)}">
                            <input type="hidden" name="csrf_token" value="${escapeHtml(csrfToken)}">
                            <input type="hidden" name="request_id" value="${id}">
                            <input type="hidden" name="decision" value="DENIED">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Deny</button>
                        </form>
                    </div>
                </div>`);
        });

        (result.notifications || []).forEach((notification) => {
            const action = String(notification.action || 'UPDATE').toUpperCase();
            const title = notification.document_title || 'Document';
            const actor = `${notification.actor_first_name || ''} ${notification.actor_last_name || ''}`.trim() || 'System';
            const link = action === 'ACCESS_REQUEST'
                ? 'pending_access_requests.php'
                : action === 'ASSIGNED' ? 'document_management.php' : 'document_routing.php';
            parts.push(`
                <a class="notification-item ${notification.notification_read_at ? 'is-read' : 'is-unread'}"
                   href="${escapeHtml(link)}"
                   data-notification-read-id="${escapeHtml(notification.audit_log_id)}">
                    <strong>${escapeHtml(title)}</strong>
                    <small class="text-muted d-block">${escapeHtml(actor)} • ${escapeHtml(action)} • ${escapeHtml(formatDate(notification.created_at))}</small>
                </a>`);
        });

        if (!result.notifications?.length && !result.requests?.length) {
            parts.push('<div class="dropdown-item text-muted small">No recent notifications.</div>');
        }

        if (result.is_admin && result.pending_approvals?.length) {
            parts.push(`<div class="dropdown-divider"></div>
                <div class="notification-header" style="padding-top:0.25rem;">
                    <span>Approvals</span>
                    <span class="text-danger">${result.pending_approvals.length} pending</span>
                </div>`);
            result.pending_approvals.forEach((approval) => {
                const name = `${approval.first_name || ''} ${approval.last_name || ''}`.trim() || approval.username || 'Unknown user';
                parts.push(`<a class="notification-item" href="admin.php">
                    <strong>${escapeHtml(name)}</strong>
                    <small class="text-muted d-block">${escapeHtml(approval.role_name || 'Staff')} • awaiting approval</small>
                </a>`);
            });
            parts.push('<a class="dropdown-item text-primary small fw-semibold" href="admin.php">View all users</a>');
        }

        menu.innerHTML = parts.join('');
        const count = Number(result.unread_count || 0);
        badge.textContent = String(count);
        badge.hidden = count === 0;
    };

    const poll = async () => {
        try {
            const response = await fetch(endpoint, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/json' }
            });
            if (response.ok) render(await response.json());
        } catch (error) {
            // Keep the current notifications visible during temporary failures.
        } finally {
            window.setTimeout(poll, 5000);
        }
    };

    window.setTimeout(poll, 1000);
})();
