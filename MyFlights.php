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
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Flights</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <style>
        /* Org-specific styles */
        <?php $inc = "./orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
        <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>

        /* Base styles */
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            background: #f5f5f5;
        }

        h1, h2 {
            font-family: Calibri, Arial, Helvetica, sans-serif;
        }

        h1 {
            font-size: 22px;
            font-weight: 600;
            color: #222;
        }

        /* Layout */
        .header-row {
            padding: 0 12px;
        }

        #flights-section {
            margin: 8px 0;
            padding: 0 12px;
        }

        /* Table styling */
        .table {
            margin-bottom: 0;
        }

        .table th {
            background: #e8e8e8;
            border-bottom: 2px solid #337ab7;
        }

        .table-striped > tbody > tr:nth-of-type(odd) {
            background: #fff;
        }

        .table-striped > tbody > tr:nth-of-type(even) {
            background: #eef4fb;
        }

        .table th,
        .table td {
            vertical-align: middle;
            padding: 6px 8px;
        }

        /* Utility classes */
        .text-right {
            text-align: right;
        }

        .show-mobile {
            display: none;
        }

        /* Summary pills - flight counts */
        .summary-pill {
            display: inline-block;
            background: #e8e8e8;
            color: #222;
            border-radius: 10px;
            padding: 2px 10px;
            font-size: 12px;
            white-space: nowrap;
            border: 1px solid #ccc;
            margin-right: 3px;
        }

        .summary-pill:last-child {
            margin-right: 0;
        }

        /* Action buttons */
        .btn-outline {
            display: inline-block;
            padding: 5px 12px;
            font-size: 12px;
            border: 1px solid #bbb;
            border-radius: 4px;
            background: #fff;
            color: #555;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-outline:hover {
            background: #f0f0f0;
            border-color: #999;
            color: #333;
            text-decoration: none;
        }

        /* Loading indicator */
        .loader {
            text-align: center;
            padding: 50px;
        }

        .spinner {
            font-size: 24px;
            color: #337ab7;
        }

        /* Print styles */
        @media print {
            .no-print {
                display: none;
            }
            @page {
                size: landscape;
            }
        }

        /* Tablet and mobile responsive */
        @media (max-width: 767px) {
            #flights-section .table thead { display: none; }
            #flights-section .table { display: block; }
            #flights-section .table tbody { display: flex; flex-wrap: wrap; gap: 6px; }
            #flights-section .table tr {
                width: calc(50% - 3px);
                min-width: 240px; flex: 1 1 auto;
                border: 1px solid #ddd; border-radius: 6px;
                padding: 5px 8px; background: #fff; box-sizing: border-box;
                overflow: hidden;
            }
            #flights-section .table > tbody > tr > td {
                display: block; border: none; padding: 2px 2px 2px 44%;
                text-align: left !important; font-size: 13px; position: relative;
                line-height: 1.35; overflow-wrap: break-word; word-break: break-word;
            }
            #flights-section .table td::before {
                content: attr(data-label); position: absolute; left: 4px;
                width: calc(44% - 12px); overflow: hidden; text-overflow: ellipsis;
                white-space: nowrap; font-weight: 600; font-size: 12px; color: #555;
                line-height: 1.35;
            }
            #flights-section .table td[data-empty="1"] { display: none; }
            #flights-section .hide-mobile { display: none !important; }
            #flights-section .show-mobile { display: block !important; }
            #flights-section .text-right { text-align: left !important; }

            #flights-section {
                margin: 0 8px;
                padding: 0;
            }

            .btn-outline {
                padding: 6px 10px;
                font-size: 13px;
            }

            .header-row .text-right {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 4px;
            }

            #summary-inline {
                margin: 0;
            }

            .header-row {
                flex-direction: row;
                align-items: flex-start !important;
                padding: 0 8px;
            }

            .header-row > div:first-child {
                flex: 1;
                min-width: 0;
            }

            .header-row > div:last-child {
                flex-shrink: 0;
            }

            #page-title {
                margin-bottom: 0 !important;
                font-size: 18px;
            }
        }

        @media (max-width: 580px) {
            #flights-section .table tbody { flex-direction: column; gap: 8px; }
            #flights-section .table tr { width: 100%; min-width: 0; }
            #flights-section .table > tbody > tr > td:last-child { padding-bottom: 8px; }
        }

        /* Small mobile responsive */
        @media (max-width: 440px) {
            #flights-section .table tbody {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            #flights-section .table tr {
                width: 100%;
            }

            #flights-section .table > tbody > tr > td:last-child {
                padding-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <?php $inc = "./orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
    <?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>

    <div class="container-fluid">
        <div class="row header-row" style="display: flex; align-items: center;">
            <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;flex:1;min-width:0;">
                <h1 id="page-title" style="margin: 0;white-space:nowrap;">Loading...</h1>
                <div id="summary-inline"></div>
            </div>
            <div class="text-right no-print" style="flex-shrink:0;">
                <a href="MyFlightsCSV" class="btn-outline">Export CSV</a>
                <button class="btn-outline" onclick="window.print()">Print</button>
            </div>
        </div>

        <div id="loader" class="loader">
            <div class="spinner">Loading your flights...</div>
        </div>

        <div id="content" style="display: none;">
            <div class="row">
                <div id="flights-section"></div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var billingOptions = {};
        var towlaunch, selflaunch, winchlaunch;
        var memberid = <?php echo intval($_SESSION['memberid']); ?>;
        var memberInstructor = false;

        function formatDuration(ms) {
            if (ms < 0) return 'In Progress';
            var hours = Math.floor(ms / 3600000);
            var mins = Math.floor((ms % 3600000) / 60000);
            return (hours < 10 ? '0' : '') + hours + ':' + (mins < 10 ? '0' : '') + mins;
        }

        function formatDate(localdate) {
            return localdate.toString().substr(6,2) + '/' + localdate.toString().substr(4,2) + '/' + localdate.toString().substr(0,4);
        }

        function formatTime(timestamp) {
            if (!timestamp) return '';
            var date = new Date(Math.floor(timestamp / 1000) * 1000);
            date.setHours(date.getHours() + 12);
            return date.toISOString().substr(11, 5);
        }

        function getLaunchInfo(launchtype, height) {
            if (parseInt(launchtype) === parseInt(towlaunch)) {
                return { label: height || '', code: 'A' };
            } else if (parseInt(launchtype) === parseInt(selflaunch)) {
                return { label: 'SELF LAUNCH', code: 'S' };
            } else if (parseInt(launchtype) === parseInt(winchlaunch)) {
                return { label: 'WINCH', code: 'W' };
            }
            return { label: '', code: '' };
        }

        function getType(row) {
            var isP1 = parseInt(row.pic) === memberid;
            var isP2 = parseInt(row.p2) === memberid;
            
            if (isP1 && (!row.p2 || parseInt(row.p2) === 0)) {
                return 'P';
            } else if (isP1) {
                return memberInstructor ? 'I' : 'P1';
            } else if (isP2 && (!row.pic || parseInt(row.pic) === 0)) {
                return 'P';
            } else {
                return 'P2';
            }
        }

        function render() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'api/myflights', true);
            xhr.onload = function() {
                document.getElementById('loader').style.display = 'none';
                document.getElementById('content').style.display = 'block';

                if (xhr.status !== 200) {
                    document.getElementById('page-title').textContent = 'Error loading data';
                    return;
                }

                var data = JSON.parse(xhr.responseText);
                document.getElementById('page-title').textContent = data.dispname + "'s Flights";
                
                billingOptions = data.billingOptions;
                towlaunch = data.towlaunch;
                selflaunch = data.selflaunch;
                winchlaunch = data.winchlaunch;
                memberInstructor = data.memberInstructor;

                window._allFlights = data.flights;
                renderFlights(data.flights);
                renderSummary(data.flights);
            };
            xhr.onerror = function() {
                document.getElementById('loader').innerHTML = '<div class="spinner">Error loading data</div>';
            };
            xhr.send();
        }

        window._toggleDateSort = function() {
            dateSortOrder = dateSortOrder === 'asc' ? 'desc' : 'asc';
            if (window._allFlights) {
                renderFlights(window._allFlights);
                renderSummary(window._allFlights);
            }
        };

        var dateSortOrder = 'desc';

        function sortByDate(a, b) {
            if (dateSortOrder === 'asc') return a.localdate - b.localdate;
            return b.localdate - a.localdate;
        }

        function renderFlights(flights) {
            if (!flights || flights.length === 0) {
                document.getElementById('flights-section').innerHTML = '<p>No flights found.</p>';
                return;
            }

            flights.sort(sortByDate);

            var sortArrow = dateSortOrder === 'asc' ? ' &#9650;' : ' &#9660;';
            var html = '<div class="table-responsive"><table class="table table-bordered table-condensed table-striped"><thead><tr>' +
                '<th style="cursor:pointer;" onclick="window._toggleDateSort()">Date' + sortArrow + '</th><th class="text-right">Glider</th><th class="text-right">Make/Model</th><th>Location</th>' +
                '<th class="text-right">Duration</th><th class="text-right">Start</th><th class="text-right">Land</th>' +
                '<th class="text-right">Tow Height</th><th class="text-right">Launch</th><th class="text-right">Type</th>' +
                '<th>Comments</th><th>Charging</th></tr></thead><tbody>';

            var totMins = 0;
            var cntP = cntP1 = cntP2 = cntI = 0;
            var totMinsP = totMinsP1 = totMinsP2 = totMinsI = 0;

            flights.forEach(function(row, idx) {
                var durationMs = parseInt(row.land) - parseInt(row.start);
                var duration = formatDuration(durationMs);
                if (durationMs >= 0) {
                    var mins = Math.floor(durationMs / 60000);
                    totMins += mins;
                }

                var type = getType(row);
                if (type === 'P') { cntP++; totMinsP += Math.floor(durationMs / 60000); }
                else if (type === 'P1') { cntP1++; totMinsP1 += Math.floor(durationMs / 60000); }
                else if (type === 'P2') { cntP2++; totMinsP2 += Math.floor(durationMs / 60000); }
                else if (type === 'I') { cntI++; totMinsI += Math.floor(durationMs / 60000); }

                var launch = getLaunchInfo(row.launchtype, row.height);
                var comments = row.comments ? row.comments.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') : '';
                var startStr = formatTime(row.start);
                var landStr = formatTime(row.land);
                var timeCombined = (startStr || landStr) ? (startStr + ' - ' + landStr + ' (' + duration + ')') : '';
                var gliderCombined = (row.glider || '') + (row.make_model ? ' (' + row.make_model + ')' : '');

                function e(v) { return (v || '').toString().trim() === ''; }

                html += '<tr>';
                html += '<td data-label="Date"' + (e(formatDate(row.localdate)) ? ' data-empty="1"' : '') + '>' + formatDate(row.localdate) + '</td>';
                html += '<td data-label="Glider" class="text-right hide-mobile"' + (e(row.glider) ? ' data-empty="1"' : '') + '>' + (row.glider || '') + '</td>';
                html += '<td data-label="Make/Model" class="text-right hide-mobile"' + (e(row.make_model) ? ' data-empty="1"' : '') + '>' + (row.make_model || '') + '</td>';
                html += '<td data-label="Glider" class="text-right show-mobile"' + (e(gliderCombined) ? ' data-empty="1"' : '') + '>' + gliderCombined + '</td>';
                html += '<td data-label="Location"' + (e(row.location) ? ' data-empty="1"' : '') + '>' + (row.location || '') + '</td>';
                html += '<td data-label="Duration" class="text-right hide-mobile"' + (e(duration) ? ' data-empty="1"' : '') + '>' + duration + '</td>';
                html += '<td data-label="Start" class="text-right hide-mobile"' + (e(startStr) ? ' data-empty="1"' : '') + '>' + startStr + '</td>';
                html += '<td data-label="Land" class="text-right hide-mobile"' + (e(landStr) ? ' data-empty="1"' : '') + '>' + landStr + '</td>';
                html += '<td data-label="Time" class="text-right show-mobile"' + (e(timeCombined) ? ' data-empty="1"' : '') + '>' + timeCombined + '</td>';
                html += '<td data-label="Tow Height" class="text-right"' + (!/^\d+$/.test(launch.label) ? ' data-empty="1"' : '') + '>' + launch.label + '</td>';
                html += '<td data-label="Launch" class="text-right"' + (e(launch.code) ? ' data-empty="1"' : '') + '>' + launch.code + '</td>';
                html += '<td data-label="Type" class="text-right"' + (e(type) ? ' data-empty="1"' : '') + '>' + type + '</td>';
                html += '<td data-label="Comments"' + (e(comments) ? ' data-empty="1"' : '') + '>' + comments + '</td>';
                html += '<td data-label="Charging"' + (e(billingOptions[row.billing_option]) ? ' data-empty="1"' : '') + '>' + (billingOptions[row.billing_option] || '') + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            document.getElementById('flights-section').innerHTML = html;
            
            window._flightSummary = { totMins, cntP, cntP1, cntP2, cntI, totMinsP, totMinsP1, totMinsP2, totMinsI };
        }

        function renderSummary(flights) {
            var s = window._flightSummary || { cntP:0, cntP1:0, cntP2:0, cntI:0, totMins:0 };
            function p(label, cnt, ms) {
                var h = Math.floor(ms / 3600000);
                var m = Math.round((ms % 3600000) / 60000);
                var prefix = label === 'Total' ? '' : 'as ';
                return prefix + label + ', ' + cnt + ' flight' + (cnt !== 1 ? 's' : '') + ', ' + h + ' h ' + m + ' m';
            }
            var parts = [];
            if (s.cntI) parts.push(p('I', s.cntI, s.totMinsI*60000));
            if (s.cntP) parts.push(p('P', s.cntP, s.totMinsP*60000));
            if (s.cntP1) parts.push(p('P1', s.cntP1, s.totMinsP1*60000));
            if (s.cntP2) parts.push(p('P2', s.cntP2, s.totMinsP2*60000));
            var total = s.cntP + s.cntP1 + s.cntP2;
            var totalTime = s.totMinsP + s.totMinsP1 + s.totMinsP2;
            if (total) parts.push(p('Total', total, totalTime*60000));
            var html = parts.map(function(p) { return '<span class="summary-pill">' + p + '</span>'; }).join('');
            document.getElementById('summary-inline').innerHTML = html;
        }

        render();
    })();
    </script>
</body>
</html>