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
        body { padding: 0; }
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
        .dataTables_info {
            display: none !important;
        }
        #members-table + .dataTables_wrapper > .dataTables_paginate {
            display: none !important;
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
    <a href="/MembersListOld" class="btn btn-default btn-sm">Old Version</a>
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
    
    <!-- Search and length together -->
    <div class="filter-group">
        <label for="dt-search">Search:</label>
        <input type="text" id="dt-search" placeholder="Name, email, phone..." style="padding: 4px; border: 1px solid #ccc; border-radius: 3px;">
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

<table id="members-table" class="table table-striped table-bordered" style="width:100%">
    <thead>
        <tr>
            <th>ID</th>
            <th>Photo</th>
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
    <tbody>
        <tr><td colspan="10">Loading...</td></tr>
    </tbody>
</table>

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
        // Destroy existing table if any
        if ($.fn.DataTable.isDataTable('#members-table')) {
            try {
                $('#members-table').DataTable().destroy();
            } catch(e) {}
            $('#members-table').empty();
        }
        
        // Rebuild table
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
                { data: 'id' },
                { 
                    data: 'photo_url',
                    render: function(data) {
                        if (data) {
                            return '<img width="40" src="' + data + '" alt="photo">';
                        }
                        return '';
                    },
                    orderable: false,
                    searchable: false,
                    width: '50px'
                },
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
            order: [[3, 'asc']],
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
            // Use dom to hide default top pagination, keep bottom
            dom: '<"top"f>t<"bottom"ip>',
            drawCallback: function(settings) {
                // Move bottom pagination to controls-bar
                var pagination = $('.dataTables_paginate');
                $('.controls-bar').append(pagination);
                pagination.css('margin-left', 'auto');
            }
        });
    }
    
    // Initial build - wrap in try/catch
    try {
        buildDataTable();
    } catch(e) {
        console.error('Initial table build error:', e);
    }
    
    // Search on Enter key
    $('#dt-search').on('keyup', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            buildDataTable();
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