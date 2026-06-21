<?php
require_once __DIR__ . '/../helpers/api-base.php';

session_start();

require_once __DIR__ . '/../helpers/logging.php';
require_once __DIR__ . '/../helpers/permissions.php';

logMsg("START method=" . $_SERVER['REQUEST_METHOD']);

if (!isset($_SESSION['userid']) || $_SESSION['userid'] <= 0) {
    http_response_code(401);
    logMsg("AUTH FAIL");
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    apiExit();
}

logMsg("AUTH OK - memberid=" . ($_SESSION['memberid'] ?? 'none'));

$org = isset($_SESSION['org']) ? intval($_SESSION['org']) : 0;
$orgWhere = $org > 0 ? "org = $org" : "1=1";

require_once __DIR__ . '/../helpers/database.php';
$con = open_gliding_db();
if (mysqli_connect_errno()) {
    http_response_code(500);
    logMsg("DB CONNECTION FAILED: " . mysqli_connect_error());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection error']);
    apiExit($con);
}

$allMonthly = [];
$sql = "SELECT
          FLOOR(localdate / 100) AS yearmonth,
          COUNT(*) AS total,
          SUM(CASE WHEN p2 IS NULL OR p2 = 0 THEN 1 ELSE 0 END) AS solo,
          SUM(CASE WHEN p2 IS NOT NULL AND p2 > 0 THEN 1 ELSE 0 END) AS dual_flights,
          SUM(CASE WHEN (land - start) > 3600000 THEN 1 ELSE 0 END) AS long_flights,
          SUM(CASE WHEN (land - start) BETWEEN 1800000 AND 3600000 THEN 1 ELSE 0 END) AS mid_flights,
          SUM(CASE WHEN (land - start) < 1800000 THEN 1 ELSE 0 END) AS short_flights,
          SUM(CASE WHEN billing_option IN (3,4,5) THEN 1 ELSE 0 END) AS trial,
          AVG((land - start) / 60000) AS avg_duration
        FROM flights
        WHERE $orgWhere AND type = 1 AND deleted = 0
          AND localdate >= 20160601
        GROUP BY yearmonth
        ORDER BY yearmonth";
$r = mysqli_query($con, $sql);
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $row['total'] = intval($row['total']);
        $row['solo'] = intval($row['solo']);
        $row['dual_flights'] = intval($row['dual_flights']);
        $row['long_flights'] = intval($row['long_flights']);
        $row['mid_flights'] = intval($row['mid_flights']);
        $row['short_flights'] = intval($row['short_flights']);
        $row['trial'] = intval($row['trial']);
        $row['avg_duration'] = round(floatval($row['avg_duration']), 1);
        $row['yearmonth'] = intval($row['yearmonth']);
        $allMonthly[] = $row;
    }
}

function ymToSeason($yearmonth) {
    $month = $yearmonth % 100;
    $year = intdiv($yearmonth, 100);
    return ($month >= 6) ? $year : $year - 1;
}

$seasonMap = [];
foreach ($allMonthly as $m) {
    $s = ymToSeason($m['yearmonth']);
    if (!isset($seasonMap[$s])) {
        $seasonMap[$s] = [];
    }
    $seasonMap[$s][] = $m;
}

function seasonRangeLabel($year) {
    return $year . '/' . ($year + 1);
}

function seasonPosition($monthNum) {
    $pos = $monthNum - 6;
    if ($pos < 0) $pos += 12;
    return $pos;
}

$seasons = [];
$seasonYears = array_keys($seasonMap);
sort($seasonYears);

foreach ($seasonYears as $sy) {
    $monthlyArr = [];
    for ($pos = 0; $pos < 12; $pos++) {
        $monthlyArr[] = null;
    }
    foreach ($seasonMap[$sy] as $m) {
        $pos = seasonPosition($m['yearmonth'] % 100);
        $monthlyArr[$pos] = $m;
    }

    $totalFlights = 0;
    $totalSolo = 0;
    $totalDual = 0;
    $totalLong = 0;
    $totalMid = 0;
    $totalShort = 0;
    $totalTrial = 0;
    $durSum = 0;
    $durCount = 0;
    foreach ($monthlyArr as $m) {
        if ($m) {
            $totalFlights += $m['total'];
            $totalSolo += $m['solo'];
            $totalDual += $m['dual_flights'];
            $totalLong += $m['long_flights'];
            $totalMid += $m['mid_flights'];
            $totalShort += $m['short_flights'];
            $totalTrial += $m['trial'];
            if ($m['avg_duration'] > 0) {
                $durSum += $m['avg_duration'] * $m['total'];
                $durCount += $m['total'];
            }
        }
    }

    $seasons[] = [
        'year' => $sy,
        'label' => seasonRangeLabel($sy),
        'monthly' => $monthlyArr,
        'totals' => [
            'flights' => $totalFlights,
            'solo' => $totalSolo,
            'dual' => $totalDual,
            'long' => $totalLong,
            'mid' => $totalMid,
            'short' => $totalShort,
            'trial' => $totalTrial,
            'avg_duration' => $durCount > 0 ? round($durSum / $durCount, 1) : 0
        ]
    ];
}

require_once __DIR__ . '/../helpers.php';
$launchNames = [
    getTowLaunchType($con) => 'Tow',
    getWinchLaunchType($con) => 'Winch',
    getSelfLaunchType($con) => 'Self Launch'
];

$launchTrends = [];
foreach ($seasonYears as $sy) {
    $start = $sy * 10000 + 601;
    $end = ($sy + 1) * 10000 + 531;
    $ltSql = "SELECT launchtype, COUNT(*) AS cnt
              FROM flights
              WHERE $orgWhere AND type = 1 AND deleted = 0
                AND localdate >= $start AND localdate <= $end
              GROUP BY launchtype
              ORDER BY launchtype";
    $ltR = mysqli_query($con, $ltSql);
    $ltRow = [];
    if ($ltR) {
        while ($lt = mysqli_fetch_assoc($ltR)) {
            $ltRow[intval($lt['launchtype'])] = intval($lt['cnt']);
        }
    }
    $launchTrends[] = [
        'year' => $sy,
        'label' => seasonRangeLabel($sy),
        'launchtypes' => $ltRow
    ];
}

$data = [
    'seasons' => $seasons,
    'launch_names' => $launchNames,
    'launch_trends' => $launchTrends,
    'season_years' => $seasonYears
];

header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $data]);
apiExit($con);
