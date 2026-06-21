<?php
require_once __DIR__ . '/../helpers/api-base.php';

session_start();

require_once __DIR__ . '/../helpers/logging.php';
require_once __DIR__ . '/../helpers/permissions.php';
logMsg("START");

require_perm('flights.list');
logMsg("AUTH OK - memberid=" . $_SESSION['memberid']);

$org = isset($_SESSION['org']) ? intval($_SESSION['org']) : 0;

require_once __DIR__ . '/../helpers/database.php';
$con = open_gliding_db();
if (mysqli_connect_errno()) {
    logMsg("DB CONNECTION FAILED: " . mysqli_connect_error());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    apiExit($con);
}

$draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$length = isset($_GET['length']) ? intval($_GET['length']) : 50;

$fromdate = isset($_GET['fromdate']) ? $_GET['fromdate'] : '';
$todate = isset($_GET['todate']) ? $_GET['todate'] : '';

$fromdateInt = '';
$todateInt = '';
if ($fromdate) {
    $fromdateInt = substr($fromdate, 0, 4) . substr($fromdate, 5, 2) . substr($fromdate, 8, 2);
}
if ($todate) {
    $todateInt = substr($todate, 0, 4) . substr($todate, 5, 2) . substr($todate, 8, 2);
}

$memberId = isset($_GET['memberId']) ? intval($_GET['memberId']) : 0;

$searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

$orderColumn = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
$orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

$columns = [
    'flights.localdate',
    'flights.seq',
    'flights.location',
    'launchtypes.name',
    'towplanes.rego_short',
    'flights.glider',
    'towpilots.displayname',
    'pics.displayname',
    'p2s.displayname',
    'flights.start',
    'flights.land',
    null,
    'flights.height',
    'billingoptions.name',
    'flights.comments',
    'flights.finalised'
];

$baseWhere = "WHERE flights.deleted <> 1 AND flights.org = " . $org;

$dateClause = '';
if ($fromdateInt) {
    $dateClause .= " AND flights.localdate >= " . intval($fromdateInt);
}
if ($todateInt) {
    $dateClause .= " AND flights.localdate <= " . intval($todateInt);
}

$memberClause = '';
if ($memberId) {
    $memberClause = " AND (flights.pic = $memberId OR flights.p2 = $memberId)";
}

$searchClause = '';
$searchParams = [];
if ($searchValue) {
    $escaped = mysqli_real_escape_string($con, $searchValue);
    $searchClause = " AND (flights.glider LIKE '%$escaped%'
        OR towplanes.rego_short LIKE '%$escaped%'
        OR towpilots.displayname LIKE '%$escaped%'
        OR pics.displayname LIKE '%$escaped%'
        OR p2s.displayname LIKE '%$escaped%'
        OR launchtypes.name LIKE '%$escaped%'
        OR flights.location LIKE '%$escaped%'
        OR billingoptions.name LIKE '%$escaped%'
        OR flights.comments LIKE '%$escaped%'
        OR flights.seq LIKE '%$escaped%')";
}

$orderClause = '';
if (isset($columns[$orderColumn]) && $columns[$orderColumn] !== null) {
    $dir = ($orderDir === 'asc') ? 'ASC' : 'DESC';
    $orderClause = "ORDER BY " . $columns[$orderColumn] . " " . $dir;
} else {
    $orderClause = "ORDER BY flights.localdate DESC, flights.seq DESC";
}

$countQuery = "SELECT COUNT(*) AS total FROM flights
    LEFT JOIN launchtypes ON launchtypes.id = flights.launchtype
    LEFT JOIN aircraft AS towplanes ON towplanes.id = flights.towplane
    LEFT JOIN members AS towpilots ON towpilots.id = flights.towpilot
    LEFT JOIN members AS pics ON pics.id = flights.pic
    LEFT JOIN members AS p2s ON p2s.id = flights.p2
    LEFT JOIN billingoptions ON billingoptions.id = flights.billing_option
    $baseWhere $dateClause $memberClause $searchClause";

$countResult = mysqli_query($con, $countQuery);
if (!$countResult) {
    logMsg("COUNT QUERY FAILED: " . mysqli_error($con));
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database query failed']);
    apiExit($con);
}
$totalCount = 0;
$filteredCount = 0;
if ($row = mysqli_fetch_array($countResult)) {
    $totalCount = intval($row['total']);
    $filteredCount = $totalCount;
}

if ($searchValue) {
    $totalQuery = "SELECT COUNT(*) AS total FROM flights
        LEFT JOIN launchtypes ON launchtypes.id = flights.launchtype
        LEFT JOIN aircraft AS towplanes ON towplanes.id = flights.towplane
        LEFT JOIN members AS towpilots ON towpilots.id = flights.towpilot
        LEFT JOIN members AS pics ON pics.id = flights.pic
        LEFT JOIN members AS p2s ON p2s.id = flights.p2
        LEFT JOIN billingoptions ON billingoptions.id = flights.billing_option
        $baseWhere $dateClause $memberClause";
    $totalResult = mysqli_query($con, $totalQuery);
    if ($totalResult && ($row = mysqli_fetch_array($totalResult))) {
        $totalCount = intval($row['total']);
    }
}

