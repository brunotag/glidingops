<?php session_start(); ?>
<?php
$org = 0;
if (isset($_SESSION['org'])) $org = $_SESSION['org'];
if (isset($_SESSION['security'])) {
    if (!($_SESSION['security'] & 1)) {
        die("Security level too low for this page");
    }
} else {
    header('Location: Login.php');
    die("Please logon");
}

// Get current filter values from URL
$filterRoles = isset($_GET['roles']) ? $_GET['roles'] : [];
$filterRolesNone = isset($_GET['roles-none']) ? true : false;
$filterStatuses = isset($_GET['statuses']) ? $_GET['statuses'] : [1]; // default to Active

$organisation = App\Models\Organisation::find($org);
$allRoles = App\Models\Role::all();
$allClasses = $organisation->membershipClasses();
$allStatuses = App\Models\MembershipStatus::all();

if ($filterRolesNone) {
    $filterRoles = null;
}
?>
<!DOCTYPE HTML>
<html style="height: 100%">
<meta name="viewport" content="width=device-width">
<meta name="viewport" content="initial-scale=1.0">
<head>
    <title>Members List (v2a - Legacy Filters)</title>
    <?php include 'jsLibraies.php'; ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
    <style>
        .main-box { height: 100%; display: flex; flex-direction: column; }
        .main-box .content { overflow-y: auto; }
        .filters { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .filterSection { display: inline-block; margin-right: 20px; margin-bottom: 10px; }
        .filterSection label { display: block; font-weight: bold; margin-bottom: 5px; }
        .dataTables_wrapper { margin-top: 20px; }
        th { white-space: nowrap; }
        td { vertical-align: middle !important; }
    </style>
    <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
</head>
<body class="main-box">
<?php $inc = "./orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
<?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>

<h2>Members List - Version A (Legacy Filters)</h2>
<p><a href="members-list-v2b.php?org=<?php echo $org; ?>">Go to Version B (DataTables Search)</a></p>

<div class="filters">
<form id="filter-form" method="get" action="members-list-v2a.php">
    <input type="hidden" name="org" value="<?php echo $org; ?>" />
    
    <div class="filterSection">
        <div>
            <input type="checkbox" id='roles-none' name='roles-none' <?php echo ($filterRolesNone) ? 'checked' : ''; ?>/>
            <label for="roles-none" style="display:inline;">Members with no roles</label>
        </div>
        <select multiple name="roles[]" id="select-roles" class="selectpicker" <?php echo ($filterRolesNone) ? 'disabled' : ''; ?>>
            <?php foreach ($allRoles as $role): ?>
                <option value="<?php echo $role->id; ?>" <?php echo ($filterRoles && in_array($role->id, $filterRoles)) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($role->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="filterSection">
        <label for="selectClasses">Classes</label>
        <select multiple name="classes[]" id="selectClasses" class="selectpicker">
            <?php foreach ($allClasses as $class): ?>
                <option value="<?php echo $class->id; ?>" <?php echo in_array($class->id, [1,2,3,4,6,11,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,29,30,31,32,33,34,35,36,37,38,39,40]) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($class->class); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="filterSection">
        <label for="selectStatuses">Statuses</label>
        <select multiple name="statuses[]" id="selectStatuses" class="selectpicker">
            <?php foreach ($allStatuses as $status): ?>
                <option value="<?php echo $status->id; ?>" <?php echo in_array($status->id, $filterStatuses) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($status->status_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="filterSection">
        <input type="submit" value="Apply Filter" class="btn btn-primary" style="margin-top: 20px;"/>
    </div>
</form>
</div>

<div class="content">
    <table id="members-table" class="table table-striped table-bordered" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Member #</th>
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
</div>

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    // Toggle roles select based on checkbox
    $('#roles-none').change(function() {
        if (this.checked) {
            $('#select-roles').prop('disabled', true).selectpicker('refresh');
        } else {
            $('#select-roles').prop('disabled', false).selectpicker('refresh');
        }
    });
    
    $('#select-roles').change(function() {
        $('#roles-none').prop('checked', false);
    });
    
    // Build filter data from URL params
    var filterRoles = [];
    var filterClasses = [];
    var filterStatuses = [];
    var filterRolesNone = false;
    
    // Get filter params from URL
    var urlParams = new URLSearchParams(window.location.search);
    
    // Roles
    var rolesParam = urlParams.getAll('roles[]');
    if (rolesParam.length > 0) {
        filterRoles = rolesParam;
    }
    
    // Roles None
    if (urlParams.get('roles-none')) {
        filterRolesNone = true;
    }
    
    // Classes
    var classesParam = urlParams.getAll('classes[]');
    if (classesParam.length > 0) {
        filterClasses = classesParam;
    }
    
    // Statuses
    var statusesParam = urlParams.getAll('statuses[]');
    if (statusesParam.length > 0) {
        filterStatuses = statusesParam;
    }
    
    var pageLength = 50;
    
    $('#members-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/api/members?org=<?php echo $org; ?>',
            type: 'GET',
            data: function(d) {
                // Add filter data to request from URL params
                if (filterRoles.length > 0) {
                    filterRoles.forEach(function(r) {
                        d['filter[roles][]'] = r;
                    });
                }
                if (filterRolesNone) {
                    d['filter[roles_none]'] = 1;
                }
                if (filterClasses.length > 0) {
                    filterClasses.forEach(function(c) {
                        d['filter[classes][]'] = c;
                    });
                }
                if (filterStatuses.length > 0) {
                    filterStatuses.forEach(function(s) {
                        d['filter[statuses][]'] = s;
                    });
                }
                return d;
            }
        },
        columns: [
            { data: 'id' },
            { data: 'member_id' },
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
        order: [[4, 'asc']], // Default: surname (column index 4)
        lengthMenu: [[25, 50, 100], [25, 50, 100]],
        pageLength: pageLength,
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
});
</script>

</body>
</html>