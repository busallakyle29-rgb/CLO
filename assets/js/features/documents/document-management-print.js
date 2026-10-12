(function () {
    let trackingSlipPrintSequence = 0;

    function printTrackingSlip(url) {
        trackingSlipPrintSequence += 1;
        const printToken = `${Date.now()}-${trackingSlipPrintSequence}`;
        const printUrl = new URL(url, window.location.href);
        printUrl.searchParams.set('print_token', printToken);

        const documentIds = [];
        printUrl.searchParams.getAll('document_id')
            .concat(
                (printUrl.searchParams.get('document_ids') || '').split(',')
            )
            .forEach(function (value) {
                const documentId = Number.parseInt(value, 10);
                if (Number.isInteger(documentId) && documentId > 0 && !documentIds.includes(documentId)) {
                    documentIds.push(documentId);
                }
            });

        fetch(
            new URL('tracking_slip_print_complete.php', window.location.href),
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token: printToken,
                    document_ids: documentIds
                }),
                keepalive: true
            }
        ).catch(function () {});

        const printFrame = document.createElement('iframe');
        printFrame.title = 'Tracking slip print document';
        printFrame.dataset.printToken = printToken;
        printFrame.style.position = 'fixed';
        printFrame.style.left = '-10000px';
        printFrame.style.top = '0';
        printFrame.style.width = '1200px';
        printFrame.style.height = '980px';
        printFrame.style.border = '0';
        document.body.appendChild(printFrame);
        printFrame.src = printUrl.toString();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('click', function (event) {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const printButton = target.closest('[data-tracking-slip-print-url]');
            if (printButton instanceof HTMLButtonElement) {
                printTrackingSlip(
                    printButton.dataset.trackingSlipPrintUrl || ''
                );
            }
        });

        window.addEventListener('message', function (event) {
            if (
                event.origin !== window.location.origin
                || event.data?.type !== 'tracking-slip-print-finished'
                || typeof event.data.token !== 'string'
            ) {
                return;
            }

            const completedFrame = Array.from(
                document.querySelectorAll('iframe[data-print-token]')
            ).find(function (frame) {
                return frame.dataset.printToken === event.data.token;
            });
            completedFrame?.remove();
        });

        const selectAllHeader = document.getElementById(
            'select-all-tracking-slip-header'
        );
        const selectAllButton = document.getElementById(
            'select-all-tracking-slips'
        );
        const allCheckboxes = document.querySelectorAll(
            '.tracking-slip-select'
        );
        const printSelectedButton = document.getElementById(
            'print-selected-tracking-slips'
        );

        const syncSelectAllState = function () {
            const checkedBoxes = document.querySelectorAll(
                '.tracking-slip-select:checked'
            ).length;
            const totalBoxes = allCheckboxes.length;

            if (selectAllHeader) {
                selectAllHeader.checked =
                    totalBoxes > 0 && checkedBoxes === totalBoxes;
                selectAllHeader.indeterminate =
                    checkedBoxes > 0 && checkedBoxes < totalBoxes;
            }

            if (selectAllButton) {
                selectAllButton.checked =
                    totalBoxes > 0 && checkedBoxes === totalBoxes;
                selectAllButton.indeterminate =
                    checkedBoxes > 0 && checkedBoxes < totalBoxes;
            }
        };

        const handleSelectAll = function (checked) {
            allCheckboxes.forEach(function (checkbox) {
                checkbox.checked = checked;
            });
            syncSelectAllState();
        };

        if (selectAllHeader) {
            selectAllHeader.addEventListener('change', function () {
                handleSelectAll(this.checked);
            });
        }

        if (selectAllButton) {
            selectAllButton.addEventListener('change', function () {
                handleSelectAll(this.checked);
            });
        }

        allCheckboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', syncSelectAllState);
        });

        if (printSelectedButton) {
            printSelectedButton.addEventListener('click', function () {
                const selectedIds = Array.from(allCheckboxes)
                    .filter(function (checkbox) {
                        return checkbox.checked;
                    })
                    .map(function (checkbox) {
                        return checkbox.value;
                    });

                if (selectedIds.length === 0) {
                    alert('Please select at least one document to print.');
                    return;
                }

                const printUrl =
                    printSelectedButton.dataset.printUrl
                    + '?document_ids='
                    + encodeURIComponent(selectedIds.join(','))
                    + '&autoprint=1';
                printTrackingSlip(printUrl);
            });
        }

        const fileHistoryDocumentId = new URLSearchParams(
            window.location.search
        ).get('file_history');
        if (fileHistoryDocumentId && /^\d+$/.test(fileHistoryDocumentId)) {
            const fileHistoryModal = document.getElementById(
                'documentFiles' + fileHistoryDocumentId
            );
            if (
                fileHistoryModal
                && window.bootstrap
                && window.bootstrap.Modal
            ) {
                window.bootstrap.Modal.getOrCreateInstance(
                    fileHistoryModal
                ).show();
            }
        }
    });
})();
