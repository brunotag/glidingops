<?php session_start();
require_once __DIR__ . '/helpers/logging.php';
logMsg("START");

$org = isset($_SESSION['org']) ? $_SESSION['org'] : 0;
require_once __DIR__ . '/helpers/permissions.php'; require_perm('analytics.dashboard');
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
    <title>Analytics Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; }
        .container-fluid { max-width: 1400px; margin: 0 auto; }
        .filter-bar { background: #fff; padding: 12px 16px; border-radius: 6px; margin: 12px 0; box-shadow: 0 1px 3px rgba(0,0,0,.1); display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .filter-bar label { font-size: 13px; font-weight: 600; margin: 0; }
        .filter-bar input[type="number"], .filter-bar select { width: auto; min-width:100px; padding: 4px 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 13px; }
        .filter-bar select { padding: 4px 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 13px; }
        .btn-outline { padding: 4px 12px; border: 1px solid #063552; background: #fff; color: #063552; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .btn-outline:hover { background: #063552; color: #fff; }
        .chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 16px 0; }
        .chart-card { background: #fff; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,.1); padding: 16px; }
        .chart-card.full { grid-column: 1 / -1; }
        .chart-card h3 { margin: 0 0 8px 0; font-size: 15px; color: #063552; }
        .chart-card canvas { max-height: 300px; max-width: 100%; }
        .chart-toggles { margin-bottom: 6px; font-size: 12px; }
        .chart-toggles label { margin-right: 14px; cursor: pointer; font-weight: normal; }
        .chart-toggles input { margin-right: 3px; }
        .cat-toggles { display:flex; flex-direction:column; gap:6px; padding-top:4px; font-size:12px; white-space:nowrap; }
        .cat-toggles label { cursor:pointer; font-weight:normal; }
        .cat-toggles input { margin-right:3px; }
        #summary-pills { display: flex; flex-wrap: wrap; gap: 8px; margin: 8px 0; }
        .summary-pill { background: #063552; color: #f26120; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .spinner { text-align: center; padding: 40px; font-size: 16px; color: #888; }
        .error-msg { background: #f2dede; color: #a94442; padding: 12px; border-radius: 4px; margin: 12px 0; display: none; }
        .trends-box { background: #fff; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,.1); padding: 16px; margin: 16px 0; }
        .trends-box h3 { margin: 0 0 12px 0; font-size: 15px; color: #063552; }
        .trends-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
        .trend-item { text-align: center; padding: 10px; border: 1px solid #e0e0e0; border-radius: 6px; }
        .trend-item .val { font-size: 20px; font-weight: 700; color: #063552; }
        .trend-item .label { font-size: 11px; color: #888; text-transform: uppercase; }
        .trend-item .diff { font-size: 13px; font-weight: 600; }
        .trend-item .diff.up { color: #2e7d32; }
        .trend-item .diff.down { color: #c62828; }
        .trend-item .diff.flat { color: #888; }
        @media (max-width: 900px) { .chart-grid { grid-template-columns: 1fr; } }
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
                <option value="season">Season vs Season</option>
                <option value="ytd" selected>YTD vs Past YTD</option>
            </select>
            <label for="year">Season:</label>
            <select id="year">
                <?php for ($y = 2016; $y <= $defaultSeason; $y++): ?>
                <option value="<?php echo $y; ?>"<?php echo $y === $defaultSeason ? ' selected' : ''; ?>><?php echo $y; ?>/<?php echo $y + 1; ?></option>
                <?php endfor; ?>
            </select>
            <span id="compare-group">
                <label for="compare">Compare:</label>
                <select id="compare">
                    <option value="0">None</option>
                    <?php for ($y = 2016; $y <= $defaultSeason; $y++): ?>
                    <option value="<?php echo $y; ?>"><?php echo $y; ?>/<?php echo $y + 1; ?></option>
                    <?php endfor; ?>
                </select>
            </span>
            <button id="load-btn" class="btn-outline">Load</button>
            <span style="flex:1;"></span>
            <a href="/SeasonTrends" class="btn-outline" style="text-decoration:none;">Season Analysis</a>
            <button class="btn-outline" onclick="window.print()">Print</button>
        </div>

        <div id="summary-pills"></div>
        <div class="error-msg" id="error-msg"></div>
        <div class="spinner" id="spinner">Loading analytics...</div>

        <div class="trends-box" id="trends-box" style="display:none;">
            <h3>Trends &amp; Comparison</h3>
            <div class="trends-grid" id="trends-grid"></div>
        </div>

        <div class="chart-grid" id="chart-grid" style="display:none;">
            <div class="chart-card full"><h3>Monthly Flight Count</h3><div class="chart-toggles" id="toggles-monthly"></div><canvas id="chart-monthly"></canvas></div>
            <div class="chart-card full"><h3>Solo vs Dual Pilot</h3><div class="chart-toggles" id="toggles-solo-dual"></div><div style="display:flex;flex-direction:row;gap:12px;"><canvas id="chart-solo-dual" style="flex:1;min-width:0;"></canvas><div class="cat-toggles" id="cat-toggles-solo-dual"></div></div></div>
            <div class="chart-card full"><h3>Flight Duration Breakdown</h3><div class="chart-toggles" id="toggles-duration"></div><div style="display:flex;flex-direction:row;gap:12px;"><canvas id="chart-duration" style="flex:1;min-width:0;"></canvas><div class="cat-toggles" id="cat-toggles-duration"></div></div></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <?php include __DIR__ . '/analytics/chart-monthly-flights.php'; ?>
    <?php include __DIR__ . '/analytics/chart-solo-dual.php'; ?>
    <?php include __DIR__ . '/analytics/chart-flight-duration.php'; ?>
    <?php include __DIR__ . '/analytics/trends-summary.php'; ?>
    <script>
    var analyticsData = null;

    var seasonLabels = ['Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar','Apr','May'];

    function monthIndex(yearmonth) {
        return parseInt(yearmonth.slice(-2), 10);
    }

    function seasonPos(monthNum) {
        var pos = monthNum - 6;
        if (pos < 0) pos += 12;
        return pos;
    }

    function buildSeasonArray(monthly) {
        var arr = new Array(12).fill(null);
        if (!monthly) return arr;
        monthly.forEach(function(r) {
            var pos = seasonPos(monthIndex(r.yearmonth));
            arr[pos] = r;
        });
        return arr;
    }

    function setupSeasonToggles(chart, containerId, mainLabel, compLabel, mainCount) {
        var container = document.getElementById(containerId);
        container.innerHTML = '';
        if (!compLabel) return;
        var groups = [
            { label: mainLabel, indices: [], checked: true },
            { label: compLabel, indices: [], checked: true }
        ];
        for (var i = 0; i < chart.data.datasets.length; i++) {
            groups[i < mainCount ? 0 : 1].indices.push(i);
        }
        groups.forEach(function(g) {
            var label = document.createElement('label');
            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.checked = true;
            cb.addEventListener('change', function() {
                g.indices.forEach(function(idx) {
                    var meta = chart.getDatasetMeta(idx);
                    meta.hidden = !cb.checked;
                });
                chart.update();
            });
            label.appendChild(cb);
            label.appendChild(document.createTextNode(g.label));
            container.appendChild(label);
        });
    }

    function setupCategoryToggles(chart, containerId, catLabels) {
        var container = document.getElementById(containerId);
        container.innerHTML = '';
        var catsPerSeason = catLabels.length;
        catLabels.forEach(function(cat, ci) {
            var label = document.createElement('label');
            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.checked = true;
            cb.addEventListener('change', function() {
                for (var i = ci; i < chart.data.datasets.length; i += catsPerSeason) {
                    var meta = chart.getDatasetMeta(i);
                    meta.hidden = !cb.checked;
                }
                chart.update();
            });
            label.appendChild(cb);
            label.appendChild(document.createTextNode(cat));
            container.appendChild(label);
        });
    }

    function hideHiddenLegend(chart) {
        chart.options.plugins.legend.labels.filter = function(item) {
            var meta = chart.getDatasetMeta(item.datasetIndex);
            return !meta.hidden;
        };
    }

    function renderAllCharts(data) {
        analyticsData = data;
        renderMonthlyFlights(data);
        renderSoloDual(data);
        renderFlightDuration(data);
        renderTrends(data);

        var pills = document.getElementById('summary-pills');
        pills.innerHTML = '';
        var sets = data.compare ? [data.main, data.compare] : [data.main];
        sets.forEach(function(s) {
            if (s && s.totals && s.totals.flights) {
                var p = document.createElement('span');
                p.className = 'summary-pill';
                p.textContent = s.label + ': ' + s.totals.flights + ' flights';
                pills.appendChild(p);
            }
        });
    }

    function loadData(mode, year, compare) {
        var url = '/api/analytics-data.php?mode=' + mode + '&year=' + year;
        if (compare > 0) url += '&compare=' + compare;

        document.getElementById('spinner').style.display = 'block';
        document.getElementById('chart-grid').style.display = 'none';
        document.getElementById('trends-box').style.display = 'none';
        document.getElementById('error-msg').style.display = 'none';

        fetch(url, { credentials: 'same-origin' })
            .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(function(json) {
                document.getElementById('spinner').style.display = 'none';
                if (!json.success) throw new Error(json.error || 'API error');
                document.getElementById('chart-grid').style.display = 'grid';
                document.getElementById('trends-box').style.display = 'block';
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
        var mode = document.getElementById('mode').value;
        var y = parseInt(document.getElementById('year').value, 10) || <?php echo $defaultSeason; ?>;
        var c = parseInt(document.getElementById('compare').value, 10) || 0;
        loadData(mode, y, c);
    });

    document.getElementById('mode').addEventListener('change', function() {
    });

    loadData('ytd', <?php echo $defaultSeason; ?>, 0);
    </script>

    <style>
        <?php $inc = "./orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
        <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
    </style>
</body>
</html>
