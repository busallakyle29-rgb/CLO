<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/security.php';
require_once __DIR__ . '/../models/AuthModel.php';

/*
|--------------------------------------------------------------------------
| SECURITY SETTINGS
|--------------------------------------------------------------------------
*/

const AUTH_SESSION_TIMEOUT = 1800; // 30 minutes
const AUTH_SESSION_REGENERATE_INTERVAL = 900; // 15 minutes

/*
|--------------------------------------------------------------------------
| START SECURE SESSION
|--------------------------------------------------------------------------
*/

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $app = get_app_config();

    $isHttps = (
        !empty($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== 'off'
    );

    session_name(
        (string) (
            $app['session_name']
            ?? 'CLO_SESSION'
        )
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    /*
     * Additional PHP session security.
     */
    ini_set(
        'session.use_only_cookies',
        '1'
    );

    ini_set(
        'session.use_strict_mode',
        '1'
    );

    ini_set(
        'session.cookie_httponly',
        '1'
    );

    if ($isHttps) {
        ini_set(
            'session.cookie_secure',
            '1'
        );
    }

    session_start();

    /*
     * Initialize session security timestamps.
     */
    if (!isset($_SESSION['_session_created_at'])) {
        $_SESSION['_session_created_at'] = time();
    }

    if (!isset($_SESSION['_last_activity'])) {
        $_SESSION['_last_activity'] = time();
    }

    if (!isset($_SESSION['_last_regenerated'])) {
        $_SESSION['_last_regenerated'] = time();
    }
}

/*
|--------------------------------------------------------------------------
| SESSION TIMEOUT
|--------------------------------------------------------------------------
|
| Automatically logs the user out after 30 minutes
| of inactivity.
|--------------------------------------------------------------------------
*/

function auth_check_session_timeout(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        start_secure_session();
    }

    /*
     * No logged-in user.
     */
    if (
        !isset($_SESSION['auth_user'])
        ||
        !is_array($_SESSION['auth_user'])
    ) {
        return true;
    }

    $activeSessionToken = (string) ($_SESSION['_active_session_token'] ?? '');
    $activeUserId = (int) ($_SESSION['auth_user']['user_id'] ?? 0);
    if ($activeSessionToken === '' || $activeUserId <= 0 || !auth_is_active_session($activeUserId, $activeSessionToken)) {
        $_SESSION = [];
        session_regenerate_id(true);
        return false;
    }

    $now = time();

    $lastActivity = (int) (
        $_SESSION['_last_activity']
        ?? $now
    );

    /*
     * Session expired.
     */
    if (
        ($now - $lastActivity)
        > AUTH_SESSION_TIMEOUT
    ) {
        auth_logout();

        return false;
    }

    /*
     * Update activity timestamp.
     */
    $_SESSION['_last_activity'] = $now;

    /*
     * Periodically regenerate session ID.
     * Helps prevent session fixation.
     */
    $lastRegenerated = (int) (
        $_SESSION['_last_regenerated']
        ?? 0
    );

    if (
        ($now - $lastRegenerated)
        >= AUTH_SESSION_REGENERATE_INTERVAL
    ) {
        session_regenerate_id(true);

        $_SESSION['_last_regenerated'] = $now;
    }

    return true;
}

function auth_is_active_session(int $userId, string $sessionToken): bool
{
    if ($userId <= 0 || $sessionToken === '') {
        return false;
    }

    try {
        $stmt = db_execute(
            'SELECT session_token_hash
             FROM active_user_sessions
             WHERE user_id = ?
               AND session_token_hash = ?
               AND updated_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
             LIMIT 1',
            'is',
            [$userId, hash('sha256', $sessionToken)]
        );
        $row = db_fetch_one_assoc($stmt) ?? [];
        $stmt->close();
        $isValid = hash_equals(
            (string) ($row['session_token_hash'] ?? ''),
            hash('sha256', $sessionToken)
        );
        if ($isValid) {
            db_execute(
                'UPDATE active_user_sessions
                 SET updated_at = CURRENT_TIMESTAMP
                 WHERE user_id = ? AND session_token_hash = ?',
                'is',
                [$userId, hash('sha256', $sessionToken)]
            )->close();
        }
        return $isValid;
    } catch (Throwable $exception) {
        error_log('Active session validation failed: ' . $exception->getMessage());
        return false;
    }
}

/*
|--------------------------------------------------------------------------
| CURRENT AUTHENTICATED USER
|--------------------------------------------------------------------------
*/

