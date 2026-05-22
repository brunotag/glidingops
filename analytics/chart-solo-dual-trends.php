<script>
var _soloDualData = null;
var _soloDualMonth = -1;

function renderSoloDualTrends(data) {
    _soloDualData = data;
    _buildSoloDualChart();
}

function _buildSoloDualChart() {
    var data = _soloDualData;
    if (!data) return;
    var monthPos = _soloDualMonth;

    var ctx = document.getElementById('chart-solo-dual-trends').getContext('2d');
    if (window._chartSoloDualTrends) { window._chartSoloDualTrends.destroy(); }

    var labels = data.seasons.map(function(s) { return s.label; });
    var soloPct, dualPct;

    if (monthPos < 0) {
        soloPct = data.seasons.map(function(s) {
            var t = s.totals.flights;
            return t > 0 ? Math.round(s.totals.solo / t * 100) : 0;
        });
        dualPct = data.seasons.map(function(s) {
            var t = s.totals.flights;
            return t > 0 ? Math.round(s.totals.dual / t * 100) : 0;
        });
    } else {
        soloPct = data.seasons.map(function(s) {
            var m = s.monthly[monthPos];
            var t = m ? m.total : 0;
            return t > 0 ? Math.round(m.solo / t * 100) : 0;
        });
        dualPct = data.seasons.map(function(s) {
            var m = s.monthly[monthPos];
            var t = m ? m.total : 0;
            return t > 0 ? Math.round(m.dual_flights / t * 100) : 0;
        });
    }

    window._chartSoloDualTrends = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'Solo %', data: soloPct, backgroundColor: '#063552' },
                { label: 'Dual %', data: dualPct, backgroundColor: '#f26120' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: true }
            },
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { callback: function(v) { return v + '%'; } } }
            }
        }
    });
}

function onSoloDualMonthChange(selectEl) {
    _soloDualMonth = parseInt(selectEl.value, 10);
    _buildSoloDualChart();
}
</script>
