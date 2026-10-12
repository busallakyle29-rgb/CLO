/* =========================================================
   CLO-PANABO DASHBOARD
   assets/js/features/dashboard/dashboard.js
   ========================================================= */

(function () {
    'use strict';

    /* =========================================================
       DOM READY
    ========================================================= */

    document.addEventListener('DOMContentLoaded', function () {

        initializeDashboardCharts();

        initializeRecentDocumentState();

    });

    /* =========================================================
       READ DASHBOARD DATA
    ========================================================= */

    function getDashboardData() {

        const dataElement =
            document.getElementById('dashboardData');

        if (!dataElement) {
            return null;
        }

        const rawData =
            dataElement.getAttribute('data-chart-data');

        if (!rawData) {
            return null;
        }

        try {

            return JSON.parse(rawData);

        } catch (error) {

            console.error(
                'CLO Dashboard: Unable to parse dashboard data.',
                error
            );

            return null;
        }
    }

    /* =========================================================
       INITIALIZE CHARTS
    ========================================================= */

    function initializeDashboardCharts() {

        if (typeof Chart === 'undefined') {

            console.warn(
                'CLO Dashboard: Chart.js is not available.'
            );

            return;
        }

        const dashboardData =
            getDashboardData();

        if (!dashboardData) {

            console.warn(
                'CLO Dashboard: No chart data found.'
            );

            return;
        }

        configureChartDefaults();

        initializeVolumeTrendChart(
            dashboardData.trend || {}
        );

        initializeTypeDistributionChart(
            dashboardData.types || {}
        );

        initializeWeeklyActivityChart(
            dashboardData.weekly || {}
        );

    }

    /* =========================================================
       CHART DEFAULTS
    ========================================================= */

    function configureChartDefaults() {

        Chart.defaults.font.family =
            'Segoe UI, Arial, sans-serif';

        Chart.defaults.font.size = 12;

        Chart.defaults.color =
            '#68778e';

        Chart.defaults.plugins.tooltip.enabled = true;

    }

    /* =========================================================
       VOLUME TREND
    ========================================================= */

    function initializeVolumeTrendChart(data) {

        const canvas =
            document.getElementById(
                'volumeTrendChart'
            );

        if (!canvas) {
            return;
        }

        const labels =
            Array.isArray(data.labels)
                ? data.labels
                : [];

        const received =
            Array.isArray(data.received)
                ? data.received
                : [];

        const resolved =
            Array.isArray(data.resolved)
                ? data.resolved
                : [];

        new Chart(
            canvas,
            {
                type: 'line',

                data: {

                    labels: labels,

                    datasets: [

                        {
                            label: 'Received',

                            data: received,

                            borderColor: '#2f6ec8',

                            backgroundColor:
                                'rgba(47, 110, 200, 0.14)',

                            fill: true,

                            tension: 0.35,

                            pointRadius: 3,

                            pointHoverRadius: 5,

                            borderWidth: 2
                        },

                        {
                            label: 'Resolved',

                            data: resolved,

                            borderColor: '#67aef5',

                            backgroundColor:
                                'rgba(103, 174, 245, 0.07)',

                            fill: true,

                            tension: 0.35,

                            pointRadius: 3,

                            pointHoverRadius: 5,

                            borderWidth: 2
                        }

                    ]

                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    interaction: {
                        mode: 'index',
                        intersect: false
                    },

                    plugins: {

                        legend: {
                            position: 'top',
                            align: 'end'
                        },

                        tooltip: {

                            callbacks: {

                                label: function (context) {

                                    return (
                                        context.dataset.label
                                        + ': '
                                        + context.parsed.y
                                    );

                                }

                            }

                        }

                    },

                    scales: {

                        y: {

                            beginAtZero: true,

                            ticks: {
                                precision: 0
                            },

                            grid: {
                                color: '#edf1f6'
                            }

                        },

                        x: {

                            grid: {
                                display: false
                            }

                        }

                    }

                }

            }
        );

    }

    /* =========================================================
       DOCUMENT TYPE DISTRIBUTION
    ========================================================= */

    function initializeTypeDistributionChart(data) {

        const canvas =
            document.getElementById(
                'typeDistributionChart'
            );

        if (!canvas) {
            return;
        }

        const labels =
            Array.isArray(data.labels)
                ? data.labels
                : [];

        const totals =
            Array.isArray(data.totals)
                ? data.totals
                : [];

        if (labels.length === 0) {

            return;
        }

        new Chart(
            canvas,
            {
                type: 'doughnut',

                data: {

                    labels: labels,

                    datasets: [

                        {
                            data: totals,

                            backgroundColor: [
                                '#204f9c',
                                '#2f6ec8',
                                '#4a90e2',
                                '#7ab4f4',
                                '#b9d8fb',
                                '#d9e9fb',
                                '#8bbcf0',
                                '#5d99d7',
                                '#376eae',
                                '#244f83'
                            ],

                            borderColor: '#ffffff',

                            borderWidth: 2,

                            hoverOffset: 5
                        }

                    ]

                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    cutout: '64%',

                    plugins: {

                        legend: {
                            display: false
                        },

                        tooltip: {

                            callbacks: {

                                label: function (context) {

                                    const value =
                                        context.parsed || 0;

                                    return (
                                        context.label
                                        + ': '
                                        + value
                                    );

                                }

                            }

                        }

                    }

                }

            }
        );

    }

    /* =========================================================
       WEEKLY ACTIVITY
    ========================================================= */

    function initializeWeeklyActivityChart(data) {

        const canvas =
            document.getElementById(
                'weeklyActivityChart'
            );

        if (!canvas) {
            return;
        }

        const labels =
            Array.isArray(data.labels)
                ? data.labels
                : [];

        const totals =
            Array.isArray(data.totals)
                ? data.totals
                : [];

        new Chart(
            canvas,
            {
                type: 'bar',

                data: {

                    labels: labels,

                    datasets: [

                        {
                            label: 'Documents',

                            data: totals,

                            backgroundColor: '#3f6fb5',

                            borderRadius: 5,

                            maxBarThickness: 34
                        }

                    ]

                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    plugins: {

                        legend: {
                            display: false
                        },

                        tooltip: {

                            callbacks: {

                                label: function (context) {

                                    return (
                                        'Documents: '
                                        + context.parsed.y
                                    );

                                }

                            }

                        }

                    },

                    scales: {

                        y: {

                            beginAtZero: true,

                            ticks: {
                                precision: 0
                            },

                            grid: {
                                color: '#edf1f6'
                            }

                        },

                        x: {

                            grid: {
                                display: false
                            }

                        }

                    }

                }

            }
        );

    }

    /* =========================================================
       RECENT DOCUMENT TABLE
    ========================================================= */

    function initializeRecentDocumentState() {

        const table =
            document.getElementById(
                'recentDocumentsTable'
            );

        if (!table) {
            return;
        }

        const body =
            document.getElementById(
                'recentDocumentsBody'
            );

        if (!body) {
            return;
        }

        const rows =
            Array.from(
                body.querySelectorAll(
                    'tr[data-type][data-status]'
                )
            );

        const noResults =
            document.getElementById(
                'noDocumentResults'
            );

        const filterInfo =
            document.getElementById(
                'documentFilterInfo'
            );

        if (rows.length === 0) {

            if (filterInfo) {

                filterInfo.textContent =
                    'No recent document records';
            }

            return;
        }

        let visibleCount = 0;

        rows.forEach(function (row) {

            if (
                row.classList.contains('d-none')
            ) {

                return;
            }

            visibleCount++;

        });

        if (filterInfo) {

            filterInfo.textContent =
                visibleCount
                + (
                    visibleCount === 1
                        ? ' document'
                        : ' documents'
                );

        }

        if (
            noResults
            && visibleCount > 0
        ) {

            noResults.classList.add(
                'd-none'
            );
        }

    }

})();