$dataQuery = "SELECT flights.id, flights.localdate, flights.seq, flights.location,
    flights.glider, flights.start, flights.land, flights.towland,
    flights.height, flights.comments, flights.finalised,
    flights.type, flights.launchtype AS launchtype_id,
    launchtypes.name AS launchtype_name,
    towplanes.rego_short AS towplane_rego,
    towpilots.displayname AS towpilot_name,
    pics.displayname AS pic_name,
    p2s.displayname AS p2_name,
    billingoptions.name AS billingoption_name
    FROM flights
    LEFT JOIN launchtypes ON launchtypes.id = flights.launchtype
    LEFT JOIN aircraft AS towplanes ON towplanes.id = flights.towplane
    LEFT JOIN members AS towpilots ON towpilots.id = flights.towpilot
    LEFT JOIN members AS pics ON pics.id = flights.pic
    LEFT JOIN members AS p2s ON p2s.id = flights.p2
    LEFT JOIN billingoptions ON billingoptions.id = flights.billing_option
    $baseWhere $dateClause $memberClause $searchClause
    $orderClause
    LIMIT " . intval($start) . ", " . intval($length);

$dataResult = mysqli_query($con, $dataQuery);
if (!$dataResult) {
    logMsg("DATA QUERY FAILED: " . mysqli_error($con));
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database query failed']);
    apiExit($con);
}

$timezone = isset($_SESSION['timezone']) ? $_SESSION['timezone'] : 'Pacific/Auckland';
$tz = new DateTimeZone($timezone);

$totalAllDurationQuery = "SELECT SUM(flights.land - flights.start) AS total FROM flights
    LEFT JOIN launchtypes ON launchtypes.id = flights.launchtype
    LEFT JOIN aircraft AS towplanes ON towplanes.id = flights.towplane
    LEFT JOIN members AS towpilots ON towpilots.id = flights.towpilot
    LEFT JOIN members AS pics ON pics.id = flights.pic
    LEFT JOIN members AS p2s ON p2s.id = flights.p2
    LEFT JOIN billingoptions ON billingoptions.id = flights.billing_option
    $baseWhere $dateClause $memberClause $searchClause";

$totalAllResult = mysqli_query($con, $totalAllDurationQuery);
$totalAllMs = 0;
if ($totalAllResult && ($row = mysqli_fetch_array($totalAllResult))) {
    $totalAllMs = intval($row['total']);
}

function formatTime($ms, $tz) {
    if (!$ms || $ms <= 0) return '';
    $sec = intval($ms / 1000);
    try {
        $dt = new DateTime('@' . $sec);
        $dt->setTimezone($tz);
        return $dt->format('H:i');
    } catch (Exception $e) {
        return '';
    }
}

function formatDate($intDate, $tz) {
    if (!$intDate) return '';
    $s = strval($intDate);
    if (strlen($s) !== 8) return $s;
    return substr($s, 6, 2) . '/' . substr($s, 4, 2) . '/' . substr($s, 0, 4);
}

function formatDuration($ms) {
    if (!$ms || $ms <= 0) return '';
    $minutes = floor($ms / 60000);
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    if ($hours > 0) return $hours . 'h ' . str_pad($mins, 2, '0', STR_PAD_LEFT) . 'm';
    return $mins . 'm';
}

$data = [];
$gliderTotal = 0;
while ($row = mysqli_fetch_assoc($dataResult)) {
    $duration = ($row['land'] && $row['start']) ? ($row['land'] - $row['start']) : 0;
    $gliderTotal += $duration;

    $rowData = [
        formatDate($row['localdate'], $tz),
        intval($row['seq']),
        htmlspecialchars($row['location'] ?? ''),
        htmlspecialchars($row['launchtype_name'] ?? ''),
        htmlspecialchars($row['towplane_rego'] ?? ''),
        htmlspecialchars($row['glider'] ?? ''),
        htmlspecialchars($row['towpilot_name'] ?? ''),
        htmlspecialchars($row['pic_name'] ?? ''),
        htmlspecialchars($row['p2_name'] ?? ''),
        formatTime($row['start'], $tz),
        formatTime($row['land'], $tz),
        formatDuration($duration),
        ($row['launchtype_id'] == 1 && $row['type'] == 1) ? intval($row['height']) : '',
        htmlspecialchars($row['billingoption_name'] ?? ''),
        htmlspecialchars($row['comments'] ?? ''),
        $row['finalised'] ? 'YES' : 'NO'
    ];

    $data[] = [
        'dt' => $rowData,
        'finalised' => $row['finalised'] ? true : false,
        'id' => intval($row['id'])
    ];
}

$gliderTotalMinutes = $gliderTotal > 0 ? floor($gliderTotal / 60000) : 0;
$gliderHours = floor($gliderTotalMinutes / 60);
$gliderMins = $gliderTotalMinutes % 60;
$totalDurationStr = $gliderHours . 'h ' . str_pad($gliderMins, 2, '0', STR_PAD_LEFT) . 'm';

$totalAllMinutes = $totalAllMs > 0 ? floor($totalAllMs / 60000) : 0;
$totalAllHours = floor($totalAllMinutes / 60);
$totalAllMins = $totalAllMinutes % 60;
$totalAllDurationStr = $totalAllHours . 'h ' . str_pad($totalAllMins, 2, '0', STR_PAD_LEFT) . 'm';

header('Content-Type: application/json');
echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $totalCount,
    'recordsFiltered' => $filteredCount,
    'data' => $data,
    'totalDuration' => $totalDurationStr,
    'totalAllDuration' => $totalAllDurationStr,
    'totalCount' => count($data)
]);

apiExit($con);