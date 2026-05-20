<?php session_start(); ?>
<?php
$org = isset($_SESSION['org']) ? (int)$_SESSION['org'] : 0;
if (!isset($_SESSION['security']) || $_SESSION['security'] < 1) {
    header('Location: /Login.php');
    die("Security level too low for this page");
}
require_once __DIR__ . '/helpers/timehelpers.php';
$tzName = orgTimezone(null, $org);
$tz = new DateTimeZone($tzName);
$now = new DateTime('now', $tz);
$todayStr = $now->format('Y-m-d');
?>
<!DOCTYPE HTML>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="utf-8">
<title>Daily Timesheet</title>
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
        min-height: 100vh;
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

    /* Card styling */
    .card {
        margin: 20px 12px;
        padding: 20px;
        border-radius: 6px;
        background: #fff;
        border: 1px solid #ddd;
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

    /* Date/Location headers */
    .date-header {
        font-size: 16px;
        color: #063552;
        margin-bottom: 12px;
    }

    .location-header {
        font-size: 14px;
        color: #666;
        margin-bottom: 16px;
        padding: 8px 12px;
        background: #e8f4f8;
        border-radius: 4px;
    }

    /* Date picker form */
    .form-inline {
        margin-bottom: 16px;
    }

    .form-inline .form-group {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
    }

    .form-inline label {
        font-weight: bold;
        font-size: 14px;
        color: #333;
    }

    .form-inline input[type="date"] {
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 14px;
    }

    .form-inline button {
        padding: 8px 20px;
        font-weight: bold;
        color: #fff;
        background: #063552;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .form-inline button:hover {
        background: #052040;
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
        #flights-section .table thead {
            display: none;
        }

        #flights-section .table {
            display: block;
        }

        #flights-section .table tbody {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        #flights-section .table tr {
            width: calc(50% - 4px);
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 6px 10px;
            background: #fff;
            box-sizing: border-box;
            min-width: 0;
        }

        #flights-section .table > tbody > tr > td {
            display: block;
            border: none;
            padding: 2px 2px 2px 40%;
            text-align: left !important;
            font-size: 13px;
            position: relative;
            line-height: 1.4;
            overflow-wrap: break-word;
            word-break: break-word;
            min-width: 0;
        }

        #flights-section .table td::before {
            content: attr(data-label);
            position: absolute;
            left: 2px;
            font-weight: 600;
            color: #555;
            white-space: nowrap;
        }

        #flights-section .table td[data-empty="1"] {
            display: none;
        }

        #flights-section .show-mobile {
            display: block !important;
        }

        #flights-section .text-right {
            text-align: left !important;
        }

        #flights-section {
            margin: 0 8px;
            padding: 0;
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

        .form-inline .form-group {
            flex-direction: column;
            align-items: stretch;
        }
    }

    /* Small mobile responsive */
    @media (max-width: 440px) {
        #flights-section .table tbody {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding-bottom: 20px;
        }

        #flights-section .table tr {
            width: 100%;
            min-height: auto;
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
            <h1 id="page-title" style="margin: 0;white-space:nowrap;">Daily Timesheet</h1>
            <form class="form-inline no-print" id="date-form" style="margin: 0;">
                <input type="date" id="fmdate" value="<?php echo $todayStr; ?>" style="padding: 4px 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; margin-left: 8px;">
                <button type="submit" name="view" value="View" style="padding: 5px 12px; font-size: 12px; border: 1px solid #bbb; border-radius: 4px; background: #fff; color: #555; cursor: pointer; margin-left: 4px;">View</button>
            </form>
        </div>
        <div class="text-right no-print" style="flex-shrink:0;">
            <button class="btn-outline" onclick="window.print()">Print</button>
        </div>
    </div>

    <div id="loader" class="loader">
        <div class="spinner">Loading...</div>
    </div>

    <div id="content" style="display: none;">
        <div class="row">
            <div id="flights-section"></div>
        </div>
    </div>
