<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if (auth_is_logged_in()) {
    redirect_to(auth_default_landing_page());
}

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['login'] = trim((string) ($_POST['login'] ?? ''));
    $result = controller_login($_POST);

    if (!empty($result['errors'])) {
        $errors = $result['errors'];
    } else {
        flash_set('success', 'Welcome back.');
        redirect_to(auth_default_landing_page());
    }
}

$flashSuccess = flash_get('success');
$flashError = flash_get('error');
$deviceLoginPending = !empty($_SESSION['_pending_device_login_token']);

if ($flashError !== null && $flashError !== '') {
    $errors[] = $flashError;
}

$firstAdminSetup = !auth_has_admin_user();
$registerableRoles = auth_get_registerable_roles();
$cloDepartment = auth_get_clo_department();
$departments = $cloDepartment ? [$cloDepartment] : [];
$activateRegister = isset($_GET['register']) && $_GET['register'] === '1';

// require dirname(__DIR__, 2) . '/includes/header.php';
// require dirname(__DIR__, 2) . '/views/auth/login_form.php';
// require dirname(__DIR__, 2) . '/includes/footer.php';

?>

<!-- /*
|--------------------------------------------------------------------------
| Login Page
|--------------------------------------------------------------------------
| Keep your existing authentication/login processing logic above this
| point if you already have one.
|--------------------------------------------------------------------------
*/

// Your existing login processing should remain here.
// Do NOT remove your existing authentication logic.

// Example:
// $errors = $errors ?? [];
// $old = $old ?? [];

/*
|--------------------------------------------------------------------------
| CITY LEGAL OFFICE
| DOCUMENT TRACKING SYSTEM
| LOGIN PAGE
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Keep your existing authentication/login processing logic above
| this HTML section.
|
|--------------------------------------------------------------------------
*/ -->

<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="theme-color"
        content="#003049"
    >

    <title>
        City Legal Office | Sign In
    </title>

    <!-- =====================================================
         FAVICON
    ====================================================== -->

    <link
        rel="icon"
        type="image/png"
        href="<?php echo e(
            app_url('assets/logo/CLO_LOGO.png')
        ); ?>"
    >

    <!-- =====================================================
         BOOTSTRAP CSS
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >

    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- =====================================================
         LOGIN CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="<?php echo e(
            app_url('assets/css/features/auth/login.css') . '?v=20261006'
        ); ?>"
    >
    <link
        rel="stylesheet"
        href="<?php echo e(app_url('assets/css/features/global/scrollbar.css')); ?>?v=20261006b"
    >
    <link
        rel="stylesheet"
        href="<?php echo e(app_url('assets/css/features/global/page-ui-enhancements.css')); ?>?v=20261006a"
    >

</head>

<body>

<!-- =========================================================
     LOGIN PAGE
========================================================= -->

