<?php session_start(); ?>
<?php
require_once __DIR__ . '/load_model.php';

// Allow org to be passed via URL for testing, otherwise use session
$org = 0;
if (isset($_GET['org'])) {
    $org = intval($_GET['org']);
} elseif (isset($_SESSION['org'])) {
    $org = $_SESSION['org'];
}

require_once __DIR__ . '/helpers/permissions.php'; require_perm('members.list');

// Get filter options from database
$organisation = App\Models\Organisation::find($org);
$allClasses = $organisation->membershipClasses()->orderBy('class')->get();
$allStatuses = App\Models\MembershipStatus::all();

// Get default filter values from URL or set defaults
$defaultStatuses = [1]; // Active only
$defaultClasses = $allClasses->where('class', '!=', 'Short Term')->pluck('id')->toArray();

$filterStatuses = isset($_GET['statuses']) ? $_GET['statuses'] : $defaultStatuses;
$filterClasses = isset($_GET['classes']) ? $_GET['classes'] : $defaultClasses;
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
    <title>Members List (v2b - DataTables Search)</title>
    <?php include 'jsLibraies.php'; ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <style>
        body { padding: 0; min-height: 100vh; }
        h2 { margin-bottom: 10px; margin-right: 20px; }
        .title-row { display: flex; align-items: center; margin-bottom: 10px; }
        .nav-links { margin-bottom: 15px; }
        .nav-links a { margin-right: 15px; }
        
        /* Combined controls bar - filters, search, length on one row */
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
        .controls-bar .filter-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }
        .controls-bar .filter-actions .search-group {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .controls-bar .selectpicker { 
            max-width: 180px; 
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
        
        /* Record count styling */
        #record-count {
            font-size: 12px;
            color: #666;
        }
        .dataTables_info {
            display: none !important;
        }
        #members-table + .dataTables_wrapper > .dataTables_paginate {
            display: none !important;
        }
        .dataTables_paginate {
            margin: 0;
        }
        .controls-bar .pagination {
            margin: 0;
        }
        
        /* DataTables button styling */
        .dt-buttons {
            display: inline-block;
            margin-right: 10px;
        }
        
        /* Container structure */
        .no-padding-container {
            width: 100%;
        }
        .padding-container {
            padding: 15px;
        }
    </style>
    <style>
/* --- MOBILE CARD PATTERN (members-table) --- */
body { min-height: 100vh; }

@media (max-width: 767px) {
    #members-table.table thead { display: none; }
    #members-table.table { display: block; }
    #members-table.table tbody { display: flex; flex-wrap: wrap; gap: 6px; }
    #members-table.table tr {
        width: calc(50% - 3px);
        min-width: 240px; flex: 1 1 auto;
        border: 1px solid #ddd; border-radius: 6px;
        padding: 5px 8px; background: #fff; box-sizing: border-box;
    }
    #members-table.table > tbody > tr > td {
        display: block; border: none; padding: 2px 2px 2px 44%;
        text-align: left !important; font-size: 13px; position: relative;
        line-height: 1.35; overflow-wrap: break-word; word-break: break-word;
    }
    #members-table.table td::before {
        content: attr(data-label); position: absolute; left: 4px;
        width: calc(44% - 12px); overflow: hidden; text-overflow: ellipsis;
        white-space: nowrap; font-weight: 600; font-size: 12px; color: #555;
        line-height: 1.35;
    }
    #members-table.table td[data-empty="1"] { display: none; }
    #members-table.table td a.btn { height: auto !important; padding: 1px 6px; font-size: 12px; line-height: 1.2; }
    #members-table.table .text-right { text-align: left !important; }
    #members-table.table .hide-mobile { display: none !important; }
    #members-table.table .show-mobile { display: block !important; }
    .padding-container { padding: 8px; }
    .title-row { flex-wrap: wrap; gap: 5px; margin-bottom: 5px; }
    .title-row h2 { font-size: 16px; margin-bottom: 0; }
    .title-row .btn { font-size: 11px; padding: 2px 6px; }
    .controls-bar { padding: 6px 8px; gap: 6px; margin-bottom: 8px; font-size: 12px; }
    .controls-bar .filter-group label { font-size: 11px; }
    .controls-bar input, .controls-bar select { font-size: 12px; }
    .controls-bar .filter-selects { flex: 1 1 100%; display: flex; flex-wrap: wrap; gap: 6px; }
    .controls-bar .filter-actions { flex: 1 1 100%; display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
    .controls-bar .filter-actions .search-group { flex: 1 1 100%; }
    #record-count { font-size: 11px; }
}

