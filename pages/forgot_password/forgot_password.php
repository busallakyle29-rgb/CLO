<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$errors = [];
$old = [];
$resetLink = null;
$successMessage = null;
$step = 'request';
$question = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['login'] = trim((string) ($_POST['login'] ?? ''));
    $old['employee_no'] = trim((string) ($_POST['employee_no'] ?? ''));

    if ((string) ($_POST['step'] ?? 'request') === 'security_question') {
        $result = controller_verify_security_answer($_POST);
    } else {
        $result = controller_forgot_password($_POST);
    }

    if (!empty($result['errors'])) {
        $errors = $result['errors'];
        $step = (string) ($_POST['step'] ?? 'request');
        if ($step === 'security_question') {
            $flow = $_SESSION['password_reset_flow'] ?? [];
            $questionKey = (string) ($flow['question_key'] ?? '');
            $question = auth_security_questions()[$questionKey] ?? null;
        }
    } else {
        $successMessage = (string) ($result['success'] ?? 'Reset request submitted.');
        $resetLink = $result['reset_link'] ?? null;
        $step = (string) ($result['step'] ?? ($resetLink ? 'complete' : 'request'));
        $question = $result['question'] ?? null;
    }
}

if (isset($_SESSION['password_reset_flow']) && $step === 'request' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $step = 'security_question';
    $flow = $_SESSION['password_reset_flow'];
    $questionKey = (string) ($flow['question_key'] ?? '');
    $question = auth_security_questions()[$questionKey] ?? null;
}

require dirname(__DIR__, 2) . '/includes/header.php';
if ($step === 'security_question') {
    require dirname(__DIR__, 2) . '/views/auth/security_question_form.php';
} else {
    require dirname(__DIR__, 2) . '/views/auth/forgot_password_form.php';
}
require dirname(__DIR__, 2) . '/includes/footer.php';
