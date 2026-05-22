<script>
function renderGrowth(data) {
    var ctx = document.getElementById('chart-growth').getContext('2d');
    if (window._chartGrowth) { window._chartGrowth.destroy(); }

    var labels = [];
    var growthPct = [];
    var growthAbs = [];
    var prev = null;
    data.seasons.forEach(function(s) {
        if (prev !== null) {
            labels.push(s.label);
            var delta = s.totals.flights - prev;
            growthAbs.push(delta);
            growthPct.push(prev > 0 ? Math.round(delta / prev * 100) : 0);
        }
        prev = s.totals.flights;
    });

    window._chartGrowth = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Change in flights',
                    data: growthAbs,
                    backgroundColor: growthAbs.map(function(v) { return v >= 0 ? '#2e7d32' : '#c62828'; }),
                    yAxisID: 'y'
                },
                {
                    label: '% Change',
                    data: growthPct,
                    type: 'line',
                    borderColor: '#063552',
                    backgroundColor: '#063552',
                    fill: false,
                    tension: 0.2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: true, position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(item) {
                            if (item.dataset.yAxisID === 'y1') return item.raw + '%';
                            var sign = item.raw >= 0 ? '+' : '';
                            return sign + item.raw + ' flights';
                        }
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, position: 'left', ticks: { stepSize: 1 } },
                y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { callback: function(v) { return v + '%'; } } }
            }
        }
    });
}
</script>
