<?php session_start();
require_once __DIR__ . '/helpers/logging.php';
logMsg("START");

$org = isset($_SESSION['org']) ? $_SESSION['org'] : 0;
require_once __DIR__ . '/helpers/permissions.php'; require_perm('analytics.pic-p2');
if (!isset($_SESSION['memberid'])) {
    logMsg("AUTH FAIL - no memberid");
    header('Location: /Login.php');
    die("Please logon");
}
logMsg("AUTH OK - memberid=" . $_SESSION['memberid']);

$currentYear = intval(date('Y'));
$currentMonth = intval(date('m'));
$defaultSeason = ($currentMonth >= 6) ? $currentYear : $currentYear - 1;
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PIC with P2 Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; }
        .container-fluid { max-width: 1400px; margin: 0 auto; }
        .filter-bar { background: #fff; padding: 12px 16px; border-radius: 6px; margin: 12px 0; box-shadow: 0 1px 3px rgba(0,0,0,.1); display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .filter-bar label { font-size: 13px; font-weight: 600; margin: 0; }
        .filter-bar select { width: auto; min-width: 100px; padding: 4px 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 13px; }
        .btn-outline { padding: 4px 12px; border: 1px solid #063552; background: #fff; color: #063552; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .btn-outline:hover { background: #063552; color: #fff; }
        .summary-pills { display: flex; flex-wrap: wrap; gap: 8px; margin: 8px 0; }
        .summary-pill { background: #063552; color: #f26120; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .spinner { text-align: center; padding: 40px; font-size: 16px; color: #888; }
        .error-msg { background: #f2dede; color: #a94442; padding: 12px; border-radius: 4px; margin: 12px 0; display: none; }
        .chart-card { background: #fff; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,.1); padding: 16px; margin: 16px 0; }
        .chart-card h3 { margin: 0 0 8px 0; font-size: 15px; color: #063552; }
        .chart-wrap { position: relative; width: 100%; }
        .chart-card canvas { max-width: 100%; }
        .section-card { background: #fff; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,.1); padding: 16px; margin: 16px 0; overflow-x: auto; }
        .section-card h3 { margin: 0 0 12px 0; font-size: 15px; color: #063552; }
        .section-card table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .section-card th, .section-card td { padding: 6px 10px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        .section-card th { background: #063552; color: #fff; font-weight: 600; cursor: pointer; white-space: nowrap; }
        .section-card th:hover { background: #0a4a6e; }
        .section-card th .sort-arrow { margin-left: 4px; }
        .section-card tr:hover { background: #f0f7fc; }
        .section-card .rank { color: #888; font-weight: 600; text-align: center; width: 40px; }
        .section-card .num { text-align: right; font-variant-numeric: tabular-nums; }
        .section-card a { color: #063552; text-decoration: none; font-weight: 600; }
        .section-card a:hover { text-decoration: underline; }
        @media print { .filter-bar, .btn-outline { display: none; } .chart-card { break-inside: avoid; } }
    </style>
</head>
<body>
    <?php $inc = "./orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
    <?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>

    <div class="container-fluid">
        <div class="filter-bar">
            <label for="mode">Mode:</label>
            <select id="mode">
                <option value="season">Full Season</option>
                <option value="ytd" selected>YTD</option>
            </select>
            <label for="year">Season:</label>
            <select id="year">
                <?php for ($y = 2016; $y <= $defaultSeason; $y++): ?>
                <option value="<?php echo $y; ?>"<?php echo $y === $defaultSeason ? ' selected' : ''; ?>><?php echo $y; ?>/<?php echo $y + 1; ?></option>
                <?php endfor; ?>
            </select>
            <button id="load-btn" class="btn-outline">Load</button>
            <span style="flex:1;"></span>
            <button class="btn-outline" onclick="window.print()">Print</button>
        </div>

        <div class="summary-pills" id="summary-pills"></div>
        <div class="error-msg" id="error-msg"></div>
        <div class="spinner" id="spinner">Loading data...</div>

        <div id="content-area" style="display:none;">
            <div class="section-card">
                <h3>All Pilots</h3>
                <table>
                    <thead>
                        <tr>
                            <th data-sort="rank" style="width:40px;">#</th>
                            <th data-sort="name">Pilot</th>
                            <th data-sort="flights" class="num sort-asc">Flights <span class="sort-arrow">&#9660;</span></th>
                            <th data-sort="hours" class="num">Time</th>
                            <th data-sort="p2s" class="num">Unique P2s</th>
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
            <div class="chart-card"><h3>Total Dual Flights per PIC</h3><div class="chart-wrap" id="chart-flights-wrap"><canvas id="chart-flights"></canvas></div></div>
            <div class="chart-card"><h3>Total Dual Hours per PIC</h3><div class="chart-wrap" id="chart-hours-wrap"><canvas id="chart-hours"></canvas></div></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
    var currentData = null;
    var currentSortField = 'flights';
    var currentSortDir = 'desc';
    var defaultSeason = <?php echo $defaultSeason; ?>;

    function updateModeForYear(selectedYear) {
        var modeSel = document.getElementById('mode');
        var ytdOpt = modeSel.querySelector('option[value="ytd"]');
        if (selectedYear < defaultSeason) {
            ytdOpt.disabled = true;
            if (modeSel.value === 'ytd') modeSel.value = 'season';
        } else {
            ytdOpt.disabled = false;
        }
    }

    function formatHours(minutes) {
        var h = Math.floor(minutes / 60);
        var m = minutes % 60;
        return h + 'h ' + (m < 10 ? '0' : '') + m + 'm';
    }

    function renderCharts(data, sortField, sortDir) {
        var rows = data.rows.slice();

        function getVal(r) {
            switch (sortField) {
                case 'name': return r.displayname;
                case 'flights': return r.flight_count;
                case 'hours': return r.total_minutes;
                case 'p2s': return r.unique_p2s;
                default: return r.flight_count;
            }
        }

        rows.sort(function(a, b) {
            var va = getVal(a), vb = getVal(b);
            if (typeof va === 'string') {
                return sortDir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
            }
            return sortDir === 'asc' ? va - vb : vb - va;
        });

        var names = rows.map(function(r) { return r.displayname; });
        var vals = rows.map(function(r) { return r.flight_count; });

        var h = Math.max(300, rows.length * 35);
        document.getElementById('chart-flights-wrap').style.height = h + 'px';
        document.getElementById('chart-hours-wrap').style.height = h + 'px';

        var ctx1 = document.getElementById('chart-flights').getContext('2d');
        if (window._chartFlights) window._chartFlights.destroy();
        window._chartFlights = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: names,
                datasets: [{ label: 'Flights', data: vals, backgroundColor: '#063552', borderRadius: 4 }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function(ctx) { return ctx.parsed.x + ' flights'; } } }
                },
                scales: {
                    x: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } },
                    y: { ticks: { font: { size: 13 } } }
                }
            }
        });

        var hourVals = rows.map(function(r) { return Math.round(r.total_minutes / 60 * 10) / 10; });
        var namesHours = rows.map(function(r) { return r.displayname; });

        var ctx2 = document.getElementById('chart-hours').getContext('2d');
        if (window._chartHours) window._chartHours.destroy();
        window._chartHours = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: namesHours,
                datasets: [{ label: 'Hours', data: hourVals, backgroundColor: '#f26120', borderRadius: 4 }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function(ctx) { return ctx.parsed.x + ' hours'; } } }
                },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 1 } },
                    y: { ticks: { font: { size: 11 } } }
                }
            }
        });
    }

    function renderTable(data) {
        var tbody = document.getElementById('table-body');
        var rows = data.rows.slice();

        function getVal(r) {
            switch (currentSortField) {
                case 'name': return r.displayname;
                case 'flights': return r.flight_count;
                case 'hours': return r.total_minutes;
                case 'p2s': return r.unique_p2s;
                default: return r.flight_count;
            }
        }

        rows.sort(function(a, b) {
            var va = getVal(a), vb = getVal(b);
            if (typeof va === 'string') {
                return currentSortDir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
            }
            return currentSortDir === 'asc' ? va - vb : vb - va;
        });

        var html = '';
        rows.forEach(function(r, i) {
            html += '<tr>' +
                '<td class="rank">' + (i + 1) + '</td>' +
                '<td><a href="/MemberNew?id=' + r.pic + '">' + r.displayname.replace(/</g, '&lt;') + '</a></td>' +
                '<td class="num">' + r.flight_count + '</td>' +
                '<td class="num">' + formatHours(r.total_minutes) + '</td>' +
                '<td class="num">' + r.unique_p2s + '</td>' +
                '</tr>';
        });
        tbody.innerHTML = html;

        var ths = document.querySelectorAll('.section-card th[data-sort]');
        ths.forEach(function(th) {
            var sort = th.getAttribute('data-sort');
            th.classList.remove('sort-asc', 'sort-desc');
            var arrow = th.querySelector('.sort-arrow');
            if (sort === currentSortField) {
                th.classList.add(currentSortDir === 'asc' ? 'sort-asc' : 'sort-desc');
                if (!arrow) {
                    arrow = document.createElement('span');
                    arrow.className = 'sort-arrow';
                    th.appendChild(arrow);
                }
                arrow.textContent = ' ' + (currentSortDir === 'asc' ? '\u25B2' : '\u25BC');
            } else if (arrow) {
                arrow.remove();
            }
        });
    }

    function renderAll(data) {
        currentData = data;
        renderCharts(data, currentSortField, currentSortDir);
        renderTable(data);

        var t = data.totals;
        var pills = document.getElementById('summary-pills');
        pills.innerHTML = '';
        [t.people + ' pilots', t.flights + ' dual flights', t.total_hours + ' total hours', 'avg ' + t.avg_unique_p2s + ' unique P2s per pilot'].forEach(function(s) {
            var p = document.createElement('span');
            p.className = 'summary-pill';
            p.textContent = s;
            pills.appendChild(p);
        });
    }

    function loadData(mode, year) {
        var url = '/api/pic-p2-analytics?mode=' + mode + '&year=' + year;
        document.getElementById('spinner').style.display = 'block';
        document.getElementById('content-area').style.display = 'none';
        document.getElementById('error-msg').style.display = 'none';

        fetch(url, { credentials: 'same-origin' })
            .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(function(json) {
                document.getElementById('spinner').style.display = 'none';
                document.getElementById('content-area').style.display = 'block';
                if (!json.success) throw new Error(json.error || 'API error');
                if (!json.data.rows || json.data.rows.length === 0) {
                    document.getElementById('error-msg').textContent = 'No data for this period.';
                    document.getElementById('error-msg').style.display = 'block';
                    return;
                }
                document.getElementById('content-area').style.display = 'block';
                renderAll(json.data);
            })
            .catch(function(err) {
                document.getElementById('spinner').style.display = 'none';
                var el = document.getElementById('error-msg');
                el.textContent = 'Error loading data: ' + err.message;
                el.style.display = 'block';
            });
    }

    document.getElementById('load-btn').addEventListener('click', function() {
        var mode = document.getElementById('mode').value;
        var y = parseInt(document.getElementById('year').value, 10) || defaultSeason;
        loadData(mode, y);
    });

    document.getElementById('year').addEventListener('change', function() {
        updateModeForYear(parseInt(this.value, 10));
    });

    document.querySelectorAll('.section-card th[data-sort]').forEach(function(th) {
        th.addEventListener('click', function() {
            var field = th.getAttribute('data-sort');
            if (field === currentSortField) {
                currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortField = field;
                currentSortDir = (field === 'name') ? 'asc' : 'desc';
            }
            if (currentData) { renderCharts(currentData, currentSortField, currentSortDir); renderTable(currentData); }
        });
    });

    updateModeForYear(defaultSeason);
    loadData(document.getElementById('mode').value, defaultSeason);
    </script>

    <style>
        <?php $inc = "./orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
        <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
    </style>
</body>
</html>
