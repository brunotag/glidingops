<script>
function renderMonthlyFlights(data) {
    var ctx = document.getElementById('chart-monthly').getContext('2d');
    if (window._chartMonthly) { window._chartMonthly.destroy(); }

    var mainArr = buildSeasonArray(data.main.monthly);
    var compArr = data.compare ? buildSeasonArray(data.compare.monthly) : null;

    var mainData = mainArr.map(function(r) { return r ? r.total : 0; });
    var compData = compArr ? compArr.map(function(r) { return r ? r.total : 0; }) : [];

    var datasets = [
        { label: data.main.label, data: mainData, backgroundColor: '#063552', borderRadius: 4 }
    ];
    if (compArr) {
        datasets.push({ label: data.compare.label, data: compData, backgroundColor: '#f26120', borderRadius: 4 });
    }

    window._chartMonthly = new Chart(ctx, {
        type: 'bar',
        data: { labels: seasonLabels, datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: !!compArr } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
    hideHiddenLegend(window._chartMonthly);
    setupSeasonToggles(window._chartMonthly, 'toggles-monthly', data.main.label, data.compare ? data.compare.label : null, 1);
}
</script>
