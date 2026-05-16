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
        <?php $inc = "./orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
        <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; background: #f5f5f5; }
        h1, h2 { font-family: Calibri, Arial, Helvetica, sans-serif; }
        .section { margin: 20px 12px; padding: 20px; border-radius: 6px; background: #fff; border: 1px solid #ddd; }
        .flights-section { }
        .table { margin-bottom: 0; }
        .table th { background: #e8e8e8; border-bottom: 2px solid #337ab7; }
        .table-striped > tbody > tr:nth-of-type(odd) { background: #fff; }
        .table-striped > tbody > tr:nth-of-type(even) { background: #eef4fb; }
        .table th, .table td { vertical-align: middle; padding: 6px 8px; }

        .text-right { text-align: right; }
        .border-top { border-top: 2px solid #337ab7; font-weight: bold; }
        .loader { text-align: center; padding: 50px; }
        .spinner { font-size: 24px; color: #337ab7; }
        @media print {
            .no-print { display: none; }
            .section { box-shadow: none; break-inside: avoid; }
            @page { size: landscape; }
        }
    </style>
</head>
<body>
    <?php $inc = "./orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
    <?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>

    <div class="container-fluid">
        <div class="row" style="display: flex; align-items: center;">
            <div class="col-xs-6">
                <h1 id="page-title" style="margin: 0;">Loading...</h1>
            </div>
            <div class="col-xs-6 text-right no-print">
                <a href="MyFlightsCSV" class="btn btn-primary">Export CSV</a>
                <button class="btn btn-primary" onclick="window.print()">Print</button>
            </div>
        </div>

        <div id="loader" class="loader">
            <div class="spinner">Loading your flights...</div>
        </div>

        <div id="content" style="display: none;">
            <div id="flights-section" class="section"></div>
            <div id="summary-section" class="section"></div>
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
            return date.toISOString().substr(11, 8);
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

                html += '<tr>';
                html += '<td>' + formatDate(row.localdate) + '</td>';
                html += '<td class="text-right">' + (row.glider || '') + '</td>';
                html += '<td class="text-right">' + (row.make_model || '') + '</td>';
                html += '<td>' + (row.location || '') + '</td>';
                html += '<td class="text-right">' + duration + '</td>';
                html += '<td class="text-right">' + formatTime(row.start) + '</td>';
                html += '<td class="text-right">' + formatTime(row.land) + '</td>';
                html += '<td class="text-right">' + launch.label + '</td>';
                html += '<td class="text-right">' + launch.code + '</td>';
                html += '<td class="text-right">' + type + '</td>';
                html += '<td>' + comments + '</td>';
                html += '<td>' + (billingOptions[row.billing_option] || '') + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            document.getElementById('flights-section').innerHTML = html;
            
            window._flightSummary = { totMins, cntP, cntP1, cntP2, cntI, totMinsP, totMinsP1, totMinsP2, totMinsI };
        }

        function renderSummary(flights) {
            var s = window._flightSummary || { cntP:0, cntP1:0, cntP2:0, cntI:0, totMins:0 };
            var html = '<h2>Flights Summary</h2><table class="table table-bordered table-condensed" style="max-width:300px;">' +
                '<tr><td>I</td><td class="text-right">' + s.cntI + '</td><td class="text-right">' + formatDuration(s.totMinsI*60000) + '</td></tr>' +
                '<tr><td>P</td><td class="text-right">' + s.cntP + '</td><td class="text-right">' + formatDuration(s.totMinsP*60000) + '</td></tr>' +
                '<tr><td>P1</td><td class="text-right">' + s.cntP1 + '</td><td class="text-right">' + formatDuration(s.totMinsP1*60000) + '</td></tr>' +
                '<tr><td>P2</td><td class="text-right">' + s.cntP2 + '</td><td class="text-right">' + formatDuration(s.totMinsP2*60000) + '</td></tr>' +
                '<tr><td class="border-top">TOTAL</td><td class="text-right border-top">' + (s.cntP+s.cntP1+s.cntP2) + '</td><td class="text-right border-top">' + formatDuration(s.totMins*60000) + '</td></tr>' +
                '</table>';
            document.getElementById('summary-section').innerHTML = html;
        }

        render();
    })();
    </script>
</body>
</html>