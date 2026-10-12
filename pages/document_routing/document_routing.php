<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| CLO DOCUMENT ROUTING
|--------------------------------------------------------------------------
| Features:
|
| - Send document to another office
| - Assign document to staff
| - Add routing remarks
| - Set due date
| - Receive document
| - Track complete movement history
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| SYSTEM FILES
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/functions/security.php';
require_once dirname(__DIR__, 2) . '/functions/auth.php';
require_once dirname(__DIR__, 2) . '/models/DocumentRoutingModel.php';
require_once dirname(__DIR__, 2) . '/models/AuditTrailModel.php';
require_once dirname(__DIR__, 2) . '/functions/document_assignment.php';
require_once dirname(__DIR__, 2) . '/functions/attachment_storage.php';

/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (function_exists('start_secure_session')) {
    start_secure_session();
}

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if (function_exists('auth_require_page')) {
    auth_require_page('document_routing.php');
} elseif (function_exists('auth_require_login')) {
    auth_require_login();
}

/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$currentUser = auth_user();

if (!is_array($currentUser)) {
    redirect_to('login.php');
    exit;
}

$currentUserId = (int) (
    $currentUser['user_id'] ?? 0
);
$canSendOutgoing = auth_has_any_role(['ADMIN', 'OUTGOING']);
$canRouteDocuments = auth_can_access_page('document_routing.php');
$cityLegalDepartmentId = routing_get_department_id_by_code('CLO');