function auth_user(): ?array
{
    start_secure_session();

    /*
     * Check session timeout first.
     */
    if (!auth_check_session_timeout()) {
        return null;
    }

    if (
        !isset($_SESSION['auth_user'])
        ||
        !is_array($_SESSION['auth_user'])
    ) {
        return null;
    }

    $userId = (int) ($_SESSION['auth_user']['user_id'] ?? 0);

    if ($userId > 0 && (!isset($_SESSION['auth_user']['theme_preference']) || $_SESSION['auth_user']['theme_preference'] === '')) {
        $_SESSION['auth_user']['theme_preference'] = user_get_theme_preference($userId);
    }

    return $_SESSION['auth_user'];
}

/*
|--------------------------------------------------------------------------
| CHECK IF LOGGED IN
|--------------------------------------------------------------------------
*/

function auth_is_logged_in(): bool
{
    return auth_user() !== null;
}

/*
|--------------------------------------------------------------------------
| CHECK USER ROLE
|--------------------------------------------------------------------------
*/

function auth_is_role(string $role): bool
{
    $user = auth_user();

    if (!$user) {
        return false;
    }

    $currentRole = strtoupper(
        trim(
            (string) (
                $user['role_name']
                ?? ''
            )
        )
    );

    $requiredRole = strtoupper(
        trim($role)
    );

    return $currentRole === $requiredRole;
}

/*
|--------------------------------------------------------------------------
| CHECK MULTIPLE ROLES
|--------------------------------------------------------------------------
*/

function auth_has_any_role(array $roles): bool
{
    $user = auth_user();

    if (!$user) {
        return false;
    }

    $currentRole = strtoupper(
        trim(
            (string) (
                $user['role_name']
                ?? ''
            )
        )
    );

    foreach ($roles as $role) {

        if (
            $currentRole === strtoupper(
                trim((string) $role)
            )
        ) {
            return true;
        }
    }

    return false;
}

function auth_can_manage_documents(): bool
{
    return auth_has_any_role(['ADMIN', 'INCOMING']);
}

/*
|--------------------------------------------------------------------------
| MANUAL PAGE ACCESS CONTROL
|--------------------------------------------------------------------------
| Add only the page filename and the roles allowed to access it.
| If a page does not exist in this map, it is treated as open to
| any authenticated user.
|--------------------------------------------------------------------------
*/

function auth_default_page_access_map(): array
{
    return [
        'dashboard.php' => ['ADMIN', 'CITY_ATTORNEY', 'LEGAL_STAFF', 'RECORDS_OFFICER', 'DOCUMENT_VIEWER', 'OUTGOING'],
        'pending_access_requests.php' => ['ADMIN', 'CITY_ATTORNEY', 'LEGAL_STAFF', 'RECORDS_OFFICER', 'DOCUMENT_VIEWER', 'INCOMING', 'OUTGOING'],
        'all_documents.php' => ['ADMIN', 'CITY_ATTORNEY', 'LEGAL_STAFF', 'RECORDS_OFFICER', 'OUTGOING'],
        'document_management.php' => ['ADMIN', 'CITY_ATTORNEY', 'LEGAL_STAFF', 'RECORDS_OFFICER', 'DOCUMENT_VIEWER', 'INCOMING', 'OUTGOING'],
        'document_routing.php' => ['ADMIN', 'CITY_ATTORNEY', 'LEGAL_STAFF', 'RECORDS_OFFICER', 'INCOMING', 'OUTGOING'],
        'document_tracking.php' => ['ADMIN', 'CITY_ATTORNEY', 'LEGAL_STAFF', 'RECORDS_OFFICER', 'DOCUMENT_VIEWER', 'OUTGOING'],
        'pending_documents.php' => ['ADMIN', 'CITY_ATTORNEY', 'LEGAL_STAFF', 'RECORDS_OFFICER', 'OUTGOING'],
        'reports.php' => ['ADMIN', 'CITY_ATTORNEY', 'LEGAL_STAFF', 'RECORDS_OFFICER', 'OUTGOING'],
        'archive_documents.php' => ['ADMIN', 'CITY_ATTORNEY', 'LEGAL_STAFF', 'RECORDS_OFFICER', 'DOCUMENT_VIEWER', 'INCOMING', 'OUTGOING'],
        'admin.php' => ['ADMIN'],
        'role_management.php' => ['ADMIN'],
        'document_type_management.php' => ['ADMIN'],
        'department_management.php' => ['ADMIN'],
        'audit_trail.php' => ['ADMIN', 'CITY_ATTORNEY', 'LEGAL_STAFF', 'RECORDS_OFFICER', 'DOCUMENT_VIEWER', 'INCOMING', 'OUTGOING'],
        'system_settings.php' => ['ADMIN'],
    ];
}

