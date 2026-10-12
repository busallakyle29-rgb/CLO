<?php
declare(strict_types=1);

require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../models/AuditTrailModel.php';

function controller_register_admin(array $post): array
{
    $errors = [];

    if (!csrf_validate('register_admin_form', $post['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request token. Please refresh and try again.';
        return ['errors' => $errors];
    }

    $employeeNo = auth_normalize_employee_no($post['employee_no'] ?? '');
    $username = trim((string) ($post['username'] ?? ''));
    $email = trim((string) ($post['email'] ?? ''));
    $firstName = trim((string) ($post['first_name'] ?? ''));
    $lastName = trim((string) ($post['last_name'] ?? ''));
    $cloDepartment = auth_get_clo_department();
    $departmentId = (int) ($cloDepartment['department_id'] ?? 0);
    $roleId = (int) ($post['role_id'] ?? 0);
    $password = (string) ($post['password'] ?? '');
    $confirmPassword = (string) ($post['confirm_password'] ?? '');
    $securityColor = auth_normalize_security_answer('color', (string) ($post['security_color'] ?? ''));
    $securityFood = auth_normalize_security_answer('food', (string) ($post['security_food'] ?? ''));
    $securityBirthdayInput = trim((string) ($post['security_birthday'] ?? ''));
    $securityBirthday = auth_normalize_security_answer('birthday', $securityBirthdayInput);
    $firstAdminSetup = !auth_has_admin_user();

    if ($employeeNo === '' || $username === '' || $email === '' || $firstName === '' || $lastName === '' || $departmentId <= 0) {
        $errors[] = 'All fields are required.';
    }

    if ($cloDepartment === null) {
        $errors[] = 'The CLO department is unavailable. Please contact the administrator.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    if ($employeeNo !== '' && $username !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $conflicts = auth_find_registration_conflicts($employeeNo, $username, $email);

        if (!empty($conflicts['employee_no'])) {
            $errors[] = 'Employee number is already registered.';
        }

        if (!empty($conflicts['username'])) {
            $errors[] = 'Username is already registered.';
        }

        if (!empty($conflicts['email'])) {
            $errors[] = 'Email address is already registered.';
        }
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    if ($securityColor === '' || $securityFood === '') {
        $errors[] = 'All security questions must be answered.';
    }

    $birthday = DateTimeImmutable::createFromFormat('!Y-m-d', $securityBirthdayInput);
    $today = new DateTimeImmutable('today');

    if (!$birthday || $birthday->format('Y-m-d') !== $securityBirthdayInput) {
        $errors[] = 'Please enter a valid full birthdate.';
    } elseif ($birthday > $today) {
        $errors[] = 'Birthdate cannot be in the future.';
    } elseif ($birthday < $today->modify('-120 years')) {
        $errors[] = 'Please enter a realistic birthdate.';
    }

    if (!$firstAdminSetup) {
        $registerableRoles = auth_get_registerable_roles();
        $allowedRoleIds = array_map(static fn (array $role): int => (int) ($role['role_id'] ?? 0), $registerableRoles);

        if ($roleId <= 0 || !in_array($roleId, $allowedRoleIds, true)) {
            $errors[] = 'Please select a valid staff role.';
        }
    }

    if ($errors !== []) {
        return ['errors' => $errors];
    }

    try {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($firstAdminSetup) {
            $userId = auth_create_admin_user([
                'employee_no' => $employeeNo,
                'username' => $username,
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'department_id' => $departmentId,
                'password_hash' => $passwordHash,
                'security_answer_color_hash' => password_hash($securityColor, PASSWORD_DEFAULT),
                'security_answer_food_hash' => password_hash($securityFood, PASSWORD_DEFAULT),
                'security_answer_birthday_hash' => password_hash($securityBirthday, PASSWORD_DEFAULT),
            ]);

            $user = auth_find_user_by_id($userId);

            if ($user) {
                auth_login($user, false);
            }

            return ['success' => 'Admin account created successfully.'];
        }

        $userId = auth_create_user_account([
            'employee_no' => $employeeNo,
            'username' => $username,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role_id' => $roleId,
            'department_id' => $departmentId,
            'is_active' => 0,
            'password_hash' => $passwordHash,
            'security_answer_color_hash' => password_hash($securityColor, PASSWORD_DEFAULT),
            'security_answer_food_hash' => password_hash($securityFood, PASSWORD_DEFAULT),
            'security_answer_birthday_hash' => password_hash($securityBirthday, PASSWORD_DEFAULT),
        ]);

        return ['success' => 'Registration submitted successfully. Your account is pending admin approval.'];
    } catch (Throwable $e) {
        return ['errors' => ['Unable to create account. Ensure username, email, or employee number is unique.']];
    }
}

function controller_login(array $post): array
{
    if (!csrf_validate('login_form', $post['csrf_token'] ?? null)) {
        return ['errors' => ['Invalid request token. Please refresh and try again.']];
    }

    $login = trim((string) ($post['login'] ?? ''));
    $password = (string) ($post['password'] ?? '');
    $rememberMe = isset($post['remember_me']) && $post['remember_me'] === '1';

    if ($login === '' || $password === '') {
        return ['errors' => ['Username/email and password are required.']];
    }

    $user = auth_attempt_login($login, $password);

    if (!$user) {
        return ['errors' => ['Invalid credentials or inactive account.']];
    }

    if (!auth_login($user, $rememberMe)) {
        $requestToken = auth_create_device_login_request((int) $user['user_id'], $rememberMe);
        $_SESSION['_pending_device_login_token'] = $requestToken;
        return ['errors' => [
            'This account is already signed in on another device. A login approval request was sent to that device.'
        ], 'device_login_pending' => true];
    }

    return ['success' => 'Login successful.'];
}

function controller_forgot_password(array $post): array
{
    if (!csrf_validate('forgot_password_form', $post['csrf_token'] ?? null)) {
        return ['errors' => ['Invalid request token. Please refresh and try again.']];
    }

    $login = trim((string) ($post['login'] ?? ''));
    $employeeNo = trim((string) ($post['employee_no'] ?? ''));

    if ($login === '' || $employeeNo === '') {
        return ['errors' => ['Unable to process the reset request.']];
    }

    $user = auth_find_user_for_reset($login, $employeeNo);

    if (!$user) {
        audit_log_create(null, 'PASSWORD_RESET', null, 'RESET_REQUEST_FAILED', null, [
            'attempted_login' => $login,
            'employee_no' => $employeeNo,
            'question' => null,
            'success' => false,
        ]);

        return [
            'success' => 'If the account details are valid, continue with the next verification step.',
            'reset_link' => null,
        ];
    }

    if (auth_is_reset_locked($user)) {
        audit_log_create((int) $user['user_id'], 'PASSWORD_RESET', (int) $user['user_id'], 'RESET_REQUEST_LOCKED', null, [
            'attempted_login' => $login,
            'employee_no' => $employeeNo,
            'question' => null,
            'success' => false,
        ]);

        return ['success' => 'Too many attempts. Please try again later.', 'reset_link' => null];
    }

    if ((int) ($user['is_active'] ?? 0) !== 1) {
        audit_log_create((int) $user['user_id'], 'PASSWORD_RESET', (int) $user['user_id'], 'RESET_REQUEST_FAILED', null, [
            'attempted_login' => $login,
            'employee_no' => $employeeNo,
            'question' => null,
            'success' => false,
        ]);

        return ['success' => 'If the account details are valid, continue with the next verification step.', 'reset_link' => null];
    }

    if (strtoupper(trim((string) ($user['role_name'] ?? ''))) === 'ADMIN') {
        audit_log_create((int) $user['user_id'], 'PASSWORD_RESET', (int) $user['user_id'], 'ADMIN_RESET_REQUESTED_MANUAL', null, [
            'attempted_login' => $login,
            'employee_no' => $employeeNo,
            'question' => null,
            'success' => false,
            'routing' => 'manual_admin_process',
        ]);

        return ['success' => 'Administrator password resets must be handled by IT or another administrator.', 'reset_link' => null];
    }

    $questionKeys = array_keys(auth_security_questions());
    $questionKey = $questionKeys[random_int(0, count($questionKeys) - 1)];

    $_SESSION['password_reset_flow'] = [
        'user_id' => (int) $user['user_id'],
        'login' => $login,
        'employee_no' => $employeeNo,
        'question_key' => $questionKey,
    ];

    return [
        'step' => 'security_question',
        'question_key' => $questionKey,
        'question' => auth_security_questions()[$questionKey],
        'success' => 'If the account details are valid, continue with the next verification step.',
        'reset_link' => null,
    ];
}

function controller_verify_security_answer(array $post): array
{
    if (!csrf_validate('security_question_form', $post['csrf_token'] ?? null)) {
        return ['errors' => ['Invalid request token. Please refresh and try again.']];
    }

    $flow = $_SESSION['password_reset_flow'] ?? null;

    if (!is_array($flow)) {
        return ['errors' => ['The reset request has expired. Please start again.']];
    }

    $userId = (int) ($flow['user_id'] ?? 0);
    $questionKey = (string) ($flow['question_key'] ?? '');
    $answer = auth_normalize_security_answer($questionKey, (string) ($post['answer'] ?? ''));
    $user = auth_find_user_by_id($userId);

    if (!$user || (int) ($user['is_active'] ?? 0) !== 1 || auth_is_reset_locked($user)) {
        unset($_SESSION['password_reset_flow']);
        return ['errors' => ['Unable to verify the reset request.']];
    }

    $hashColumn = [
        'color' => 'security_answer_color_hash',
        'food' => 'security_answer_food_hash',
        'birthday' => 'security_answer_birthday_hash',
    ][$questionKey] ?? '';
    $answerHash = (string) ($user[$hashColumn] ?? '');
    $verified = $answer !== '' && $answerHash !== '' && password_verify($answer, $answerHash);

    if (!$verified) {
        auth_increment_reset_failure($userId);
        audit_log_create($userId, 'PASSWORD_RESET', $userId, 'RESET_ANSWER_FAILED', null, [
            'attempted_login' => (string) ($flow['login'] ?? ''),
            'employee_no' => (string) ($flow['employee_no'] ?? ''),
            'question' => $questionKey,
            'success' => false,
        ]);

        return ['errors' => ['Unable to verify the reset request.']];
    }

    auth_reset_reset_failures($userId);
    $app = get_app_config();
    $selector = bin2hex(random_bytes(12));
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $app['reset_lifetime_minutes'] . ' minutes'));

    auth_store_password_reset($userId, $selector, $tokenHash, $expiresAt);
    audit_log_create($userId, 'PASSWORD_RESET', $userId, 'RESET_ANSWER_VERIFIED', null, [
        'attempted_login' => (string) ($flow['login'] ?? ''),
        'employee_no' => (string) ($flow['employee_no'] ?? ''),
        'question' => $questionKey,
        'success' => true,
    ]);
    unset($_SESSION['password_reset_flow']);

    return [
        'success' => 'Verification successful. Use the reset link below to set a new password.',
        'reset_link' => app_url('reset_password.php?selector=' . urlencode($selector) . '&token=' . urlencode($token)),
    ];
}

function controller_reset_password(array $post): array
{
    if (!csrf_validate('reset_password_form', $post['csrf_token'] ?? null)) {
        return ['errors' => ['Invalid request token. Please refresh and try again.']];
    }

    $selector = trim((string) ($post['selector'] ?? ''));
    $token = trim((string) ($post['token'] ?? ''));
    $password = (string) ($post['password'] ?? '');
    $confirm = (string) ($post['confirm_password'] ?? '');

    if ($selector === '' || $token === '') {
        return ['errors' => ['Reset token is missing or invalid.']];
    }

    if (strlen($password) < 8) {
        return ['errors' => ['Password must be at least 8 characters long.']];
    }

    if ($password !== $confirm) {
        return ['errors' => ['Password confirmation does not match.']];
    }

    $reset = auth_find_valid_password_reset($selector);

    if (!$reset) {
        return ['errors' => ['Reset token is invalid or expired.']];
    }

    if (!hash_equals((string) $reset['token_hash'], hash('sha256', $token))) {
        return ['errors' => ['Reset token is invalid or expired.']];
    }

    auth_update_user_password((int) $reset['user_id'], password_hash($password, PASSWORD_DEFAULT));
    auth_mark_password_reset_used((int) $reset['reset_id']);
    auth_delete_remember_tokens_by_user((int) $reset['user_id']);
    auth_reset_reset_failures((int) $reset['user_id']);
    audit_log_create((int) $reset['user_id'], 'PASSWORD_RESET', (int) $reset['user_id'], 'PASSWORD_RESET_COMPLETED', null, [
        'attempted_login' => null,
        'employee_no' => null,
        'question' => null,
        'success' => true,
    ]);

    return ['success' => 'Password updated successfully. You can now log in.'];
}