</div>

<script>
(function() {
    var currentDate = '<?php echo $todayStr; ?>';

    function render(data) {
        var flights = data.flights || [];
        var html = '';

        if (flights.length === 0) {
            html += '<p style="padding: 20px; text-align: center; color: #666;">No flights found for this date.</p>';
        } else {
            html += '<table class="table table-bordered table-condensed table-striped">';
            html += '<thead><tr>';
            html += '<th>SEQ</th><th class="text-right">Launch</th><th class="text-right">Glider</th><th class="text-right">W. Drv</th><th class="text-right">PIC</th><th class="text-right">P2</th><th class="text-right">Duration</th><th class="text-right">Charge</th><th>Location</th><th>Comments</th>';
            html += '</tr></thead><tbody>';

            flights.forEach(function(f) {
                html += '<tr>';
                html += '<td data-label="SEQ">' + f.seq + '</td>';
                html += '<td data-label="Launch" class="text-right"' + (e(f.towplane) ? ' data-empty="1"' : '') + '>' + escapeHtml(f.towplane) + '</td>';
                html += '<td data-label="Glider" class="text-right"' + (e(f.glider) ? ' data-empty="1"' : '') + '>' + escapeHtml(f.glider) + '</td>';
                html += '<td data-label="W. Drv" class="text-right"' + (e(f.towpilot) ? ' data-empty="1"' : '') + '>' + escapeHtml(f.towpilot) + '</td>';
                html += '<td data-label="PIC" class="text-right"' + (e(f.pic) ? ' data-empty="1"' : '') + '>' + escapeHtml(f.pic) + '</td>';
                html += '<td data-label="P2" class="text-right"' + (e(f.p2) ? ' data-empty="1"' : '') + '>' + escapeHtml(f.p2) + '</td>';
                html += '<td data-label="Duration" class="text-right">' + f.duration + '</td>';
                html += '<td data-label="Charge" class="text-right"' + (e(f.billing) ? ' data-empty="1"' : '') + '>' + escapeHtml(f.billing) + '</td>';
                html += '<td data-label="Location"' + (e(f.location) ? ' data-empty="1"' : '') + '>' + escapeHtml(f.location) + '</td>';
                html += '<td data-label="Comments"' + (e(f.comments) ? ' data-empty="1"' : '') + '>' + escapeHtml(f.comments) + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
        }

        document.getElementById('flights-section').innerHTML = html;
    }

    function e(v) {
        return (v || '').toString().trim() === '';
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function loadFlights(date) {
        currentDate = date;
        document.getElementById('loader').style.display = 'block';
        document.getElementById('content').style.display = 'none';

        fetch('/api/daily-flights?date=' + date + '&_=' + Date.now(), { credentials: 'same-origin' })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                document.getElementById('loader').style.display = 'none';
                document.getElementById('content').style.display = 'block';

                if (!data || !data.success) {
                    document.getElementById('flights-section').innerHTML = '<p style="color:red;padding:20px;">' + escapeHtml(data ? data.error : 'Unknown error') + '</p>';
                    return;
                }

                render(data);
            })
            .catch(function(err) {
                document.getElementById('loader').style.display = 'none';
                document.getElementById('content').style.display = 'block';
                document.getElementById('flights-section').innerHTML = '<p style="color:red;padding:20px;">Error: ' + err.message + '</p>';
            });
    }

    document.getElementById('date-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var date = document.getElementById('fmdate').value;
        if (date) {
            history.pushState(null, '', '?org=<?php echo $org; ?>&date=' + date);
            loadFlights(date);
        }
    });

    var urlParams = new URLSearchParams(window.location.search);
    var paramDate = urlParams.get('date');
    var dateToLoad = paramDate || '<?php echo $todayStr; ?>';
    document.getElementById('fmdate').value = dateToLoad;
    currentDate = dateToLoad;
    loadFlights(dateToLoad);
})();
</script>
</body>
</html>