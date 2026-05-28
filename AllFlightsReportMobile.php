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

$today = date('Y-m-d');
$firstOfMonth = date('Y-m-01');
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Flights</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <style>
        <?php $inc = "./orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
        <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>

        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            background: #f5f5f5;
        }
        h1, h2 { font-family: Calibri, Arial, Helvetica, sans-serif; }
        h1 { font-size: 22px; font-weight: 600; color: #222; }

        .header-row { padding: 0 12px; }
        #flights-section { margin: 8px 0; padding: 0 12px; }

        /* --- Desktop table --- */
        .table { margin-bottom: 0; }
        .table th { background: #e8e8e8; border-bottom: 2px solid #337ab7; }
        .table-striped > tbody > tr:nth-of-type(odd) { background: #fff; }
        .table-striped > tbody > tr:nth-of-type(even) { background: #eef4fb; }
        .table th, .table td { vertical-align: middle; padding: 6px 8px; }

        .text-right { text-align: right; }
        .show-mobile { display: none; }

        /* Non-finalised highlight */
        .row-nonfinalised { background-color: #fffde7 !important; }
        .table-striped > tbody > tr.row-nonfinalised:nth-of-type(odd) { background-color: #fffde7 !important; }
        .table-striped > tbody > tr.row-nonfinalised:nth-of-type(even) { background-color: #fffde7 !important; }

        /* Summary pills */
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
        .summary-pill:last-child { margin-right: 0; }

        /* Buttons */
        .btn-outline {
            display: inline-block;
            padding: 5px 12px;
            font-size: 13px;
            border: 1px solid #bbb;
            border-radius: 4px;
            background: #fff;
            color: #555;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-outline:hover { background: #f0f0f0; border-color: #999; color: #333; text-decoration: none; }

        .autocomplete-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #ccc;
            border-top: none;
            z-index: 1060;
            max-height: 300px;
            overflow-y: auto;
        }
        .autocomplete-suggestions .ac-item {
            padding: 6px 10px;
            cursor: pointer;
            font-size: 13px;
            color: #333;
        }
        .autocomplete-suggestions .ac-item:hover,
        .autocomplete-suggestions .ac-item.selected {
            background: #e94560;
            color: #fff;
        }

        .loader { text-align: center; padding: 50px; }
        .spinner { font-size: 24px; color: #337ab7; }

        /* Print */
        @media print {
            .no-print { display: none; }
            @page { size: landscape; }
        }

        /* --- Mobile card layout --- */
        @media (max-width: 767px) {
            #flights-section .table thead { display: none; }
            #flights-section .table { display: block; }
            #flights-section .table tbody { display: flex; flex-wrap: wrap; gap: 6px; }
            #flights-section .table tr {
                width: calc(50% - 3px);
                min-width: 240px; flex: 1 1 auto;
                border: 1px solid #ddd; border-radius: 6px;
                padding: 5px 8px; background: #fff; box-sizing: border-box;
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

            #flights-section { margin: 0 8px; padding: 0; }
            .btn-outline { padding: 6px 10px; }
            .header-row { padding: 4px 8px; gap: 4px; }
            #page-title { font-size: 18px; }
            .header-row input[type="date"] { width: 120px; }
            .header-row .member-wrap input { width: 110px; }
        }

        @media (max-width: 580px) {
            #flights-section .table tbody { flex-direction: column; gap: 8px; }
            #flights-section .table tr { width: 100%; min-width: 0; }
            #flights-section .table > tbody > tr > td:last-child { padding-bottom: 8px; }
        }
    </style>
</head>
<body>
    <?php $inc = "./orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
    <?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>

    <div class="container-fluid">
        <div class="row header-row no-print" style="display:flex;flex-direction:column;gap:6px;">
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                    <h1 id="page-title" style="margin:0;white-space:nowrap;font-size:20px;">All Flights</h1>
                    <div id="summary-inline" style="display:inline;"></div>
                </div>
                <span style="flex:1;min-width:4px;"></span>
                <a href="/AllFlightsReportNew" class="btn-outline" style="padding:3px 8px;flex-shrink:0;text-decoration:none;">Web Version</a>
                <button class="btn-outline" onclick="window.print()" style="padding:3px 8px;flex-shrink:0;">Print</button>
            </div>
            <div class="filter-block" style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:6px;">
                    <label for="fromdate" style="font-size:13px;font-weight:600;margin:0;">From:</label>
                    <input type="date" id="fromdate" value="<?php echo $firstOfMonth; ?>" style="padding:3px 6px;border:1px solid #ccc;border-radius:3px;font-size:13px;">
                    <label for="todate" style="font-size:13px;font-weight:600;margin:0;">To:</label>
                    <input type="date" id="todate" value="<?php echo $today; ?>" style="padding:3px 6px;border:1px solid #ccc;border-radius:3px;font-size:13px;">
                    <button id="reset-filters" class="btn-outline" style="padding:3px 8px;">Today</button>
                </div>
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:6px;">
                    <label for="member-search" style="font-size:13px;font-weight:600;margin:0;">Member:</label>
                    <div class="member-wrap" style="display:inline-block;position:relative;">
                        <input type="text" id="member-search" placeholder="Name..." autocomplete="off" style="padding:3px 6px;border:1px solid #ccc;border-radius:3px;font-size:13px;width:100px;">
                        <input type="hidden" id="filter-member" value="">
                        <div id="member-suggestions" class="autocomplete-suggestions" style="display:none;"></div>
                    </div>
                    <button id="apply-filters" class="btn-outline" style="padding:3px 8px;">Reload</button>
                </div>
            </div>
        </div>

        <div id="loader" class="loader">
            <div class="spinner">Loading flights...</div>
        </div>

        <div id="content" style="display:none;">
            <div class="row">
                <div id="flights-section"></div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var dateSortOrder = 'desc';
        var allFlights = null;

        function formatDate(localdate) {
            var s = localdate.toString();
            if (s.length !== 8) return s;
            return s.substr(6,2) + '/' + s.substr(4,2) + '/' + s.substr(0,4);
        }

        function formatDuration(ms) {
            if (ms < 0) return '';
            var hours = Math.floor(ms / 3600000);
            var mins = Math.floor((ms % 3600000) / 60000);
            return (hours > 0 ? hours + 'h ' : '') + (mins < 10 ? '0' : '') + mins + 'm';
        }

        function escapeHtml(s) {
            if (!s) return s;
            var d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        function isEmpty(v) {
            return (v || '').toString().trim() === '';
        }

        function buildUrl() {
            var fromdate = document.getElementById('fromdate').value;
            var todate = document.getElementById('todate').value;
            var memberId = document.getElementById('filter-member').value;
            var url = '/api/flights-report?fromdate=' + encodeURIComponent(fromdate)
                    + '&todate=' + encodeURIComponent(todate)
                    + '&length=10000&start=0';
            if (memberId) url += '&memberId=' + encodeURIComponent(memberId);
            return url;
        }

        function fetchData() {
            var url = buildUrl();
            document.getElementById('loader').style.display = 'block';
            document.getElementById('content').style.display = 'none';

            var xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.onload = function() {
                document.getElementById('loader').style.display = 'none';
                document.getElementById('content').style.display = 'block';

                if (xhr.status !== 200) {
                    document.getElementById('page-title').textContent = 'Error loading data';
                    document.getElementById('flights-section').innerHTML = '<p>Failed to load flights.</p>';
                    return;
                }

                var resp = JSON.parse(xhr.responseText);
                allFlights = resp.data || [];
                document.getElementById('page-title').textContent = 'All Flights';
                renderTable(allFlights);
                renderSummary(allFlights, resp);
            };
            xhr.onerror = function() {
                document.getElementById('loader').innerHTML = '<div class="spinner">Error loading data</div>';
            };
            xhr.send();
        }

        window._toggleDateSort = function() {
            dateSortOrder = dateSortOrder === 'asc' ? 'desc' : 'asc';
            if (allFlights) {
                renderTable(allFlights);
                renderSummary(allFlights);
            }
        };

        function sortByDate(a, b) {
            var da = a.dt[0];
            var db = b.dt[0];
            if (dateSortOrder === 'asc') return da.localeCompare(db);
            return db.localeCompare(da);
        }

        function renderTable(flights) {
            if (!flights || flights.length === 0) {
                document.getElementById('flights-section').innerHTML = '<p>No flights found for this period.</p>';
                return;
            }

            flights.sort(sortByDate);

            var sortArrow = dateSortOrder === 'asc' ? ' &#9650;' : ' &#9660;';
            var html = '<table class="table table-bordered table-condensed table-striped"><thead><tr>'
                + '<th style="cursor:pointer;" onclick="window._toggleDateSort()">Date' + sortArrow + '</th>'
                + '<th>Glider</th><th>PIC</th><th>P2</th><th>Location</th><th>Launch</th>'
                + '<th class="hide-mobile">Tow</th><th class="hide-mobile">Towy</th><th class="hide-mobile">Height</th>'
                + '<th>Time</th><th>Charge</th><th>Comments</th>'
                + '</tr></thead><tbody>';

            flights.forEach(function(item) {
                var d = item.dt;
                var finalised = item.finalised;
                var rowClass = finalised ? '' : 'row-nonfinalised';

                var dateVal = d[0] || '';
                var glider = d[5] || '';
                var pic = d[7] || '';
                var p2 = d[8] || '';
                var location = d[2] || '';
                var launch = d[3] || '';
                var tow = d[4] || '';
                var towy = d[6] || '';
                var height = d[12] || '';
                var takeoff = d[9] || '';
                var land = d[10] || '';
                var duration = d[11] || '';
                var timeCombined = (takeoff || land) ? (takeoff + ' - ' + land + ' (' + duration + ')') : (duration || '');
                var charge = d[13] || '';
                var comments = d[14] || '';

                html += '<tr class="' + rowClass + '">';
                html += '<td data-label="Date"' + (isEmpty(dateVal) ? ' data-empty="1"' : '') + '>' + escapeHtml(dateVal) + '</td>';
                html += '<td data-label="Glider"' + (isEmpty(glider) ? ' data-empty="1"' : '') + '>' + escapeHtml(glider) + '</td>';
                html += '<td data-label="PIC"' + (isEmpty(pic) ? ' data-empty="1"' : '') + '>' + escapeHtml(pic) + '</td>';
                html += '<td data-label="P2"' + (isEmpty(p2) ? ' data-empty="1"' : '') + '>' + escapeHtml(p2) + '</td>';
                html += '<td data-label="Location"' + (isEmpty(location) ? ' data-empty="1"' : '') + '>' + escapeHtml(location) + '</td>';
                html += '<td data-label="Launch"' + (isEmpty(launch) ? ' data-empty="1"' : '') + '>' + escapeHtml(launch) + '</td>';
                html += '<td data-label="Tow" class="hide-mobile"' + (isEmpty(tow) ? ' data-empty="1"' : '') + '>' + escapeHtml(tow) + '</td>';
                html += '<td data-label="Towy" class="hide-mobile"' + (isEmpty(towy) ? ' data-empty="1"' : '') + '>' + escapeHtml(towy) + '</td>';
                html += '<td data-label="Height" class="hide-mobile"' + (isEmpty(height) ? ' data-empty="1"' : '') + '>' + escapeHtml(height) + '</td>';
                html += '<td data-label="Time"' + (isEmpty(timeCombined) ? ' data-empty="1"' : '') + '>' + escapeHtml(timeCombined) + '</td>';
                html += '<td data-label="Charge"' + (isEmpty(charge) ? ' data-empty="1"' : '') + '>' + escapeHtml(charge) + '</td>';
                html += '<td data-label="Comments"' + (isEmpty(comments) ? ' data-empty="1"' : '') + '>' + escapeHtml(comments) + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            document.getElementById('flights-section').innerHTML = html;
        }

        function renderSummary(flights, resp) {
            var totalMinutes = 0;
            if (resp && resp.totalAllDuration) {
                var m = resp.totalAllDuration.match(/(\d+)\s*h\s*(\d+)\s*m/);
                if (m) {
                    var totalMinutes = parseInt(m[1]) * 60 + parseInt(m[2]);
                }
            }
            if (!totalMinutes && flights) {
                flights.forEach(function(item) {
                    var d = item.dt;
                    var dur = d[11] || '';
                    var m = dur.match(/(?:(\d+)h\s*)?(\d+)m/);
                    if (m) {
                        totalMinutes += (parseInt(m[1] || 0) * 60 + parseInt(m[2]));
                    }
                });
            }

            var count = flights ? flights.length : 0;
            var hours = Math.floor(totalMinutes / 60);
            var mins = totalMinutes % 60;

            var parts = [];
            parts.push(count + ' flight' + (count !== 1 ? 's' : ''));
            if (totalMinutes > 0) {
                parts.push(hours + 'h ' + (mins < 10 ? '0' : '') + mins + 'm total');
            }
            var html = parts.map(function(p) {
                return '<span class="summary-pill">' + p + '</span>';
            }).join('');
            document.getElementById('summary-inline').innerHTML = html;
        }

        /* Autocomplete */
        var acTimeout = null;
        document.getElementById('member-search').addEventListener('input', function() {
            var val = this.value.trim();
            if (val.length < 2) {
                document.getElementById('filter-member').value = '';
                document.getElementById('member-suggestions').style.display = 'none';
                return;
            }
            clearTimeout(acTimeout);
            acTimeout = setTimeout(function() {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', '/api/member-search?search=' + encodeURIComponent(val), true);
                xhr.onload = function() {
                    if (xhr.status !== 200) return;
                    var data = JSON.parse(xhr.responseText);
                    var list = document.getElementById('member-suggestions');
                    if (!data || data.length === 0) {
                        list.style.display = 'none';
                        return;
                    }
                    list.innerHTML = data.map(function(m, i) {
                        return '<div class="ac-item" data-id="' + m.id + '" data-idx="' + i + '">' + escapeHtml(m.name) + '</div>';
                    }).join('');
                    list.style.display = 'block';
                    list._items = data;
                    list._idx = -1;
                };
                xhr.send();
            }, 200);
        });

        document.addEventListener('mousedown', function(e) {
            var target = e.target;
            if (target.classList.contains('ac-item')) {
                var id = parseInt(target.getAttribute('data-id'), 10);
                var name = target.textContent;
                document.getElementById('filter-member').value = id;
                document.getElementById('member-search').value = name;
                document.getElementById('member-suggestions').style.display = 'none';
                fetchData();
            }
        });

        document.getElementById('member-search').addEventListener('keydown', function(e) {
            var list = document.getElementById('member-suggestions');
            var items = list.querySelectorAll('.ac-item');
            if (list.style.display === 'none' || items.length === 0) return;
            var idx = list._idx || -1;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (idx >= 0) items[idx].classList.remove('selected');
                idx = Math.min(idx + 1, items.length - 1);
                items[idx].classList.add('selected');
                list._idx = idx;
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (idx >= 0) items[idx].classList.remove('selected');
                idx = Math.max(idx - 1, -1);
                if (idx >= 0) items[idx].classList.add('selected');
                list._idx = idx;
            } else if (e.key === 'Enter') {
                if (idx >= 0 && items[idx]) {
                    items[idx].click();
                    e.preventDefault();
                }
            } else if (e.key === 'Escape') {
                list.style.display = 'none';
            }
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.member-wrap')) {
                document.getElementById('member-suggestions').style.display = 'none';
            }
        });

        document.getElementById('apply-filters').addEventListener('click', fetchData);

        document.getElementById('reset-filters').addEventListener('click', function() {
            document.getElementById('fromdate').value = '<?php echo $firstOfMonth; ?>';
            document.getElementById('todate').value = '<?php echo $today; ?>';
            document.getElementById('filter-member').value = '';
            document.getElementById('member-search').value = '';
            document.getElementById('member-suggestions').style.display = 'none';
            fetchData();
        });

        fetchData();
    })();
    </script>
</body>
</html>
