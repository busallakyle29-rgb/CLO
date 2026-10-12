<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| CLO DOCUMENT TRACKING
|--------------------------------------------------------------------------
| Features:
|
| - Search by Tracking Number
| - Display current status
| - Display current office
| - Display date received
| - Display complete movement timeline
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| LOAD SYSTEM FILES
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/functions/security.php';
require_once dirname(__DIR__, 2) . '/functions/auth.php';
require_once dirname(__DIR__, 2) . '/functions/document_access.php';
require_once dirname(__DIR__, 2) . '/models/DocumentTrackingModel.php';

/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

if (function_exists('start_secure_session')) {
    start_secure_session();
}

if (function_exists('auth_require_page')) {
    auth_require_page('document_tracking.php');
} elseif (function_exists('auth_require_login')) {
    auth_require_login();
}

$isLoggedIn = true;
$currentUser = auth_user();

/*
|--------------------------------------------------------------------------
| LIVE SUGGESTIONS (JSON)
|--------------------------------------------------------------------------
*/

if (isset($_GET['suggest'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    $suggestQuery = trim((string) ($_GET['q'] ?? ''));
    $suggestField = strtolower(trim((string) ($_GET['field'] ?? 'all')));

    if (!in_array($suggestField, ['tracking_no', 'all'], true)) {
        $suggestField = 'all';
    }

    $suggestions = [];

    if ($suggestQuery !== '') {
        try {
            $suggestions = tracking_suggest_documents(
                $suggestQuery,
                $suggestField,
                8,
                !$isLoggedIn
            );
        } catch (Throwable $exception) {
            $suggestions = [];
        }
    }

    echo json_encode(
        ['suggestions' => $suggestions],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| SEARCH VALUE
|--------------------------------------------------------------------------
*/

$searchQuery = trim(
    (string) (
        $_GET['q']
        ?? $_GET['search']
        ?? $_GET['tracking_no']
        ?? $_GET['title']
        ?? ''
    )
);

/*
|--------------------------------------------------------------------------
| DOCUMENT DATA
|--------------------------------------------------------------------------
*/

$document = null;

$timeline = [];

$attachments = [];

$canViewDocumentContent = false;

$error = '';

$searched = false;

function tracking_get_attachments(
    int $documentId,
    bool $publicOnly = true
): array
{
    $visibilitySql = $publicOnly
        ? ' AND a.is_public = 1'
        : '';

    $stmt = db_execute(
        "
        SELECT
            a.attachment_id,
            a.file_name,
            a.file_path,
            a.mime_type,
            a.file_size_bytes,
            a.uploaded_at,
            CONCAT(
                COALESCE(u.first_name, ''),
                ' ',
                COALESCE(u.last_name, '')
            ) AS uploaded_by_name
        FROM attachments a
        LEFT JOIN users u
            ON u.user_id = a.uploaded_by_user_id
        WHERE a.document_id = ?
        " . $visibilitySql . "
        ORDER BY a.uploaded_at DESC, a.attachment_id DESC
        ",
        'i',
        [$documentId]
    );

    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows;
}

/*
|--------------------------------------------------------------------------
| SEARCH DOCUMENT
|--------------------------------------------------------------------------
*/

if ($searchQuery !== '') {

    $searched = true;

    try {

        $document = tracking_find_document_by_query(
            $searchQuery,
            !$isLoggedIn
        );

        if ($document === null) {
            $error = 'No document found for "' . $searchQuery . '". Try a tracking number, document title, or uploaded file name.';
        } else {
            $documentId = (int) $document['document_id'];
            $canViewDocumentContent = document_user_can_view_content(
                $document,
                $currentUser,
                db_connection()
            );
            $timeline = tracking_get_timeline($documentId);
            if ($canViewDocumentContent) {
                $attachments = tracking_get_attachments(
                    $documentId,
                    !$isLoggedIn
                );
            }
        }

    } catch (Throwable $exception) {
        error_log('Document tracking search failed: ' . $exception->getMessage());
        $error = 'Unable to search the document right now. Please try again later.';
    }
}

/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__, 2) . '/includes/header.php';

?>

<!-- =========================================================
     DOCUMENT TRACKING
========================================================= -->

<div class="container-fluid">

    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="page-header bg-white rounded-3 shadow-sm p-4 mb-4">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">

            <div>

                <h3 class="mb-1">

                    <i class="bi bi-geo-alt text-primary"></i>

                    Document Tracking

                </h3>

                <div class="text-muted">

                    Search a document using its tracking number
                    and view its complete movement history.

                </div>

            </div>

            <?php if (!$isLoggedIn): ?>
                <div class="d-flex gap-2 flex-wrap tracking-page-actions">

                    <a
                        href="login.php"
                        class="tracking-page-btn tracking-page-btn-primary"
                    >
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Login</span>
                    </a>

                    <a
                        href="index.php"
                        class="tracking-page-btn tracking-page-btn-secondary"
                    >
                        <i class="bi bi-house-door"></i>
                        <span>Back to Main Page</span>
                    </a>

                </div>
            <?php endif; ?>

        </div>

    </div>

    <!-- =====================================================
         SEARCH
    ====================================================== -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <form
                method="GET"
                class="row g-3 align-items-end"
                data-suggest-url="<?= e(app_url('document_tracking.php')) ?>"
            >

                <div class="col-md-10">

                    <label
                        for="tracking_search"
                        class="form-label"
                    >
                        Tracking Number or Document Name
                    </label>

                    <div class="tracking-suggest-wrap">

                        <input
                            type="text"
                            class="form-control form-control-lg"
                            id="tracking_search"
                            name="q"
                            placeholder="Search by tracking number, document name, or uploaded file name"
                            value="<?= e($searchQuery) ?>"
                            autocomplete="off"
                            data-tracking-suggest="all"
                            aria-autocomplete="list"
                            aria-expanded="false"
                        >

                        <div
                            class="tracking-suggest-list"
                            id="trackingSearchSuggestions"
                            hidden
                        ></div>

                    </div>

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
         ERROR STATE
    ====================================================== -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger alert-dismissible fade show" role="alert">

            <i class="bi bi-exclamation-triangle-fill"></i>

            <?= e($error) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

        </div>

    <?php endif; ?>

    <!-- =====================================================
         DOCUMENT DETAILS
    ====================================================== -->

    <?php if ($document !== null): ?>

        <?php

        $dateReceived =
            $document['date_received']
            ?? null;

        $currentOffice =
            $document['current_department_name']
            ?? 'Unknown';

        $statusName =
            $document['status_name']
            ?? 'Unknown';

        ?>

        <div class="document-details">

            <!-- =====================================================
                 DOCUMENT SUMMARY
            ====================================================== -->

            <div class="card border-0 shadow-sm mb-4">

                <div class="card-header bg-white py-3">

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

                        <div>

                            <strong>
                                <i class="bi bi-file-earmark text-primary"></i>
                                Document Information
                            </strong>

                        </div>

                        <span class="badge text-bg-info">

                            <?= e((string) ($document['tracking_no'] ?? '')) ?>

                        </span>

                    </div>

                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-3">

                            <div class="small text-muted">
                                Current Status
                            </div>

                            <strong class="text-primary">
                                <?= e($statusName) ?>
                            </strong>

                        </div>

                        <div class="col-md-3">

                            <div class="small text-muted">
                                Current Location
                            </div>

                            <strong>
                                <?= e($currentOffice) ?>
                            </strong>

                        </div>

                        <div class="col-md-3">

                            <div class="small text-muted">
                                Date Received
                            </div>

                            <strong>

                                <?php if (!empty($dateReceived)): ?>

                                    <?= e(
                                        date(
                                            'M d, Y',
                                            strtotime(
                                                $dateReceived
                                            )
                                        )
                                    ) ?>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </strong>

                        </div>

                        <div class="col-md-3">

                            <div class="small text-muted">
                                Last Updated
                            </div>

                            <strong>

                                <?php if (!empty($document['updated_at'])): ?>

                                    <?= e(
                                        date(
                                            'M d, Y h:i A',
                                            strtotime(
                                                (string) $document['updated_at']
                                            )
                                        )
                                    ) ?>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </strong>

                        </div>

                    </div>

                </div>

            </div>

            <!-- =====================================================
                 ATTACHED FILES
            ====================================================== -->

            <div class="card border-0 shadow-sm mb-4">

                <div class="card-header bg-white py-3">

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

                        <strong>
                            <i class="bi bi-paperclip text-info"></i>
                            <?= $canViewDocumentContent ? 'Uploaded Files' : 'Attachments' ?>
                        </strong>

                        <?php if ($canViewDocumentContent): ?>
                        <span class="badge bg-info-subtle text-info">
                            <?= count($attachments) ?>
                            file(s)
                        </span>
                        <?php endif; ?>

                    </div>

                </div>

                <div class="card-body">

                    <?php if (!$canViewDocumentContent): ?>
                        <div class="alert alert-warning mb-0">
                            Access to this document's files is restricted.
                        </div>
                    <?php else: ?>
                    <div class="input-group mb-3 attachment-file-filter">

                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>

                        <input
                            type="search"
                            class="form-control"
                            placeholder="Type file name to filter..."
                            aria-label="Filter uploaded files by name"
                            data-attachment-filter="true"
                        >

                    </div>

                    <?php if (empty($attachments)): ?>

                        <div class="text-muted text-center py-4">
                            <i class="bi bi-file-earmark-text fs-1 d-block mb-2"></i>
                            No uploaded files for this document.
                        </div>

                    <?php else: ?>

                        <div class="list-group attachment-file-list">

                            <?php foreach ($attachments as $attachment): ?>

                                <?php
                                $attachmentFileName = (string) ($attachment['file_name'] ?? 'Untitled file');
                                $attachmentId = (int) ($attachment['attachment_id'] ?? 0);
                                $attachmentUrl = $attachmentId > 0
                                    ? app_url('attachment.php?id=' . $attachmentId)
                                    : '#';
                                ?>

                                <div
                                    class="list-group-item attachment-file-item"
                                    data-file-name="<?= e(strtolower($attachmentFileName)) ?>"
                                >

                                    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-start">

                                        <div>

                                            <div class="fw-semibold">
                                                <?= e($attachmentFileName) ?>
                                            </div>

                                            <div class="small text-muted">
                                                Uploaded: <?= e(date('M d, Y h:i A', strtotime((string) ($attachment['uploaded_at'] ?? 'now')))) ?>
                                                <?php if (!empty($attachment['uploaded_by_name'])): ?>
                                                    by <?= e($attachment['uploaded_by_name']) ?>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (!empty($attachment['file_size_bytes'])): ?>
                                                <div class="small text-muted">
                                                    Size: <?= e(number_format((int) $attachment['file_size_bytes'] / 1024, 1) . ' KB') ?>
                                                </div>
                                            <?php endif; ?>

                                        </div>

                                        <div class="btn-group">

                                            <a
                                                href="<?= e($attachmentUrl) ?>"
                                                target="_blank"
                                                rel="noopener"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                <i class="bi bi-eye"></i>
                                                View
                                            </a>

                                            <a
                                                href="<?= e($attachmentUrl) ?>"
                                                download
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                <i class="bi bi-download"></i>
                                                Download
                                            </a>

                                        </div>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <div class="text-muted text-center py-4 attachment-file-no-results" style="display:none;">
                            <i class="bi bi-file-earmark-x fs-1 d-block mb-2"></i>
                            No files match your search.
                        </div>

                    <?php endif; ?>
                    <?php endif; ?>

                </div>

            </div>

            <!-- =====================================================
                 COMPLETE TIMELINE
            ====================================================== -->

            <div class="card border-0 shadow-sm mb-4">

                <div class="card-header bg-white py-3">

                    <div class="d-flex justify-content-between align-items-center">

                        <strong>

                            <i class="bi bi-clock-history text-primary"></i>

                            Complete Timeline

                        </strong>

                        <span class="badge bg-secondary">

                            <?= count($timeline) ?>

                            Movement(s)

                        </span>

                    </div>

                </div>

                <div class="card-body">

                    <div class="tracking-timeline">

                        <!-- =================================================
                             INITIAL RECEIVED EVENT
                        ================================================== -->

                        <div class="timeline-item">

                            <div class="timeline-marker timeline-marker-primary">

                                <i class="bi bi-inbox"></i>

                            </div>

                            <div class="timeline-content">

                                <div class="timeline-card">

                                    <div class="d-flex flex-wrap justify-content-between gap-2">

                                        <div>

                                            <h6 class="mb-1">

                                                Document Registered

                                            </h6>

                                            <div class="text-muted small">

                                                Initial document registration

                                            </div>

                                        </div>

                                        <div class="small text-muted">

                                            <?php if (
                                                !empty($dateReceived)
                                            ): ?>

                                                <?= e(
                                                    date(
                                                        'M d, Y h:i A',
                                                        strtotime(
                                                            $dateReceived
                                                        )
                                                    )
                                                ) ?>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                    <div class="mt-3">

                                        <div class="small text-muted">
                                            Office
                                        </div>

                                        <strong>

                                            <?= e(
                                                $document[
                                                    'current_department_name'
                                                ]
                                                ?? 'Unknown'
                                            ) ?>

                                        </strong>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <!-- =================================================
                             ROUTING EVENTS
                        ================================================== -->

                        <?php foreach (
                            $timeline
                            as $index => $route
                        ): ?>

                            <?php

                            $routeStatus =
                                strtoupper(
                                    trim(
                                        (string) (
                                            $route[
                                                'route_status'
                                            ]
                                            ?? ''
                                        )
                                    )
                                );

                            $fromDepartment =
                                $route[
                                    'from_department_name'
                                ]
                                ?? 'Unknown';

                            $toDepartment =
                                $route[
                                    'to_department_name'
                                ]
                                ?? 'Unknown';

                            $routedByName =
                                trim(
                                    (
                                        $route[
                                            'routed_by_first_name'
                                        ]
                                        ?? ''
                                    )
                                    . ' ' .
                                    (
                                        $route[
                                            'routed_by_last_name'
                                        ]
                                        ?? ''
                                    )
                                );

                            if ($routedByName === '') {

                                $routedByName =
                                    $route[
                                        'routed_by_username'
                                    ]
                                    ?? 'Unknown User';
                            }

                            $routedToName =
                                trim(
                                    (
                                        $route[
                                            'routed_to_first_name'
                                        ]
                                        ?? ''
                                    )
                                    . ' ' .
                                    (
                                        $route[
                                            'routed_to_last_name'
                                        ]
                                        ?? ''
                                    )
                                );

                            if ($routedToName === '') {

                                $routedToName =
                                    $route[
                                        'routed_to_username'
                                    ]
                                    ?? 'Unassigned';
                            }

                            $routeDate =
                                $route['routed_at']
                                ?? null;

                            $receivedDate =
                                $route['received_at']
                                ?? null;

                            $remarks =
                                trim(
                                    (string) (
                                        $route['remarks']
                                        ?? ''
                                    )
                                );

                            if ($routeStatus === 'RECEIVED') {

                                $markerClass =
                                    'timeline-marker-success';

                                $icon =
                                    'bi-check-circle';

                            } else {

                                $markerClass =
                                    'timeline-marker-warning';

                                $icon =
                                    'bi-send';
                            }

                            ?>

                            <div class="timeline-item">

                                <div class="timeline-marker <?= $markerClass ?>">

                                    <i class="bi <?= $icon ?>"></i>

                                </div>

                                <div class="timeline-content">

                                    <div class="timeline-card">

                                        <!-- HEADER -->

                                        <div class="d-flex flex-wrap justify-content-between gap-2">

                                            <div>

                                                <h6 class="mb-1">

                                                    <?php if (
                                                        $routeStatus === 'RECEIVED'
                                                    ): ?>

                                                        Document Received

                                                    <?php else: ?>

                                                        Document Routed

                                                    <?php endif; ?>

                                                </h6>

                                                <span
                                                    class="badge <?= $routeStatus === 'RECEIVED'
                                                        ? 'text-bg-success'
                                                        : 'text-bg-warning' ?>"
                                                >

                                                    <?= e(
                                                        $routeStatus
                                                        !== ''
                                                            ? $routeStatus
                                                            : 'UNKNOWN'
                                                    ) ?>

                                                </span>

                                            </div>

                                            <div class="small text-muted">

                                                <?php if (
                                                    !empty($routeDate)
                                                ): ?>

                                                    <?= e(
                                                        date(
                                                            'M d, Y h:i A',
                                                            strtotime(
                                                                $routeDate
                                                            )
                                                        )
                                                    ) ?>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                        <!-- MOVEMENT -->

                                        <div class="route-movement mt-4">

                                            <div class="route-office">

                                                <div class="small text-muted">
                                                    From
                                                </div>

                                                <strong>

                                                    <?= e(
                                                        $fromDepartment
                                                    ) ?>

                                                </strong>

                                            </div>

                                            <div class="route-arrow">

                                                <i class="bi bi-arrow-right"></i>

                                            </div>

                                            <div class="route-office">

                                                <div class="small text-muted">
                                                    To
                                                </div>

                                                <strong>

                                                    <?= e(
                                                        $toDepartment
                                                    ) ?>

                                                </strong>

                                            </div>

                                        </div>

                                        <!-- ROUTED BY -->

                                        <div class="row g-3 mt-2">

                                            <div class="col-md-6">

                                                <div class="small text-muted">

                                                    Routed By

                                                </div>

                                                <strong>

                                                    <i class="bi bi-person"></i>

                                                    <?= e(
                                                        $routedByName
                                                    ) ?>

                                                </strong>

                                            </div>

                                            <div class="col-md-6">

                                                <div class="small text-muted">

                                                    Routed To

                                                </div>

                                                <strong>

                                                    <i class="bi bi-person-check"></i>

                                                    <?= e(
                                                        $routedToName
                                                    ) ?>

                                                </strong>

                                            </div>

                                        </div>

                                        <!-- RECEIVED DATE -->

                                        <?php if (
                                            !empty($receivedDate)
                                        ): ?>

                                            <div class="mt-3">

                                                <div class="small text-muted">

                                                    Received At

                                                </div>

                                                <strong class="text-success">

                                                    <i class="bi bi-check2-circle"></i>

                                                    <?= e(
                                                        date(
                                                            'M d, Y h:i A',
                                                            strtotime(
                                                                $receivedDate
                                                            )
                                                        )
                                                    ) ?>

                                                </strong>

                                            </div>

                                        <?php endif; ?>

                                        <!-- DUE DATE -->

                                        <?php if (
                                            !empty(
                                                $route['due_at']
                                            )
                                        ): ?>

                                            <div class="mt-3">

                                                <div class="small text-muted">

                                                    Due Date

                                                </div>

                                                <strong>

                                                    <i class="bi bi-calendar-event"></i>

                                                    <?= e(
                                                        date(
                                                            'M d, Y h:i A',
                                                            strtotime(
                                                                $route['due_at']
                                                            )
                                                        )
                                                    ) ?>

                                                </strong>

                                            </div>

                                        <?php endif; ?>

                                        <!-- REMARKS -->

                                        <?php if (
                                            $remarks !== ''
                                        ): ?>

                                            <div class="remarks-box mt-3">

                                                <div class="small fw-semibold text-muted mb-1">

                                                    <i class="bi bi-chat-left-text"></i>

                                                    Remarks

                                                </div>

                                                <div>

                                                    <?= nl2br(
                                                        e($remarks)
                                                    ) ?>

                                                </div>

                                            </div>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                        <!-- =================================================
                             CURRENT LOCATION
                        ================================================== -->

                        <div class="timeline-item timeline-current">

                            <div class="timeline-marker timeline-marker-dark">

                                <i class="bi bi-geo-alt-fill"></i>

                            </div>

                            <div class="timeline-content">

                                <div class="timeline-card current-location-card">

                                    <div class="d-flex flex-wrap justify-content-between gap-2">

                                        <div>

                                            <h6 class="mb-1">

                                                Current Location

                                            </h6>

                                            <div class="text-muted small">

                                                Latest recorded document location

                                            </div>

                                        </div>

                                        <span class="badge text-bg-dark">

                                            CURRENT

                                        </span>

                                    </div>

                                    <div class="mt-3">

                                        <div class="small text-muted">
                                            Office
                                        </div>

                                        <strong class="fs-5">

                                            <?= e(
                                                $currentOffice
                                            ) ?>

                                        </strong>

                                    </div>

                                    <div class="mt-3">

                                        <div class="small text-muted">
                                            Status
                                        </div>

                                        <span class="badge text-bg-primary">

                                            <?= e(
                                                $statusName
                                            ) ?>

                                        </span>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    <?php elseif (
        !$searched
        && $error === ''
    ): ?>

        <!-- =================================================
             EMPTY STATE
        ================================================== -->

        <div class="card border-0 shadow-sm">

            <div class="card-body text-center py-5">

                <i
                    class="bi bi-search fs-1 text-primary d-block mb-3"
                ></i>

                <h5>

                    Track a Document

                </h5>

                <p class="text-muted mb-0">

                    Enter a tracking number or document title above to view
                    the document's current location and
                    complete movement history.

                </p>

            </div>

        </div>

    <?php endif; ?>

</div>

<!-- =========================================================
     MOBILE SIDEBAR
========================================================= -->

<script src="<?php echo e(app_url('assets/js/features/documents/document-tracking.js')); ?>?v=20260820e"></script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
