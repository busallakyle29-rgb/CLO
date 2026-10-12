                        <?php if ($canEditDocument): ?>
                        <!-- EDIT MODAL -->

                        <div
                            class="modal fade"
                            id="editDocument<?= $documentId ?>"
                            tabindex="-1"
                            aria-hidden="true"
                        >

                            <div class="modal-dialog modal-lg modal-dialog-scrollable">

                                <div class="modal-content">

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($csrfToken) ?>"
                                        >

                                        <div class="modal-header">

                                            <h5 class="modal-title">

                                                <i class="bi bi-pencil text-primary"></i>

                                                Edit Document

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
                                                name="document_id"
                                                value="<?= $documentId ?>"
                                            >

                                            <div class="mb-3">

                                                <label class="form-label">
                                                    Tracking Number
                                                </label>

                                                <input
                                                    type="text"
                                                    class="form-control"
                                                    value="<?= e(
                                                        $document[
                                                            'tracking_no'
                                                        ]
                                                        ?? ''
                                                    ) ?>"
                                                    readonly
                                                >

                                            </div>

                                            <div class="mb-3">

                                                <label class="form-label">

                                                    Document Title

                                                    <span class="text-danger">
                                                        *
                                                    </span>

                                                </label>

                                                <input
                                                    type="text"
                                                    name="title"
                                                    class="form-control"
                                                    maxlength="255"
                                                    required
                                                    value="<?= e(
                                                        $document['title']
                                                        ?? ''
                                                    ) ?>"
                                                >

                                            </div>

                                            <div class="mb-3">

                                                <label class="form-label">
                                                    Description
                                                </label>

                                                <textarea
                                                    name="description"
                                                    class="form-control"
                                                    rows="3"
                                                    maxlength="2000"
                                                ><?= e(
                                                    $document[
                                                        'description'
                                                    ]
                                                    ?? ''
                                                ) ?></textarea>

                                            </div>

                                            <div class="row">

                                                <div class="col-12 col-md-6 mb-3">

                                                    <label class="form-label">
                                                        Document Type
                                                    </label>

                                                    <select
                                                        name="document_type_id"
                                                        class="form-select"
                                                        required
                                                    >

                                                        <option value="">
                                                            Select type
                                                        </option>

                                                        <?php foreach (
                                                            $documentTypes
                                                            as $type
                                                        ): ?>

                                                            <option
                                                                value="<?= (int) $type['document_type_id'] ?>"
                                                                <?= (
                                                                    (int) (
                                                                        $document[
                                                                            'document_type_id'
                                                                        ]
                                                                        ?? 0
                                                                    )
                                                                    ===
                                                                    (int) (
                                                                        $type[
                                                                            'document_type_id'
                                                                        ]
                                                                    )
                                                                )
                                                                    ? 'selected'
                                                                    : '' ?>
                                                            >

                                                                <?= e(
                                                                    $type[
                                                                        'type_name'
                                                                    ]
                                                                ) ?>

                                                            </option>

                                                        <?php endforeach; ?>

                                                    </select>

                                                </div>

                                                <div class="col-12 col-md-6 mb-3">

                                                    <label class="form-label">
                                                        Current Department
                                                    </label>

                                                    <select
                                                        class="form-select"
                                                        disabled
                                                        aria-describedby="currentDepartmentHelp<?= $documentId ?>"
                                                    >

                                                        <option value="">
                                                            Select department
                                                        </option>

                                                        <?php foreach (
                                                            $departments
                                                            as $department
                                                        ): ?>

                                                            <option
                                                                value="<?= (int) $department['department_id'] ?>"
                                                                <?= (
                                                                    (int) (
                                                                        $document[
                                                                            'current_department_id'
                                                                        ]
                                                                        ?? 0
                                                                    )
                                                                    ===
                                                                    (int) (
                                                                        $department[
                                                                            'department_id'
                                                                        ]
                                                                    )
                                                                )
                                                                    ? 'selected'
                                                                    : '' ?>
                                                            >

                                                                <?= e(
                                                                    $department[
                                                                        'department_name'
                                                                    ]
                                                                ) ?>

                                                            </option>

                                                        <?php endforeach; ?>

                                                    </select>

                                                    <div
                                                        class="form-text"
                                                        id="currentDepartmentHelp<?= $documentId ?>"
                                                    >

                                                    </div>

                                                </div>

                                                <div class="col-12 col-md-6 mb-3">

                                                    <label class="form-label">

                                                        Status

                                                        <span class="text-danger">
                                                            *
                                                        </span>

                                                    </label>

                                                    <select
                                                        name="current_status_id"
                                                        class="form-select"
                                                        required
                                                    >

                                                        <option value="">
                                                            Select status
                                                        </option>

                                                        <?php foreach (
                                                            $statuses
                                                            as $status
                                                        ): ?>

                                                            <option
                                                                value="<?= (int) $status['status_id'] ?>"
                                                                <?= (
                                                                    (int) (
                                                                        $document[
                                                                            'current_status_id'
                                                                        ]
                                                                        ?? 0
                                                                    )
                                                                    ===
                                                                    (int) (
                                                                        $status[
                                                                            'status_id'
                                                                        ]
                                                                    )
                                                                )
                                                                    ? 'selected'
                                                                    : '' ?>
                                                            >

                                                                <?= e(
                                                                    $status[
                                                                        'status_name'
                                                                    ]
                                                                ) ?>

                                                            </option>

                                                        <?php endforeach; ?>

                                                    </select>

                                                </div>

                                                <div class="col-12 col-md-6 mb-3">

                                                    <label class="form-label">
                                                        Priority
                                                    </label>

                                                    <select
                                                        name="priority_level"
                                                        class="form-select"
                                                    >

                                                        <?php foreach (
                                                            [
                                                                'Low',
                                                                'Medium',
                                                                'High',
                                                                'Urgent'
                                                            ]
                                                            as $option
                                                        ): ?>

                                                            <option
                                                                value="<?= e($option) ?>"
                                                                <?= (
                                                                    $priorityValue
                                                                    ===
                                                                    $option
                                                                )
                                                                    ? 'selected'
                                                                    : '' ?>
                                                            >

                                                                <?= e(
                                                                    $option
                                                                ) ?>

                                                            </option>

                                                        <?php endforeach; ?>

                                                    </select>

                                                </div>

                                                <div class="col-12 col-md-6 mb-3">

                                                    <label class="form-label">
                                                        Confidentiality
                                                    </label>

                                                    <select
                                                        name="confidentiality_level"
                                                        class="form-select"
                                                    >

                                                        <?php foreach (
                                                            [
                                                                'Normal',
                                                                'Confidential',
                                                                'Highly Confidential'
                                                            ]
                                                            as $level
                                                        ): ?>

                                                            <option
                                                                value="<?= e($level) ?>"
                                                                <?= (
                                                                    $confidentialityValue ===
                                                                    $level
                                                                )
                                                                    ? 'selected'
                                                                    : '' ?>
                                                            >

                                                                <?= e(
                                                                    $level
                                                                ) ?>

                                                            </option>

                                                        <?php endforeach; ?>

                                                    </select>

                                                </div>

                                                <div class="col-12 col-md-6 mb-3">

                                                    <label class="form-label">
                                                        Due Date
                                                    </label>

                                                    <input
                                                        type="date"
                                                        name="date_due"
                                                        class="form-control"
                                                        value="<?= !empty(
                                                            $document['date_due']
                                                        )
                                                            ? e(
                                                                date(
                                                                    'Y-m-d',
                                                                    strtotime(
                                                                        $document[
                                                                            'date_due'
                                                                        ]
                                                                    )
                                                                )
                                                            )
                                                            : '' ?>"
                                                    >

                                                </div>

                                                <div class="col-12 col-md-6 mb-3">

                                                    <label class="form-label">
                                                        External Reference No.
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="external_reference_no"
                                                        class="form-control"
                                                        maxlength="100"
                                                        placeholder="Enter number only (CLO- is automatic)"
                                                        value="<?= e(
                                                            $document[
                                                                'external_reference_no'
                                                            ]
                                                            ?? ''
                                                        ) ?>"
                                                    >

                                                </div>

                                                <div class="col-12 mb-3">

                                                    <label class="form-label">
                                                        Assigned Staff
                                                    </label>

                                                    <select
                                                        name="current_assignee_user_id"
                                                        class="form-select"
                                                        <?= !$canManageDocuments ? 'disabled' : '' ?>
                                                    >

                                                        <option value="">
                                                            Unassigned
                                                        </option>

                                                        <?php foreach (
                                                            $users
                                                            as $staff
                                                        ): ?>

                                                            <?php

                                                            $staffName =
                                                                trim(
                                                                    (
                                                                        $staff[
                                                                            'first_name'
                                                                        ]
                                                                        ?? ''
                                                                    )
                                                                    .
                                                                    ' ' .
                                                                    (
                                                                        $staff[
                                                                            'last_name'
                                                                        ]
                                                                        ?? ''
                                                                    )
                                                                );

                                                            if (
                                                                $staffName === ''
                                                            ) {

                                                                $staffName =
                                                                    $staff[
                                                                        'username'
                                                                    ]
                                                                    ??
                                                                    '';
                                                            }

                                                            ?>

                                                            <option
                                                                value="<?= (int) $staff['user_id'] ?>"
                                                                data-department-id="<?= (int) ($staff['department_id'] ?? 0) ?>"
                                                                <?= (
                                                                    (int) (
                                                                        $document[
                                                                            'current_assignee_user_id'
                                                                        ]
                                                                        ?? 0
                                                                    )
                                                                    ===
                                                                    (int) (
                                                                        $staff[
                                                                            'user_id'
                                                                        ]
                                                                    )
                                                                )
                                                                    ? 'selected'
                                                                    : '' ?>
                                                            >

                                                                <?= e(
                                                                    $staffName
                                                                ) ?>

                                                            </option>

                                                        <?php endforeach; ?>

                                                    </select>

                                                </div>

                                            </div>

                                            <?php
                                            $slipOverrides = document_tracking_slip_get_overrides((int) $documentId);
                                            $slipTrackingNo = trim((string) ($slipOverrides['tracking_no'] ?? ($document['tracking_no'] ?? '')));
                                            $slipRefNo = trim((string) ($document['external_reference_no'] ?? ''));
                                            if ($slipRefNo === '') {
                                                $slipRefNo = trim((string) ($slipOverrides['ref_no'] ?? ''));
                                            }
                                            $slipFrom = trim((string) ($slipOverrides['from'] ?? ($document['origin_department_name'] ?? ($document['origin_department'] ?? ''))));
                                            $slipDateReceived = trim((string) ($slipOverrides['date_received'] ?? ($document['date_received'] ?? '')));
                                            $slipRemarks = trim((string) ($slipOverrides['remarks'] ?? ($document['description'] ?? '')));
                                            $slipAssignedTo = trim((string) ($slipOverrides['assigned_to'] ?? (($document['assigned_staff_name'] ?? '') ?: (($document['assignee_first_name'] ?? '') . ' ' . ($document['assignee_last_name'] ?? '')))));
                                            $slipActionAppropriate = !empty($slipOverrides['action_appropriate_action']);
                                            $slipActionReview = !empty($slipOverrides['action_review']);
                                            $slipActionCommentReply = !empty($slipOverrides['action_comment_reply']);
                                            $slipActionReference = !empty($slipOverrides['action_reference']);
                                            $slipActionCountersign = !empty($slipOverrides['action_countersign']);
                                            $slipActionOthers = !empty($slipOverrides['action_others']);
                                            $slipTaskDraft = !empty($slipOverrides['task_draft']);
                                            $slipTaskDiscuss = !empty($slipOverrides['task_discuss']);
                                            $slipTaskAttend = !empty($slipOverrides['task_attend']);
                                            $slipTaskFacilitate = !empty($slipOverrides['task_facilitate']);
                                            $slipTaskReview = !empty($slipOverrides['task_review']);
                                            $slipTaskFile = !empty($slipOverrides['task_file']);
                                            $slipTaskRelease = !empty($slipOverrides['task_release']);
                                            $slipTaskNote = !empty($slipOverrides['task_note']);
                                            $slipTaskInvestigation = !empty($slipOverrides['task_investigation']);
                                            $slipTaskDisseminate = !empty($slipOverrides['task_disseminate']);
                                            $slipTaskOthers = !empty($slipOverrides['task_others']);
                                            $slipTaskEndorse = !empty($slipOverrides['task_endorse']);
                                            $slipOutputLetter = !empty($slipOverrides['output_letter']);
                                            $slipOutputMemo = !empty($slipOverrides['output_memo']);
                                            $slipOutputInspection = !empty($slipOverrides['output_inspection']);
                                            $slipOutputMeeting = !empty($slipOverrides['output_meeting']);
                                            $slipOutputContract = !empty($slipOverrides['output_contract']);
                                            $slipOutputInvestigation = !empty($slipOverrides['output_investigation']);
                                            $slipOutputAffidavit = !empty($slipOverrides['output_affidavit']);
                                            $slipOutputOthers = !empty($slipOverrides['output_others']);
                                            ?>

                                            <?php if ($canManageDocuments): ?>
                                            <div class="border rounded p-3 bg-light mb-3 mt-2">
                                                <h6 class="fw-bold mb-3">Edit Tracking Slip</h6>

                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Ref. No.</label>
                                                        <input type="text" name="ref_no" class="form-control" placeholder="Enter number only (CLO- is automatic)" value="<?= e($slipRefNo) ?>">
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">From</label>
                                                        <input type="text" name="from" class="form-control" value="<?= e($slipFrom ?: 'City Legal Office') ?>">
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Date/Time Received</label>
                                                        <input type="datetime-local" name="date_received" class="form-control" value="<?= e($slipDateReceived !== '' ? date('Y-m-d\TH:i', strtotime($slipDateReceived)) : '') ?>">
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Assigned To</label>
                                                        <input type="text" name="assigned_to" class="form-control" value="<?= e($slipAssignedTo ?: 'Unassigned') ?>">
                                                    </div>

                                                    <div class="col-12">
                                                        <label class="form-label">Remarks</label>
                                                        <textarea name="remarks" class="form-control" rows="3"><?= e($slipRemarks ?: 'No remarks provided.') ?></textarea>
                                                    </div>

                                                    <div class="col-12">
                                                        <div class="border rounded p-2">
                                                            <div class="fw-bold mb-2">ACTION DESIRED:</div>
                                                            <div class="row g-2">
                                                                <div class="col-md-6">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="action_appropriate_action" value="1" <?= $slipActionAppropriate ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">for appropriate action</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="action_review" value="1" <?= $slipActionReview ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">for review</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="action_comment_reply" value="1" <?= $slipActionCommentReply ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">for comment/reply</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="action_reference" value="1" <?= $slipActionReference ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Reference</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="action_countersign" value="1" <?= $slipActionCountersign ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">for countersign/signature</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="action_others" value="1" <?= $slipActionOthers ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">others</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-12">
                                                        <div class="border rounded p-2">
                                                            <div class="fw-bold mb-2">TASK/S:</div>
                                                            <div class="row g-2">
                                                                <div class="col-md-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="task_draft" value="1" <?= $slipTaskDraft ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Draft</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="task_discuss" value="1" <?= $slipTaskDiscuss ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">See me/let's discuss</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="task_attend" value="1" <?= $slipTaskAttend ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Attend</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="task_facilitate" value="1" <?= $slipTaskFacilitate ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Pls. Facilitate</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="task_review" value="1" <?= $slipTaskReview ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Review</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="task_file" value="1" <?= $slipTaskFile ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">for file/reference</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="task_release" value="1" <?= $slipTaskRelease ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">for release</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="task_note" value="1" <?= $slipTaskNote ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Pls. Take Note</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="task_investigation" value="1" <?= $slipTaskInvestigation ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">For Investigation</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="task_disseminate" value="1" <?= $slipTaskDisseminate ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">pls. disseminate</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="task_others" value="1" <?= $slipTaskOthers ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Others</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="task_endorse" value="1" <?= $slipTaskEndorse ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Endorse to SP/CMO</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-12">
                                                        <div class="border rounded p-2">
                                                            <div class="fw-bold mb-2">OUTPUT/S:</div>
                                                            <div class="row g-2">
                                                                <div class="col-md-4">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="output_letter" value="1" <?= $slipOutputLetter ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Letter</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="output_memo" value="1" <?= $slipOutputMemo ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Memo/E.O</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="output_inspection" value="1" <?= $slipOutputInspection ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Inspection Report</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="output_meeting" value="1" <?= $slipOutputMeeting ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Meeting</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="output_contract" value="1" <?= $slipOutputContract ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">MOA/ Contract/ Agreement</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="output_investigation" value="1" <?= $slipOutputInvestigation ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Investigation Report</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="output_affidavit" value="1" <?= $slipOutputAffidavit ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Affidavit</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-8">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="output_others" value="1" <?= $slipOutputOthers ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Others</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>

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
                                                name="update_document"
                                                class="btn btn-primary"
                                            >

                                                <i class="bi bi-save"></i>

                                                Save Changes

                                            </button>

                                        </div>

                                    </form>

                                </div>

                            </div>

                        </div>
                        <?php endif; ?>
