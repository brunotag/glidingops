<?php session_start(); ?>
<?php
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
?>
<!DOCTYPE HTML>
<html style="height: 100%">
<meta name="viewport" content="width=device-width">
<meta name="viewport" content="initial-scale=1.0">
<head>
    <title>Members List (v2b - DataTables Search)</title>
    <?php include 'jsLibraies.php'; ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
    <style>
        .main-box { height: 100%; display: flex; flex-direction: column; }
        .main-box .content { overflow-y: auto; padding: 15px; }
        .dataTables_wrapper { margin-top: 20px; }
        th { white-space: nowrap; }
        td { vertical-align: middle !important; }
    </style>
    <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
</head>
<body class="main-box">
<?php $inc = "./orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
<?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>

<h2>Members List - Version B (DataTables Search)</h2>
<p><a href="members-list-v2a.php?org=<?php echo $org; ?>">Go to Version A (Legacy Filters)</a> | <a href="AllMembers">Original Version</a></p>

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
    var pageLength = 50;
    
    $('#members-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/api/members?org=<?php echo $org; ?>',
            type: 'GET',
            xhrFields: { withCredentials: true }
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