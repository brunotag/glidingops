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

if (isset($_SESSION['security'])) {
    if (!($_SESSION['security'] & 1)) {
        die("Security level too low for this page");
    }
} else {
    header('Location: Login.php');
    die("Please logon");
}

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
<meta name="viewport" content="width=device-width">
<head>
    <title>Members List (v2b - DataTables Search)</title>
    <?php include 'jsLibraies.php'; ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <style>
        body { padding: 10px; }
        h2 { margin-bottom: 10px; }
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
        
        /* DataTables button styling */
        .dt-buttons {
            display: inline-block;
            margin-right: 10px;
        }
    </style>
    <style>
    <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
    <?php $inc = "./orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
    </style>
</head>
<body>
<?php $inc = "./orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
<?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>

<h2>Members List - Version B (DataTables with Filters)</h2>
<div class="nav-links">
    <a href="members-list-v2a.php?org=<?php echo $org; ?>">Go to Version A (Legacy Filters)</a>
    <a href="AllMembers">Original Version</a>
</div>

<div class="controls-bar">
    <!-- Filters -->
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
    
    <button id="apply-filters" class="btn btn-primary btn-sm">Apply</button>
    <button id="reset-filters" class="btn btn-default btn-sm">Reset</button>
    
    <!-- DataTables controls will go here via dom -->
    <span id="record-count"></span>
</div>

<table id="members-table" class="table table-striped table-bordered" style="width:100%">
    <thead>
        <tr>
            <th>ID</th>
            <th>First Name</th>
            <th>Surname</th>
            <th>Display Name</th>
            <th>Class</th>
            <th>Status</th>
            <th>Email</th>
            <th>Mobile</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
<script>
$(document).ready(function() {
    var filterClasses = <?php echo json_encode($filterClasses); ?>;
    var filterStatuses = <?php echo json_encode($filterStatuses); ?>;
    var org = <?php echo $org; ?>;
    
    function updateFiltersFromSelect() {
        filterClasses = $('#filter-classes').val() || [];
        filterStatuses = $('#filter-statuses').val() || [];
    }
    
    function rebuildTable() {
        if ($.fn.DataTable.isDataTable('#members-table')) {
            $('#members-table').DataTable().destroy();
            $('#members-table').find('tbody').empty();
        }
        
        $('#members-table').DataTable({
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
                    return d;
                },
                dataSrc: function(json) {
                    $('#record-count').text('Showing ' + json.recordsFiltered + ' of ' + json.recordsTotal + ' members');
                    return json.data;
                }
            },
            columns: [
                { data: 'id' },
                { data: 'firstname' },
                { data: 'surname' },
                { data: 'displayname' },
                { data: 'class' },
                { data: 'status' },
                { data: 'email' },
                { data: 'phone_mobile' },
                { 
                    data: 'edit_url',
                    render: function(data) {
                        return '<a href="' + data + '">Edit</a>';
                    },
                    sortable: false,
                    searchable: false
                }
            ],
            order: [[2, 'asc']], // Default: surname (column index 2)
            lengthMenu: [[25, 50, 100], [25, 50, 100]],
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
            }
        });
        
        // Style the DataTables wrapper controls to be inline with our filters
        $('.dataTables_length').css('display', 'inline-block').css('margin-right', '15px');
        $('.dataTables_filter').css('display', 'inline-block').css('margin-right', '15px');
        $('.dataTables_info').css('display', 'inline-block').css('margin-right', '15px');
        $('.dataTables_paginate').css('display', 'inline-block');
        $('.dataTables_wrapper').css('margin-top', '10px');
    }
    
    // Initial build
    rebuildTable();
    
    // Apply filters button
    $('#apply-filters').click(function() {
        rebuildTable();
    });
    
    // Reset button - defaults: Active status, no Short Term
    $('#reset-filters').click(function() {
        // Reset to defaults: Active status (1), all except Short Term
        var defaultClasses = <?php echo json_encode($defaultClasses); ?>;
        var defaultStatuses = <?php echo json_encode($defaultStatuses); ?>;
        
        $('#filter-classes').selectpicker('val', defaultClasses);
        $('#filter-statuses').selectpicker('val', defaultStatuses);
        
        rebuildTable();
    });
});
</script>

</body>
</html>