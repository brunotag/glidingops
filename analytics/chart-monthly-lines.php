<script>
function renderMonthlyLines(data) {
    var ctx = document.getElementById('chart-monthly-lines').getContext('2d');
    if (window._chartMonthlyLines) { window._chartMonthlyLines.destroy(); }

    var labels = data.seasons.map(function(s) { return s.label; });
    var monthNames = ['Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar','Apr','May'];
    var monthColors = ['#1f77b4','#ff7f0e','#2ca02c','#d62728','#9467bd','#8c564b','#e377c2','#7f7f7f','#bcbd22','#17becf','#aec7e8','#ffbb78'];

    var datasets = [];
    monthNames.forEach(function(mname, mi) {
        var values = data.seasons.map(function(s) {
            var m = s.monthly[mi];
            return m ? m.total : 0;
        });
        datasets.push({
            label: mname,
            data: values,
            borderColor: monthColors[mi],
            backgroundColor: monthColors[mi],
            fill: false,
            tension: 0.2,
            pointRadius: 3,
            pointHoverRadius: 5
        });
    });

    window._chartMonthlyLines = new Chart(ctx, {
        type: 'line',
        data: { labels: labels, datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: true, position: 'bottom' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    setupMonthToggles(window._chartMonthlyLines, 'toggles-monthly-lines', monthNames, monthColors);
}

function setupMonthToggles(chart, containerId, monthNames, monthColors) {
    var container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = '';
    monthNames.forEach(function(mname, mi) {
        var label = document.createElement('label');
        label.style.cssText = 'display:inline-block;margin-right:12px;font-size:12px;cursor:pointer;font-weight:normal;';
        var cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.checked = true;
        var colorSwatch = document.createElement('span');
        colorSwatch.style.cssText = 'display:inline-block;width:10px;height:10px;border-radius:2px;background:' + monthColors[mi] + ';margin-right:3px;vertical-align:middle;';
        cb.addEventListener('change', function() {
            var meta = chart.getDatasetMeta(mi);
            meta.hidden = !cb.checked;
            chart.update();
        });
        label.appendChild(cb);
        label.appendChild(colorSwatch);
        label.appendChild(document.createTextNode(mname));
        container.appendChild(label);
    });
}
</script>
