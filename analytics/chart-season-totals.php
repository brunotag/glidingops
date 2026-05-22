<script>
var _seasonTotalsMode = 'all';

var deltaPlugin = {
    id: 'deltaLabels',
    afterDatasetsDraw: function(chart) {
        var ctx = chart.ctx;
        var data = chart.data;
        var datasets = data.datasets;
        var maxIdx = data.labels.length - 1;

        // Find the last non-hidden dataset (top of stacked bar)
        var topMeta = null;
        for (var d = datasets.length - 1; d >= 0; d--) {
            if (!chart.getDatasetMeta(d).hidden) {
                topMeta = chart.getDatasetMeta(d);
                break;
            }
        }
        if (!topMeta) return;

        // Compute total per bar (sum of visible datasets)
        var barTotals = [];
        for (var i = 0; i <= maxIdx; i++) {
            var sum = 0;
            for (var d = 0; d < datasets.length; d++) {
                if (chart.getDatasetMeta(d).hidden) continue;
                sum += datasets[d].data[i] || 0;
            }
            barTotals.push(sum);
        }

        ctx.save();
        ctx.font = '11px Arial';
        ctx.textAlign = 'center';

        for (var i = 1; i <= maxIdx; i++) {
            var bar = topMeta.data[i];
            if (!bar || !bar.x || !bar.y) continue;
            var x = bar.x;
            var y = bar.y;

            var delta = barTotals[i] - barTotals[i - 1];
            var text = (delta >= 0 ? '+' : '') + delta;
            ctx.fillStyle = delta >= 0 ? '#2e7d32' : '#c62828';
            ctx.fillText(text, x, y - 8);
        }

        ctx.restore();
    }
};

function renderSeasonTotals(data) {
    _seasonTotalsData = data;
    _buildSeasonTotals();
}

function _buildSeasonTotals() {
    var data = _seasonTotalsData;
    if (!data) return;

    var ctx = document.getElementById('chart-season-totals').getContext('2d');
    if (window._chartSeasonTotals) { window._chartSeasonTotals.destroy(); }

    var labels = data.seasons.map(function(s) { return s.label; });
    var solo = data.seasons.map(function(s) { return s.totals.solo; });
    var dual = data.seasons.map(function(s) { return s.totals.dual; });

    var datasets = [];
    if (_seasonTotalsMode === 'solo') {
        datasets.push({ label: 'Solo', data: solo, backgroundColor: '#063552' });
    } else if (_seasonTotalsMode === 'dual') {
        datasets.push({ label: 'Dual', data: dual, backgroundColor: '#f26120' });
    } else {
        datasets.push({ label: 'Solo', data: solo, backgroundColor: '#063552', stack: 'total' });
        datasets.push({ label: 'Dual', data: dual, backgroundColor: '#f26120', stack: 'total' });
    }

    window._chartSeasonTotals = new Chart(ctx, {
        type: 'bar',
        data: { labels: labels, datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: true },
                tooltip: {
                    callbacks: {
                        footer: function(items) {
                            var i = items[0].dataIndex;
                            var s = data.seasons[i];
                            return 'Total: ' + s.totals.flights;
                        }
                    }
                }
            },
            scales: {
                y: { stacked: _seasonTotalsMode === 'all', beginAtZero: true, ticks: { stepSize: 1 } }
            }
        },
        plugins: [deltaPlugin]
    });
}

function onSeasonTotalsModeChange(selectEl) {
    _seasonTotalsMode = selectEl.value;
    _buildSeasonTotals();
}
</script>
