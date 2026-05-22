<script>
function renderDurationTrends(data) {
    var ctx = document.getElementById('chart-duration-trends').getContext('2d');
    if (window._chartDurationTrends) { window._chartDurationTrends.destroy(); }

    var labels = data.seasons.map(function(s) { return s.label; });
    var longPct = data.seasons.map(function(s) {
        var t = s.totals.flights;
        return t > 0 ? Math.round(s.totals.long / t * 100) : 0;
    });
    var midPct = data.seasons.map(function(s) {
        var t = s.totals.flights;
        return t > 0 ? Math.round(s.totals.mid / t * 100) : 0;
    });
    var shortPct = data.seasons.map(function(s) {
        var t = s.totals.flights;
        return t > 0 ? Math.round(s.totals.short / t * 100) : 0;
    });

    window._chartDurationTrends = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: '> 1h', data: longPct, backgroundColor: '#1565c0', stack: 'dur' },
                { label: '30m-1h', data: midPct, backgroundColor: '#00838f', stack: 'dur' },
                { label: '<= 30m', data: shortPct, backgroundColor: '#e0e0e0', stack: 'dur' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: true },
                tooltip: {
                    callbacks: {
                        label: function(item) {
                            return item.dataset.label + ': ' + item.raw + '%';
                        }
                    }
                }
            },
            scales: {
                y: { stacked: true, beginAtZero: true, max: 100, ticks: { callback: function(v) { return v + '%'; } } }
            }
        }
    });
}
</script>
