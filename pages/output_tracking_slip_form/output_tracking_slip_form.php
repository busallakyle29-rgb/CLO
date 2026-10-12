<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/functions/security.php';
require_once dirname(__DIR__, 2) . '/functions/auth.php';
require_once dirname(__DIR__, 2) . '/models/AuditTrailModel.php';

if (function_exists('start_secure_session')) {
    start_secure_session();
}

if (function_exists('auth_require_login')) {
    auth_require_login();
}

if (!auth_is_role('INCOMING')) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h1>Only Incoming staff can print tracking slips.</h1></body></html>';
    exit;
}

function tracking_slip_override_key(int $documentId): string
{
    return 'tracking_slip_overrides_' . $documentId;
}

function tracking_slip_get_overrides(int $documentId): array
{
    $key = tracking_slip_override_key($documentId);
    $value = $_SESSION[$key] ?? [];

    if (!is_array($value)) {
        return [];
    }

    return $value;
}

function checked(bool $condition): string
{
    return $condition ? '☑' : '☐';
}

function tracking_slip_format_datetime(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '';
    }

    $timestamp = strtotime($value);
    return $timestamp === false ? '' : date('d M Y / h:i A', $timestamp);
}

$documentId = filter_input(INPUT_GET, 'document_id', FILTER_VALIDATE_INT);
$documentId = $documentId !== false && $documentId !== null ? (int) $documentId : 0;

$requestedDocumentIds = [];

if (isset($_GET['document_ids'])) {
    $requestedDocumentIdsCsv = (string) $_GET['document_ids'];

    foreach (explode(',', $requestedDocumentIdsCsv) as $requestedDocumentId) {
        $parsedDocumentId = filter_var(trim((string) $requestedDocumentId), FILTER_VALIDATE_INT);

        if ($parsedDocumentId !== false && $parsedDocumentId !== null && (int) $parsedDocumentId > 0) {
            $requestedDocumentIds[] = (int) $parsedDocumentId;
        }
    }
}

if ($documentId > 0) {
    $requestedDocumentIds[] = $documentId;
}

$requestedDocumentIds = array_values(array_unique($requestedDocumentIds));

if (empty($requestedDocumentIds)) {
    http_response_code(400);
    echo '<!DOCTYPE html><html><head><title>Invalid Slip Request</title></head><body><h1>Invalid tracking slip request.</h1></body></html>';
    exit;
}

$documentsForPrint = [];

