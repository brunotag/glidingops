<script>
function renderAvgDuration(data) {
    var ctx = document.getElementById('chart-duration').getContext('2d');
    if (window._chartDuration) { window._chartDuration.destroy(); }

    var labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var mainData = [];
    var compData = [];

    for (var i = 1; i <= 12; i++) {
        var m = data.main.monthly.find(function(r) { return parseInt(r.yearmonth.slice(-2), 10) === i; });
        mainData.push(m ? m.avg_duration : 0);
        if (data.compare) {
            var c = data.compare.monthly.find(function(r) { return parseInt(r.yearmonth.slice(-2), 10) === i; });
            compData.push(c ? c.avg_duration : 0);
        }
    }

    var datasets = [
        { label: String(data.main.year), data: mainData, borderColor: '#063552', backgroundColor: 'rgba(6,53,82,0.1)', fill: true, tension: 0.3 }
    ];
    if (data.compare) {
        datasets.push({ label: String(data.compare.year), data: compData, borderColor: '#f26120', backgroundColor: 'rgba(242,97,32,0.1)', fill: true, tension: 0.3 });
    }

    window._chartDuration = new Chart(ctx, {
        type: 'line',
        data: { labels: labels, datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: !!data.compare } },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}
</script>
