<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$hasAdmin = auth_has_admin_user();
$registerableRoles = auth_get_registerable_roles();
$cloDepartment = auth_get_clo_department();
$departments = $cloDepartment ? [$cloDepartment] : [];
$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'employee_no' => trim((string) ($_POST['employee_no'] ?? '')),
        'username' => trim((string) ($_POST['username'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'first_name' => trim((string) ($_POST['first_name'] ?? '')),
        'last_name' => trim((string) ($_POST['last_name'] ?? '')),
        'security_color' => trim((string) ($_POST['security_color'] ?? '')),
        'security_food' => trim((string) ($_POST['security_food'] ?? '')),
        'security_birthday' => trim((string) ($_POST['security_birthday'] ?? '')),
        'department_id' => trim((string) ($_POST['department_id'] ?? '')),
        'role_id' => trim((string) ($_POST['role_id'] ?? '')),
    ];

    $result = controller_register_admin($_POST);

    if (!empty($result['errors'])) {
        $errors = $result['errors'];
    } else {
        if ($hasAdmin) {
            flash_set('success', 'Registration submitted successfully. Your account is pending admin approval.');
            redirect_to('login.php');
        }

        flash_set('success', 'Admin account registered successfully.');
        redirect_to('dashboard.php');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#003049">
    <title>City Legal Office | Register</title>
    <link rel="icon" type="image/png" href="<?php echo e(app_url('assets/logo/CLO_LOGO.png')); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo e(app_url('assets/css/features/auth/login.css')); ?>?v=20261006">
    <link rel="stylesheet" href="<?php echo e(app_url('assets/css/features/global/scrollbar.css')); ?>?v=20261006b">
    <link rel="stylesheet" href="<?php echo e(app_url('assets/css/features/global/page-ui-enhancements.css')); ?>?v=20261006a">
</head>
<body>
    <main class="login-page">
        <div class="login-content">
            <section class="login-card auth-shell is-register-mode" id="authShell">
                <div class="auth-panels">
                    <div class="auth-panel auth-panel-login">
                        <div class="login-logo">
                            <img src="<?php echo e(app_url('assets/logo/CLO_LOGO.png')); ?>" alt="City Legal Office Logo" onerror="this.style.display='none'; document.getElementById('loginFallbackIcon').style.display='block';">
                            <i id="loginFallbackIcon" class="bi bi-bank2" style="display:none;"></i>
                        </div>
                        <div class="login-office-name">CITY LEGAL OFFICE</div>
                        <div class="login-system-name">DOCUMENT TRACKING SYSTEM</div>
                        <h1 class="login-title">Ready to Join?</h1>
                        <p class="login-subtitle">Create your account and wait for admin approval.</p>
                        <div class="security-notice">
                            <i class="bi bi-shield-lock-fill"></i>
                            <span>Your account details are protected.</span>
                        </div>
                        <div class="register-section">
                            <span>Already registered?</span>
                            <a href="<?php echo e(app_url('login.php')); ?>">Sign In</a>
                        </div>
                    </div>

                    <div class="auth-panel auth-panel-register">
                        <?php require dirname(__DIR__, 2) . '/views/auth/register_admin_form.php'; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?php echo e(app_url('assets/js/features/auth/login.js')); ?>"></script>
</body>
</html>