foreach ($requestedDocumentIds as $requestedDocumentId) {
    $stmt = db_execute(
        '
            SELECT
                d.document_id,
                d.tracking_no,
                d.title,
                d.description,
                d.external_reference_no,
                d.date_received,
                d.date_due,
                TIMESTAMP(d.date_received, TIME(d.created_at)) AS initial_received_at,
                CASE
                    WHEN d.current_assignee_user_id IS NULL THEN NULL
                    ELSE GREATEST(
                        COALESCE(
                            (
                                SELECT MAX(dr.routed_at)
                                FROM document_routes dr
                                WHERE dr.document_id = d.document_id
                                  AND dr.routed_to_user_id = d.current_assignee_user_id
                            ),
                            \'1000-01-01 00:00:00\'
                        ),
                        COALESCE(
                            (
                                SELECT MAX(al.created_at)
                                FROM audit_logs al
                                WHERE al.entity_type = \'DOCUMENT\'
                                  AND al.entity_id = d.document_id
                                  AND al.action = \'UPDATED\'
                                  AND CAST(JSON_UNQUOTE(JSON_EXTRACT(al.new_values, \'$.current_assignee_user_id\')) AS UNSIGNED) = d.current_assignee_user_id
                                  AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(al.old_values, \'$.current_assignee_user_id\')), \'\') <> CAST(d.current_assignee_user_id AS CHAR)
                            ),
                            \'1000-01-01 00:00:00\'
                        ),
                        d.created_at
                    )
                END AS date_assigned,
                                COALESCE(
                                        (
                                                SELECT dar.accepted_at
                                                FROM document_assignment_receipts dar
                                                WHERE dar.document_id = d.document_id
                                                    AND dar.user_id = d.current_assignee_user_id
                                        ),
                                        (
                                                SELECT MAX(dr.received_at)
                                                FROM document_routes dr
                                                WHERE dr.document_id = d.document_id
                                                    AND dr.routed_to_user_id = d.current_assignee_user_id
                                                    AND dr.received_at IS NOT NULL
                                        )
                                ) AS assignee_received_at,
                d.priority_level,
                d.current_status_id,
                d.current_assignee_user_id,
                ds.status_name,
                COALESCE(
                    NULLIF(d.origin_department_other, \'\'),
                    od.department_name
                ) AS origin_department,
                dt.type_name,
                u.first_name AS assignee_first_name,
                u.last_name AS assignee_last_name,
                u.username AS assignee_username
            FROM documents d
            LEFT JOIN document_status ds ON ds.document_status_id = d.current_status_id
            LEFT JOIN departments od ON od.department_id = d.origin_department_id
            LEFT JOIN document_types dt ON dt.document_type_id = d.document_type_id
            LEFT JOIN users u ON u.user_id = d.current_assignee_user_id
            WHERE d.document_id = ?
            LIMIT 1
        ',
        'i',
        [$requestedDocumentId]
    );

    $documentRow = db_fetch_one_assoc($stmt);
    $stmt->close();

    if ($documentRow) {
        $documentsForPrint[] = $documentRow;
    }
}

if (empty($documentsForPrint)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>Slip Not Found</title></head><body><h1>Tracking slip not found.</h1></body></html>';
    exit;
}

$cloPersonnelStmt = db_execute(
    '
        SELECT
            u.user_id,
            u.username,
            u.first_name,
            u.last_name
        FROM users u
                INNER JOIN roles r
                        ON r.role_id = u.role_id
        INNER JOIN departments d
            ON d.department_id = u.department_id
        WHERE d.department_code = ?
          AND u.is_active = 1
                    AND r.role_name <> ?
        ORDER BY u.first_name ASC, u.last_name ASC, u.username ASC
    ',
        'ss',
        ['CLO', 'ADMIN']
);

$cloPersonnel = db_fetch_all_assoc($cloPersonnelStmt);
$cloPersonnelStmt->close();

function tracking_slip_assigned_staff(
    array $personnel,
    int $assignedUserId,
    string $assignedName
): array {
    $staff = [];

    foreach ($personnel as $person) {
        $name = trim(
            (string) ($person['first_name'] ?? '')
            . ' '
            . (string) ($person['last_name'] ?? '')
        );

        if ($name === '') {
            $name = (string) ($person['username'] ?? '');
        }

        $staff[] = [
            'selected' => (
                (int) ($person['user_id'] ?? 0) === $assignedUserId
                || ($assignedUserId <= 0 && strcasecmp($name, $assignedName) === 0)
            ),
            'name' => $name,
        ];
    }

    if (empty($staff)) {
        $staff[] = [
            'selected' => true,
            'name' => $assignedName,
        ];
    }

    return $staff;
}

$document = $documentsForPrint[0];

$slipOverrides = tracking_slip_get_overrides($documentId);

$currentUser = auth_user();
$currentUserId = (int) ($currentUser['user_id'] ?? 0);
$autoPrint = isset($_GET['autoprint']) && (string) $_GET['autoprint'] === '1';

$generateRequested = isset($_GET['generate']) && (string) $_GET['generate'] === '1';
if ($generateRequested && $currentUserId > 0 && $documentId > 0) {
    audit_log_create(
        $currentUserId,
        'DOCUMENT_TRACKING_SLIP',
        $documentId,
        'GENERATE',
        null,
        [
            'tracking_no' => (string) ($document['tracking_no'] ?? ''),
            'document_title' => (string) ($document['title'] ?? ''),
            'generated_by_user_id' => $currentUserId,
            'generated_at' => date('Y-m-d H:i:s')
        ]
    );
}

$trackingNo = trim((string) ($slipOverrides['tracking_no'] ?? $document['tracking_no'] ?? ''));
if ($trackingNo === '') {
    $trackingNo = 'CLO-' . date('Y') . '-' . str_pad((string) ((int) ($document['document_id'] ?? 0)), 6, '0', STR_PAD_LEFT);
}

$from = trim((string) ($slipOverrides['from'] ?? $document['origin_department'] ?? '')) !== '' ? (string) ($slipOverrides['from'] ?? $document['origin_department']) : 'City Legal Office';
$referenceNo = (string) ($document['external_reference_no'] ?? '');
if ($referenceNo === '') {
    $referenceNo = (string) ($slipOverrides['ref_no'] ?? '');
}
$remarks = trim((string) ($slipOverrides['remarks'] ?? $document['description'] ?? '')) !== '' ? (string) ($slipOverrides['remarks'] ?? $document['description']) : 'No remarks provided.';

$dateTimeReceived = tracking_slip_format_datetime((string) ($document['initial_received_at'] ?? ''));
$dateReceivedValue = (string) ($slipOverrides['date_received'] ?? ($document['assignee_received_at'] ?? ''));
$dateReceived = tracking_slip_format_datetime($dateReceivedValue);

$assignedName = trim((string) ($slipOverrides['assigned_to'] ?? (($document['assignee_first_name'] ?? '') . ' ' . ($document['assignee_last_name'] ?? ''))));
if ($assignedName === '') {
    $assignedName = trim((string) ($document['assignee_username'] ?? ''));
}
if ($assignedName === '') {
    $assignedName = 'Unassigned';
}

$assignedStaff = tracking_slip_assigned_staff(
    $cloPersonnel,
    (int) ($document['current_assignee_user_id'] ?? 0),
    $assignedName
);

$priorityLevel = strtoupper(trim((string) ($document['priority_level'] ?? 'MEDIUM')));
$typeName = strtolower(trim((string) ($document['type_name'] ?? '')));
$statusName = strtolower(trim((string) ($document['status_name'] ?? '')));

$actionDesired = [
    'appropriate_action' => !empty($slipOverrides['action_appropriate_action']),
    'review' => !empty($slipOverrides['action_review']),
    'comment_reply' => !empty($slipOverrides['action_comment_reply']),
    'reference' => !empty($slipOverrides['action_reference']),
    'countersign' => !empty($slipOverrides['action_countersign']),
    'others' => !empty($slipOverrides['action_others']),
];

$tasks = [
    'draft' => !empty($slipOverrides['task_draft']),
    'discuss' => !empty($slipOverrides['task_discuss']),
    'attend' => !empty($slipOverrides['task_attend']),
    'facilitate' => !empty($slipOverrides['task_facilitate']),
    'review' => !empty($slipOverrides['task_review']),
    'file' => !empty($slipOverrides['task_file']),
    'release' => !empty($slipOverrides['task_release']),
    'note' => !empty($slipOverrides['task_note']),
    'investigation' => !empty($slipOverrides['task_investigation']),
    'disseminate' => !empty($slipOverrides['task_disseminate']),
    'others' => !empty($slipOverrides['task_others']),
    'endorse' => !empty($slipOverrides['task_endorse']),
];

$outputs = [
    'letter' => !empty($slipOverrides['output_letter']),
    'memo' => !empty($slipOverrides['output_memo']),
    'inspection' => !empty($slipOverrides['output_inspection']),
    'meeting' => !empty($slipOverrides['output_meeting']),
    'contract' => !empty($slipOverrides['output_contract']),
    'investigation' => !empty($slipOverrides['output_investigation']),
    'affidavit' => !empty($slipOverrides['output_affidavit']),
    'others' => !empty($slipOverrides['output_others']),
];

$dateAssigned = tracking_slip_format_datetime(isset($document['date_assigned']) ? (string) $document['date_assigned'] : null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">

<link rel="stylesheet" href="<?= e(app_url('assets/css/features/tracking/tracking-slip.css')) ?>?v=20261007h">
</head>

<body>

<?php if (count($documentsForPrint) > 1): ?>

<?php $printDocPages = array_chunk($documentsForPrint, 3); ?>

<?php foreach ($printDocPages as $pageIndex => $pageDocuments): ?>

<div class="slip-page" <?= $pageIndex === array_key_last($printDocPages) ? '' : 'style="page-break-after: always;"' ?>>

    <?php foreach ($pageDocuments as $printDocument): ?>

        <?php
        $printDocumentId = (int) ($printDocument['document_id'] ?? 0);
        $printSlipOverrides = tracking_slip_get_overrides($printDocumentId);

        $printTrackingNo = trim((string) ($printSlipOverrides['tracking_no'] ?? $printDocument['tracking_no'] ?? ''));
        if ($printTrackingNo === '') {
            $printTrackingNo = 'CLO-' . date('Y') . '-' . str_pad((string) ((int) ($printDocument['document_id'] ?? 0)), 6, '0', STR_PAD_LEFT);
        }

        $printFrom = trim((string) ($printSlipOverrides['from'] ?? $printDocument['origin_department'] ?? '')) !== '' ? (string) ($printSlipOverrides['from'] ?? $printDocument['origin_department']) : 'City Legal Office';
        $printReferenceNo = (string) ($printDocument['external_reference_no'] ?? '');
        if ($printReferenceNo === '') {
            $printReferenceNo = (string) ($printSlipOverrides['ref_no'] ?? '');
        }
        $printRemarks = trim((string) ($printSlipOverrides['remarks'] ?? $printDocument['description'] ?? '')) !== '' ? (string) ($printSlipOverrides['remarks'] ?? $printDocument['description']) : 'No remarks provided.';

        $printDateTimeReceived = tracking_slip_format_datetime((string) ($printDocument['initial_received_at'] ?? ''));
        $printDateReceivedValue = (string) ($printSlipOverrides['date_received'] ?? ($printDocument['assignee_received_at'] ?? ''));
        $printDateReceived = tracking_slip_format_datetime($printDateReceivedValue);

        $printAssignedName = trim((string) ($printSlipOverrides['assigned_to'] ?? (($printDocument['assignee_first_name'] ?? '') . ' ' . ($printDocument['assignee_last_name'] ?? ''))));
        if ($printAssignedName === '') {
            $printAssignedName = trim((string) ($printDocument['assignee_username'] ?? ''));
        }
        if ($printAssignedName === '') {
            $printAssignedName = 'Unassigned';
        }

        $printAssignedStaff = tracking_slip_assigned_staff(
            $cloPersonnel,
            (int) ($printDocument['current_assignee_user_id'] ?? 0),
            $printAssignedName
        );
        $printPriorityLevel = strtoupper(trim((string) ($printDocument['priority_level'] ?? 'MEDIUM')));
        $printTypeName = strtolower(trim((string) ($printDocument['type_name'] ?? '')));
        $printStatusName = strtolower(trim((string) ($printDocument['status_name'] ?? '')));

        $printActionDesired = [
            'appropriate_action' => !empty($printSlipOverrides['action_appropriate_action']),
            'review' => !empty($printSlipOverrides['action_review']),
            'comment_reply' => !empty($printSlipOverrides['action_comment_reply']),
            'reference' => !empty($printSlipOverrides['action_reference']),
            'countersign' => !empty($printSlipOverrides['action_countersign']),
            'others' => !empty($printSlipOverrides['action_others']),
        ];

        $printTasks = [
            'draft' => !empty($printSlipOverrides['task_draft']),
            'discuss' => !empty($printSlipOverrides['task_discuss']),
            'attend' => !empty($printSlipOverrides['task_attend']),
            'facilitate' => !empty($printSlipOverrides['task_facilitate']),
            'review' => !empty($printSlipOverrides['task_review']),
            'file' => !empty($printSlipOverrides['task_file']),
            'release' => !empty($printSlipOverrides['task_release']),
            'note' => !empty($printSlipOverrides['task_note']),
            'investigation' => !empty($printSlipOverrides['task_investigation']),
            'disseminate' => !empty($printSlipOverrides['task_disseminate']),
            'others' => !empty($printSlipOverrides['task_others']),
            'endorse' => !empty($printSlipOverrides['task_endorse']),
        ];

        $printOutputs = [
            'letter' => !empty($printSlipOverrides['output_letter']),
            'memo' => !empty($printSlipOverrides['output_memo']),
            'inspection' => !empty($printSlipOverrides['output_inspection']),
            'meeting' => !empty($printSlipOverrides['output_meeting']),
            'contract' => !empty($printSlipOverrides['output_contract']),
            'investigation' => !empty($printSlipOverrides['output_investigation']),
            'affidavit' => !empty($printSlipOverrides['output_affidavit']),
            'others' => !empty($printSlipOverrides['output_others']),
        ];

        $printDateAssigned = tracking_slip_format_datetime(isset($printDocument['date_assigned']) ? (string) $printDocument['date_assigned'] : null);
        ?>

        <div class="slip-sheet">

            <div class="form-container">

                <div class="header">
                    <div class="province">Province of Davao del Norte</div>
                    <div class="city">CITY OF PANABO</div>
                    <div class="title">CITY LEGAL OFFICE OUTPUT TRACKING SLIP</div>
                </div>

                <table class="field-table">
                    <tr>
                        <td>
                            <span class="field-label">Tracking No.:</span>
                            <span class="line"><span class="value"><?= e($printTrackingNo) ?></span></span>
                        </td>
                        <td>
                            <span class="field-label">Ref. No.:</span>
                            <span class="line"><span class="value"><?= e($printReferenceNo ?: '') ?></span></span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="field-label">From:</span>
                            <span class="line"><span class="value"><?= e($printFrom) ?></span></span>
                        </td>
                        <td>
                            <span class="field-label">Date/Time Received:</span>
                            <span class="line"><span class="value"><?= e($printDateTimeReceived) ?></span></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span class="field-label">Remarks:</span>
                            <span class="line"><?= e($printRemarks) ?></span>
                        </td>
                    </tr>
                </table>

                <div class="section-title">ACTION DESIRED:</div>
                <table class="checkbox-table">
                    <tr>
                        <td width="50%"><?= checked(!empty($printActionDesired['appropriate_action'])) ?> for appropriate action</td>
                        <td width="50%"><?= checked(!empty($printActionDesired['review'])) ?> for review</td>
                    </tr>
                    <tr>
                        <td><?= checked(!empty($printActionDesired['comment_reply'])) ?> for comment/reply</td>
                        <td><?= checked(!empty($printActionDesired['reference'])) ?> Reference</td>
                    </tr>
                    <tr>
                        <td><?= checked(!empty($printActionDesired['countersign'])) ?> for countersign/signature</td>
                        <td><?= checked(!empty($printActionDesired['others'])) ?> others __________________</td>
                    </tr>
                </table>

                <div class="divider"></div>

                <div class="section-divider"></div>
                <div class="section-title">ASSIGNED TO:</div>
                <table class="checkbox-table assigned-staff-table">
                    <?php
                    $printStaffChunks = array_chunk($printAssignedStaff, 5);
                    foreach ($printStaffChunks as $staffRow):
                    ?>
                    <tr>
                        <?php foreach ($staffRow as $staff): ?>
                        <td width="20%"><?= checked(!empty($staff['selected'])) ?> <?= e($staff['name'] ?? '') ?></td>
                        <?php endforeach; ?>
                        <?php $remaining = 5 - count($staffRow); for ($i = 0; $i < $remaining; $i++): ?>
                        <td width="20%"></td>
                        <?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                </table>

                <div class="section-divider"></div>
                <div class="section-title">TASK/S:</div>
                <table class="checkbox-table">
                    <tr>
                        <td><?= checked(!empty($printTasks['draft'])) ?> Draft</td>
                        <td><?= checked(!empty($printTasks['discuss'])) ?> See me/let's discuss</td>
                        <td><?= checked(!empty($printTasks['attend'])) ?> Attend</td>
                        <td><?= checked(!empty($printTasks['facilitate'])) ?> Pls. Facilitate</td>
                    </tr>
                    <tr>
                        <td><?= checked(!empty($printTasks['review'])) ?> Review</td>
                        <td><?= checked(!empty($printTasks['file'])) ?> for file/reference</td>
                        <td><?= checked(!empty($printTasks['release'])) ?> for release</td>
                        <td><?= checked(!empty($printTasks['note'])) ?> Pls. Take Note</td>
                    </tr>
                    <tr>
                        <td><?= checked(!empty($printTasks['investigation'])) ?> For Investigation</td>
                        <td><?= checked(!empty($printTasks['disseminate'])) ?> pls. disseminate</td>
                        <td colspan="2"><?= checked(!empty($printTasks['others'])) ?> Others __________________</td>
                    </tr>
                    <tr>
                        <td colspan="4"><?= checked(!empty($printTasks['endorse'])) ?> Endorse to SP/CMO</td>
                    </tr>
                </table>

                <div class="divider"></div>

                <div class="section-divider"></div>
                <div class="section-title">OUTPUT/S:</div>
                <table class="checkbox-table assigned-staff-table">
                    <tr>
                        <td width="33%"><?= checked(!empty($printOutputs['letter'])) ?> Letter</td>
                        <td width="33%"><?= checked(!empty($printOutputs['memo'])) ?> Memo/E.O</td>
                        <td width="34%"><?= checked(!empty($printOutputs['inspection'])) ?> Inspection Report</td>
                    </tr>
                    <tr>
                        <td><?= checked(!empty($printOutputs['meeting'])) ?> Meeting</td>
                        <td><?= checked(!empty($printOutputs['contract'])) ?> MOA/ Contract/ Agreement</td>
                        <td><?= checked(!empty($printOutputs['investigation'])) ?> Investigation Report</td>
                    </tr>
                    <tr>
                        <td><?= checked(!empty($printOutputs['affidavit'])) ?> Affidavit</td>
                        <td colspan="2"><?= checked(!empty($printOutputs['others'])) ?> Others __________________</td>
                    </tr>
                </table>

                <div class="section-divider"></div>
                <table class="field-table" style="margin-top:5px;">
                    <tr>
                    <td>
                        <span class="field-label">Date Assigned: _______________________________</span>
                        <span class="line"><?= e($dateAssigned ?? '') ?></span>
                    </td>
                    <td>
                        <span class="field-label">Date Received: _______________________________</span>
                        <span class="line"><?= e($dateReceived ?: '') ?></span>
                    </td>
                </tr>
                </table>

                <table class="slip-table">
                    <thead>
                        <tr><th colspan="3">EVALUATION</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="eval-label">1<sup>st</sup> DRAFT</td>
                            <td class="eval-date">Date Submitted: <br><br>Date Returned: <br></td>
                            <td class="eval-action"><b>CGDH's ACTION</b><br><br><br></td>
                        </tr>
                        <tr class="remarks-row"><td colspan="3"><b>Remarks/ Instructions:</b></td></tr>
                        <tr>
                            <td class="eval-label">2<sup>nd</sup> DRAFT</td>
                            <td class="eval-date">Date Submitted: <br><br>Date Returned: <br></td>
                            <td class="eval-action"><b>CGDH's ACTION</b><br><br><br></td>
                        </tr>
                        <tr class="remarks-row"><td colspan="3"><b>Remarks/ Instructions:</b></td></tr>
                        <tr>
                            <td class="eval-label">3<sup>rd</sup> DRAFT</td>
                            <td class="eval-date">Date Submitted: <br><br>Date Returned: <br></td>
                            <td class="eval-action"><b>CGDH's ACTION</b><br><br><br></td>
                        </tr>
                        <tr class="remarks-row"><td colspan="3"><b>Remarks/ Instructions:</b></td></tr>
                    </tbody>
                </table>

                <table class="slip-table rating-table">
                    <thead>
                        <tr>
                            <th width="25%">FINAL ASSESSMENT</th>
                            <th width="25%">SUPERVISOR</th>
                            <th width="25%">CGDH</th>
                            <th width="25%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Quantity</td><td></td><td></td><td></td></tr>
                        <tr><td>Effectiveness</td><td></td><td></td><td></td></tr>
                        <tr><td>Timeliness</td><td></td><td></td><td>Signature of Rater:<br><br><br></td></tr>
                        <tr>
                            <td>Final Rating</td>
                            <td></td>
                            <td></td>
                            <td>Date Rated:<br><br></td>
                        </tr>
                    </tbody>
                </table>

            </div>

        </div>

    <?php endforeach; ?>

</div>

<?php endforeach; ?>

<?php else: ?>

<div class="slip-page">

    <div class="slip-sheet">

        <div class="form-container">

            <div class="header">
                <div class="province">Province of Davao del Norte</div>
                <div class="city">CITY OF PANABO</div>
                <div class="title">CITY LEGAL OFFICE OUTPUT TRACKING SLIP</div>
            </div>

            <table class="field-table">
                <tr>
                    <td>
                        <span class="field-label">Tracking No.:</span>
                        <span class="line"><span class="value"><?= e($trackingNo) ?></span></span>
                    </td>
                    <td>
                        <span class="field-label">Ref. No.:</span>
                        <span class="line"><span class="value"><?= e($referenceNo ?: '') ?></span></span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="field-label">From:</span>
                        <span class="line"><span class="value"><?= e($from) ?></span></span>
                    </td>
                    <td>
                        <span class="field-label">Date/Time Received:</span>
                        <span class="line"><span class="value"><?= e($dateTimeReceived) ?></span></span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span class="field-label">Remarks:</span>
                        <span class="line"><?= e($remarks) ?></span>
                    </td>
                </tr>
            </table>

            <div class="section-title bord">ACTION DESIRED:</div>
            <table class="checkbox-table">
                <tr>
                    <td width="50%"><?= checked(!empty($actionDesired['appropriate_action'])) ?> for appropriate action</td>
                    <td width="50%"><?= checked(!empty($actionDesired['review'])) ?> for review</td>
                </tr>
                <tr>
                    <td><?= checked(!empty($actionDesired['comment_reply'])) ?> for comment/reply</td>
                    <td><?= checked(!empty($actionDesired['reference'])) ?> Reference</td>
                </tr>
                <tr>
                    <td><?= checked(!empty($actionDesired['countersign'])) ?> for countersign/signature</td>
                    <td><?= checked(!empty($actionDesired['others'])) ?> others __________________</td>
                </tr>
            </table>

            <div class="divider"></div>

            <div class="section-divider"></div>
            <div class="section-title">ASSIGNED TO:</div>
            <table class="checkbox-table">
                <?php
                $staffChunks = array_chunk($assignedStaff, 5);
                foreach ($staffChunks as $staffRow):
                ?>
                <tr>
                    <?php foreach ($staffRow as $staff): ?>
                    <td width="20%"><?= checked(!empty($staff['selected'])) ?> <?= e($staff['name'] ?? '') ?></td>
                    <?php endforeach; ?>
                    <?php $remaining = 5 - count($staffRow); for ($i = 0; $i < $remaining; $i++): ?>
                    <td width="20%"></td>
                    <?php endfor; ?>
                </tr>
                <?php endforeach; ?>
            </table>

            <div class="section-divider"></div>
            <div class="section-title">TASK/S:</div>
            <table class="checkbox-table">
                <tr>
                    <td><?= checked(!empty($tasks['draft'])) ?> Draft</td>
                    <td><?= checked(!empty($tasks['discuss'])) ?> See me/let's discuss</td>
                    <td><?= checked(!empty($tasks['attend'])) ?> Attend</td>
                    <td><?= checked(!empty($tasks['facilitate'])) ?> Pls. Facilitate</td>
                </tr>
                <tr>
                    <td><?= checked(!empty($tasks['review'])) ?> Review</td>
                    <td><?= checked(!empty($tasks['file'])) ?> for file/reference</td>
                    <td><?= checked(!empty($tasks['release'])) ?> for release</td>
                    <td><?= checked(!empty($tasks['note'])) ?> Pls. Take Note</td>
                </tr>
                <tr>
                    <td><?= checked(!empty($tasks['investigation'])) ?> For Investigation</td>
                    <td><?= checked(!empty($tasks['disseminate'])) ?> pls. disseminate</td>
                    <td colspan="2"><?= checked(!empty($tasks['others'])) ?> Others __________________</td>
                </tr>
                <tr>
                    <td colspan="4"><?= checked(!empty($tasks['endorse'])) ?> Endorse to SP/CMO</td>
                </tr>
            </table>

            <div class="divider"></div>

            <div class="section-divider"></div>
            <div class="section-title">OUTPUT/S:</div>
            <table class="checkbox-table">
                <tr>
                    <td width="33%"><?= checked(!empty($outputs['letter'])) ?> Letter</td>
                    <td width="33%"><?= checked(!empty($outputs['memo'])) ?> Memo/E.O</td>
                    <td width="34%"><?= checked(!empty($outputs['inspection'])) ?> Inspection Report</td>
                </tr>
                <tr>
                    <td><?= checked(!empty($outputs['meeting'])) ?> Meeting</td>
                    <td><?= checked(!empty($outputs['contract'])) ?> MOA/ Contract/ Agreement</td>
                    <td><?= checked(!empty($outputs['investigation'])) ?> Investigation Report</td>
                </tr>
                <tr>
                    <td><?= checked(!empty($outputs['affidavit'])) ?> Affidavit</td>
                    <td colspan="2"><?= checked(!empty($outputs['others'])) ?> Others __________________</td>
                </tr>
            </table>

            <div class="section-divider"></div>
            <table class="field-table" style="margin-top:5px;">
                <tr>
                    <td>
                        <span class="field-label">Date Assigned: _______________________________</span>
                        <span class="line"><?= e($dateAssigned ?? '') ?></span>
                    </td>
                    <td>
                        <span class="field-label">Date Received: _______________________________</span>
                        <span class="line"><?= e($dateReceived ?: '') ?></span>
                    </td>
                </tr>
            </table>

            <table class="slip-table">
                <thead>
                    <tr><th colspan="3">EVALUATION</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="eval-label">1<sup>st</sup> DRAFT</td>
                        <td class="eval-date">Date Submitted: <br><br>Date Returned: <br></td>
                        <td class="eval-action"><b>CGDH's ACTION</b><br><br><br></td>
                    </tr>
                    <tr class="remarks-row"><td colspan="3"><b>Remarks/ Instructions:</b></td></tr>
                    <tr>
                        <td class="eval-label">2<sup>nd</sup> DRAFT</td>
                        <td class="eval-date">Date Submitted: <br><br>Date Returned: <br></td>
                        <td class="eval-action"><b>CGDH's ACTION</b><br><br><br></td>
                    </tr>
                    <tr class="remarks-row"><td colspan="3"><b>Remarks/ Instructions:</b></td></tr>
                    <tr>
                        <td class="eval-label">3<sup>rd</sup> DRAFT</td>
                        <td class="eval-date">Date Submitted: <br><br>Date Returned: <br></td>
                        <td class="eval-action"><b>CGDH's ACTION</b><br><br><br></td>
                    </tr>
                    <tr class="remarks-row"><td colspan="3"><b>Remarks/ Instructions:</b></td></tr>
                </tbody>
            </table>

            <table class="slip-table rating-table">
                <thead>
                    <tr>
                        <th width="25%">FINAL ASSESSMENT</th>
                        <th width="25%">SUPERVISOR</th>
                        <th width="25%">CGDH</th>
                        <th width="25%"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Quantity</td><td></td><td></td><td></td></tr>
                    <tr><td>Effectiveness</td><td></td><td></td><td></td></tr>
                    <tr><td>Timeliness</td><td></td><td></td><td>Signature of Rater:<br><br><br></td></tr>
                    <tr>
                        <td>Final Rating</td>
                        <td></td>
                        <td></td>
                        <td>Date Rated:<br><br><br></td>
                    </tr>
                   
                </tbody>
            </table>

        </div>

    </div>

</div>

<?php endif; ?>

<?php if ($autoPrint): ?>
<script
    src="<?= e(app_url('assets/js/features/tracking/tracking-slip-autoprint.js')) ?>?v=20261007d"
    data-print-token="<?= e((string) ($_GET['print_token'] ?? '')) ?>"
    data-print-complete-endpoint="<?= e(app_url('tracking_slip_print_complete.php')) ?>"
    data-document-ids="<?= e(implode(',', array_map(
        static fn (array $printDocument): string => (string) ($printDocument['document_id'] ?? 0),
        $documentsForPrint
    ))) ?>"
></script>
<?php endif; ?>

</body>
</html>
