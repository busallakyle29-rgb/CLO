<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| CLO - ARCHIVE DOCUMENTS
|--------------------------------------------------------------------------
| Functions:
| - View archived documents
| - Search archived documents
| - Restore archived document
| - Secure archive/restore actions
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/functions/security.php';
require_once dirname(__DIR__, 2) . '/functions/auth.php';

/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

if (function_exists('start_secure_session')) {
    start_secure_session();
}

if (function_exists('auth_require_page')) {
    auth_require_page('archive_documents.php');
} elseif (function_exists('auth_require_role')) {
    auth_require_role(['ADMIN', 'RECORDS_OFFICER']);
} elseif (function_exists('auth_require_login')) {
    auth_require_login();
}

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

try {
    $conn = db_connection();
} catch (Throwable $exception) {
    http_response_code(500);
    exit('Database connection failed.');
}

/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$currentUserId = 0;
$canManageArchive = auth_has_any_role(['ADMIN', 'RECORDS_OFFICER']);
$canViewAllArchived = auth_has_any_role(['ADMIN', 'INCOMING', 'RECORDS_OFFICER']);

if (function_exists('auth_user')) {

    $currentUser = auth_user();

    if (is_array($currentUser)) {

        $currentUserId = (int) (
            $currentUser['user_id'] ?? 0
        );
    }
}

$canViewAllArchivedValue = $canViewAllArchived ? 1 : 0;

/*
|--------------------------------------------------------------------------
| LOCAL REDIRECT
|--------------------------------------------------------------------------
*/

