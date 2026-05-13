<?php session_start(); ?>
<?php
require_once __DIR__ . '/load_model.php';

$org = 0;
if (isset($_GET['org'])) {
    $org = intval($_GET['org']);
} elseif (isset($_SESSION['org'])) {
    $org = $_SESSION['org'];
}

if (isset($_SESSION['security'])) {
    if (!($_SESSION['security'] & 64)) {
        die("Security level too low for this page");
    }
} else {
    header('Location: /Login.php');
    die("Please logon");
}

$organisation = App\Models\Organisation::find($org);
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width">
<head>
    <title>Users List (v2b - DataTables)</title>
    <?php include 'jsLibraies.php'; ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
    <style>
        body { padding: 0; }
        h2 { margin-bottom: 10px; margin-right: 20px; }
        .title-row { display: flex; align-items: center; margin-bottom: 10px; }
        .nav-links { margin-bottom: 15px; }
        .nav-links a { margin-right: 15px; }

        .controls-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .controls-bar > * {
            margin: 0;
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
        .controls-bar .pagination {
            margin: 0;
        }
        .controls-bar .dataTables_paginate {
            margin: 0;
        }

        #record-count {
            font-size: 12px;
            color: #666;
        }
        .no-padding-container {
            width: 100%;
        }
        .padding-container {
            padding: 15px;
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
    <h2>Users List</h2>
    <a href="users-list.php" class="btn btn-default btn-sm">Old Version</a>
    <a href="/Users" class="btn btn-primary btn-sm">Create New</a>
</div>

<div class="controls-bar">
    <div class="filter-group">
        <label for="dt-search">Search:</label>
        <input type="text" id="dt-search" placeholder="Name, usercode, member..." style="padding: 4px; border: 1px solid #ccc; border-radius: 3px;">
    </div>

    <div class="filter-group">
        <label for="dt-length">Elements per page:</label>
        <select id="dt-length" class="form-control" style="display: inline-block; width: auto; padding: 4px;">
            <option value="25">25</option>
            <option value="50" selected>50</option>
            <option value="100">100</option>
        </select>
    </div>

    <span id="record-count"></span>
</div>

<table id="users-table" class="table table-striped table-bordered" style="width:100%;">
<style>
    #users-table td, #users-table th { vertical-align: middle; }
</style>
<thead>
    <th>Actions</th>
    <th>ID</th>
    <th>Name</th>
    <th>Usercode</th>
    <th>Organisation</th>
    <th>Security Level</th>
    <th>Member</th>
    <th>Force PW Reset</th>
</thead>
<tbody>
    <tr><td colspan="8">Loading...</td></tr>
</tbody>
</table>

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    var org = <?php echo $org; ?>;
    var table;

function buildDataTable() {
        if ($.fn.DataTable.isDataTable('#users-table')) {
            $('#users-table').DataTable().destroy();
            $('#users-table').empty();
        }

        // Remove old pagination from controls-bar before building new table
        $('.controls-bar .dataTables_paginate').remove();

        var searchVal = $('#dt-search').val() || '';
        var lengthVal = parseInt($('#dt-length').val()) || 50;

        table = $('#users-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/api/users',
                type: 'GET',
                xhrFields: { withCredentials: true },
                data: function(d) {
                    d['search[value]'] = searchVal;
                    d['length'] = lengthVal;
                    return d;
                },
                dataSrc: function(json) {
                    $('#record-count').text('Showing ' + json.recordsFiltered + ' users');
                    return json.data;
                }
            },
            columns: [
                {
                    data: 'id',
                    title: 'Actions',
                    render: function(data, type, full) {
                        return '<a href="/Users/' + data + '" class="btn btn-primary btn-xs" style="display:flex;align-items:center;justify-content:center;width:100%;height:40px;box-sizing:border-box;">Edit</a>';
                    },
                    orderable: false,
                    searchable: false,
                    width: '80px'
                },
                { data: 'id' },
                { data: 'name' },
                { data: 'usercode' },
                { data: 'org' },
                { data: 'securitylevel' },
                { data: 'member' },
                { data: 'force_pw_reset', render: function(data) {
                    return data == 1 ? 'Yes' : 'No';
                }}
            ],
            order: [[2, 'asc']],
            lengthMenu: [[25, 50, 100], ['25', '50', '100']],
            pageLength: 50,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ users",
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
            initComplete: function(settings, json) {
                var pagination = $('#users-table_wrapper .dataTables_paginate');
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

    $('#dt-length').on('change', function() {
        buildDataTable();
    });
});
</script>

</div>

</body>
</html>
