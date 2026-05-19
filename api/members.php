<?php
require_once __DIR__ . '/../helpers/api-base.php';

session_start();

require_once __DIR__ . '/../helpers/logging.php';
logMsg("START");

if (!isset($_SESSION['security']) || !($_SESSION['security'] & 1)) {
    logMsg("AUTH FAIL");
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    apiExit($con);
}
logMsg("AUTH OK - memberid=" . $_SESSION['memberid']);

$org = isset($_SESSION['org']) ? $_SESSION['org'] : 0;
if ($org === null) $org = 0;

$con_params = require(__DIR__ . '/../config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect(
    $con_params['hostname'],
    $con_params['username'],
    $con_params['password'],
    $con_params['dbname']
);

if (mysqli_connect_errno()) {
    logMsg("DB CONNECTION FAILED: " . mysqli_connect_error());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    apiExit($con);
}

// DataTables parameters
$draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$length = isset($_GET['length']) ? intval($_GET['length']) : 50;

// Search
$searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

// Order
$orderColumn = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 4; // default: surname
$orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'asc';

// Filter parameters from legacy filters
// Handle both array (filter[roles][]) and single value (filter[roles]) formats
$filterRoles = [];
$filterClasses = [];
$filterStatuses = [];
$filterRolesNone = false;

if (isset($_GET['filter'])) {
    $f = $_GET['filter'];
    
    // Roles
    if (isset($f['roles'])) {
        $filterRoles = is_array($f['roles']) ? array_values($f['roles']) : [$f['roles']];
    }
    
    // Classes  
    if (isset($f['classes'])) {
        $filterClasses = is_array($f['classes']) ? array_values($f['classes']) : [$f['classes']];
    }
    
    // Statuses
    if (isset($f['statuses'])) {
        $filterStatuses = is_array($f['statuses']) ? array_values($f['statuses']) : [$f['statuses']];
    }
    
    // Roles None
    if (isset($f['roles_none'])) {
        $filterRolesNone = true;
    }
}

// Column mapping (DataTables column index -> DB field)
$columns = [
    0 => 'members.id',
    1 => 'members.member_id',
    2 => 'members.firstname',
    3 => 'members.surname',
    4 => 'members.displayname',
    5 => 'membership_class.class',
    6 => 'membership_status.status_name',
    7 => 'members.email',
    8 => 'members.phone_mobile',
    9 => 'users.id'
];

$orderField = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'members.surname';

// Build WHERE clause
$whereConditions = [];
$params = [];
$paramTypes = '';

if ($org > 0) {
    $whereConditions[] = "members.org = ?";
    $params[] = $org;
    $paramTypes .= 'i';
}

if (!empty($searchValue)) {
    $whereConditions[] = "(members.firstname LIKE ? OR members.surname LIKE ? OR members.displayname LIKE ? OR members.email LIKE ? OR members.phone_mobile LIKE ?)";
    $searchParam = '%' . $searchValue . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    $paramTypes .= 'sssss';
}

// Filter by classes
if (!empty($filterClasses)) {
    $placeholders = implode(',', array_fill(0, count($filterClasses), '?'));
    $whereConditions[] = "membership_class.id IN ($placeholders)";
    $params = array_merge($params, array_map('intval', $filterClasses));
    $paramTypes .= str_repeat('i', count($filterClasses));
}

// Filter by statuses
if (!empty($filterStatuses)) {
    $placeholders = implode(',', array_fill(0, count($filterStatuses), '?'));
    $whereConditions[] = "membership_status.id IN ($placeholders)";
    $params = array_merge($params, array_map('intval', $filterStatuses));
    $paramTypes .= str_repeat('i', count($filterStatuses));
}

// Filter by roles (more complex - need subquery)
if (!empty($filterRoles)) {
    $roleIds = implode(',', array_map('intval', $filterRoles));
    $whereConditions[] = "members.id IN (
        SELECT DISTINCT rm.member_id 
        FROM role_member rm 
        WHERE rm.role_id IN ($roleIds)
    )";
}

// Filter for members with NO roles
if ($filterRolesNone) {
    $whereConditions[] = "members.id NOT IN (SELECT DISTINCT member_id FROM role_member)";
}

$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
}

// Get total count (unfiltered)
$totalQuery = "SELECT COUNT(*) as total FROM members";
if ($org > 0) {
    $totalQuery .= " WHERE org = ?";
    $totalParams = [$org];
    $totalTypes = 'i';
} else {
    $totalParams = [];
    $totalTypes = '';
}

$stmt = mysqli_prepare($con, $totalQuery);
if (!empty($totalParams)) {
    mysqli_stmt_bind_param($stmt, $totalTypes, ...$totalParams);
}
mysqli_stmt_execute($stmt);
$totalResult = mysqli_stmt_get_result($stmt);
$totalRow = mysqli_fetch_array($totalResult);
$recordsTotal = $totalRow['total'];

// Get filtered count
$filteredQuery = "SELECT COUNT(*) as filtered 
    FROM members 
    LEFT JOIN membership_class ON membership_class.id = members.class
    LEFT JOIN membership_status ON membership_status.id = members.status
    $whereClause";

$stmt = mysqli_prepare($con, $filteredQuery);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $paramTypes, ...$params);
}
mysqli_stmt_execute($stmt);
$filteredResult = mysqli_stmt_get_result($stmt);
$filteredRow = mysqli_fetch_array($filteredResult);
$recordsFiltered = $filteredRow['filtered'];

// Get data with pagination
$dataQuery = "SELECT 
    members.id,
    members.member_id,
    members.firstname,
    members.surname,
    members.displayname,
    membership_class.class as class_name,
    membership_status.status_name as status_name,
    members.email,
    members.phone_mobile,
    users.id as user_id
FROM members 
LEFT JOIN membership_class ON membership_class.id = members.class
LEFT JOIN membership_status ON membership_status.id = members.status
LEFT JOIN users ON users.member = members.id
$whereClause
ORDER BY $orderField " . strtoupper($orderDir) . "
LIMIT ? OFFSET ?";

$params[] = $length;
$params[] = $start;
$paramTypes .= 'ii';

$stmt = mysqli_prepare($con, $dataQuery);
mysqli_stmt_bind_param($stmt, $paramTypes, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $photoUrl = $row['id'] ? '/img/members/' . $row['id'] . '.jpg' : null;
    
    $data[] = [
        'id' => $row['id'],
        'member_id' => $row['member_id'],
        'firstname' => $row['firstname'],
        'surname' => $row['surname'],
        'displayname' => $row['displayname'],
        'class' => $row['class_name'],
        'status' => $row['status_name'],
        'email' => $row['email'],
        'phone_mobile' => $row['phone_mobile'],
        'photo_url' => $photoUrl,
        'edit_url' => '/MemberNew?id=' . $row['id'],
        'user_id' => $row['user_id']
    ];
}

mysqli_close($con);

header('Content-Type: application/json');
echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
]);
apiExit();