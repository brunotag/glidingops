<script>
function renderTrialFlights(data) {
    var ctx = document.getElementById('chart-trial-flights').getContext('2d');
    if (window._chartTrialFlights) { window._chartTrialFlights.destroy(); }

    var labels = data.seasons.map(function(s) { return s.label; });
    var trialValues = data.seasons.map(function(s) { return s.totals.trial; });

    window._chartTrialFlights = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Trial Flights',
                data: trialValues,
                backgroundColor: '#9467bd',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
}
</script>
