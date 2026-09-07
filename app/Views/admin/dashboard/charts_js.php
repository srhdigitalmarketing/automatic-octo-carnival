<script>
    (function () {
        var statistics = <?= json_encode($visitorStats, JSON_NUMERIC_CHECK) ?>;
        var visitorsTarget = document.querySelector('#visitor_statistics_chart');
        var platformTarget = document.querySelector('#visitor_platform_chart');

        if (visitorsTarget) {
            new ApexCharts(visitorsTarget, {
                chart: { type: 'area', height: 276, toolbar: { show: false }, fontFamily: 'Inter, Segoe UI, Arial, sans-serif' },
                series: [{ name: 'Visitors', data: statistics.daily }],
                colors: ['#3569ef'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                fill: { type: 'gradient', gradient: { opacityFrom: .34, opacityTo: .03, stops: [0, 92, 100] } },
                grid: { borderColor: '#edf1f7', strokeDashArray: 4, padding: { left: 4, right: 8 } },
                xaxis: { categories: statistics.labels, tickAmount: 6, labels: { style: { colors: '#7a8699', fontSize: '11px' } }, axisBorder: { color: '#edf1f7' }, axisTicks: { color: '#edf1f7' } },
                yaxis: { min: 0, labels: { style: { colors: '#7a8699', fontSize: '11px' }, formatter: function (value) { return Math.round(value).toLocaleString('id-ID'); } } },
                tooltip: { y: { formatter: function (value) { return value.toLocaleString('id-ID') + ' visitors'; } } }
            }).render();
        }

        if (platformTarget) {
            var platformTotal = statistics.platforms.desktop + statistics.platforms.mobile;
            new ApexCharts(platformTarget, {
                chart: { type: 'donut', height: 248, fontFamily: 'Inter, Segoe UI, Arial, sans-serif' },
                series: platformTotal ? [statistics.platforms.desktop, statistics.platforms.mobile] : [1, 0],
                labels: ['Desktop', 'Mobile'],
                colors: ['#3569ef', '#8b7cf6'],
                stroke: { colors: ['#fff'], width: 6 },
                dataLabels: { enabled: false },
                legend: { show: false },
                plotOptions: { pie: { donut: { size: '68%', labels: { show: true, total: { show: true, label: platformTotal ? 'Visitors' : 'Belum ada data', formatter: function () { return platformTotal.toLocaleString('id-ID'); } } } } } },
                tooltip: { y: { formatter: function (value) { return platformTotal ? value.toLocaleString('id-ID') + ' visitors' : 'Belum ada data'; } } }
            }).render();
        }
    })();
</script>
