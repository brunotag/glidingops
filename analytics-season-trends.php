<?php session_start();
require_once __DIR__ . '/helpers/logging.php';
logMsg("START");

$org = isset($_SESSION['org']) ? $_SESSION['org'] : 0;
if (!isset($_SESSION['security']) || !($_SESSION['security'] & 1)) {
    logMsg("AUTH FAIL");
    die("Security level too low for this page");
}
if (!isset($_SESSION['memberid'])) {
    logMsg("AUTH FAIL - no memberid");
    header('Location: /Login.php');
    die("Please logon");
}
logMsg("AUTH OK - memberid=" . $_SESSION['memberid']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Season Analysis</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; }
        .container-fluid { max-width: 1400px; margin: 0 auto; }
        .filter-bar { background: #fff; padding: 12px 16px; border-radius: 6px; margin: 12px 0; box-shadow: 0 1px 3px rgba(0,0,0,.1); display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .filter-bar label { font-size: 13px; font-weight: 600; margin: 0; }
        .filter-bar select { width: auto; min-width: 120px; padding: 4px 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 13px; }
        .btn-outline { padding: 4px 12px; border: 1px solid #063552; background: #fff; color: #063552; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .btn-outline:hover { background: #063552; color: #fff; }
        .chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 16px 0; }
        .chart-card { background: #fff; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,.1); padding: 16px; }
        .chart-card.full { grid-column: 1 / -1; }
        .chart-card h3 { margin: 0 0 8px 0; font-size: 15px; color: #063552; }
        .chart-card canvas { max-height: 300px; max-width: 100%; }
        .chart-toggles { margin-bottom: 6px; font-size: 12px; }
        .chart-note { font-size: 11px; color: #888; margin-top: 4px; font-style: italic; }
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
            <span style="font-weight:600;color:#063552;">Season Analysis</span>
            <span style="flex:1;"></span>
            <button class="btn-outline" onclick="window.print()">Print</button>
        </div>

        <div class="error-msg" id="error-msg"></div>
        <div class="spinner" id="spinner">Loading season trends...</div>

        <div class="chart-grid" id="chart-grid" style="display:none;">
            <div class="chart-card full">
                <h3>Total Flights Per Season</h3>
                <p class="chart-note">Shows total, solo only, or dual only. Numbers on top show delta vs previous season.</p>
                <div style="margin-bottom:6px;">
                    <select id="season-totals-mode" style="padding:4px 6px;border:1px solid #ccc;border-radius:3px;font-size:13px;" onchange="onSeasonTotalsModeChange(this)">
                        <option value="all">All (Solo + Dual)</option>
                        <option value="solo">Solo only</option>
                        <option value="dual">Dual only</option>
                    </select>
                </div>
                <canvas id="chart-season-totals"></canvas>
            </div>

            <div class="chart-card full">
                <h3>Monthly Activity Trends (Line Chart)</h3>
                <p class="chart-note">One line per month across seasons. Check/uncheck months to show/hide.</p>
                <div class="chart-toggles" id="toggles-monthly-lines"></div>
                <canvas id="chart-monthly-lines"></canvas>
            </div>

            <div class="chart-card full">
                <h3>Solo vs Dual % Per Season</h3>
                <p class="chart-note">Percentage of solo vs dual flights. Select a month to drill down, or "Whole Year" for the full season.</p>
                <div style="margin-bottom:6px;">
                    <select id="solo-dual-month" style="padding:4px 6px;border:1px solid #ccc;border-radius:3px;font-size:13px;" onchange="onSoloDualMonthChange(this)">
                        <option value="-1">Whole Year</option>
                        <option value="0">Jun</option>
                        <option value="1">Jul</option>
                        <option value="2">Aug</option>
                        <option value="3">Sep</option>
                        <option value="4">Oct</option>
                        <option value="5">Nov</option>
                        <option value="6">Dec</option>
                        <option value="7">Jan</option>
                        <option value="8">Feb</option>
                        <option value="9">Mar</option>
                        <option value="10">Apr</option>
                        <option value="11">May</option>
                    </select>
                </div>
                <canvas id="chart-solo-dual-trends"></canvas>
            </div>

            <div class="chart-card full">
                <h3>Flight Duration % Per Season</h3>
                <p class="chart-note">Breakdown of short (&lt;=30m), medium (30m-1h), and long (&gt;1h) flights as percentage of total.</p>
                <canvas id="chart-duration-trends"></canvas>
            </div>

            <div class="chart-card">
                <h3>Trial Flights Per Season</h3>
                <p class="chart-note">Number of trial/introductory flights per season (billing options 3/4/5).</p>
                <canvas id="chart-trial-flights"></canvas>
            </div>

            <div class="chart-card">
                <h3>Season-over-Season Growth</h3>
                <p class="chart-note">Absolute and percentage change from previous season.</p>
                <canvas id="chart-growth"></canvas>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <?php include __DIR__ . '/analytics/chart-season-totals.php'; ?>
    <?php include __DIR__ . '/analytics/chart-monthly-lines.php'; ?>
    <?php include __DIR__ . '/analytics/chart-solo-dual-trends.php'; ?>
    <?php include __DIR__ . '/analytics/chart-duration-trends.php'; ?>
    <?php include __DIR__ . '/analytics/chart-avg-duration.php'; ?>
    <?php include __DIR__ . '/analytics/chart-growth.php'; ?>

    <script>
    var seasonLabels = ['Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar','Apr','May'];

    function renderAllCharts(data) {
        renderSeasonTotals(data);
        renderMonthlyLines(data);
        renderSoloDualTrends(data);
        renderDurationTrends(data);
        renderTrialFlights(data);
        renderGrowth(data);

    }

    function loadData() {
        var url = '/api/analytics-trends.php';

        document.getElementById('spinner').style.display = 'block';
        document.getElementById('chart-grid').style.display = 'none';
        document.getElementById('error-msg').style.display = 'none';

        fetch(url, { credentials: 'same-origin' })
            .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(function(json) {
                document.getElementById('spinner').style.display = 'none';
                if (!json.success) throw new Error(json.error || 'API error');
                if (!json.data.seasons || json.data.seasons.length === 0) {
                    document.getElementById('error-msg').textContent = 'No data available.';
                    document.getElementById('error-msg').style.display = 'block';
                    return;
                }
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

    loadData();
    </script>

    <style>
        <?php $inc = "./orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
        <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
    </style>
</body>
</html>