function auth_get_allowed_pages_for_role(string $roleName): array
{
    $roleKey = strtoupper(trim($roleName));
    $rows = auth_get_page_access_rows();
    $allowedPages = [];

    foreach ($rows as $row) {
        $rowRole = strtoupper(trim((string) ($row['role_name'] ?? '')));
        $pageName = trim((string) ($row['page_name'] ?? ''));

        if ($rowRole !== $roleKey || $pageName === '') {
            continue;
        }

        if ((int) ($row['is_allowed'] ?? 1) === 1) {
            $allowedPages[] = strtolower($pageName);
        }
    }

    return array_values(array_unique($allowedPages));
}

function auth_page_access_map(): array
{
    $defaultMap = auth_default_page_access_map();
    $dbRows = auth_get_page_access_rows();

    if ($dbRows === []) {
        return $defaultMap;
    }

    $dbMap = [];

    foreach ($dbRows as $row) {
        $pageName = strtolower(trim((string) ($row['page_name'] ?? '')));
        $roleName = strtoupper(trim((string) ($row['role_name'] ?? '')));
        $isAllowed = (int) ($row['is_allowed'] ?? 1);

        if ($pageName === '' || $roleName === '') {
            continue;
        }

        if ($isAllowed !== 1) {
            continue;
        }

        if (!isset($dbMap[$pageName])) {
            $dbMap[$pageName] = [];
        }

        $dbMap[$pageName][] = $roleName;
    }

    foreach ($dbMap as $pageName => $roles) {
        $defaultMap[$pageName] = array_values(array_unique($roles));
    }

    return $defaultMap;
}

function auth_can_access_page(string $page): bool
{
    $user = auth_user();

    if (!$user) {
        return false;
    }

    $pageName = strtolower(basename((string) $page));
    $currentRole = strtoupper(
        trim(
            (string) (
                $user['role_name']
                ?? ''
            )
        )
    );

    if ($pageName === 'role_management.php') {
        return $currentRole === 'ADMIN';
    }

    $allowedPages = auth_get_allowed_pages_for_role($currentRole);

    if ($allowedPages === []) {
        $defaultMap = auth_default_page_access_map();
        $defaultAllowedRoles = $defaultMap[$pageName] ?? [];

        return in_array($currentRole, $defaultAllowedRoles, true);
    }

    return in_array($pageName, $allowedPages, true);
}

function auth_default_landing_page(): string
{
    foreach ([
        'dashboard.php',
        'document_routing.php',
        'document_management.php',
        'all_documents.php',
    ] as $page) {
        if (auth_can_access_page($page)) {
            return $page;
        }
    }

    return 'dashboard.php';
}