<main class="login-page">

    <!-- =====================================================
         BACKGROUND DECORATIONS
    ====================================================== -->

    <div
        class="login-circle login-circle-1"
        aria-hidden="true"
    ></div>

    <div
        class="login-circle login-circle-2"
        aria-hidden="true"
    ></div>

    <div
        class="login-circle login-circle-3"
        aria-hidden="true"
    ></div>

    <!-- =====================================================
         LOGIN CONTENT
    ====================================================== -->

    <div class="login-content">

        <!-- =================================================
             LOGIN CARD
        ================================================== -->

        <section class="login-card auth-shell <?php echo $activateRegister ? 'is-register-mode' : ''; ?>" id="authShell">
            <div class="auth-panels">
                <div class="auth-panel auth-panel-login">
                    <div class="login-logo">
                        <img
                            src="<?php echo e(app_url('assets/logo/CLO_LOGO.png')); ?>"
                            alt="City Legal Office Logo"
                            onerror="this.style.display='none'; document.getElementById('loginFallbackIcon').style.display='block';"
                        >
                        <i id="loginFallbackIcon" class="bi bi-bank2" style="display:none;"></i>
                    </div>

                    <div class="login-office-name">CITY LEGAL OFFICE</div>
                    <div class="login-system-name">DOCUMENT TRACKING SYSTEM</div>
                    <h1 class="login-title">Welcome Back</h1>
                    <p class="login-subtitle">Sign in to access the Document Tracking System.</p>

                    <?php if (!empty($errors)): ?>
                        <div class="login-alert" role="alert">
                            <div class="login-alert-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                            <div>
                                <?php foreach ($errors as $error): ?>
                                    <div class="login-error"><?php echo e((string) $error); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($flashSuccess)): ?>
                        <div class="login-success" role="alert">
                            <div class="login-success-icon"><i class="bi bi-check-circle-fill"></i></div>
                            <div><?php echo e($flashSuccess); ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo e(app_url('login.php')); ?>" id="loginForm" autocomplete="on">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('login_form')); ?>">

                        <div class="login-field">
                            <label for="login" class="login-label">Username or Email</label>
                            <div class="input-wrapper">
                                <i class="bi bi-person"></i>
                                <input type="text" class="login-input" id="login" name="login" placeholder="Enter username or email" autocomplete="username" required value="<?php echo e($old['login'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="login-field">
                            <div class="password-label-row">
                                <label for="password" class="login-label mb-0">Password</label>
                                <a href="<?php echo e(app_url('forgot_password.php')); ?>" class="forgot-link">Forgot password?</a>
                            </div>

                            <div class="input-wrapper mt-2">
                                <i class="bi bi-lock"></i>
                                <input type="password" class="login-input" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                                <button type="button" class="password-toggle" id="passwordToggle" aria-label="Show password">
                                    <i class="bi bi-eye" id="passwordIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="remember-row">
                            <label class="remember-label" for="remember_me">
                                <input class="remember-checkbox" type="checkbox" id="remember_me" name="remember_me" value="1">
                                <span class="custom-checkbox"></span>
                                <span>Remember me</span>
                            </label>
                        </div>

                        <button type="submit" class="login-button" id="loginButton">
                            <span class="login-button-icon"><i class="bi bi-box-arrow-in-right"></i></span>
                            <span id="loginButtonText">Sign In</span>
                            <i class="bi bi-arrow-right login-arrow"></i>
                        </button>
                    </form>

                    <div class="register-section">
                        <span>Need an User Account?</span>
                        <a href="#" data-auth-toggle="register">Create Account</a>
                    </div>

                    <div class="security-notice">
                        <i class="bi bi-shield-lock-fill"></i>
                        <span>Your connection and account information are protected.</span>
                    </div>
                </div>

                <div class="auth-panel auth-panel-register">
                    <?php
                        $old = $old ?? [];
                        $errors = $errors ?? [];
                        require dirname(__DIR__, 2) . '/views/auth/register_admin_form.php';
                    ?>
                </div>
            </div>
        </section>

        <!-- =================================================
             BACK TO CITY LEGAL OFFICE
        ================================================== -->

        <div class="back-home">

            <a
                href="<?php echo e(
                    app_url('index.php')
                ); ?>"
            >

                <i class="bi bi-arrow-left"></i>

                Back to City Legal Office

            </a>

        </div>

        <!-- =================================================
             FOOTER
        ================================================== -->

        <div class="login-footer">

            © 2026 City Legal Office

            <span>•</span>

            Document Tracking System

        </div>

    </div>

</main>

<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>

<!-- =========================================================
     LOGIN JS
========================================================= -->

<script
    src="<?php echo e(
        app_url('assets/js/features/auth/login.js')
    ); ?>"
></script>

<?php if ($deviceLoginPending): ?>
<script>
(() => {
    const endpoint = <?php echo json_encode(app_url('device_login_request.php')); ?>;
    const landingPage = <?php echo json_encode(app_url('dashboard.php')); ?>;
    const poll = async () => {
        try {
            const response = await fetch(endpoint, { credentials: 'same-origin', cache: 'no-store' });
            const result = await response.json();
            if (result.status === 'APPROVED') {
                window.location.href = landingPage;
                return;
            }
            if (result.status === 'DENIED' || result.status === 'EXPIRED' || result.status === 'NONE') {
                return;
            }
            window.setTimeout(poll, 3000);
        } catch (error) {
            window.setTimeout(poll, 5000);
        }
    };
    window.setTimeout(poll, 1500);
})();
</script>
<?php endif; ?>

</body>

</html>
