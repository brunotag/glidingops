<script>
function renderSoloDual(data) {
    var ctx = document.getElementById('chart-solo-dual').getContext('2d');
    if (window._chartSoloDual) { window._chartSoloDual.destroy(); }

    var labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var soloMain = [], dualMain = [];
    var soloComp = [], dualComp = [];

    for (var i = 1; i <= 12; i++) {
        var m = data.main.monthly.find(function(r) { return parseInt(r.yearmonth.slice(-2), 10) === i; });
        soloMain.push(m ? m.solo : 0);
        dualMain.push(m ? m.dual_flights : 0);
        if (data.compare) {
            var c = data.compare.monthly.find(function(r) { return parseInt(r.yearmonth.slice(-2), 10) === i; });
            soloComp.push(c ? c.solo : 0);
            dualComp.push(c ? c.dual_flights : 0);
        }
    }

    var datasets = [];
    if (data.compare) {
        datasets.push({ label: 'Solo ' + data.main.year, data: soloMain, backgroundColor: '#063552' });
        datasets.push({ label: 'Dual ' + data.main.year, data: dualMain, backgroundColor: '#4a90d9' });
        datasets.push({ label: 'Solo ' + data.compare.year, data: soloComp, backgroundColor: '#f26120' });
        datasets.push({ label: 'Dual ' + data.compare.year, data: dualComp, backgroundColor: '#f5a623' });
    } else {
        datasets.push({ label: 'Solo', data: soloMain, backgroundColor: '#063552' });
        datasets.push({ label: 'Dual', data: dualMain, backgroundColor: '#4a90d9' });
    }

    window._chartSoloDual = new Chart(ctx, {
        type: 'bar',
        data: { labels: labels, datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                x: { stacked: true },
                y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
}
</script>
