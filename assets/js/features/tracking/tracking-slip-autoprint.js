(function () {
    const script = document.currentScript;
    const printToken = script ? script.dataset.printToken || '' : '';
    window.addEventListener('load', function () {
        if (printToken !== '' && window.parent !== window) {
            window.addEventListener('afterprint', function () {
                window.parent.postMessage(
                    {
                        type: 'tracking-slip-print-finished',
                        token: printToken
                    },
                    window.location.origin
                );
            }, { once: true });
        }

        window.print();
    });
})();