if ($currentUserId <= 0) {
    redirect_to('login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| CURRENT USER DATABASE RECORD
|--------------------------------------------------------------------------
*/

$currentUserRecord =
    routing_find_user($currentUserId);

if (!$currentUserRecord) {
    die('Unable to load current user.');
}

$currentDepartmentId =
    (int) (
        $currentUserRecord['department_id']
        ?? 0
    );

$currentDepartmentName =
    (string) (
        $currentUserRecord['department_name']
        ?? ''
    );

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function routing_redirect(
    string $type,
    string $message
): never {

    header(
        'Location: document_routing.php?' .
        http_build_query([
            'message_type' => $type,
            'message' => $message
        ])
    );

    exit;
}

function routing_format_user(
    ?string $firstName,
    ?string $lastName,
    ?string $username
): string {

    $name = trim(
        (string) $firstName .
        ' ' .
        (string) $lastName
    );

    if ($name !== '') {
        return $name;
    }

    return (string) $username;
}

function routing_format_date(
    ?string $date
): string {

    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '—';
    }

    return date(
        'M d, Y h:i A',
        $timestamp
    );
}

/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

$search = trim(
    (string) (
        $_GET['search'] ?? ''
    )
);

$documents =
    routing_get_documents('');

$departments =
    routing_get_departments();

if (!$canSendOutgoing) {
    $departments = array_values(array_filter(
        $departments,
        static fn (array $department): bool => (string) ($department['department_code'] ?? '') === 'CLO'
    ));
}

$usersByDepartment = [];

/*
|--------------------------------------------------------------------------
| LOAD USERS PER DEPARTMENT
|--------------------------------------------------------------------------
*/

foreach ($departments as $department) {

    $departmentId =
        (int) $department['department_id'];

    $usersByDepartment[$departmentId] =
        routing_get_users_by_department(
            $departmentId
        );
}

/*
|--------------------------------------------------------------------------
| ERRORS
|--------------------------------------------------------------------------
*/

$errors = [];

/*
|--------------------------------------------------------------------------
| SEND DOCUMENT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['accept_assignment'])
) {
    if (!csrf_validate('document_routing_form', $_POST['csrf_token'] ?? null)) {
        routing_redirect('danger', 'Invalid request token. Please refresh and try again.');
    }

    $assignmentDocumentId = filter_input(INPUT_POST, 'document_id', FILTER_VALIDATE_INT);
    $assignmentDocumentId = $assignmentDocumentId !== false && $assignmentDocumentId !== null
        ? (int) $assignmentDocumentId
        : 0;

    try {
        $conn = db_connection();
        $conn->begin_transaction();
        $stmt = db_execute(
            'UPDATE document_assignment_receipts dar
             INNER JOIN documents d ON d.document_id = dar.document_id
             SET dar.accepted_at = CURRENT_TIMESTAMP
             WHERE dar.document_id = ?
               AND dar.user_id = ?
               AND dar.accepted_at IS NULL
               AND d.current_assignee_user_id = ?',
            'iii',
            [$assignmentDocumentId, $currentUserId, $currentUserId]
        );
        $accepted = $stmt->affected_rows === 1;
        $stmt->close();

        if (!$accepted) {
            throw new RuntimeException('This document is not awaiting your acceptance.');
        }

        audit_log_create(
            $currentUserId,
            'DOCUMENT',
            $assignmentDocumentId,
            'ASSIGNMENT_ACCEPTED',
            ['accepted_at' => null],
            ['accepted_by_user_id' => $currentUserId]
        );
        routing_set_document_status($conn, $assignmentDocumentId, 'Received');
        $conn->commit();
        routing_redirect('success', 'Assigned document accepted successfully.');
    } catch (Throwable $exception) {
        if (isset($conn) && $conn instanceof mysqli) {
            try {
                $conn->rollback();
            } catch (Throwable $ignored) {
            }
        }
        routing_redirect('danger', $exception->getMessage());
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['send_document'])
) {

    if (!$canRouteDocuments) {
        $errors[] = 'You do not have permission to route documents.';
    }

    if (!csrf_validate('document_routing_form', $_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request token. Please refresh and try again.';
    }

    $documentId = filter_input(
        INPUT_POST,
        'document_id',
        FILTER_VALIDATE_INT
    );

    $toDepartmentId = filter_input(
        INPUT_POST,
        'to_department_id',
        FILTER_VALIDATE_INT
    );

    $routedToUserId = filter_input(
        INPUT_POST,
        'routed_to_user_id',
        FILTER_VALIDATE_INT
    );

    $externalRecipientName = trim(
        (string) (
            $_POST['external_recipient_name'] ?? ''
        )
    );

    $remarks = trim(
        (string) (
            $_POST['remarks'] ?? ''
        )
    );

    $dueAt = trim(
        (string) (
            $_POST['due_at'] ?? ''
        )
    );

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (!$documentId) {

        $errors[] =
            'Invalid document.';
    }

    if (!$toDepartmentId) {

        $errors[] =
            'Destination department is required.';
    }

    $destinationUsers = $toDepartmentId
        ? routing_get_users_by_department((int) $toDepartmentId)
        : [];

    if (!$canSendOutgoing) {
        if ((int) $toDepartmentId !== $cityLegalDepartmentId || $cityLegalDepartmentId <= 0) {
            $errors[] = 'Your role can only route documents to a user in City Legal Office.';
        }

        if (!$routedToUserId || $routedToUserId <= 0) {
            $errors[] = 'Select a City Legal Office staff member to receive the document.';
        }

        if ($externalRecipientName !== '') {
            $errors[] = 'Only the outgoing account can route documents to other departments or external recipients.';
        }
    }

    if ($toDepartmentId && empty($destinationUsers)) {
        if ($externalRecipientName === '') {
            $errors[] =
                'Enter the name of the person receiving the document.';
        }

        $routedToUserId = null;
    } elseif ($externalRecipientName !== '') {
        $errors[] =
            'A typed recipient can only be used for departments without system users.';
    }

    if (preg_match('/\A[\s\S]{0,150}\z/u', $externalRecipientName) !== 1) {
        $errors[] =
            'Recipient name must not exceed 150 characters.';
    }

    if (
        $routedToUserId !== false
        && $routedToUserId !== null
        && $routedToUserId > 0
    ) {

        $selectedUser =
            routing_find_user(
                (int) $routedToUserId
            );

        if (!$selectedUser) {

            $errors[] =
                'Selected receiving staff does not exist.';

        } elseif (
            (int) $selectedUser['department_id']
            !== (int) $toDepartmentId
        ) {

            $errors[] =
                'Selected staff does not belong to the destination department.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DUE DATE
    |--------------------------------------------------------------------------
    */

    $formattedDueAt = null;

    if ($dueAt !== '') {

        $dateObject =
            DateTime::createFromFormat(
                'Y-m-d\TH:i',
                $dueAt
            );

        if (!$dateObject) {

            $errors[] =
                'Invalid due date.';

        } else {

            $formattedDueAt =
                $dateObject->format(
                    'Y-m-d H:i:s'
                );
        }
    }

    $document = $documentId
        ? routing_find_document((int) $documentId)
        : null;

    if (
        $formattedDueAt !== null
        && $document
    ) {
        $latestReceivedAt = trim((string) (
            $document['latest_received_at']
            ?? $document['date_received']
            ?? ''
        ));

        if ($latestReceivedAt !== '') {
            $minimumDueDate = date('Y-m-d', strtotime($latestReceivedAt));
            $selectedDueDate = substr($formattedDueAt, 0, 10);

            if ($selectedDueDate < $minimumDueDate) {
                $errors[] = 'The due date cannot be earlier than the document\'s latest received date (' . $minimumDueDate . ').';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE ROUTING
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        if (!$document) {

            $errors[] =
                'Document not found.';

        } elseif (
            !auth_is_role('ADMIN')
            && (int) ($document['current_assignee_user_id'] ?? 0) !== $currentUserId
            && !routing_is_latest_recipient((int) $documentId, $currentUserId)
        ) {

            $errors[] =
                'You can only route documents assigned or routed to you.';

        } else {

            $fromDepartmentId =
                (int) (
                    $document[
                        'current_department_id'
                    ] ?? 0
                );

            if (!$fromDepartmentId) {

                $errors[] =
                    'The document has no current department.';
            }
        }
    }

    if (empty($errors)) {

        try {

            /*
            |--------------------------------------------------------------------------
            | START DATABASE TRANSACTION
            |--------------------------------------------------------------------------
            */

            $conn = db_connection();
            $conn->begin_transaction();

            $currentAssigneeUserId = (int) (
                $document['current_assignee_user_id'] ?? 0
            );
            $assignedUserId = $currentAssigneeUserId > 0
                ? $currentAssigneeUserId
                : null;

            /*
            |--------------------------------------------------------------------------
            | CREATE ROUTE
            |--------------------------------------------------------------------------
            */

            $routeId =
                routing_create(
                    (int) $documentId,
                    (int) $fromDepartmentId,
                    (int) $toDepartmentId,
                    $currentUserId,
                    (
                        $routedToUserId
                        && $routedToUserId > 0
                    )
                        ? (int) $routedToUserId
                        : null,
                    $externalRecipientName !== ''
                        ? $externalRecipientName
                        : null,
                    $remarks,
                    $formattedDueAt
                );

            if ($routeId <= 0) {

                throw new RuntimeException(
                    'Unable to create document routing record.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE DOCUMENT CURRENT LOCATION
            |--------------------------------------------------------------------------
            */

            routing_update_document_location(
                (int) $documentId,
                (int) $toDepartmentId,
                $assignedUserId
            );
            routing_set_document_status($conn, (int) $documentId, 'Routed');

            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $conn->commit();

            audit_log_create(
                $currentUserId,
                'DOCUMENT',
                (int) $documentId,
                'ROUTED',
                [
                    'current_department_id' => (int) $fromDepartmentId,
                    'current_assignee_user_id' => $currentAssigneeUserId > 0
                        ? $currentAssigneeUserId
                        : null,
                ],
                [
                    'route_id' => (int) $routeId,
                    'current_department_id' => (int) $toDepartmentId,
                    'current_assignee_user_id' => $assignedUserId,
                    'external_recipient_name' => $externalRecipientName !== '' ? $externalRecipientName : null,
                    'route_remarks' => $remarks !== '' ? $remarks : null,
                    'due_at' => $formattedDueAt,
                ]
            );

            routing_redirect(
                'success',
                'Document routed successfully.'
            );

        } catch (Throwable $exception) {

            if (
                isset($conn)
                && $conn instanceof mysqli
            ) {

                try {
                    $conn->rollback();
                } catch (Throwable $ignored) {
                }

            }

            $errors[] =
                'Unable to route document: ' .
                $exception->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| RECEIVE DOCUMENT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['receive_document'])
) {

    if (!csrf_validate('document_routing_form', $_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request token. Please refresh and try again.';
    }

    $routeId = filter_input(
        INPUT_POST,
        'document_route_id',
        FILTER_VALIDATE_INT
    );

    $receiveRemarks = trim(
        (string) (
            $_POST['receive_remarks'] ?? ''
        )
    );

    $supportingFile = $_FILES['document_file'] ?? null;
    $hasSupportingFile = is_array($supportingFile)
        && (int) ($supportingFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $supportingExtension = '';
    $supportingMimeType = '';

    if ($hasSupportingFile) {
        $uploadError = (int) ($supportingFile['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK) {
            $errors[] = 'Supporting file upload failed.';
        } else {
            $temporaryPath = (string) ($supportingFile['tmp_name'] ?? '');
            $originalFilename = basename((string) ($supportingFile['name'] ?? ''));
            $supportingExtension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
            $allowedFileTypes = [
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];

            if (!is_uploaded_file($temporaryPath)) {
                $errors[] = 'Invalid supporting file upload.';
            } else {
                $fileSize = filesize($temporaryPath);
                if ($fileSize === false || $fileSize <= 0 || $fileSize > 10 * 1024 * 1024) {
                    $errors[] = 'Supporting file must be between 1 byte and 10 MB.';
                }

                $detectedMime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
                if (
                    !isset($allowedFileTypes[$supportingExtension])
                    || $detectedMime !== $allowedFileTypes[$supportingExtension]
                ) {
                    $errors[] = 'Only valid PDF, DOC and DOCX files are allowed.';
                } else {
                    $supportingMimeType = $detectedMime;
                }
            }
        }
    }

    if (!$routeId) {

        $errors[] =
            'Invalid routing record.';

    } else {

        $route =
            routing_find_route(
                (int) $routeId
            );

        if (!$route) {

            $errors[] =
                'Routing record not found.';

        } elseif (
            strtoupper(
                (string) $route['route_status']
            ) !== 'PENDING'
        ) {

            $errors[] =
                'This document routing has already been received.';
        } elseif (
            (int) $route['to_department_id']
            !== $currentDepartmentId
        ) {

            $errors[] =
                'This document is not routed to your department.';
        } elseif (
            !empty($route['routed_to_user_id'])
            && (int) $route['routed_to_user_id'] !== $currentUserId
        ) {

            $errors[] =
                'This document is routed to another user.';
        }
    }

    if (empty($errors)) {

        $conn = null;
        $supportingFilePath = null;

        try {

            $conn = db_connection();
            $conn->begin_transaction();

            if ($hasSupportingFile) {
                $uploadDirectory = dirname(__DIR__, 2) . '/uploads/';
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                    throw new RuntimeException('Unable to prepare attachment storage.');
                }

                $originalFilename = basename((string) ($supportingFile['name'] ?? ''));
                $storedFilename = attachment_storage_filename(
                    $originalFilename,
                    $uploadDirectory
                );
                $supportingFilePath = $uploadDirectory . $storedFilename;
                if (!move_uploaded_file((string) $supportingFile['tmp_name'], $supportingFilePath)) {
                    throw new RuntimeException('Unable to save the supporting file.');
                }

                $savedFileSize = filesize($supportingFilePath);
                $checksumSha256 = hash_file('sha256', $supportingFilePath);
                if ($savedFileSize === false || $checksumSha256 === false) {
                    throw new RuntimeException('Unable to verify the saved supporting file.');
                }

                $relativePath = 'uploads/' . $storedFilename;
                $stmt = $conn->prepare(
                    'INSERT INTO attachments
                        (document_id, uploaded_by_user_id, file_name, stored_name, file_path,
                         mime_type, file_size_bytes, checksum_sha256, is_public, uploaded_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)'
                );
                $stmt->bind_param(
                    'iissssis',
                    $route['document_id'],
                    $currentUserId,
                    $originalFilename,
                    $originalFilename,
                    $relativePath,
                    $supportingMimeType,
                    $savedFileSize,
                    $checksumSha256
                );
                $stmt->execute();
                $stmt->close();
            }

            routing_receive(
                (int) $routeId,
                $receiveRemarks !== ''
                    ? $receiveRemarks
                    : null
            );

            audit_log_create(
                $currentUserId,
                'DOCUMENT',
                (int) ($route['document_id'] ?? 0),
                'RECEIVED',
                [
                    'route_status' => 'PENDING',
                    'received_by_user_id' => null,
                ],
                [
                    'route_id' => (int) $routeId,
                    'route_status' => 'RECEIVED',
                    'received_by_user_id' => $currentUserId,
                    'remarks' => $receiveRemarks !== '' ? $receiveRemarks : null,
                ]
            );
            routing_set_document_status($conn, (int) ($route['document_id'] ?? 0), 'Received');
            $conn->commit();

            routing_redirect(
                'success',
                'Document received successfully.'
            );

        } catch (Throwable $exception) {

            if ($conn instanceof mysqli) {
                try {
                    $conn->rollback();
                } catch (Throwable $ignored) {
                }
            }

            if ($supportingFilePath !== null && is_file($supportingFilePath)) {
                @unlink($supportingFilePath);
            }

            $errors[] =
                'Unable to receive document: ' .
                $exception->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| RELOAD DATA AFTER POST
|--------------------------------------------------------------------------
*/

$documents =
    routing_get_documents(
        '',
        auth_is_role('ADMIN') ? null : $currentUserId
    );

/*
|--------------------------------------------------------------------------
| PENDING ROUTES
|--------------------------------------------------------------------------
*/

$pendingRoutes = [];

if ($currentDepartmentId > 0) {

    $pendingRoutes =
        routing_get_pending_for_department(
            $currentDepartmentId,
            $currentUserId
        );
}

/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

$messageType =
    (string) (
        $_GET['message_type'] ?? ''
    );

$message =
    (string) (
        $_GET['message'] ?? ''
    );

/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__, 2) . '/includes/header.php';

?>

<div class="container-fluid">

    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="page-header bg-white rounded-3 shadow-sm p-4 mb-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

            <div>

                <h3 class="mb-1">

                    <i class="bi bi-signpost-2 text-primary"></i>

                    Document Routing

                </h3>

                <div class="text-muted">

                    Send, receive and track document movements between offices.

                </div>

            </div>

        </div>

    </div>

    <!-- =====================================================
         ALERTS
    ====================================================== -->

    <?php if (!empty($errors)): ?>

        <div class="alert alert-danger">

            <strong>
                Please correct the following:
            </strong>

            <ul class="mb-0 mt-2">

                <?php foreach ($errors as $error): ?>

                    <li>
                        <?= e($error) ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>

    <?php if ($message !== ''): ?>

        <div class="alert alert-<?= $messageType === 'success'
            ? 'success'
            : 'danger' ?> alert-dismissible fade show">

            <?= e($message) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>

    <!-- =====================================================
         CURRENT OFFICE
    ====================================================== -->

    <!-- <div class="alert alert-primary shadow-sm">

        <div class="d-flex align-items-center gap-3">

            <i class="bi bi-building fs-3"></i>

            <div>

                <div class="small text-muted">
                    Your Current Office
                </div>

                <strong>
                    <?= e($currentDepartmentName ?: 'Unknown') ?>
                </strong>

            </div>

        </div>

    </div> -->

    <!-- =====================================================
         PENDING DOCUMENTS
    ====================================================== -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-header bg-white py-3">

            <div class="d-flex justify-content-between align-items-center">

                <strong>

                    <i class="bi bi-inbox text-warning"></i>

                    Documents Waiting to be Received

                </strong>

                <span class="badge bg-warning text-dark">

                    <?= count($pendingRoutes) ?>

                </span>

            </div>

        </div>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>

                        <th>Tracking No.</th>

                        <th>Document</th>

                        <th>From Office</th>

                        <th>Routed By</th>

                        <th>Routed To</th>

                        <th>Date</th>

                        <th>Due</th>

                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                <?php if (empty($pendingRoutes)): ?>

                    <tr>

                        <td
                            colspan="8"
                            class="text-center py-4 text-muted"
                        >

                            <i class="bi bi-check-circle fs-3 d-block mb-2"></i>

                            No pending documents.

                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($pendingRoutes as $route): ?>

                        <tr>

                            <td>

                                <strong class="text-primary">

                                    <?= e(
                                        $route['tracking_no']
                                    ) ?>

                                </strong>

                            </td>

                            <td>

                                <strong>

                                    <?= e(
                                        $route['title']
                                    ) ?>

                                </strong>

                                <?php if (trim((string) ($route['external_reference_no'] ?? '')) !== ''): ?>
                                    <div class="small text-muted">
                                        Ref: <?= e((string) $route['external_reference_no']) ?>
                                    </div>
                                <?php endif; ?>

                            </td>

                            <td>

                                <?= e(
                                    $route[
                                        'from_department_name'
                                    ] ?? '-'
                                ) ?>

                            </td>

                            <td>

                                <?= e(
                                    routing_format_user(
                                        $route[
                                            'routed_by_first_name'
                                        ] ?? '',
                                        $route[
                                            'routed_by_last_name'
                                        ] ?? '',
                                        $route[
                                            'routed_by_username'
                                        ] ?? ''
                                    )
                                ) ?>

                            </td>

                            <td>

                                <?= e(
                                    $route['external_recipient_name']
                                    ?: routing_format_user(
                                        $route['routed_to_first_name'] ?? '',
                                        $route['routed_to_last_name'] ?? '',
                                        $route['routed_to_username'] ?? ''
                                    )
                                    ?: 'Unassigned'
                                ) ?>

                            </td>

                            <td>

                                <?= e(
                                    routing_format_date(
                                        $route['routed_at']
                                        ?? null
                                    )
                                ) ?>

                            </td>

                            <td>

                                <?= e(
                                    routing_format_date(
                                        $route['due_at']
                                        ?? null
                                    )
                                ) ?>

                            </td>

                            <td>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-success"
                                    data-bs-toggle="modal"
                                    data-bs-target="#receiveModal<?= (int) $route['document_route_id'] ?>"
                                >

                                    <i class="bi bi-check-lg"></i>

                                    Receive

                                </button>

                            </td>

                        </tr>

                        <!-- RECEIVE MODAL -->

                        <div
                            class="modal fade"
                            id="receiveModal<?= (int) $route['document_route_id'] ?>"
                            tabindex="-1"
                        >

                            <div class="modal-dialog modal-dialog-scrollable">

                                <div class="modal-content">

                                    <input
                                        type="hidden"
                                        form="receiveRouteForm<?= (int) $route['document_route_id'] ?>"
                                        name="csrf_token"
                                        value="<?= e(csrf_token('document_routing_form')) ?>"
                                    >

                                        <div class="modal-header">

                                            <h5 class="modal-title">

                                                <i class="bi bi-box-arrow-in-down"></i>

                                                Receive Document

                                            </h5>

                                            <button
                                                type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"
                                            ></button>

                                        </div>

                                        <div class="modal-body">

                                            <input
                                                type="hidden"
                                                form="receiveRouteForm<?= (int) $route['document_route_id'] ?>"
                                                name="document_route_id"
                                                value="<?= (int) $route['document_route_id'] ?>"
                                            >

                                            <div class="mb-3">

                                                <label class="form-label">
                                                    Tracking Number
                                                </label>

                                                <input
                                                    type="text"
                                                    class="form-control"
                                                    value="<?= e($route['tracking_no']) ?>"
                                                    readonly
                                                >

                                            </div>

                                            <div class="mb-3">

                                                <label class="form-label">
                                                    Document
                                                </label>

                                                <input
                                                    type="text"
                                                    class="form-control"
                                                    value="<?= e($route['title']) ?>"
                                                    readonly
                                                >

                                            </div>

                                            <div class="mb-3">

                                                <label class="form-label">
                                                    Upload Supporting File
                                                </label>

                                                <input
                                                    type="file"
                                                    form="receiveRouteForm<?= (int) $route['document_route_id'] ?>"
                                                    name="document_file"
                                                    class="form-control"
                                                    accept=".pdf,.doc,.docx"
                                                >

                                                <div class="form-text">
                                                    Optional attachment. Allowed: PDF, DOC, DOCX.
                                                </div>

                                            </div>

                                            <div class="mb-3">

                                                <label class="form-label">
                                                    Remarks
                                                </label>

                                                <textarea
                                                    name="receive_remarks"
                                                    form="receiveRouteForm<?= (int) $route['document_route_id'] ?>"
                                                    class="form-control"
                                                    rows="4"
                                                    placeholder="Optional receiving remarks..."
                                                ></textarea>

                                            </div>

                                        </div>

                                        <div class="modal-footer">

                                            <button
                                                type="button"
                                                class="btn btn-secondary"
                                                data-bs-dismiss="modal"
                                            >
                                                Cancel
                                            </button>

                                            <button
                                                type="submit"
                                                form="receiveRouteForm<?= (int) $route['document_route_id'] ?>"
                                                name="receive_document"
                                                class="btn btn-success"
                                            >

                                                <i class="bi bi-check-lg"></i>

                                                Confirm Receive

                                            </button>

                                        </div>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

                <?php foreach ($pendingRoutes as $pendingRoute): ?>
                    <form
                        id="receiveRouteForm<?= (int) $pendingRoute['document_route_id'] ?>"
                        method="POST"
                        enctype="multipart/form-data"
                    ></form>
                <?php endforeach; ?>

        </div>

    </div>

    <!-- =====================================================
         SEARCH
    ====================================================== -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <form
                method="GET"
                class="row g-2"
            >

                <div class="col-md-10">

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Search tracking number, title, description or reference..."
                        value="<?= e($search) ?>"
                        autocomplete="off"
                    >

                </div>

                <div class="col-md-2 d-grid">

                    <button
                        type="submit"
                        class="btn btn-outline-primary"
                    >

                        <i class="bi bi-search"></i>

                        Search

                    </button>

                </div>

            </form>

        </div>

    </div>

    <!-- =====================================================
         DOCUMENT LIST
    ====================================================== -->

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">

            <div class="d-flex justify-content-between">

                <strong>

                    <i class="bi bi-file-earmark-text"></i>

                    Documents

                </strong>

                <span class="badge bg-primary">

                    <?= count($documents) ?>

                </span>

            </div>

        </div>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>

                        <th>Tracking Number</th>

                        <th>Document</th>

                        <th>Current Office</th>

                        <th>Assigned Staff</th>

                        <th class="text-end">
                            Actions
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (empty($documents)): ?>

                    <tr>

                        <td
                            colspan="5"
                            class="text-center py-5 text-muted"
                        >

                            <i class="bi bi-folder2-open fs-1 d-block mb-2"></i>

                            No documents found.

                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($documents as $document): ?>

                        <?php

                        $documentId =
                            (int) $document['document_id'];

                        $routingReceivedAt = trim((string) (
                            $document['latest_received_at']
                            ?? $document['date_received']
                            ?? ''
                        ));
                        $minimumDueAt = $routingReceivedAt !== ''
                            ? date('Y-m-d', strtotime($routingReceivedAt)) . 'T00:00'
                            : '';

                        $assignedName =
                            routing_format_user(
                                $document[
                                    'assignee_first_name'
                                ] ?? '',
                                $document[
                                    'assignee_last_name'
                                ] ?? '',
                                $document[
                                    'assignee_username'
                                ] ?? ''
                            );

                        ?>

                        <tr>

                            <td>

                                <strong class="text-primary">

                                    <?= e(
                                        $document['tracking_no']
                                    ) ?>

                                </strong>

                                <?php if (trim((string) ($document['external_reference_no'] ?? '')) !== ''): ?>
                                    <div class="small text-muted">
                                        Ref: <?= e((string) $document['external_reference_no']) ?>
                                    </div>
                                <?php endif; ?>

                            </td>

                            <td>

                                <strong>

                                    <?= e(
                                        $document['title']
                                    ) ?>

                                </strong>

                            </td>

                            <td>

                                <span class="badge text-bg-light">

                                    <i class="bi bi-building"></i>

                                    <?= e(
                                        $document[
                                            'current_department_name'
                                        ] ?? '-'
                                    ) ?>

                                </span>

                            </td>

                            <td>

                                <?= !empty(
                                    $document[
                                        'current_assignee_user_id'
                                    ]
                                )
                                    ? e($assignedName)
                                    : '<span class="text-muted">Unassigned</span>' ?>

                            </td>

                            <td class="text-end">

                                <div class="btn-group">

                                    <?php if (
                                        (int) ($document['current_assignee_user_id'] ?? 0) === $currentUserId
                                        && empty($document['assignment_accepted_at'])
                                    ): ?>
                                        <form method="POST" class="d-inline">
                                            <?= csrf_field('document_routing_form') ?>
                                            <input type="hidden" name="document_id" value="<?= $documentId ?>">
                                            <button type="submit" name="accept_assignment" class="btn btn-sm btn-success">
                                                <i class="bi bi-check-circle"></i>
                                                Accept
                                            </button>
                                        </form>
                                    <?php elseif (
                                        (int) ($document['current_assignee_user_id'] ?? 0) === $currentUserId
                                        && !empty($document['assignment_accepted_at'])
                                    ): ?>
                                        <span class="btn btn-sm btn-outline-success disabled">
                                            <i class="bi bi-check2-all"></i>
                                            Accepted
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($canRouteDocuments): ?>

                                    <!-- ROUTE -->

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#routeModal<?= $documentId ?>"
                                    >

                                        <i class="bi bi-send"></i>

                                        Route

                                    </button>

                                    <?php endif; ?>

                                    <!-- HISTORY -->

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#historyModal<?= $documentId ?>"
                                    >

                                        <i class="bi bi-clock-history"></i>

                                        History

                                    </button>

                                </div>

                            </td>

                        </tr>

                        <!-- =================================================
                             ROUTE MODAL
                        ================================================== -->

                            <?php if ($canRouteDocuments): ?>
                        <div
                            class="modal fade"
                            id="routeModal<?= $documentId ?>"
                            tabindex="-1"
                        >

                            <div class="modal-dialog modal-lg modal-dialog-scrollable">

                                <div class="modal-content">

                                    <input
                                        type="hidden"
                                        form="routeDocumentForm<?= $documentId ?>"
                                        name="csrf_token"
                                        value="<?= e(csrf_token('document_routing_form')) ?>"
                                    >

                                        <div class="modal-header">

                                            <h5 class="modal-title">

                                                <i class="bi bi-send text-primary"></i>

                                                Route Document

                                            </h5>

                                            <button
                                                type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"
                                            ></button>

                                        </div>

                                        <div class="modal-body">

                                            <input
                                                type="hidden"
                                                form="routeDocumentForm<?= $documentId ?>"
                                                name="document_id"
                                                value="<?= $documentId ?>"
                                            >

                                            <div class="alert alert-info">

                                                <strong>

                                                    <?= e(
                                                        $document[
                                                            'tracking_no'
                                                        ]
                                                    ) ?>

                                                </strong>

                                                <br>

                                                <?= e(
                                                    $document['title']
                                                ) ?>

                                            </div>

                                            <div class="mb-3">

                                                <label class="form-label">

                                                    From Office

                                                </label>

                                                <input
                                                    type="text"
                                                    class="form-control"
                                                    value="<?= e(
                                                        $document[
                                                            'current_department_name'
                                                        ] ?? '-'
                                                    ) ?>"
                                                    readonly
                                                >

                                            </div>

                                            <div class="mb-3">

                                                <label class="form-label">

                                                    Destination Office

                                                    <span class="text-danger">
                                                        *
                                                    </span>

                                                </label>

                                                <select
                                                    name="to_department_id"
                                                    form="routeDocumentForm<?= $documentId ?>"
                                                    class="form-select destination-department"
                                                    data-document="<?= $documentId ?>"
                                                    required
                                                >

                                                    <option value="">
                                                        Select destination office
                                                    </option>

                                                    <?php foreach (
                                                        $departments
                                                        as $department
                                                    ): ?>

                                                        <?php

                                                        $departmentId =
                                                            (int) $department[
                                                                'department_id'
                                                            ];

                                                        ?>

                                                        <option
                                                            value="<?= $departmentId ?>"
                                                        >

                                                            <?= e(
                                                                $department[
                                                                    'department_name'
                                                                ]
                                                            ) ?>

                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>

                                            </div>

                                            <div class="mb-3">

                                                <label class="form-label">

                                                    Assign To Staff

                                                </label>

                                                <select
                                                    name="routed_to_user_id"
                                                    form="routeDocumentForm<?= $documentId ?>"
                                                    class="form-select routed-user"
                                                    id="routedUser<?= $documentId ?>"
                                                >

                                                    <option value="">
                                                        Unassigned
                                                    </option>

                                                </select>

                                                <div class="form-text">

                                                    Select the staff member who will receive the document.

                                                </div>

                                            </div>

                                            <div
                                                class="mb-3"
                                                id="externalRecipientGroup<?= $documentId ?>"
                                                hidden
                                            >

                                                <label
                                                    class="form-label"
                                                    for="externalRecipient<?= $documentId ?>"
                                                >

                                                    Assigned Person at Destination

                                                    <span class="text-danger">*</span>

                                                </label>

                                                <input
                                                    type="text"
                                                    name="external_recipient_name"
                                                    form="routeDocumentForm<?= $documentId ?>"
                                                    class="form-control external-recipient-name"
                                                    id="externalRecipient<?= $documentId ?>"
                                                    maxlength="150"
                                                    autocomplete="off"
                                                    disabled
                                                >

                                            </div>

                                            <div class="mb-3">

                                                <label class="form-label">

                                                    Remarks

                                                </label>

                                                <textarea
                                                    name="remarks"
                                                    form="routeDocumentForm<?= $documentId ?>"
                                                    class="form-control"
                                                    rows="4"
                                                    maxlength="2000"
                                                    placeholder="Enter routing instructions or remarks..."
                                                ></textarea>

                                            </div>

                                            <div class="mb-3">

                                                <label class="form-label">

                                                    Due Date (on or after received date)

                                                </label>

                                                <input
                                                    type="datetime-local"
                                                    name="due_at"
                                                    form="routeDocumentForm<?= $documentId ?>"
                                                    class="form-control"
                                                    <?= $minimumDueAt !== ''
                                                        ? 'min="' . e($minimumDueAt) . '"'
                                                        : '' ?>
                                                >

                                                <div class="form-text">

                                                    Optional. If set, choose the received date or a later date.

                                                </div>

                                            </div>

                                        </div>

                                        <div class="modal-footer">

                                            <button
                                                type="button"
                                                class="btn btn-secondary"
                                                data-bs-dismiss="modal"
                                            >

                                                Cancel

                                            </button>

                                            <button
                                                type="submit"
                                                form="routeDocumentForm<?= $documentId ?>"
                                                name="send_document"
                                                class="btn btn-primary"
                                            >

                                                <i class="bi bi-send"></i>

                                                Send Document

                                            </button>

                                        </div>

                                </div>

                            </div>

                        </div>

                        <?php endif; ?>

                        <!-- =================================================
                             HISTORY MODAL
                        ================================================== -->

                        <?php

                        $history =
                            routing_get_history(
                                $documentId
                            );

                        ?>

                        <div
                            class="modal fade"
                            id="historyModal<?= $documentId ?>"
                            tabindex="-1"
                        >

                            <div class="modal-dialog modal-xl modal-dialog-scrollable">

                                <div class="modal-content">

                                    <div class="modal-header">

                                        <h5 class="modal-title">

                                            <i class="bi bi-clock-history"></i>

                                            Document Movement History

                                        </h5>

                                        <button
                                            type="button"
                                            class="btn-close"
                                            data-bs-dismiss="modal"
                                        ></button>

                                    </div>

                                    <div class="modal-body">

                                        <div class="mb-4">

                                            <strong class="text-primary">

                                                <?= e(
                                                    $document['tracking_no']
                                                ) ?>

                                            </strong>

                                            <div>

                                                <?= e(
                                                    $document['title']
                                                ) ?>

                                            </div>

                                        </div>

                                        <?php if (empty($history)): ?>

                                            <div class="text-center text-muted py-5">

                                                <i class="bi bi-clock-history fs-1 d-block mb-2"></i>

                                                No routing history yet.

                                            </div>

                                        <?php else: ?>

                                            <div class="timeline">

                                                <?php foreach (
                                                    $history
                                                    as $movement
                                                ): ?>

                                                    <?php

                                                    $routeStatus =
                                                        strtoupper(
                                                            (string) (
                                                                $movement[
                                                                    'route_status'
                                                                ] ?? ''
                                                            )
                                                        );

                                                    $badgeClass =
                                                        $routeStatus === 'RECEIVED'
                                                            ? 'success'
                                                            : 'primary';

                                                    ?>

                                                    <div class="timeline-item">

                                                        <div
                                                            class="timeline-dot bg-<?= $badgeClass ?>"
                                                        ></div>

                                                        <div class="timeline-content">

                                                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">

                                                                <div>

                                                                    <strong>

                                                                        <?= e(
                                                                            $movement[
                                                                                'from_department_name'
                                                                            ] ?? '-'
                                                                        ) ?>

                                                                        <i class="bi bi-arrow-right mx-2"></i>

                                                                        <?= e(
                                                                            $movement[
                                                                                'to_department_name'
                                                                            ] ?? '-'
                                                                        ) ?>

                                                                    </strong>

                                                                </div>

                                                                <span
                                                                    class="badge text-bg-<?= $badgeClass ?>"
                                                                >

                                                                    <?= e(
                                                                        $routeStatus
                                                                    ) ?>

                                                                </span>

                                                            </div>

                                                            <div class="small text-muted mt-2">

                                                                Routed by:

                                                                <strong>

                                                                    <?= e(
                                                                        routing_format_user(
                                                                            $movement[
                                                                                'routed_by_first_name'
                                                                            ] ?? '',
                                                                            $movement[
                                                                                'routed_by_last_name'
                                                                            ] ?? '',
                                                                            $movement[
                                                                                'routed_by_username'
                                                                            ] ?? ''
                                                                        )
                                                                    ) ?>

                                                                </strong>

                                                            </div>

                                                            <?php
                                                            $movementRecipient = trim(
                                                                (string) ($movement['external_recipient_name'] ?? '')
                                                            );
                                                            if ($movementRecipient === '' && !empty($movement['routed_to_user_id'])) {
                                                                $movementRecipient = routing_format_user(
                                                                    $movement['routed_to_first_name'] ?? '',
                                                                    $movement['routed_to_last_name'] ?? '',
                                                                    $movement['routed_to_username'] ?? ''
                                                                );
                                                            }
                                                            ?>

                                                            <?php if ($movementRecipient !== ''): ?>

                                                                <div class="small text-muted">

                                                                    Routed to:

                                                                    <strong>

                                                                        <?= e($movementRecipient) ?>

                                                                    </strong>

                                                                </div>

                                                            <?php endif; ?>

                                                            <div class="small text-muted">

                                                                Routed:

                                                                <?= e(
                                                                    routing_format_date(
                                                                        $movement[
                                                                            'routed_at'
                                                                        ] ?? null
                                                                    )
                                                                ) ?>

                                                            </div>

                                                            <?php if (
                                                                !empty(
                                                                    $movement[
                                                                        'received_at'
                                                                    ]
                                                                )
                                                            ): ?>

                                                                <div class="small text-success">

                                                                    Received:

                                                                    <?= e(
                                                                        routing_format_date(
                                                                            $movement[
                                                                                'received_at'
                                                                            ]
                                                                        )
                                                                    ) ?>

                                                                </div>

                                                            <?php endif; ?>

                                                            <?php if (
                                                                !empty(
                                                                    $movement[
                                                                        'due_at'
                                                                    ]
                                                                )
                                                            ): ?>

                                                                <div class="small text-warning">

                                                                    Due:

                                                                    <?= e(
                                                                        routing_format_date(
                                                                            $movement[
                                                                                'due_at'
                                                                            ]
                                                                        )
                                                                    ) ?>

                                                                </div>

                                                            <?php endif; ?>

                                                            <?php if (
                                                                !empty(
                                                                    $movement[
                                                                        'remarks'
                                                                    ]
                                                                )
                                                            ): ?>

                                                                <div class="alert alert-light border mt-3 mb-0">

                                                                    <strong>
                                                                        Remarks:
                                                                    </strong>

                                                                    <div class="mt-1">

                                                                        <?= nl2br(
                                                                            e(
                                                                                $movement[
                                                                                    'remarks'
                                                                                ]
                                                                            )
                                                                        ) ?>

                                                                    </div>

                                                                </div>

                                                            <?php endif; ?>

                                                        </div>

                                                    </div>

                                                <?php endforeach; ?>

                                            </div>

                                        <?php endif; ?>

                                    </div>

                                    <div class="modal-footer">

                                        <button
                                            type="button"
                                            class="btn btn-secondary"
                                            data-bs-dismiss="modal"
                                        >

                                            Close

                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

                <?php foreach ($documents as $routedDocument): ?>
                    <form
                        id="routeDocumentForm<?= (int) $routedDocument['document_id'] ?>"
                        method="POST"
                    ></form>
                <?php endforeach; ?>

        </div>

    </div>

</div>

<script type="application/json" id="department-users-data"><?=
    json_encode(
    $usersByDepartment,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
) ?></script>

<script src="<?php echo e(app_url('assets/js/features/documents/document-routing.js')); ?>"></script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
