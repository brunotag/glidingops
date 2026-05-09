<?php
require_once __DIR__ . '/../helpers/api-base.php';

session_start();

require_once __DIR__ . '/../helpers/logging.php';
logMsg("START");

if (!isset($_SESSION['security']) || !($_SESSION['security'] & 64)) {
    logMsg("AUTH FAIL - security=" . ($_SESSION['security'] ?? 'null'));
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    apiExit($con);
}
logMsg("AUTH OK - memberid=" . ($_SESSION['memberid'] ?? 'null'));

$org = isset($_SESSION['org']) ? $_SESSION['org'] : 0;
if ($org === null) $org = 0;

$con_params = require(__DIR__ . '/../config/database.php');
$con_params = $con_params['gliding'];
logMsg("Connecting to DB...");
$con = mysqli_connect(
    $con_params['hostname'],
    $con_params['username'],
    $con_params['password'],
    $con_params['dbname']
);
logMsg("DB connected, checking error: " . mysqli_connect_error());

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
$orderColumn = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 1;
$orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'asc';

// Column mapping (DataTables column index -> DB field)
$columns = [
    0 => 'users.id',
    1 => 'users.name',
    2 => 'users.usercode',
    3 => 'organisations.name',
    4 => 'users.securitylevel',
    5 => 'members.displayname',
    6 => 'users.force_pw_reset'
];

$orderField = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'users.name';

// Build WHERE clause
$whereConditions = [];
$params = [];
$paramTypes = '';

if ($org > 0) {
    $whereConditions[] = "users.org = ?";
    $params[] = $org;
    $paramTypes .= 'i';
}

if (!empty($searchValue)) {
    $whereConditions[] = "(users.name LIKE ? OR users.usercode LIKE ? OR members.displayname LIKE ?)";
    $searchParam = '%' . $searchValue . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    $paramTypes .= 'sss';
}

$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
}

// Get total count (unfiltered)
$totalQuery = "SELECT COUNT(*) as total FROM users";
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
    FROM users
    LEFT JOIN organisations ON organisations.id = users.org
    LEFT JOIN members ON members.id = users.member
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
    users.id,
    users.name,
    users.usercode,
    organisations.name as org_name,
    users.securitylevel,
    members.displayname as member_name,
    users.force_pw_reset
FROM users
LEFT JOIN organisations ON organisations.id = users.org
LEFT JOIN members ON members.id = users.member
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
    $data[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'usercode' => $row['usercode'],
        'org' => $row['org_name'] ?? '',
        'securitylevel' => $row['securitylevel'],
        'member' => $row['member_name'] ?? '',
        'force_pw_reset' => $row['force_pw_reset'],
        'edit_url' => '/Users/' . $row['id']
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