@media (max-width: 580px) {
    #members-table.table tbody { flex-direction: column; gap: 8px; }
    #members-table.table tr { width: 100%; min-width: 0; }
    #members-table.table > tbody > tr > td:last-child { padding-bottom: 8px; }
}
    </style>
    <style>
    <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
    <?php $inc = "./orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
    </style>
</head>
<body>
<!-- No padding container for head and menu -->
<div class="no-padding-container">
<?php $inc = "./orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
<?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>
</div>

<!-- Padding container for content -->
<div class="padding-container">
<div class="title-row">
    <h2>Members List</h2>
    <a href="/MemberNew" class="btn btn-primary btn-sm">Create New</a>
</div>

<div class="controls-bar">
    <div class="filter-selects">
        <div class="filter-group">
            <label for="filter-classes">Class:</label>
            <select id="filter-classes" name="classes[]" multiple class="selectpicker" data-live-search="true" data-width="150px">
                <?php foreach ($allClasses as $class): ?>
                    <option value="<?php echo $class->id; ?>" <?php echo in_array($class->id, $filterClasses) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($class->class); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="filter-statuses">Status:</label>
            <select id="filter-statuses" name="statuses[]" multiple class="selectpicker" data-live-search="true" data-width="120px">
                <?php foreach ($allStatuses as $status): ?>
                    <option value="<?php echo $status->id; ?>" <?php echo in_array($status->id, $filterStatuses) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($status->status_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div class="filter-actions">
        <div class="filter-group search-group">
            <label for="dt-search">Search:</label>
            <input type="text" id="dt-search" placeholder="Name, email, phone..." style="padding: 4px; border: 1px solid #ccc; border-radius: 3px;">
        </div>
        <button id="apply-filters" class="btn btn-primary btn-sm">Apply Filters</button>
        <button id="reset-filters" class="btn btn-default btn-sm">Reset</button>
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

<table id="members-table" class="table table-striped table-bordered" style="width:100%;">
<style>
    #members-table td, #members-table th { vertical-align: middle; }
</style>
<thead>
            <th>Actions</th>
            <th>Photo</th>
            <th>First Name</th>
            <th>Surname</th>
            <th>Display Name</th>
            <th>Class</th>
            <th>Status</th>
            <th>Email</th>
            <th>Mobile</th>
            <th>User</th>
        </thead>
    <tbody>
        <tr><td colspan="10">Loading...</td></tr>
    </tbody>
</table>

<!-- Photo Modal -->
<div id="photoModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="photoModalLabel">Member Photo</h4>
            </div>
            <div class="modal-body" style="text-align:center;">
                <img id="modalPhoto" src="" style="max-width:100%;max-height:80vh;">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
<script>
$(document).ready(function() {
    var filterClasses = <?php echo json_encode($filterClasses); ?>;
    var filterStatuses = <?php echo json_encode($filterStatuses); ?>;
    var org = <?php echo $org; ?>;
    var table;
    
    function updateFiltersFromSelect() {
        var classesVal = $('#filter-classes').val();
        var statusesVal = $('#filter-statuses').val();
        filterClasses = classesVal ? classesVal : [];
        filterStatuses = statusesVal ? statusesVal : [];
    }
    
function buildDataTable() {
        if ($.fn.DataTable.isDataTable('#members-table')) {
            $('#members-table').DataTable().destroy();
            $('#members-table').empty();
        }

        // Remove old pagination from controls-bar before building new table
        $('.controls-bar .dataTables_paginate').remove();

        var searchVal = $('#dt-search').val() || '';
        var lengthVal = parseInt($('#dt-length').val()) || 50;

        table = $('#members-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/api/members?org=' + org,
                type: 'GET',
                xhrFields: { withCredentials: true },
                data: function(d) {
                    updateFiltersFromSelect();
                    d['filter[classes]'] = filterClasses;
                    d['filter[statuses]'] = filterStatuses;
                    d['search[value]'] = searchVal;
                    d['length'] = lengthVal;
                    return d;
                },
                dataSrc: function(json) {
                    $('#record-count').text('Showing ' + json.recordsFiltered + ' members (filtered from ' + json.recordsTotal + ' total)');
                    return json.data;
                }
            },
            columns: [
                {
                    data: 'edit_url',
                    title: 'Actions',
                    render: function(data, type, full) {
                        return '<a href="' + data + '" class="btn btn-primary btn-xs" style="display:flex;align-items:center;justify-content:center;width:100%;height:40px;box-sizing:border-box;">Edit</a>';
                    },
                    orderable: false,
                    searchable: false,
                    width: '80px'
                },
                {
                    data: 'photo_url',
                    title: 'Photo',
                    render: function(data, type, full) {
                        if (!data) {
                            return '<img width="60" src="/img/noprofile.png" alt="no photo" style="border-radius:3px;">';
                        }
                        var name = (full.displayname || '').replace(/'/g, "\\'");
                        return '<img width="60" src="' + data + '" alt="photo" style="cursor:pointer;border-radius:3px;" onerror="$(this).attr(\'src\',\'/img/noprofile.png\')" onclick="$(\'#modalPhoto\').attr(\'src\',\'' + data + '\');$(\'#photoModalLabel\').text(\'' + name + '\');$(\'#photoModal\').modal(\'show\');">';
                    },
                    orderable: false,
                    searchable: false,
                    width: '70px'
                },
                { data: 'firstname' },
                { data: 'surname' },
                { data: 'displayname' },
                { data: 'class' },
                { data: 'status' },
                { data: 'email' },
                { data: 'phone_mobile' },
                {
                    data: 'user_id',
                    title: 'User',
                    render: function(data) {
                        if (data) {
                            return '<a href="/Users/' + data + '" class="btn btn-success btn-xs" style="display:flex;align-items:center;justify-content:center;width:100%;height:40px;box-sizing:border-box;">Yes (Edit)</a>';
                        }
                        return '<span style="color:#999;">No</span>';
                    },
                    orderable: true,
                    searchable: false,
                    width: '80px'
                }
            ],
            order: [[2, 'asc']],
            lengthMenu: [[25, 50, 100], ['25', '50', '100']],
            pageLength: 50,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ members",
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
                var headers = $('#members-table thead th');
                $(row).children('td').each(function(i) {
                    var label = $(headers[i]).text().trim();
                    $(this).attr('data-label', label);
                    if ($(this).text().trim() === '' && !$(this).find('img').length) {
                        $(this).attr('data-empty', '1');
                    }
                });
            },
            initComplete: function(settings, json) {
                var pagination = $('#members-table_wrapper .dataTables_paginate');
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

    // Length change
    $('#dt-length').on('change', function() {
        buildDataTable();
    });

    // Apply filters button
    $('#apply-filters').click(function() {
        buildDataTable();
    });

    // Reset button
    $('#reset-filters').click(function() {
        var defaultClasses = <?php echo json_encode($defaultClasses); ?>;
        var defaultStatuses = <?php echo json_encode($defaultStatuses); ?>;

        $('#filter-classes').selectpicker('val', defaultClasses);
        $('#filter-statuses').selectpicker('val', defaultStatuses);
        $('#dt-search').val('');

        buildDataTable();
    });
});
</script>

</div><!-- end padding-container -->

</body>
</html>