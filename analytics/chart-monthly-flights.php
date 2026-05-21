<script>
function renderMonthlyFlights(data) {
    var ctx = document.getElementById('chart-monthly').getContext('2d');
    if (window._chartMonthly) { window._chartMonthly.destroy(); }

    var labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var mainData = [];
    var compData = [];

    for (var i = 1; i <= 12; i++) {
        var m = data.main.monthly.find(function(r) { return parseInt(r.yearmonth.slice(-2), 10) === i; });
        mainData.push(m ? m.total : 0);
        if (data.compare) {
            var c = data.compare.monthly.find(function(r) { return parseInt(r.yearmonth.slice(-2), 10) === i; });
            compData.push(c ? c.total : 0);
        }
    }

    var datasets = [
        { label: String(data.main.year), data: mainData, backgroundColor: '#063552', borderRadius: 4 }
    ];
    if (data.compare) {
        datasets.push({ label: String(data.compare.year), data: compData, backgroundColor: '#f26120', borderRadius: 4 });
    }

    window._chartMonthly = new Chart(ctx, {
        type: 'bar',
        data: { labels: labels, datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: !!data.compare } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
}
</script>
