<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/models/UserProfileModel.php';

auth_require_login();

$user = auth_user();
$userId = (int) ($user['user_id'] ?? 0);

if ($userId <= 0) {
    flash_set('error', 'Unable to determine logged-in user.');
    redirect_to('dashboard.php');
    exit;
}

$profile = profile_get_user($userId);
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $csrfForm = match ($action) {
        'change_password' => 'profile_password_form',
        'update_security_answers' => 'profile_security_answers_form',
        'upload_profile_picture' => 'profile_picture_form',
        'remove_profile_picture' => 'profile_picture_form',
        default => 'profile_update_form',
    };

    $csrfToken = $_POST['csrf_token'] ?? null;
    $csrfValid = csrf_validate($csrfForm, $csrfToken);

    if (
        !$csrfValid
        && in_array(
            $action,
            ['upload_profile_picture', 'remove_profile_picture'],
            true
        )
    ) {
        $csrfValid = csrf_validate('profile_update_form', $csrfToken);
    }

    if (!$csrfValid) {
        flash_set('error', 'Invalid request token. Please refresh and try again.');
        redirect_to('user_profile.php');
    } elseif ($action === 'update_profile') {
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($firstName === '') {
            $errors[] = 'First name is required.';
        }
        if ($lastName === '') {
            $errors[] = 'Last name is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email address is required.';
        }

        if (empty($errors)) {
            try {
                profile_update_user($userId, [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                ]);

                if (isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])) {
                    $_SESSION['auth_user']['first_name'] = $firstName;
                    $_SESSION['auth_user']['last_name'] = $lastName;
                    $_SESSION['auth_user']['email'] = $email;
                }

                $successMessage = 'Profile updated successfully.';
                $profile = profile_get_user($userId);
            } catch (Throwable $e) {
                $errors[] = 'Unable to update profile: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($currentPassword === '') {
            $errors[] = 'Current password is required.';
        }
        if ($newPassword === '' || strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors) && $profile) {
            if (!profile_verify_password($currentPassword, (string) ($profile['password_hash'] ?? ''))) {
                $errors[] = 'Current password is incorrect.';
            } else {
                try {
                    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    profile_update_password($userId, $passwordHash);
                    $successMessage = 'Password changed successfully.';
                } catch (Throwable $e) {
                    $errors[] = 'Unable to change password: ' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'update_security_answers') {
        $securityColor = auth_normalize_security_answer('color', (string) ($_POST['security_color'] ?? ''));
        $securityFood = auth_normalize_security_answer('food', (string) ($_POST['security_food'] ?? ''));
        $securityBirthday = auth_normalize_security_answer('birthday', (string) ($_POST['security_birthday'] ?? ''));

        if ($securityColor === '' || $securityFood === '' || $securityBirthday === '') {
            $errors[] = 'All security questions must be answered.';
        } else {
            try {
                profile_update_security_answers($userId, [
                    'color' => $securityColor,
                    'food' => $securityFood,
                    'birthday' => $securityBirthday,
                ]);
                $successMessage = 'Security answers updated successfully.';
            } catch (Throwable $e) {
                $errors[] = 'Unable to update security answers.';
            }
        }
    } elseif ($action === 'remove_profile_picture') {
        $oldPicture = (string) ($profile['profile_picture'] ?? '');

        try {
            profile_remove_picture($userId);
            if ($oldPicture !== '' && str_starts_with($oldPicture, 'uploads/profile_pictures/')) {
                $oldPath = dirname(__DIR__, 2) . '/' . $oldPicture;
                if (is_file($oldPath)) {
                    unlink($oldPath);
                }
            }
            $successMessage = 'Profile picture removed successfully.';
            $profile = profile_get_user($userId);
            if (isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])) {
                $_SESSION['auth_user']['profile_picture'] = null;
            }
        } catch (Throwable $e) {
            $errors[] = 'Unable to remove profile picture: ' . $e->getMessage();
        }
    } elseif ($action === 'upload_profile_picture') {
        $upload = $_FILES['profile_picture'] ?? null;

        if (!is_array($upload) || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Please select a profile picture.';
        } elseif ((int) ($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors[] = 'Unable to upload the profile picture.';
        } elseif ((int) ($upload['size'] ?? 0) > 2 * 1024 * 1024) {
            $errors[] = 'Profile picture must not exceed 2 MB.';
        } elseif (!is_uploaded_file((string) ($upload['tmp_name'] ?? ''))) {
            $errors[] = 'Invalid profile picture upload.';
        } else {
            $imageInfo = @getimagesize((string) $upload['tmp_name']);
            $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file((string) $upload['tmp_name']);
            $allowedMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
            ];

            if ($imageInfo === false || !isset($allowedMimeTypes[$mimeType])) {
                $errors[] = 'Please upload a valid JPG, PNG, GIF, or WebP image.';
            } else {
                $uploadDirectory = dirname(__DIR__, 2) . '/uploads/profile_pictures';
                $relativePath = 'uploads/profile_pictures/' . bin2hex(random_bytes(16)) . '.' . $allowedMimeTypes[$mimeType];
                $destination = dirname(__DIR__, 2) . '/' . $relativePath;

                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                    $errors[] = 'Unable to prepare profile picture storage.';
                } elseif (!move_uploaded_file((string) $upload['tmp_name'], $destination)) {
                    $errors[] = 'Unable to save the profile picture.';
                } else {
                    try {
                        profile_update_picture($userId, $relativePath);
                        $oldPicture = (string) ($profile['profile_picture'] ?? '');
                        if ($oldPicture !== '' && str_starts_with($oldPicture, 'uploads/profile_pictures/')) {
                            $oldPath = dirname(__DIR__, 2) . '/' . $oldPicture;
                            if (is_file($oldPath)) {
                                unlink($oldPath);
                            }
                        }
                        $successMessage = 'Profile picture updated successfully.';
                        $profile = profile_get_user($userId);
                        if (isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])) {
                            $_SESSION['auth_user']['profile_picture'] = $relativePath;
                        }
                    } catch (Throwable $e) {
                        if (is_file($destination)) {
                            unlink($destination);
                        }
                        $errors[] = 'Unable to update profile picture: ' . $e->getMessage();
                    }
                }
            }
        }
    }

    if ($successMessage !== '' && empty($errors)) {
        flash_set('success', $successMessage);
        redirect_to('user_profile.php');
    }
}

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">

    <!-- PAGE HEADER -->
    <div class="page-header bg-white rounded-3 shadow-sm p-4 mb-4">
        <div>
            <h3 class="mb-1">
                <i class="bi bi-person-circle text-primary"></i>
                My Profile
            </h3>
            <div class="text-muted">
                View and manage your user profile information
            </div>
        </div>
    </div>

    <!-- ALERTS -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Error:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($successMessage !== ''): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-1"></i>
            <?php echo e($successMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($profile): ?>

        <div class="row g-4">

            <!-- PROFILE INFORMATION -->
            <div class="col-lg-8">

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <strong>
                            <i class="bi bi-file-person text-primary"></i>
                            Personal Information
                        </strong>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <?php echo csrf_field('profile_update_form'); ?>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input
                                        type="text"
                                        name="first_name"
                                        class="form-control"
                                        value="<?php echo e((string) ($profile['first_name'] ?? '')); ?>"
                                        required
                                    >
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input
                                        type="text"
                                        name="last_name"
                                        class="form-control"
                                        value="<?php echo e((string) ($profile['last_name'] ?? '')); ?>"
                                        required
                                    >
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Email Address</label>
                                    <input
                                        type="email"
                                        name="email"
                                        class="form-control"
                                        value="<?php echo e((string) ($profile['email'] ?? '')); ?>"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- SECURITY ANSWERS -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white py-3">
                        <strong>
                            <i class="bi bi-shield-lock text-primary"></i>
                            Password Recovery Questions
                        </strong>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">
                            Set all three answers so you can recover your password if needed.
                        </p>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_security_answers">
                            <?php echo csrf_field('profile_security_answers_form'); ?>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="security_color">Favorite Color</label>
                                    <input type="text" id="security_color" name="security_color" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="security_food">Favorite Food</label>
                                    <input type="text" id="security_food" name="security_food" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="security_birthday">Full Birthdate</label>
                                    <input type="date" id="security_birthday" name="security_birthday" class="form-control" required>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Save Recovery Answers
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- CHANGE PASSWORD -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white py-3">
                        <strong>
                            <i class="bi bi-lock text-primary"></i>
                            Change Password
                        </strong>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <?php echo csrf_field('profile_password_form'); ?>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Current Password</label>
                                    <input
                                        type="password"
                                        name="current_password"
                                        class="form-control"
                                        required
                                    >
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">New Password</label>
                                    <input
                                        type="password"
                                        name="new_password"
                                        class="form-control"
                                        placeholder="Min. 8 characters"
                                        required
                                    >
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirm Password</label>
                                    <input
                                        type="password"
                                        name="confirm_password"
                                        class="form-control"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-lock-fill me-1"></i>
                                    Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            <!-- PROFILE DETAILS SIDEBAR -->
            <div class="col-lg-4">

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <strong>
                            <i class="bi bi-camera text-primary"></i>
                            Profile Picture
                        </strong>
                    </div>
                    <div class="card-body text-center">
                        <?php if (!empty($profile['profile_picture'])): ?>
                            <img
                                src="<?php echo e(app_url((string) $profile['profile_picture'])); ?>"
                                alt="Profile picture"
                                class="profile-picture-preview mb-3"
                            >
                        <?php else: ?>
                            <div class="profile-picture-placeholder mb-3">
                                <i class="bi bi-person"></i>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_profile_picture">
                            <?php echo csrf_field('profile_picture_form'); ?>
                            <input
                                type="file"
                                name="profile_picture"
                                class="form-control mb-2"
                                accept="image/jpeg,image/png,image/gif,image/webp"
                                required
                            >
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-upload me-1"></i>
                                Upload Picture
                            </button>
                        </form>
                        <?php if (!empty($profile['profile_picture'])): ?>
                            <form method="POST" class="mt-2">
                                <input type="hidden" name="action" value="remove_profile_picture">
                                <?php echo csrf_field('profile_picture_form'); ?>
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash me-1"></i>
                                    Remove Picture
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <strong>
                            <i class="bi bi-info-circle text-primary"></i>
                            Account Details
                        </strong>
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label small text-muted">Username</label>
                            <div class="fw-semibold">
                                <?php echo e((string) ($profile['username'] ?? 'N/A')); ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Employee Number</label>
                            <div class="fw-semibold">
                                <?php echo e((string) ($profile['employee_no'] ?? 'N/A')); ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Role</label>
                            <div class="fw-semibold">
                                <span class="badge bg-primary">
                                    <?php echo e((string) ($profile['role_name'] ?? 'Unknown')); ?>
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Department</label>
                            <div class="fw-semibold">
                                <?php echo e((string) ($profile['department_name'] ?? 'N/A')); ?>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Account Status</label>
                            <div>
                                <?php if ((int) ($profile['is_active'] ?? 0) === 1): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($profile['last_login_at'])): ?>
                            <div class="mb-3">
                                <label class="form-label small text-muted">Last Login</label>
                                <div class="small">
                                    <?php echo e(date('M d, Y h:i A', strtotime((string) $profile['last_login_at']))); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Account Created</label>
                            <div class="small">
                                <?php echo e(date('M d, Y', strtotime((string) ($profile['created_at'] ?? 'now')))); ?>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>

    <?php else: ?>

        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Unable to load profile information.
        </div>

    <?php endif; ?>

</div>

<?php
require dirname(__DIR__, 2) . '/includes/footer.php';
?>