function auth_require_page(string $page): void
{
    auth_require_login();

    if (!auth_can_access_page($page)) {
        $pageName = strtolower(basename($page));

        http_response_code(403);

        flash_set('error', 'You do not have permission to access that page.');
        redirect_to(auth_default_landing_page());
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/

function auth_require_login(): void
{
    if (!auth_is_logged_in()) {

        flash_set(
            'error',
            'Your session has expired or you are not logged in. Please log in again.'
        );

        redirect_to('login.php');

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| REQUIRE SPECIFIC ROLE
|--------------------------------------------------------------------------
*/

function auth_require_role(array $roles): void
{
    auth_require_login();

    $user = auth_user();

    if (!$user) {

        flash_set(
            'error',
            'Please log in to continue.'
        );

        redirect_to('login.php');

        exit;
    }

    $currentRole = strtoupper(
        trim(
            (string) (
                $user['role_name']
                ?? ''
            )
        )
    );

    $allowedRoles = array_map(
        static function ($role): string {

            return strtoupper(
                trim((string) $role)
            );
        },
        $roles
    );

    if (
        !in_array(
            $currentRole,
            $allowedRoles,
            true
        )
    ) {

        http_response_code(403);

        flash_set(
            'error',
            'You do not have permission to access that page.'
        );

        redirect_to('dashboard.php');

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| REQUIRE ADMIN
|--------------------------------------------------------------------------
*/

function auth_require_admin(): void
{
    auth_require_role([
        'ADMIN'
    ]);
}

/*
|--------------------------------------------------------------------------
| LOGIN USER
|--------------------------------------------------------------------------
*/

function auth_create_device_login_request(int $userId, bool $rememberMe): string
{
    $token = bin2hex(random_bytes(32));
    db_execute(
        'INSERT INTO login_device_requests
            (user_id, request_token_hash, remember_me, expires_at)
         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))',
        'isi',
        [$userId, hash('sha256', $token), $rememberMe ? 1 : 0]
    )->close();
    return $token;
}

function auth_get_device_login_request(string $token): ?array
{
    if ($token === '') {
        return null;
    }
    $stmt = db_execute(
        'SELECT request_id, user_id, remember_me, status, expires_at
         FROM login_device_requests
         WHERE request_token_hash = ?
         LIMIT 1',
        's',
        [hash('sha256', $token)]
    );
    $row = db_fetch_one_assoc($stmt);
    $stmt->close();
    if (!$row) {
        return null;
    }
    if ($row['status'] === 'PENDING' && strtotime((string) $row['expires_at']) <= time()) {
        db_execute(
            'UPDATE login_device_requests
             SET status = "EXPIRED", decided_at = NOW()
             WHERE request_id = ? AND status = "PENDING"',
            'i',
            [(int) $row['request_id']]
        )->close();
        $row['status'] = 'EXPIRED';
    }
    return $row;
}

function auth_decide_device_login_request(int $requestId, int $userId, string $decision): bool
{
    $decision = strtoupper($decision);
    if (!in_array($decision, ['APPROVED', 'DENIED'], true)) {
        return false;
    }
    $stmt = db_execute(
        'SELECT user_id FROM login_device_requests
         WHERE request_id = ? AND status = "PENDING"
           AND expires_at > NOW()
         LIMIT 1',
        'i',
        [$requestId]
    );
    $request = db_fetch_one_assoc($stmt);
    $stmt->close();
    if (!$request || (int) $request['user_id'] !== $userId) {
        return false;
    }
    db_execute(
        'UPDATE login_device_requests
         SET status = ?, decided_at = NOW()
         WHERE request_id = ? AND status = "PENDING"',
        'si',
        [$decision, $requestId]
    )->close();
    return true;
}

function auth_login(
    array $user,
    bool $rememberMe = false,
    bool $allowAdditionalSession = false
): bool {

    if (
        session_status()
        !== PHP_SESSION_ACTIVE
    ) {
        start_secure_session();
    }

    /*
     * Regenerate session ID after successful authentication.
     */
    session_regenerate_id(true);

    $userId = (int) (
        $user['user_id']
        ?? 0
    );

    if ($userId > 0) {
        if (!$allowAdditionalSession) {
            db_execute(
                'DELETE FROM active_user_sessions
                 WHERE user_id = ?
                   AND updated_at <= DATE_SUB(NOW(), INTERVAL 30 MINUTE)',
                'i',
                [$userId]
            )->close();

            $existingSessionStmt = db_execute(
                'SELECT session_id
                 FROM active_user_sessions
                 WHERE user_id = ?
                 LIMIT 1',
                'i',
                [$userId]
            );
            $hasExistingSession = db_fetch_one_assoc($existingSessionStmt) !== null;
            $existingSessionStmt->close();
            if ($hasExistingSession) {
                return false;
            }
        }

        $sessionToken = bin2hex(random_bytes(32));
        $activeSessionStmt = db_execute(
            'INSERT INTO active_user_sessions
                (user_id, session_token_hash, updated_at)
             VALUES (?, ?, CURRENT_TIMESTAMP)',
            'is',
            [$userId, hash('sha256', $sessionToken)]
        );
        $sessionStarted = $activeSessionStmt->affected_rows === 1;
        $activeSessionStmt->close();

        if (!$sessionStarted) {
            return false;
        }

        $_SESSION['_active_session_token'] = $sessionToken;
    }

    /*
     * Store only required authentication data.
     */
    $_SESSION['auth_user'] = [

        'user_id' => $userId,

        'employee_no' => (string) (
            $user['employee_no']
            ?? ''
        ),

        'username' => (string) (
            $user['username']
            ?? ''
        ),

        'email' => (string) (
            $user['email']
            ?? ''
        ),

        'first_name' => (string) (
            $user['first_name']
            ?? ''
        ),

        'last_name' => (string) (
            $user['last_name']
            ?? ''
        ),

        'theme_preference' => normalize_theme_preference((string) (
            $user['theme_preference']
            ?? 'light'
        )),

        'role_name' => (string) (
            $user['role_name']
            ?? ''
        ),

        'department_name' => (string) (
            $user['department_name']
            ?? ''
        ),
    ];

    /*
     * Reset session security timestamps.
     */
    $_SESSION['_session_created_at'] = time();

    $_SESSION['_last_activity'] = time();

    $_SESSION['_last_regenerated'] = time();

    /*
     * Update last login in database.
     */
    if ($userId > 0) {

        auth_update_last_login(
            $userId
        );
    }

    /*
     * Remember me.
     */
    if ($rememberMe && $userId > 0) {

        auth_set_remember_me_cookie(
            $userId
        );
    }

    return true;
}

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

function auth_logout(): void
{
    $userId = (int) ($_SESSION['auth_user']['user_id'] ?? 0);
    $sessionToken = (string) ($_SESSION['_active_session_token'] ?? '');
    if ($userId > 0 && $sessionToken !== '') {
        db_execute(
            'DELETE FROM active_user_sessions
             WHERE user_id = ? AND session_token_hash = ?',
            'is',
            [$userId, hash('sha256', $sessionToken)]
        )->close();
    }

    if ($userId > 0) {
        db_execute(
            'DELETE FROM user_message_presence WHERE user_id = ?',
            'i',
            [$userId]
        )->close();
    }

    /*
     * Remove remember-me token.
     */
    auth_clear_remember_me_cookie();

    /*
     * Clear session data.
     */
    $_SESSION = [];

    /*
     * Remove session cookie.
     */
    if (
        ini_get('session.use_cookies')
    ) {

        $params =
            session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => (bool) $params['secure'],
                'httponly' => (bool) $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    /*
     * Destroy server-side session.
     */
    if (
        session_status()
        === PHP_SESSION_ACTIVE
    ) {
        session_destroy();
    }
}

/*
|--------------------------------------------------------------------------
| ATTEMPT LOGIN
|--------------------------------------------------------------------------
*/

function auth_attempt_login(
    string $login,
    string $password
): ?array {

    /*
     * Normalize input.
     */
    $login = trim($login);

    /*
     * Basic validation.
     */
    if ($login === '') {
        return null;
    }

    if ($password === '') {
        return null;
    }

    /*
     * Prevent extremely large input.
     */
    if (mb_strlen($login) > 150) {
        return null;
    }

    if (mb_strlen($password) > 500) {
        return null;
    }

    /*
     * AuthModel should use prepared statements.
     */
    $user = auth_find_user_by_login(
        $login
    );

    if (!$user) {
        return null;
    }

    /*
     * Account must be active.
     */
    if (
        (int) (
            $user['is_active']
            ?? 0
        ) !== 1
    ) {
        return null;
    }

    /*
     * Verify password securely.
     */
    if (
        !password_verify(
            $password,
            (string) (
                $user['password_hash']
                ?? ''
            )
        )
    ) {
        return null;
    }

    /*
     * Upgrade password hash automatically
     * when PHP recommends a stronger algorithm.
     *
     * No database update is performed here because
     * authentication model already handles database
     * operations separately.
     */
    if (
        password_needs_rehash(
            (string) $user['password_hash'],
            PASSWORD_DEFAULT
        )
    ) {
        /*
         * Intentionally left without database update.
         */
    }

    return $user;
}

/*
|--------------------------------------------------------------------------
| REMEMBER ME COOKIE
|--------------------------------------------------------------------------
*/

function auth_set_remember_me_cookie(
    int $userId
): void {

    if ($userId <= 0) {
        return;
    }

    $app = get_app_config();

    $selector = bin2hex(
        random_bytes(12)
    );

    $validator = bin2hex(
        random_bytes(32)
    );

    $tokenHash = hash(
        'sha256',
        $validator
    );

    $rememberDays = max(
        1,
        (int) (
            $app['remember_lifetime_days']
            ?? 30
        )
    );

    $expiresAt = date(
        'Y-m-d H:i:s',
        strtotime(
            '+' .
            $rememberDays .
            ' days'
        )
    );

    auth_store_remember_token(
        $userId,
        $selector,
        $tokenHash,
        $expiresAt
    );

    $cookieValue =
        $selector .
        ':' .
        $validator;

    $isHttps = (
        !empty($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== 'off'
    );

    setcookie(
        (string) (
            $app['remember_cookie_name']
            ?? 'CLO_REMEMBER'
        ),
        $cookieValue,
        [
            'expires' => strtotime($expiresAt),
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

/*
|--------------------------------------------------------------------------
| CLEAR REMEMBER ME COOKIE
|--------------------------------------------------------------------------
*/

function auth_clear_remember_me_cookie(): void
{
    $app = get_app_config();

    $cookieName =
        (string) (
            $app['remember_cookie_name']
            ?? 'CLO_REMEMBER'
        );

    if (
        !empty(
            $_COOKIE[$cookieName]
        )
    ) {

        $parts = explode(
            ':',
            (string) $_COOKIE[$cookieName],
            2
        );

        if (
            count($parts) === 2
            &&
            $parts[0] !== ''
        ) {

            auth_delete_remember_token_by_selector(
                $parts[0]
            );
        }
    }

    $isHttps = (
        !empty($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== 'off'
    );

    setcookie(
        $cookieName,
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );

    unset(
        $_COOKIE[$cookieName]
    );
}

/*
|--------------------------------------------------------------------------
| TRY REMEMBER ME LOGIN
|--------------------------------------------------------------------------
*/

function auth_try_remember_me_login(): void
{
    /*
     * Already authenticated.
     */
    if (auth_is_logged_in()) {
        return;
    }

    $app = get_app_config();

    $cookieName =
        (string) (
            $app['remember_cookie_name']
            ?? 'CLO_REMEMBER'
        );

    $cookieValue =
        $_COOKIE[$cookieName]
        ?? '';

    if (
        !is_string($cookieValue)
        ||
        $cookieValue === ''
    ) {
        return;
    }

    /*
     * Maximum safe cookie length.
     */
    if (
        strlen($cookieValue) > 200
    ) {

        auth_clear_remember_me_cookie();

        return;
    }

    /*
     * Cookie format:
     * selector:validator
     */
    if (
        strpos(
            $cookieValue,
            ':'
        ) === false
    ) {

        auth_clear_remember_me_cookie();

        return;
    }

    [
        $selector,
        $validator
    ] = explode(
        ':',
        $cookieValue,
        2
    );

    /*
     * Validate token components.
     */
    if (
        !preg_match(
            '/^[a-f0-9]{24}$/i',
            $selector
        )
        ||
        !preg_match(
            '/^[a-f0-9]{64}$/i',
            $validator
        )
    ) {

        auth_clear_remember_me_cookie();

        return;
    }

    /*
     * Find stored token.
     */
    $row = auth_find_remember_token(
        $selector
    );

    if (!$row) {

        auth_clear_remember_me_cookie();

        return;
    }

    /*
     * Check expiration.
     */
    $expiresTimestamp =
        strtotime(
            (string) (
                $row['expires_at']
                ?? ''
            )
        );

    if (
        $expiresTimestamp === false
        ||
        $expiresTimestamp < time()
    ) {

        auth_delete_remember_token_by_selector(
            $selector
        );

        auth_clear_remember_me_cookie();

        return;
    }

    /*
     * Hash supplied validator.
     */
    $validatorHash = hash(
        'sha256',
        $validator
    );

    /*
     * Constant-time comparison.
     */
    if (
        !hash_equals(
            (string) (
                $row['token_hash']
                ?? ''
            ),
            $validatorHash
        )
    ) {

        /*
         * Possible stolen token.
         * Invalidate all remember-me tokens
         * belonging to this user.
         */
        if (
            !empty($row['user_id'])
        ) {

            auth_delete_remember_tokens_by_user(
                (int) $row['user_id']
            );
        }

        auth_clear_remember_me_cookie();

        return;
    }

    /*
     * Load user.
     */
    $user = auth_find_user_by_id(
        (int) (
            $row['user_id']
            ?? 0
        )
    );

    if (
        !$user
        ||
        (int) (
            $user['is_active']
            ?? 0
        ) !== 1
    ) {

        auth_delete_remember_token_by_selector(
            $selector
        );

        auth_clear_remember_me_cookie();

        return;
    }

    /*
     * Update token usage.
     */
    auth_touch_remember_token(
        (int) (
            $row['token_id']
            ?? 0
        )
    );

    /*
     * Log user in.
     */
    auth_login(
        $user,
        false
    );
}
