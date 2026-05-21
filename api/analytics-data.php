<?php
require_once __DIR__ . '/../helpers/api-base.php';

session_start();

require_once __DIR__ . '/../helpers/logging.php';

logMsg("START method=" . $_SERVER['REQUEST_METHOD']);

if (!isset($_SESSION['security']) || $_SESSION['security'] < 1) {
    http_response_code(401);
    logMsg("AUTH FAIL");
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    apiExit($con);
}

logMsg("AUTH OK - memberid=" . ($_SESSION['memberid'] ?? 'none'));

$org = isset($_SESSION['org']) ? intval($_SESSION['org']) : 0;
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$compare = isset($_GET['compare']) ? intval($_GET['compare']) : 0;

require_once __DIR__ . '/../config/database.php';

$db_params = require __DIR__ . '/../config/database.php';
$con = mysqli_connect($db_params['gliding']['hostname'], $db_params['gliding']['username'], $db_params['gliding']['password'], $db_params['gliding']['dbname']);
if (mysqli_connect_errno()) {
    http_response_code(500);
    logMsg("DB CONNECTION FAILED: " . mysqli_connect_error());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection error']);
    apiExit($con);
}

function getYearData($con, $org, $year) {
    $startDate = $year * 10000 + 101;
    $endDate = $year * 10000 + 1231;

    $orgWhere = $org > 0 ? "org = $org" : "1=1";

    $monthly = [];
    $sql = "SELECT
              FLOOR(localdate / 100) AS yearmonth,
              COUNT(*) AS total,
              SUM(CASE WHEN p2 IS NULL OR p2 = 0 THEN 1 ELSE 0 END) AS solo,
              SUM(CASE WHEN p2 IS NOT NULL AND p2 > 0 THEN 1 ELSE 0 END) AS dual_flights,
              SUM(CASE WHEN (land - start) > 3600000 THEN 1 ELSE 0 END) AS long_flights,
              AVG((land - start) / 60000) AS avg_duration
            FROM flights
            WHERE $orgWhere AND type = 1 AND deleted = 0
              AND localdate >= $startDate AND localdate <= $endDate
            GROUP BY yearmonth
            ORDER BY yearmonth";
    $r = mysqli_query($con, $sql);
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $row['total'] = intval($row['total']);
            $row['solo'] = intval($row['solo']);
            $row['dual_flights'] = intval($row['dual_flights']);
            $row['long_flights'] = intval($row['long_flights']);
            $row['avg_duration'] = round(floatval($row['avg_duration']), 1);
            $row['yearmonth'] = strval($row['yearmonth']);
            $monthly[] = $row;
        }
    }

    $launchTypes = [];
    $sql = "SELECT launchtype, COUNT(*) AS count
            FROM flights
            WHERE $orgWhere AND type = 1 AND deleted = 0
              AND localdate >= $startDate AND localdate <= $endDate
            GROUP BY launchtype";
    $r = mysqli_query($con, $sql);
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $launchTypes[] = [
                'launchtype' => intval($row['launchtype']),
                'count' => intval($row['count'])
            ];
        }
    }

    $topAircraft = [];
    $sql = "SELECT glider, COUNT(*) AS flights
            FROM flights
            WHERE $orgWhere AND type = 1 AND deleted = 0
              AND localdate >= $startDate AND localdate <= $endDate
              AND glider IS NOT NULL AND glider != ''
            GROUP BY glider
            ORDER BY flights DESC
            LIMIT 10";
    $r = mysqli_query($con, $sql);
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $topAircraft[] = [
                'glider' => $row['glider'],
                'flights' => intval($row['flights'])
            ];
        }
    }

    $totalFlights = 0;
    foreach ($monthly as $m) {
        $totalFlights += $m['total'];
    }

    return [
        'year' => $year,
        'total_flights' => $totalFlights,
        'monthly' => $monthly,
        'launch_types' => $launchTypes,
        'top_aircraft' => $topAircraft
    ];
}

$data = [];
$data['main'] = getYearData($con, $org, $year);
if ($compare > 0 && $compare !== $year) {
    $data['compare'] = getYearData($con, $org, $compare);
}

require_once __DIR__ . '/../helpers.php';
$data['launch_names'] = [
    getTowLaunchType($con) => 'Tow',
    getWinchLaunchType($con) => 'Winch',
    getSelfLaunchType($con) => 'Self Launch'
];

header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $data]);
apiExit($con);
