<script>
function renderFlightDuration(data) {
    var ctx = document.getElementById('chart-duration').getContext('2d');
    if (window._chartDuration) { window._chartDuration.destroy(); }

    var mainArr = buildSeasonArray(data.main.monthly);
    var compArr = data.compare ? buildSeasonArray(data.compare.monthly) : null;

    function extract(arr, field) { return arr.map(function(r) { return r ? r[field] : 0; }); }

    var longMain = extract(mainArr, 'long_flights');
    var midMain = extract(mainArr, 'mid_flights');
    var shortMain = extract(mainArr, 'short_flights');

    var coldColors = ['#1565c0', '#00838f', '#4527a0'];
    var warmColors = ['#e65100', '#c62828', '#f9a825'];

    var datasets = [
        { label: '> 1h ' + data.main.label, data: longMain, backgroundColor: coldColors[0], stack: 'main' },
        { label: '30m-1h ' + data.main.label, data: midMain, backgroundColor: coldColors[1], stack: 'main' },
        { label: '<= 30m ' + data.main.label, data: shortMain, backgroundColor: coldColors[2], stack: 'main' }
    ];

    if (compArr) {
        var longComp = extract(compArr, 'long_flights');
        var midComp = extract(compArr, 'mid_flights');
        var shortComp = extract(compArr, 'short_flights');
        datasets.push({ label: '> 1h ' + data.compare.label, data: longComp, backgroundColor: warmColors[0], stack: 'compare' });
        datasets.push({ label: '30m-1h ' + data.compare.label, data: midComp, backgroundColor: warmColors[1], stack: 'compare' });
        datasets.push({ label: '<= 30m ' + data.compare.label, data: shortComp, backgroundColor: warmColors[2], stack: 'compare' });
    }

    window._chartDuration = new Chart(ctx, {
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
    hideHiddenLegend(window._chartDuration);
    setupSeasonToggles(window._chartDuration, 'toggles-duration', data.main.label, data.compare ? data.compare.label : null, 3);
    setupCategoryToggles(window._chartDuration, 'cat-toggles-duration', ['> 1h', '30m-1h', '<= 30m']);
}
</script>
