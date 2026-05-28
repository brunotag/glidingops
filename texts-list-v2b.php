<?php session_start(); ?>
<?php
$org = 0;
if (isset($_SESSION['org'])) {
    $org = intval($_SESSION['org']);
}

if (isset($_SESSION['security'])) {
    if (!($_SESSION['security'] & 4)) {
        die("Security level too low for this page");
    }
} else {
    header('Location: /Login.php');
    die("Please logon");
}

require_once __DIR__ . '/helpers/timehelpers.php';

function timeFormat($dt) {
    if (!$dt || substr($dt, 0, 4) == '0000') {
        return '';
    }
    try {
        $d = new DateTime($dt);
        return timeLocalFormat($d, $_SESSION['timezone'] ?? 'Pacific/Auckland', 'd/m/Y H:i:s');
    } catch (Exception $e) {
        return '';
    }
}
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
    <title>Sent Messages</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <style>
        body { padding: 0; min-height: 100vh; }
        .padding-container { padding: 15px; }
        .controls-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .controls-bar .dataTables_paginate {
            margin-left: auto;
        }
        #record-count {
            color: #666;
            font-size: 14px;
        }
        .dataTables_info {
            display: none !important;
        }
        table.dataTable {
            font-size: 14px;
        }
        th { background-color: #f5f5f5; }
        .status-pending { color: #f0ad4e; }
        .status-sent { color: #5cb85c; }
        .status-error { color: #d9534f; }
        .status-email { color: #5bc9dd; }
        td:first-child { text-align: center; }
        .table > thead > tr > th { border-bottom: 2px solid #ddd; }
        td { vertical-align: middle; }
        .title-row { display: flex; align-items: center; margin-bottom: 10px; }
        .title-row h1 { margin: 0 15px 0 0; font-size:22px; font-weight:600; color:#222; }
        #page-title { font-size:22px; font-weight:600; color:#222; margin: 0; }
    </style>
    <style>
/* --- MOBILE CARD PATTERN (texts-table) --- */
body { min-height: 100vh; }

@media (max-width: 767px) {
    #texts-table.table thead { display: none; }
    #texts-table.table { display: block; }
    #texts-table.table tbody { display: flex; flex-wrap: wrap; gap: 6px; }
    #texts-table.table tr {
        width: calc(50% - 3px);
        min-width: 240px; flex: 1 1 auto;
        border: 1px solid #ddd; border-radius: 6px;
        padding: 5px 8px; background: #fff; box-sizing: border-box;
    }
    #texts-table.table > tbody > tr > td {
        display: block; border: none; padding: 2px 2px 2px 44%;
        text-align: left !important; font-size: 13px; position: relative;
        line-height: 1.35; overflow-wrap: break-word; word-break: break-word;
    }
    #texts-table.table td::before {
        content: attr(data-label); position: absolute; left: 4px;
        width: calc(44% - 12px); overflow: hidden; text-overflow: ellipsis;
        white-space: nowrap; font-weight: 600; font-size: 12px; color: #555;
        line-height: 1.35;
    }
    #texts-table.table td[data-empty="1"] { display: none; }
    #texts-table.table .text-right { text-align: left !important; }
    #texts-table.table .hide-mobile { display: none !important; }
    #texts-table.table .show-mobile { display: block !important; }
    .padding-container { padding: 8px; }
    .title-row { flex-wrap: wrap; gap: 5px; margin-bottom: 5px; }
    .title-row h1 { font-size: 18px; margin-bottom: 0; }
    .controls-bar { padding: 6px 8px; gap: 6px; margin-bottom: 8px; font-size: 12px; }
    #record-count { font-size: 11px; }
}

@media (max-width: 580px) {
    #texts-table.table tbody { flex-direction: column; gap: 8px; }
    #texts-table.table tr { width: 100%; min-width: 0; }
    #texts-table.table > tbody > tr > td:last-child { padding-bottom: 8px; }
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
    <h1 id="page-title" style="margin:0">Sent Messages</h1>

</div>

<div id="message-area"></div>

<div class="controls-bar">
    <label for="dt-search">Search:</label>
    <input type="text" id="dt-search" placeholder="Message, member, email..." style="padding: 6px; border: 1px solid #ccc; border-radius: 3px; width: 500px;">
    <span id="record-count">Loading...</span>
</div>

<table id="texts-table" class="table table-striped table-bordered" style="width:100%">
    <thead>
        <tr>
            <th style="width:60px;">ID</th>
            <th>Message</th>
            <th>Member</th>
            <th>Email</th>
            <th style="width:100px;">Status</th>
            <th style="width:150px;">Sent</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    var org = <?php echo $org; ?>;
    var table;

    function buildDataTable() {
        if ($.fn.DataTable.isDataTable('#texts-table')) {
            $('#texts-table').DataTable().destroy();
            $('#texts-table').empty();
        }

        $('.controls-bar .dataTables_paginate').remove();

        var searchVal = $('#dt-search').val() || '';
        var lengthVal = parseInt($('#dt-length').val()) || 50;

        table = $('#texts-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/api/texts?org=' + org,
                type: 'GET',
                xhrFields: { withCredentials: true },
                data: function(d) {
                    d['search[value]'] = searchVal;
                    d['length'] = lengthVal;
                    return d;
                },
                dataSrc: function(json) {
                    $('#record-count').text('Showing ' + json.recordsFiltered + ' messages');
                    return json.data;
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTables error:', error, thrown);
                    $('#record-count').text('Error loading data');
                }
            },
            columns: [
                { data: 'id', title: 'ID' },
                { data: 'message', title: 'Message', render: function(data) {
                        var encoded = btoa(unescape(encodeURIComponent(data)));
                        return '<span class="clickable-msg" data-full="' + encoded + '" style="cursor:pointer;color:#337ab7;" title="Click to filter">' + data + '</span>';
                    } },
                { data: 'member', title: 'Member' },
                { data: 'email', title: 'Email' },
                {
                    data: 'status_label',
                    title: 'Status',
                    render: function(data, type, row) {
                        var statusClass = 'status-email';
                        if (row.status === 0) statusClass = 'status-pending';
                        else if (row.status === 1) statusClass = 'status-sent';
                        else if (row.status === 2) statusClass = 'status-error';
                        return '<span class="' + statusClass + '">' + data + '</span>';
                    }
                },
                {
                    data: 'msg_created',
                    title: 'Sent',
                    render: function(data) {
                        if (!data) return '';
                        try {
                            var d = new Date(data.replace(' ', 'T'));
                            if (isNaN(d.getTime())) return data;
                            var day = ('0' + d.getDate()).slice(-2);
                            var month = ('0' + (d.getMonth() + 1)).slice(-2);
                            var year = d.getFullYear();
                            var hours = ('0' + d.getHours()).slice(-2);
                            var mins = ('0' + d.getMinutes()).slice(-2);
                            var secs = ('0' + d.getSeconds()).slice(-2);
                            return day + '/' + month + '/' + year + ' ' + hours + ':' + mins + ':' + secs;
                        } catch(e) { return data; }
                    }
                }
            ],
            order: [[5, 'desc']],
            lengthMenu: [[25, 50, 100], ['25', '50', '100']],
            pageLength: 50,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ messages",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            searching: false,
            lengthChange: false,
            dom: '<"top"f>t<"bottom"ip>',
            createdRow: function(row, data, dataIndex) {
                var headers = $('#texts-table thead th');
                $(row).children('td').each(function(i) {
                    var label = $(headers[i]).text().trim();
                    $(this).attr('data-label', label);
                    if ($(this).text().trim() === '' && !$(this).find('img').length) {
                        $(this).attr('data-empty', '1');
                    }
                });
            },
            initComplete: function(settings, json) {
                var pagination = $('#texts-table_wrapper .dataTables_paginate');
                if (pagination.length) {
                    pagination.detach();
                    $('.controls-bar').append(pagination);
                    pagination.css('margin-left', 'auto');
                }
            }
        });
    }

    try {
        buildDataTable();
    } catch(e) {
        console.error('Initial table build error:', e);
    }

    // Auto-search on type (debounce 500ms after 2+ chars)
    var searchTimeout;
    $('#dt-search').on('keyup', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            buildDataTable();
            return;
        }
        var searchVal = $(this).val() || '';
        clearTimeout(searchTimeout);
        if (searchVal.length >= 2) {
            searchTimeout = setTimeout(function() {
                buildDataTable();
            }, 500);
        }
    });

    // Click on message text to filter
    $(document).on('click', '.clickable-msg', function() {
        var encoded = $(this).data('full');
        var decoded = decodeURIComponent(escape(atob(encoded)));
        // Decode HTML entities
        var temp = document.createElement('textarea');
        temp.innerHTML = decoded;
        decoded = temp.value;
        $('#dt-search').val(decoded);
        buildDataTable();
    });
});
</script>

</body>
</html>