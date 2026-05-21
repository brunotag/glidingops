<script>
function renderTopAircraft(data) {
    var ctx = document.getElementById('chart-aircraft').getContext('2d');
    if (window._chartAircraft) { window._chartAircraft.destroy(); }

    var acMain = data.main.top_aircraft || [];
    var acComp = data.compare ? (data.compare.top_aircraft || []) : [];

    if (!data.compare) {
        var labels = acMain.map(function(r) { return r.glider; }).reverse();
        var vals = acMain.map(function(r) { return r.flights; }).reverse();

        window._chartAircraft = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{ data: vals, backgroundColor: '#063552', borderRadius: 4 }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    } else {
        var allRegos = {};
        acMain.concat(acComp).forEach(function(r) { allRegos[r.glider] = true; });
        var regos = Object.keys(allRegos).sort();
        var mainVals = regos.map(function(r) { var f = acMain.find(function(a) { return a.glider === r; }); return f ? f.flights : 0; });
        var compVals = regos.map(function(r) { var f = acComp.find(function(a) { return a.glider === r; }); return f ? f.flights : 0; });

        window._chartAircraft = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: regos,
                datasets: [
                    { label: String(data.main.year), data: mainVals, backgroundColor: '#063552' },
                    { label: String(data.compare.year), data: compVals, backgroundColor: '#f26120' }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }
}
</script>