function archive_redirect(
    string $type,
    string $message
): never {

    header(
        'Location: archive_documents.php?' .
        http_build_query([
            'message_type' => $type,
            'message' => $message
        ])
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| GET ARCHIVED STATUS ID
|--------------------------------------------------------------------------
*/

function get_archived_status_id(
    mysqli $conn
): int {

    $stmt = $conn->prepare("
        SELECT document_status_id
        FROM document_status
        WHERE LOWER(status_name) = 'archived'
        LIMIT 1
    ");

    if (!$stmt) {

        throw new RuntimeException(
            'Unable to prepare archived status query.'
        );
    }

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    $stmt->close();

    return (int) (
        $row['document_status_id'] ?? 0
    );
}

/*
|--------------------------------------------------------------------------
| GET ACTIVE STATUS
|--------------------------------------------------------------------------
|
| Used when restoring a document.
|
| Default:
| Received
|
*/

function get_restore_status_id(
    mysqli $conn
): int {

    $stmt = $conn->prepare("
        SELECT document_status_id
        FROM document_status
        WHERE LOWER(status_name) = 'received'
        LIMIT 1
    ");

    if (!$stmt) {

        throw new RuntimeException(
            'Unable to prepare restore status query.'
        );
    }

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    $stmt->close();

    return (int) (
        $row['document_status_id'] ?? 0
    );
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    /*
     * Validate CSRF before doing
     * any database modification.
     */

    if (function_exists('csrf_require')) {
        csrf_require('archive_restore_form');
    }
}

/*
|--------------------------------------------------------------------------
| DELETE DOCUMENT
|--------------------------------------------------------------------------
*/

$errors = [];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['delete_document'])
    && $canManageArchive
) {

    $documentId = filter_input(
        INPUT_POST,
        'document_id',
        FILTER_VALIDATE_INT
    );

    if (!$documentId) {

        $errors[] =
            'Invalid document.';
    }

    if (empty($errors)) {

        $transactionStarted = false;

        try {

            $archivedStatusId =
                get_archived_status_id(
                    $conn
                );

            if (!$archivedStatusId) {

                throw new RuntimeException(
                    'The "Archived" status does not exist.'
                );
            }

            $attachmentPaths = [];

            $attachmentStmt = $conn->prepare(
                'SELECT file_path FROM attachments WHERE document_id = ?'
            );

            if (!$attachmentStmt) {

                throw new RuntimeException(
                    'Unable to prepare attachment query.'
                );
            }

            $attachmentStmt->bind_param('i', $documentId);
            $attachmentStmt->execute();

            $attachmentResult =
                $attachmentStmt->get_result();

            while ($attachment = $attachmentResult->fetch_assoc()) {

                if (!empty($attachment['file_path'])) {

                    $attachmentPaths[] =
                        (string) $attachment['file_path'];
                }
            }

            $attachmentStmt->close();

            $conn->begin_transaction();
            $transactionStarted = true;

            $deleteStmt = $conn->prepare('
                DELETE FROM documents
                WHERE document_id = ?
                  AND current_status_id = ?
            ');

            if (!$deleteStmt) {

                throw new RuntimeException(
                    'Unable to prepare delete query.'
                );
            }

            $deleteStmt->bind_param(
                'ii',
                $documentId,
                $archivedStatusId
            );

            if (!$deleteStmt->execute()) {

                $deleteStmt->close();

                throw new RuntimeException(
                    'Unable to delete document.'
                );
            }

            $affectedRows =
                $deleteStmt->affected_rows;

            $deleteStmt->close();

            if ($affectedRows === 0) {

                throw new RuntimeException(
                    'Document was not found in the archive.'
                );
            }

            $conn->commit();

            foreach ($attachmentPaths as $attachmentPath) {

                $fullPath =
                    dirname(__DIR__, 2) . '/' . ltrim($attachmentPath, '/\\');

                if (is_file($fullPath)) {

                    @unlink($fullPath);
                }
            }

            archive_redirect(
                'success',
                'Document deleted permanently.'
            );

        } catch (Throwable $exception) {

            if ($transactionStarted) {
                $conn->rollback();
            }

            $errors[] =
                'Unable to delete document: ' .
                $exception->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| RESTORE DOCUMENT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['restore_document'])
    && $canManageArchive
) {

    $documentId = filter_input(
        INPUT_POST,
        'document_id',
        FILTER_VALIDATE_INT
    );

    if (!$documentId) {

        $errors[] =
            'Invalid document.';
    }

    if (empty($errors)) {

        try {

            $archivedStatusId =
                get_archived_status_id(
                    $conn
                );

            if (!$archivedStatusId) {

                throw new RuntimeException(
                    'The "Archived" status does not exist.'
                );
            }

            $restoreStatusId =
                get_restore_status_id(
                    $conn
                );

            if (!$restoreStatusId) {

                throw new RuntimeException(
                    'The "Received" status does not exist.'
                );
            }

            /*
             * Only restore documents that
             * are currently archived.
             */

            $stmt = $conn->prepare("
                UPDATE documents
                SET
                    current_status_id = ?,
                    archived_at = NULL,
                    archived_by_user_id = NULL,
                    updated_at = CURRENT_TIMESTAMP
                WHERE
                    document_id = ?
                    AND current_status_id = ?
            ");

            if (!$stmt) {

                throw new RuntimeException(
                    'Unable to prepare restore query.'
                );
            }

            $stmt->bind_param(
                'iii',
                $restoreStatusId,
                $documentId,
                $archivedStatusId
            );

            $stmt->execute();

            $affectedRows =
                $stmt->affected_rows;

            $stmt->close();

            if ($affectedRows === 0) {

                throw new RuntimeException(
                    'Document was not found in the archive.'
                );
            }

            archive_redirect(
                'success',
                'Document restored successfully.'
            );

        } catch (Throwable $exception) {

            $errors[] =
                'Unable to restore document: ' .
                $exception->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search = trim(
    (string) (
        $_GET['search'] ?? ''
    )
);

/*
|--------------------------------------------------------------------------
| GET ARCHIVED STATUS
|--------------------------------------------------------------------------
*/

try {

    $archivedStatusId =
        get_archived_status_id(
            $conn
        );

} catch (Throwable $exception) {

    $archivedStatusId = 0;

    $errors[] =
        $exception->getMessage();
}

/*
|--------------------------------------------------------------------------
| LIVE SUGGESTIONS (JSON)
|--------------------------------------------------------------------------
*/

if (isset($_GET['suggest'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    $suggestQuery = trim((string) ($_GET['q'] ?? ''));
    $suggestions = [];

    if ($suggestQuery !== '' && $archivedStatusId > 0) {
        $like = '%' . $suggestQuery . '%';
        $prefix = $suggestQuery . '%';

        try {
            $stmt = $conn->prepare("
                SELECT
                    d.tracking_no,
                    d.title,
                    COALESCE(d.external_reference_no, '') AS external_reference_no
                FROM documents d
                WHERE
                    d.current_status_id = ?
                    AND (? = 1 OR d.current_assignee_user_id = ?)
                    AND (
                        d.tracking_no LIKE ?
                        OR d.title LIKE ?
                        OR d.description LIKE ?
                        OR d.external_reference_no LIKE ?
                    )
                ORDER BY
                    CASE
                        WHEN d.tracking_no LIKE ? THEN 0
                        WHEN d.title LIKE ? THEN 1
                        ELSE 2
                    END,
                    d.document_id DESC
                LIMIT 8
            ");

            if ($stmt) {
                $stmt->bind_param(
                    'iiissssss',
                    $archivedStatusId,
                    $canViewAllArchivedValue,
                    $currentUserId,
                    $like,
                    $like,
                    $like,
                    $like,
                    $prefix,
                    $prefix
                );
                $stmt->execute();
                $result = $stmt->get_result();
                $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
                $stmt->close();

                foreach ($rows as $row) {
                    $suggestions[] = [
                        'tracking_no' => (string) ($row['tracking_no'] ?? ''),
                        'title' => (string) ($row['title'] ?? ''),
                        'reference' => (string) ($row['external_reference_no'] ?? ''),
                    ];
                }
            }
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
| LOAD ARCHIVED DOCUMENTS
|--------------------------------------------------------------------------
*/

$documents = [];

if ($archivedStatusId > 0) {

        $stmt = $conn->prepare("
            SELECT
                d.*,

                dt.type_name,

                ds.status_name,

                COALESCE(
                    NULLIF(d.origin_department_other, ''),
                    od.department_name
                ) AS origin_department_name,

                cd.department_name
                    AS current_department_name,

                CONCAT(
                    COALESCE(au.first_name, ''),
                    ' ',
                    COALESCE(au.last_name, '')
                ) AS archived_by_name,

                au.username AS archived_by_username

            FROM documents d

            LEFT JOIN document_types dt
                ON dt.document_type_id =
                   d.document_type_id

            LEFT JOIN document_status ds
                ON ds.document_status_id =
                   d.current_status_id

            LEFT JOIN departments od
                ON od.department_id =
                   d.origin_department_id

            LEFT JOIN departments cd
                ON cd.department_id =
                   d.current_department_id

            LEFT JOIN users au
                ON au.user_id =
                   d.archived_by_user_id

            WHERE
                d.current_status_id = ?
                AND (? = 1 OR d.current_assignee_user_id = ?)

            ORDER BY
                COALESCE(
                    d.archived_at,
                    d.updated_at
                ) DESC,

                d.document_id DESC
        ");

        if (!$stmt) {

            $errors[] =
                'Unable to prepare archive query.';
        } else {

            $stmt->bind_param(
                'iii',
                $archivedStatusId,
                $canViewAllArchivedValue,
                $currentUserId
            );
        }

    if (isset($stmt) && $stmt) {

        try {

            $stmt->execute();

            $result =
                $stmt->get_result();

            $documents =
                $result->fetch_all(
                    MYSQLI_ASSOC
                );

            $stmt->close();

        } catch (Throwable $exception) {

            $errors[] =
                'Unable to load archived documents: ' .
                $exception->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| MESSAGES
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

                    <i class="bi bi-archive text-danger"></i>

                    Document Archive

                </h3>

                <div class="text-muted">

                    View and restore archived documents.

                </div>

            </div>

            <div>

                <a
                    href="document_management.php"
                    class="btn btn-outline-primary"
                >

                    <i class="bi bi-folder2-open"></i>

                    Document Management

                </a>

            </div>

        </div>

    </div>

    <!-- =====================================================
         ERRORS
    ====================================================== -->

    <?php if (!empty($errors)): ?>

        <div class="alert alert-danger">

            <strong>
                Unable to complete the request:
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

    <!-- =====================================================
         SUCCESS MESSAGE
    ====================================================== -->

    <?php if ($message !== ''): ?>

        <div
            class="alert alert-<?= $messageType === 'success'
                ? 'success'
                : 'danger' ?> alert-dismissible fade show"
        >

            <?= e($message) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>

    <!-- =====================================================
         SEARCH
    ====================================================== -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <form
                method="GET"
                class="row g-2"
                id="archiveSearchForm"
                data-suggest-url="<?= e(app_url('archive_documents.php')) ?>"
            >

                <div class="col-md-10">

                    <label
                        for="archiveSearch"
                        class="visually-hidden"
                    >
                        Search archived documents
                    </label>

                    <div class="archive-suggest-wrap">

                        <input
                            id="archiveSearch"
                            type="search"
                            name="search"
                            class="form-control"
                            placeholder="Search tracking number, title, description or reference..."
                            value="<?= e($search) ?>"
                            autocomplete="off"
                            data-archive-suggest="true"
                            aria-autocomplete="list"
                            aria-expanded="false"
                        >

                        <div
                            class="archive-suggest-list"
                            id="archiveSuggestions"
                            hidden
                        ></div>

                    </div>

                </div>

                <div class="col-md-2 d-grid">

                    <button
                        type="submit"
                        class="btn btn-outline-danger"
                    >

                        <i class="bi bi-search"></i>

                        Search

                    </button>

                </div>

            </form>

        </div>

    </div>

    <!-- =====================================================
         ARCHIVE TABLE
    ====================================================== -->

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

                <strong>

                    <i class="bi bi-archive"></i>

                    Archived Documents

                </strong>

                <span class="badge bg-danger">

                    <?= count($documents) ?>

                    Archived

                </span>

            </div>

        </div>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>

                        <th>
                            Tracking Number
                        </th>

                        <th>
                            Document
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Department
                        </th>

                        <th>
                            Date Received
                        </th>

                        <th>
                            Archived
                        </th>

                        <th>
                            Archived By
                        </th>

                        <th class="text-end">
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody id="archiveDocumentsBody">

                <?php if (empty($documents)): ?>

                    <tr>

                        <td
                            colspan="8"
                            class="text-center py-5 text-muted"
                        >

                            <i class="bi bi-archive fs-1 d-block mb-2"></i>

                            No archived documents found.

                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($documents as $document): ?>

                        <?php

                        $documentId =
                            (int) (
                                $document['document_id']
                                ?? 0
                            );

                        $archivedBy =
                            trim(
                                (string) (
                                    $document['archived_by_name']
                                    ?? ''
                                )
                            );

                        if ($archivedBy === '') {

                            $archivedBy = trim(
                                (string) (
                                    $document['archived_by_username']
                                    ?? ''
                                )
                            );
                        }

                        if ($archivedBy === '') {

                            $archivedBy = 'System';
                        }

                        ?>

                        <tr
                            class="archive-doc-row"
                            data-tracking-no="<?= e((string) ($document['tracking_no'] ?? '')) ?>"
                        >

                            <!-- TRACKING -->

                            <td>

                                <strong class="text-danger">

                                    <?= e(
                                        $document['tracking_no']
                                        ?? '-'
                                    ) ?>

                                </strong>

                                <div class="small text-muted">

                                    ID:
                                    <?= $documentId ?>

                                </div>

                            </td>

                            <!-- DOCUMENT -->

                            <td>

                                <strong>

                                    <?= e(
                                        $document['title']
                                        ?? '-'
                                    ) ?>

                                </strong>

                                <?php if (
                                    !empty(
                                        $document['external_reference_no']
                                    )
                                ): ?>

                                    <div class="small text-muted">

                                        Ref:
                                        <?= e(
                                            $document[
                                                'external_reference_no'
                                            ]
                                        ) ?>

                                    </div>

                                <?php endif; ?>

                            </td>

                            <!-- TYPE -->

                            <td>

                                <?= e(
                                    $document['type_name']
                                    ?? '-'
                                ) ?>

                            </td>

                            <!-- DEPARTMENT -->

                            <td>

                                <?= e(
                                    $document[
                                        'current_department_name'
                                    ]
                                    ?? '-'
                                ) ?>

                            </td>

                            <!-- DATE RECEIVED -->

                            <td>

                                <?php if (
                                    !empty(
                                        $document['date_received']
                                    )
                                ): ?>

                                    <?= e(
                                        date(
                                            'M d, Y h:i A',
                                            strtotime(
                                                $document[
                                                    'date_received'
                                                ]
                                            )
                                        )
                                    ) ?>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- ARCHIVED -->

                            <td>

                                <?php

                                $archiveDate =
                                    $document['archived_at']
                                    ??
                                    $document['updated_at']
                                    ??
                                    null;

                                ?>

                                <?php if ($archiveDate): ?>

                                    <?= e(
                                        date(
                                            'M d, Y h:i A',
                                            strtotime(
                                                $archiveDate
                                            )
                                        )
                                    ) ?>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- ARCHIVED BY -->

                            <td>

                                <?= e(
                                    $archivedBy
                                ) ?>

                            </td>

                            <!-- ACTION -->

                            <td class="text-end">

                                <?php if ($canManageArchive): ?>

                                <form
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Restore this document from the archive?');"
                                >

                                    <?= csrf_field('archive_restore_form') ?>

                                    <input
                                        type="hidden"
                                        name="document_id"
                                        value="<?= $documentId ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="restore_document"
                                        class="btn btn-sm btn-outline-success"
                                        title="Restore document"
                                    >

                                        <i class="bi bi-arrow-counterclockwise"></i>

                                        Restore

                                    </button>

                                </form>

                                <form
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Delete this archived document permanently? This cannot be undone.');"
                                >

                                    <?= csrf_field('archive_restore_form') ?>

                                    <input
                                        type="hidden"
                                        name="document_id"
                                        value="<?= $documentId ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="delete_document"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Delete permanently"
                                    >

                                        <i class="bi bi-trash"></i>

                                        Delete

                                    </button>

                                </form>

                                <?php else: ?>
                                    <span class="text-muted small">View only</span>
                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script src="<?php echo e(app_url('assets/js/features/documents/archive-documents.js')); ?>?v=20260820f"></script>

<?php
require_once dirname(__DIR__, 2) . '/includes/footer.php';
?>
