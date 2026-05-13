<?php session_start(); ?>
<?php
$org = 0;
if (isset($_SESSION['org'])) {
    $org = intval($_SESSION['org']);
}

if (isset($_SESSION['security'])) {
    if (!($_SESSION['security'] & 1)) {
        die("Security level too low for this page");
    }
} else {
    header('Location: /Login.php');
    die("Please logon");
}

$today = date('Y-m-d');
$thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width">
<head>
    <title>All Flights Report (New)</title>
    <?php include 'jsLibraies.php'; ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <style>
        body { padding: 0; }
        .padding-container { padding: 15px; }
        .title-row { display: flex; align-items: center; margin-bottom: 10px; }
        .title-row h2 { margin: 0 15px 0 0; }
        .nav-links { margin-bottom: 15px; }
        .nav-links a { margin-right: 15px; }
        .controls-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .controls-bar .filter-group {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .controls-bar .filter-group label {
            font-weight: bold;
            font-size: 12px;
            white-space: nowrap;
        }
        .controls-bar .dt-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: auto;
        }
        .controls-bar .dataTables_filter {
            margin: 0;
        }
        .controls-bar .dataTables_length {
            margin: 0;
        }
        .controls-bar .dataTables_filter input {
            margin: 0 0 0 5px;
        }
        .controls-bar .dataTables_info {
            margin: 0;
            font-size: 12px;
        }
        .dataTables_info {
            display: none !important;
        }
        #record-count {
            font-size: 12px;
            color: #666;
        }
        th { background-color: #f5f5f5; }
        td, th { vertical-align: middle; }
        td:first-child { white-space: nowrap; }
        .row-nonfinalised td { background-color: #fffde7 !important; }
        table.dataTable tbody tr.row-nonfinalised td { background-color: #fffde7 !important; }
        .total-row { font-weight: bold; background: #f5f5f5; }
        .total-row td { padding: 8px; border-top: 2px solid #ddd; }
        input[type="date"] {
            padding: 4px 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 14px;
            font-family: inherit;
        }
        .controls-bar .dataTables_paginate {
            margin-left: auto;
        }
        .controls-bar .pagination {
            margin: 0;
        }
        #flights-table_wrapper {
            margin-top: 0;
        }
        #flights-table_wrapper .dataTables_paginate {
            display: inline-block;
        }
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
    </style>
    <style>
    <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
    <?php $inc = "./orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
    </style>
</head>
<body>
<div class="no-padding-container">
<?php $inc = "./orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
<?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>
</div>

<div class="padding-container">
<div class="title-row">
    <h2>All Flights Report</h2>
    <a href="/app/allFlightsReport" class="btn btn-default btn-sm">Old Version</a>
</div>

<div class="controls-bar">
    <div class="filter-group">
        <label for="fromdate">From:</label>
        <input type="date" id="fromdate" value="<?php echo $thirtyDaysAgo; ?>" />
    </div>
    <div class="filter-group">
        <label for="todate">To:</label>
        <input type="date" id="todate" value="<?php echo $today; ?>" />
    </div>
    <button id="apply-filters" class="btn btn-primary btn-sm">View Report</button>
    <button id="reset-filters" class="btn btn-default btn-sm">Today</button>
    <div class="filter-group">
        <label for="member-search">Member:</label>
        <div style="position:relative;">
            <input type="text" id="member-search" placeholder="Type name..." style="padding:4px 8px;border:1px solid #ccc;border-radius:3px;font-size:13px;width:180px;" autocomplete="off" />
            <input type="hidden" id="filter-member" value="" />
            <div id="member-suggestions" class="autocomplete-suggestions" style="display:none;"></div>
        </div>
    </div>
    <span id="record-count"></span>
</div>

<table id="flights-table" class="table table-striped table-bordered" style="width:100%;">
<thead>
    <tr>
        <th>DATE</th>
        <th>SEQ</th>
        <th>LOCATION</th>
        <th>LAUNCH</th>
        <th>TOW</th>
        <th>GLIDER</th>
        <th>TOWY</th>
        <th>PIC</th>
        <th>P2</th>
        <th>TAKE OFF</th>
        <th>LAND</th>
        <th>DUR</th>
        <th>HGT</th>
        <th>CHARGE</th>
        <th>COMMENTS</th>
        <th>FINAL</th>
    </tr>
</thead>
<tbody>
</tbody>
</table>

<div id="totals-row" style="display:none;">
    <table class="table" style="width:100%; margin-top: -1px;">
        <tr class="total-row">
            <td colspan="11" style="text-align: right;">Total Duration:</td>
            <td id="total-duration"></td>
            <td colspan="4"></td>
        </tr>
    </table>
</div>

</div>

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js"></script>
<script>
function escapeHtml(s) {
    if (!s) return s;
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function buildUrl() {
    var fromdate = document.getElementById('fromdate').value;
    var todate = document.getElementById('todate').value;
    var memberId = document.getElementById('filter-member').value;
    var url = '/api/flights-report?fromdate=' + encodeURIComponent(fromdate) + '&todate=' + encodeURIComponent(todate);
    if (memberId) url += '&memberId=' + encodeURIComponent(memberId);
    return url;
}

var table = null;

function loadData() {
    var url = buildUrl();

    if (table) {
        table.ajax.url(url).load();
        return;
    }

    table = $('#flights-table').DataTable({
        processing: true,
        serverSide: true,
        dom: 'tip',
        ajax: {
            url: url,
            dataSrc: function(json) {
                var rows = [];
                json.data.forEach(function(item) {
                    var cls = item.finalised ? '' : 'row-nonfinalised';
                    rows.push({
                        DT_RowClass: cls,
                        cells: item.dt,
                        flightId: item.id
                    });
                });
                json.recordsTotal = json.recordsTotal || 0;
                json.recordsFiltered = json.recordsFiltered || 0;

                var rc = document.getElementById('record-count');
                if (rc) {
                    var pageMin = json.totalDuration || '0h 00m';
                    var totalMin = json.totalAllDuration || '0h 00m';
                    var count = json.data ? json.data.length : 0;
                    var total = json.recordsFiltered || 0;
                    rc.textContent = 'Showing ' + count + ' of ' + total + ' flights | Page: ' + pageMin + ' Total: ' + totalMin;
                }

                return rows;
            }
        },
        columns: [
            { data: 'cells.0' },
            { data: 'cells.1', className: 'text-right' },
            { data: 'cells.2' },
            { data: 'cells.3' },
            { data: 'cells.4' },
            { data: 'cells.5' },
            { data: 'cells.6' },
            { data: 'cells.7' },
            { data: 'cells.8' },
            { data: 'cells.9', className: 'text-right' },
            { data: 'cells.10', className: 'text-right' },
            { data: 'cells.11', className: 'text-right' },
            { data: 'cells.12', className: 'text-right' },
            { data: 'cells.13' },
            { data: 'cells.14' },
            {
                data: 'cells.15',
                className: 'text-center',
                render: function(data, type, row) {
                    if (data === 'YES') return 'YES';
                    return '<span style="color:#d9534f;font-weight:700;">NO</span>';
                }
            }
        ],
        order: [[0, 'desc'], [1, 'desc']],
        pageLength: 50,
        lengthMenu: [[25, 50, 100, 200], [25, 50, 100, 200]],
        searching: false,
        paging: true,
        info: true,
        stateSave: false,
        drawCallback: function() {
            var api = this.api();
            var info = api.page.info();

            var controlsBar = document.querySelector('.controls-bar');
            var paginate = document.querySelector('#flights-table_wrapper .dataTables_paginate');
            if (paginate && controlsBar && paginate.parentNode !== controlsBar) {
                controlsBar.appendChild(paginate);
            }

            document.getElementById('totals-row').style.display = 'none';
        }
    });
}

$('#apply-filters').on('click', loadData);

var selectedMemberId = '';
var acTimeout = null;

$('#member-search').on('input', function() {
    var val = this.value.trim();
    if (val.length < 2) {
        document.getElementById('filter-member').value = '';
        document.getElementById('member-suggestions').style.display = 'none';
        selectedMemberId = '';
        return;
    }
    clearTimeout(acTimeout);
    acTimeout = setTimeout(function() {
        $.get('/api/member-search?search=' + encodeURIComponent(val), function(data) {
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
        });
    }, 200);
});

$(document).on('mousedown', '.ac-item', function() {
    var id = parseInt(this.getAttribute('data-id'), 10);
    var name = this.textContent;
    document.getElementById('filter-member').value = id;
    document.getElementById('member-search').value = name;
    document.getElementById('member-suggestions').style.display = 'none';
    selectedMemberId = id;
    loadData();
});

$('#member-search').on('keydown', function(e) {
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

$(document).on('click', function(e) {
    if (!e.target.closest('.filter-group') || !e.target.closest('#member-search')) {
        document.getElementById('member-suggestions').style.display = 'none';
    }
});

$('#reset-filters').on('click', function() {
    var today = '<?php echo $today; ?>';
    var thirtyAgo = '<?php echo $thirtyDaysAgo; ?>';
    document.getElementById('fromdate').value = thirtyAgo;
    document.getElementById('todate').value = today;
    document.getElementById('filter-member').value = '';
    document.getElementById('member-search').value = '';
    document.getElementById('member-suggestions').style.display = 'none';
    selectedMemberId = '';
    loadData();
});

$(document).ready(function() {
    loadData();
});
</script>
</body>
</html>