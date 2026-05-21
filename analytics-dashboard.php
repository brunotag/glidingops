<?php session_start();
require_once __DIR__ . '/helpers/logging.php';
logMsg("START");

$org = isset($_SESSION['org']) ? $_SESSION['org'] : 0;
if (!isset($_SESSION['security']) || $_SESSION['security'] < 1) {
    logMsg("AUTH FAIL");
    die("Security level too low for this page");
}
if (!isset($_SESSION['memberid'])) {
    logMsg("AUTH FAIL - no memberid");
    header('Location: /Login.php');
    die("Please logon");
}
logMsg("AUTH OK - memberid=" . $_SESSION['memberid']);

$currentYear = intval(date('Y'));
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; }
        .container-fluid { max-width: 1400px; margin: 0 auto; }
        .filter-bar { background: #fff; padding: 12px 16px; border-radius: 6px; margin: 12px 0; box-shadow: 0 1px 3px rgba(0,0,0,.1); display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .filter-bar label { font-size: 13px; font-weight: 600; margin: 0; }
        .filter-bar input[type="number"] { width: 80px; padding: 4px 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 13px; }
        .btn-outline { padding: 4px 12px; border: 1px solid #063552; background: #fff; color: #063552; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .btn-outline:hover { background: #063552; color: #fff; }
        .chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 16px 0; }
        .chart-card { background: #fff; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,.1); padding: 16px; }
        .chart-card.full { grid-column: 1 / -1; }
        .chart-card h3 { margin: 0 0 8px 0; font-size: 15px; color: #063552; }
        .chart-card canvas { max-height: 300px; max-width: 100%; }
        #summary-pills { display: flex; flex-wrap: wrap; gap: 8px; margin: 8px 0; }
        .summary-pill { background: #063552; color: #f26120; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .spinner { text-align: center; padding: 40px; font-size: 16px; color: #888; }
        .error-msg { background: #f2dede; color: #a94442; padding: 12px; border-radius: 4px; margin: 12px 0; display: none; }
        @media (max-width: 900px) { .chart-grid { grid-template-columns: 1fr; } }
        @media print { .filter-bar, .btn-outline { display: none; } .chart-card { break-inside: avoid; } }
    </style>
</head>
<body>
    <?php $inc = "./orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
    <?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>

    <div class="container-fluid">
        <div class="filter-bar">
            <label for="year">Year:</label>
            <input type="number" id="year" min="2000" max="2099" value="<?php echo $currentYear; ?>">
            <label for="compare">Compare:</label>
            <input type="number" id="compare" min="0" max="2099" value="0" placeholder="0">
            <span style="font-size:11px;color:#888;">(0 = no comparison)</span>
            <button id="load-btn" class="btn-outline">Load</button>
            <span style="flex:1;"></span>
            <button class="btn-outline" onclick="window.print()">Print</button>
        </div>

        <div id="summary-pills"></div>
        <div class="error-msg" id="error-msg"></div>
        <div class="spinner" id="spinner">Loading analytics...</div>

        <div class="chart-grid" id="chart-grid" style="display:none;">
            <div class="chart-card"><h3>Monthly Flight Count</h3><canvas id="chart-monthly"></canvas></div>
            <div class="chart-card"><h3>Solo vs Dual Pilot</h3><canvas id="chart-solo-dual"></canvas></div>
            <div class="chart-card"><h3>Long Flights (> 1 hour)</h3><canvas id="chart-long"></canvas></div>
            <div class="chart-card"><h3>Launch Type Mix</h3><canvas id="chart-launch"></canvas></div>
            <div class="chart-card"><h3>Average Duration (minutes)</h3><canvas id="chart-duration"></canvas></div>
            <div class="chart-card"><h3>Top Aircraft</h3><canvas id="chart-aircraft"></canvas></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <?php include __DIR__ . '/analytics/chart-monthly-flights.php'; ?>
    <?php include __DIR__ . '/analytics/chart-solo-dual.php'; ?>
    <?php include __DIR__ . '/analytics/chart-long-flights.php'; ?>
    <?php include __DIR__ . '/analytics/chart-launch-type.php'; ?>
    <?php include __DIR__ . '/analytics/chart-avg-duration.php'; ?>
    <?php include __DIR__ . '/analytics/chart-top-aircraft.php'; ?>
    <script>
    var analyticsData = null;

    function renderAllCharts(data) {
        analyticsData = data;
        renderMonthlyFlights(data);
        renderSoloDual(data);
        renderLongFlights(data);
        renderLaunchType(data);
        renderAvgDuration(data);
        renderTopAircraft(data);

        var pills = document.getElementById('summary-pills');
        pills.innerHTML = '';
        if (data.main && data.main.total_flights) {
            var p = document.createElement('span');
            p.className = 'summary-pill';
            p.textContent = data.main.year + ': ' + data.main.total_flights + ' flights';
            pills.appendChild(p);
        }
        if (data.compare && data.compare.total_flights) {
            var p = document.createElement('span');
            p.className = 'summary-pill';
            p.textContent = data.compare.year + ': ' + data.compare.total_flights + ' flights';
            pills.appendChild(p);
        }
    }

    function loadData(year, compare) {
        var url = '/api/analytics-data.php?year=' + year;
        if (compare > 0) url += '&compare=' + compare;

        document.getElementById('spinner').style.display = 'block';
        document.getElementById('chart-grid').style.display = 'none';
        document.getElementById('error-msg').style.display = 'none';

        fetch(url, { credentials: 'same-origin' })
            .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(function(json) {
                document.getElementById('spinner').style.display = 'none';
                if (!json.success) throw new Error(json.error || 'API error');
                document.getElementById('chart-grid').style.display = 'grid';
                renderAllCharts(json.data);
            })
            .catch(function(err) {
                document.getElementById('spinner').style.display = 'none';
                var el = document.getElementById('error-msg');
                el.textContent = 'Error loading data: ' + err.message;
                el.style.display = 'block';
            });
    }

    document.getElementById('load-btn').addEventListener('click', function() {
        var y = parseInt(document.getElementById('year').value, 10) || <?php echo $currentYear; ?>;
        var c = parseInt(document.getElementById('compare').value, 10) || 0;
        loadData(y, c);
    });

    loadData(<?php echo $currentYear; ?>, 0);
    </script>

    <style>
        <?php $inc = "./orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
        <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
    </style>
</body>
</html>
