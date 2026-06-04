<?php
session_start();
require_once __DIR__ . '/../helpers/api-base.php';
require_once __DIR__ . '/../helpers/logging.php';
require_once __DIR__ . '/../helpers/permissions.php';

logMsg("texts API called - userid=" . ($_SESSION['userid'] ?? 'null') . ", memberid=" . ($_SESSION['memberid'] ?? 'null'));

$org = 0;
if (isset($_SESSION['org'])) {
    $org = intval($_SESSION['org']);
}

if (!isset($_SESSION['userid']) || $_SESSION['userid'] <= 0) {
    logMsg("texts API - auth failed");
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    apiExit();
}
if (!has_perm('api.texts')) {
    logMsg("texts API - permission denied");
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    apiExit();
}

logMsg("texts API - auth OK");

$con_params = require(__DIR__ . '/../config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);

if (mysqli_connect_errno()) {
    logMsg("DB CONNECTION FAILED: " . mysqli_connect_error());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    apiExit($con);
}

// DataTables parameters
$draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$length = isset($_GET['length']) ? intval($_GET['length']) : 50;

// Search
$searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

// Order
$orderColumn = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 5;
$orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

// Column mapping (DataTables column index -> DB field)
$columns = [
    0 => 'texts.txt_id',
    1 => 'messages.msg',
    2 => 'members.displayname',
    3 => 'members.email',
    4 => 'texts.txt_status',
    5 => 'messages.create_time'
];

$orderField = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'texts.txt_timestamp_create';

// Status mapping
$statusLabels = [
    0 => 'Pending',
    1 => 'Sent',
    2 => 'Error',
    3 => 'Sent via Email'
];

// Build WHERE clause
$whereConditions = [];
$params = [];
$paramTypes = '';

if (!empty($searchValue)) {
    $whereConditions[] = "(messages.msg LIKE ? OR members.displayname LIKE ? OR members.email LIKE ?)";
    $searchParam = '%' . $searchValue . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    $paramTypes .= 'sss';
}

$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
}

// Get total count (unfiltered)
$totalQuery = "SELECT COUNT(*) as total FROM texts
    INNER JOIN messages ON messages.id = texts.txt_msg_id
    INNER JOIN members ON members.id = texts.txt_member_id";
$stmt = mysqli_prepare($con, $totalQuery);
mysqli_stmt_execute($stmt);
$totalResult = mysqli_stmt_get_result($stmt);
$totalRow = mysqli_fetch_array($totalResult);
$recordsTotal = $totalRow['total'];

// Get filtered count
$filteredQuery = "SELECT COUNT(*) as filtered
    FROM texts
    INNER JOIN messages ON messages.id = texts.txt_msg_id
    INNER JOIN members ON members.id = texts.txt_member_id
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
    texts.txt_id,
    messages.msg,
    members.displayname,
    members.email,
    texts.txt_status,
    texts.txt_timestamp_create,
    messages.create_time as msg_create_time
FROM texts
INNER JOIN messages ON messages.id = texts.txt_msg_id
INNER JOIN members ON members.id = texts.txt_member_id
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
    $status = intval($row['txt_status']);
    $data[] = [
        'id' => $row['txt_id'],
        'message' => $row['msg'] ?? '',
        'member' => $row['displayname'] ?? '',
        'email' => $row['email'] ?? '',
        'status' => $status,
        'status_label' => $statusLabels[$status] ?? 'Unknown',
        'msg_created' => $row['msg_create_time'] ?? ''
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