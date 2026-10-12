<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SYSTEM SETTINGS
|--------------------------------------------------------------------------
| Admin-only page.
|
| Allows administrator to change:
| - Office Name
| - Logo
| - Address
| - System Title
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/models/SystemSettingsModel.php';

/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/

auth_require_page('system_settings.php');

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['system_settings_csrf'])
    ||
    !is_string($_SESSION['system_settings_csrf'])
) {

    $_SESSION['system_settings_csrf'] =
        bin2hex(
            random_bytes(32)
        );
}

$csrfToken =
    (string) $_SESSION['system_settings_csrf'];

/*
|--------------------------------------------------------------------------
| LOAD CURRENT SETTINGS
|--------------------------------------------------------------------------
*/

$settings =
    system_settings_get();

/*
|--------------------------------------------------------------------------
| FORM DEFAULTS
|--------------------------------------------------------------------------
*/

$officeName =
    (string) (
        $settings['office_name']
        ?? 'City Legal Office'
    );

$address =
    (string) (
        $settings['address']
        ?? ''
    );

$systemTitle =
    (string) (
        $settings['system_title']
        ?? 'Document Tracking'
    );

$currentLogo =
    (string) (
        $settings['logo_path']
        ?? ''
    );

/*
|--------------------------------------------------------------------------
| HANDLE FORM
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        /*
        |--------------------------------------------------------------------------
        | CSRF
        |--------------------------------------------------------------------------
        */

        $submittedToken =
            (string) (
                $_POST['csrf_token']
                ?? ''
            );

        if (
            $submittedToken === ''
            ||
            !hash_equals(
                $csrfToken,
                $submittedToken
            )
        ) {

            throw new RuntimeException(
                'Invalid security token. Please refresh the page and try again.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | INPUT
        |--------------------------------------------------------------------------
        */

        $officeName =
            trim(
                (string) (
                    $_POST['office_name']
                    ?? ''
                )
            );

        $address =
            trim(
                (string) (
                    $_POST['address']
                    ?? ''
                )
            );

        $systemTitle =
            trim(
                (string) (
                    $_POST['system_title']
                    ?? ''
                )
            );

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if ($officeName === '') {

            throw new RuntimeException(
                'Office Name is required.'
            );
        }

        if (mb_strlen($officeName) > 255) {

            throw new RuntimeException(
                'Office Name must not exceed 255 characters.'
            );
        }

        if ($systemTitle === '') {

            throw new RuntimeException(
                'System Title is required.'
            );
        }

        if (mb_strlen($systemTitle) > 255) {

            throw new RuntimeException(
                'System Title must not exceed 255 characters.'
            );
        }

        if (mb_strlen($address) > 5000) {

            throw new RuntimeException(
                'Address is too long.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | LOGO
        |--------------------------------------------------------------------------
        */

        $newLogoPath = null;
        $uploadedLogoFullPath = null;
        $oldLogoFullPath = null;

        $hasUpload =
            isset($_FILES['logo'])
            &&
            is_array($_FILES['logo'])
            &&
            (
                (int) (
                    $_FILES['logo']['error']
                    ?? UPLOAD_ERR_NO_FILE
                )
                !== UPLOAD_ERR_NO_FILE
            );

        if ($hasUpload) {

            $uploadError =
                (int) (
                    $_FILES['logo']['error']
                    ?? UPLOAD_ERR_NO_FILE
                );

            if (
                $uploadError
                !== UPLOAD_ERR_OK
            ) {

                $uploadMessages = [

                    UPLOAD_ERR_INI_SIZE =>
                        'The uploaded logo is too large.',

                    UPLOAD_ERR_FORM_SIZE =>
                        'The uploaded logo is too large.',

                    UPLOAD_ERR_PARTIAL =>
                        'The logo upload was incomplete.',

                    UPLOAD_ERR_NO_TMP_DIR =>
                        'The server temporary upload directory is missing.',

                    UPLOAD_ERR_CANT_WRITE =>
                        'The server could not save the uploaded logo.',

                    UPLOAD_ERR_EXTENSION =>
                        'The logo upload was blocked by the server.',

                ];

                throw new RuntimeException(
                    $uploadMessages[$uploadError]
                    ?? 'The logo upload failed.'
                );
            }

            $tmpName =
                (string) (
                    $_FILES['logo']['tmp_name']
                    ?? ''
                );

            $originalName =
                (string) (
                    $_FILES['logo']['name']
                    ?? ''
                );

            $fileSize =
                (int) (
                    $_FILES['logo']['size']
                    ?? 0
                );

            /*
            |--------------------------------------------------------------------------
            | File size: 5 MB maximum
            |--------------------------------------------------------------------------
            */

            if ($fileSize <= 0) {

                throw new RuntimeException(
                    'The uploaded logo is empty.'
                );
            }

            if ($fileSize > 5 * 1024 * 1024) {

                throw new RuntimeException(
                    'Logo size must not exceed 5 MB.'
                );
            }

            if (
                !is_uploaded_file($tmpName)
            ) {

                throw new RuntimeException(
                    'Invalid logo upload.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Detect MIME type
            |--------------------------------------------------------------------------
            */

            $finfo =
                new finfo(
                    FILEINFO_MIME_TYPE
                );

            $mimeType =
                $finfo->file(
                    $tmpName
                );

            $allowedMimeTypes = [

                'image/jpeg' => 'jpg',

                'image/png' => 'png',

                'image/gif' => 'gif',

                'image/webp' => 'webp',

            ];

            if (
                !isset(
                    $allowedMimeTypes[$mimeType]
                )
            ) {

                throw new RuntimeException(
                    'Invalid logo format. Please upload JPG, PNG, GIF, or WebP.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Verify actual image
            |--------------------------------------------------------------------------
            */

            $imageInfo =
                @getimagesize(
                    $tmpName
                );

            if (
                $imageInfo === false
            ) {

                throw new RuntimeException(
                    'The uploaded file is not a valid image.'
                );
            }

            $extension =
                $allowedMimeTypes[$mimeType];

            /*
            |--------------------------------------------------------------------------
            | Create upload directory
            |--------------------------------------------------------------------------
            */

            $uploadDirectory =
                dirname(__DIR__, 2)
                . '/uploads/settings';

            if (
                !is_dir(
                    $uploadDirectory
                )
            ) {

                if (
                    !mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    )
                    &&
                    !is_dir(
                        $uploadDirectory
                    )
                ) {

                    throw new RuntimeException(
                        'Unable to create the logo upload directory.'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Random filename
            |--------------------------------------------------------------------------
            */

            $filename =
                'logo_'
                . bin2hex(
                    random_bytes(16)
                )
                . '.'
                . $extension;

            $destination =
                $uploadDirectory
                . DIRECTORY_SEPARATOR
                . $filename;

            /*
            |--------------------------------------------------------------------------
            | Move file
            |--------------------------------------------------------------------------
            */

            if (
                !move_uploaded_file(
                    $tmpName,
                    $destination
                )
            ) {

                throw new RuntimeException(
                    'Unable to save the uploaded logo.'
                );
            }

            $uploadedLogoFullPath =
                $destination;

            /*
            |--------------------------------------------------------------------------
            | Database path
            |--------------------------------------------------------------------------
            */

            $newLogoPath =
                'uploads/settings/'
                . $filename;
        }

        /*
        |--------------------------------------------------------------------------
        | CURRENT USER
        |--------------------------------------------------------------------------
        */

        $currentUser =
            auth_user();

        $updatedBy =
            null;

        if (
            is_array($currentUser)
            &&
            isset($currentUser['user_id'])
        ) {

            $updatedBy =
                (int) $currentUser['user_id'];
        }

        /*
        |--------------------------------------------------------------------------
        | Old logo path
        |--------------------------------------------------------------------------
        */

        if (
            $currentLogo !== ''
        ) {

            $oldLogoFullPath =
                dirname(__DIR__, 2)
                . '/'
                . ltrim(
                    $currentLogo,
                    '/'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE DATABASE
        |--------------------------------------------------------------------------
        */

        system_settings_update(
            $officeName,
            $newLogoPath,
            $address,
            $systemTitle,
            $updatedBy
        );

        /*
        |--------------------------------------------------------------------------
        | Delete old logo after successful DB update
        |--------------------------------------------------------------------------
        */

        if (
            $newLogoPath !== null
            &&
            $oldLogoFullPath !== null
            &&
            is_file($oldLogoFullPath)
            &&
            realpath($oldLogoFullPath)
            !== realpath($uploadedLogoFullPath ?? '')
        ) {

            @unlink(
                $oldLogoFullPath
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Refresh settings
        |--------------------------------------------------------------------------
        */

        $settings =
            system_settings_get();

        $officeName =
            (string) (
                $settings['office_name']
                ?? ''
            );

        $address =
            (string) (
                $settings['address']
                ?? ''
            );

        $systemTitle =
            (string) (
                $settings['system_title']
                ?? ''
            );

        $currentLogo =
            (string) (
                $settings['logo_path']
                ?? ''
            );

        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        flash_set(
            'success',
            'System settings have been updated successfully.'
        );

        header(
            'Location: '
            . app_url(
                'system_settings.php'
            )
        );

        exit;

    } catch (Throwable $e) {

        /*
        |--------------------------------------------------------------------------
        | Remove newly uploaded file if update failed
        |--------------------------------------------------------------------------
        */

        if (
            isset($uploadedLogoFullPath)
            &&
            is_string($uploadedLogoFullPath)
            &&
            $uploadedLogoFullPath !== ''
            &&
            is_file($uploadedLogoFullPath)
        ) {

            @unlink(
                $uploadedLogoFullPath
            );
        }

        flash_set(
            'error',
            $e->getMessage()
        );
    }
}

/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require dirname(__DIR__, 2) . '/includes/header.php';

?>

<div class="settings-page">

    <!-- PAGE HEADER -->

    <div class="settings-card mb-3">

        <div class="settings-header">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">

                <div>

                    <div class="settings-title h3">

                        <i class="bi bi-gear-wide-connected me-1"></i>

                        System Settings

                    </div>

                    <div class="settings-subtitle">

                        Manage the office information and branding used by the system.

                    </div>

                </div>

                <div class="no-print">

                    <span class="badge text-bg-primary">

                        <i class="bi bi-shield-check me-1"></i>

                        Administrator Only

                    </span>

                </div>

            </div>

        </div>

        <!-- FORM -->

        <div class="settings-body">

            <form
                method="post"
                enctype="multipart/form-data"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo e($csrfToken); ?>"
                >

                <!-- OFFICE INFORMATION -->

                <div class="settings-section-title">

                    <i class="bi bi-building me-1"></i>

                    Office Information

                </div>

                <div class="row g-4">

                    <!-- OFFICE NAME -->

                    <div class="col-md-6">

                        <label
                            for="office_name"
                            class="form-label settings-label"
                        >
                            Office Name
                        </label>

                        <input
                            type="text"
                            id="office_name"
                            name="office_name"
                            class="form-control"
                            maxlength="255"
                            value="<?php echo e($officeName); ?>"
                            required
                        >

                        <div class="settings-help">

                            The official name of your office or department.

                        </div>

                    </div>

                    <!-- SYSTEM TITLE -->

                    <div class="col-md-6">

                        <label
                            for="system_title"
                            class="form-label settings-label"
                        >
                            System Title
                        </label>

                        <input
                            type="text"
                            id="system_title"
                            name="system_title"
                            class="form-control"
                            maxlength="255"
                            value="<?php echo e($systemTitle); ?>"
                            required
                        >

                        <div class="settings-help">

                            The title used to identify the document tracking system.

                        </div>

                    </div>

                    <!-- ADDRESS -->

                    <div class="col-12">

                        <label
                            for="address"
                            class="form-label settings-label"
                        >
                            Address
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            class="form-control"
                            rows="3"
                            maxlength="5000"
                        ><?php echo e($address); ?></textarea>

                        <div class="settings-help">

                            Enter the complete office address.

                        </div>

                    </div>

                </div>

                <hr class="my-4">

                <!-- LOGO -->

                <div class="settings-section-title">

                    <i class="bi bi-image me-1"></i>

                    Office Logo

                </div>

                <div class="row g-4 align-items-start">

                    <div class="col-md-auto">

                        <div class="logo-preview">

                            <?php if ($currentLogo !== ''): ?>

                                <img
                                    src="<?php echo e(
                                        app_url(
                                            ltrim(
                                                $currentLogo,
                                                '/'
                                            )
                                        )
                                    ); ?>"
                                    alt="Current Office Logo"
                                >

                            <?php else: ?>

                                <div class="logo-placeholder">

                                    <i
                                        class="bi bi-image"
                                        style="font-size: 2rem;"
                                    ></i>

                                    <div class="mt-1">
                                        No logo
                                    </div>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                    <div class="col-md-8">

                        <label
                            for="logo"
                            class="form-label settings-label"
                        >
                            Upload New Logo
                        </label>

                        <input
                            type="file"
                            id="logo"
                            name="logo"
                            class="form-control"
                            accept="image/jpeg,image/png,image/gif,image/webp"
                        >

                        <div class="settings-help">

                            Allowed formats: JPG, PNG, GIF, WebP.

                            Maximum file size: 5 MB.

                        </div>

                        <div class="settings-note mt-3">

                            <i class="bi bi-info-circle me-1"></i>

                            Leave this field empty if you want to keep the current logo.

                        </div>

                    </div>

                </div>

                <hr class="my-4">

                <!-- PREVIEW -->

                <div class="settings-section-title">

                    <i class="bi bi-eye me-1"></i>

                    Current System Information

                </div>

                <div class="card border-0 bg-light">

                    <div class="card-body">

                        <div class="row g-3">

                            <div class="col-md-4">

                                <div class="small text-muted">
                                    Office Name
                                </div>

                                <div class="fw-semibold text-primary">

                                    <?php echo e(
                                        $officeName
                                    ); ?>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="small text-muted">
                                    System Title
                                </div>

                                <div class="fw-semibold text-primary">

                                    <?php echo e(
                                        $systemTitle
                                    ); ?>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="small text-muted">
                                    Address
                                </div>

                                <div class="fw-semibold">

                                    <?php
                                    echo $address !== ''
                                        ? nl2br(
                                            e($address)
                                        )
                                        : '<span class="text-muted">Not set</span>';
                                    ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- BUTTONS -->

                <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mt-4 no-print">

                    <a
                        href="<?php echo e(
                            app_url('dashboard.php')
                        ); ?>"
                        class="btn btn-light border"
                    >

                        <i class="bi bi-arrow-left me-1"></i>

                        Back to Dashboard

                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-check-circle me-1"></i>

                        Save System Settings

                    </button>

                </div>

            </form>

        </div>

    </div>

    <!-- LAST UPDATED -->

    <?php if (!empty($settings['updated_at'])): ?>

        <div class="settings-meta text-end">

            <i class="bi bi-clock-history me-1"></i>

            Last updated:

            <?php
            $updatedTimestamp =
                strtotime(
                    (string) $settings['updated_at']
                );

            echo $updatedTimestamp !== false
                ? e(
                    date(
                        'M j, Y g:i A',
                        $updatedTimestamp
                    )
                )
                : e(
                    (string) $settings['updated_at']
                );
            ?>

        </div>

    <?php endif; ?>

</div>

<?php

require dirname(__DIR__, 2) . '/includes/footer.php';
