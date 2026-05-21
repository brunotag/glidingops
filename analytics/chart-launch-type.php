<script>
function renderLaunchType(data) {
    var ctx = document.getElementById('chart-launch').getContext('2d');
    if (window._chartLaunch) { window._chartLaunch.destroy(); }

    var names = data.launch_names || { '1': 'Tow', '2': 'Winch', '3': 'Self Launch' };
    var ltMain = data.main.launch_types || [];

    if (!data.compare) {
        var labels = ltMain.map(function(r) { return names[r.launchtype] || 'Type ' + r.launchtype; });
        var counts = ltMain.map(function(r) { return r.count; });
        var colors = ['#063552', '#4a90d9', '#f26120'];

        window._chartLaunch = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{ data: counts, backgroundColor: colors.slice(0, labels.length) }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    } else {
        var ltComp = data.compare.launch_types || [];
        var allTypes = {};
        ltMain.concat(ltComp).forEach(function(r) {
            allTypes[r.launchtype] = names[r.launchtype] || 'Type ' + r.launchtype;
        });
        var typeIds = Object.keys(allTypes).sort();
        var mainVals = typeIds.map(function(id) {
            var f = ltMain.find(function(r) { return r.launchtype === parseInt(id, 10); });
            return f ? f.count : 0;
        });
        var compVals = typeIds.map(function(id) {
            var f = ltComp.find(function(r) { return r.launchtype === parseInt(id, 10); });
            return f ? f.count : 0;
        });

        window._chartLaunch = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: typeIds.map(function(id) { return allTypes[id]; }),
                datasets: [
                    { label: String(data.main.year), data: mainVals, backgroundColor: '#063552' },
                    { label: String(data.compare.year), data: compVals, backgroundColor: '#f26120' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }
}
</script>
