<script>
function renderSoloDual(data) {
    var ctx = document.getElementById('chart-solo-dual').getContext('2d');
    if (window._chartSoloDual) { window._chartSoloDual.destroy(); }

    var mainArr = buildSeasonArray(data.main.monthly);
    var compArr = data.compare ? buildSeasonArray(data.compare.monthly) : null;

    function extract(arr, field) { return arr.map(function(r) { return r ? r[field] : 0; }); }

    var soloMain = extract(mainArr, 'solo');
    var dualMain = extract(mainArr, 'dual_flights');

    var datasets = [];
    if (compArr) {
        var soloComp = extract(compArr, 'solo');
        var dualComp = extract(compArr, 'dual_flights');
        datasets.push({ label: 'Solo ' + data.main.label, data: soloMain, backgroundColor: '#063552', stack: 'main' });
        datasets.push({ label: 'Dual ' + data.main.label, data: dualMain, backgroundColor: '#4a90d9', stack: 'main' });
        datasets.push({ label: 'Solo ' + data.compare.label, data: soloComp, backgroundColor: '#f26120', stack: 'compare' });
        datasets.push({ label: 'Dual ' + data.compare.label, data: dualComp, backgroundColor: '#f5a623', stack: 'compare' });
    } else {
        datasets.push({ label: 'Solo', data: soloMain, backgroundColor: '#063552' });
        datasets.push({ label: 'Dual', data: dualMain, backgroundColor: '#4a90d9' });
    }

    window._chartSoloDual = new Chart(ctx, {
        type: 'bar',
        data: { labels: seasonLabels, datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
    hideHiddenLegend(window._chartSoloDual);
    setupSeasonToggles(window._chartSoloDual, 'toggles-solo-dual', data.main.label, data.compare ? data.compare.label : null, 2);
    setupCategoryToggles(window._chartSoloDual, 'cat-toggles-solo-dual', ['Solo', 'Dual']);
}
</script>
