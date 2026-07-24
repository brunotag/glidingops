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
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'season';
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

require_once __DIR__ . '/../helpers/database.php';

$con = open_gliding_db();
if (mysqli_connect_errno()) {
    http_response_code(500);
    logMsg("DB CONNECTION FAILED: " . mysqli_connect_error());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection error']);
    apiExit($con);
}

function seasonRange($seasonYear) {
    $start = $seasonYear * 10000 + 601;
    $end = ($seasonYear + 1) * 10000 + 531;
    return [$start, $end];
}

function ytdRange($seasonYear) {
    $now = getdate();
    $currentMonth = $now['mon'];
    $start = $seasonYear * 10000 + 601;
    $endSeasonYear = ($currentMonth >= 6) ? $seasonYear : $seasonYear + 1;
    $end = $endSeasonYear * 10000 + $currentMonth * 100 + 31;
    return [$start, $end];
}

if ($mode === 'ytd') {
    list($start, $end) = ytdRange($year);
    $label = $year . ' YTD';
} else {
    list($start, $end) = seasonRange($year);
    $label = $year . '/' . ($year + 1) . ' Season';
}

$orgWhere = $org > 0 ? "f.org = $org" : "1=1";

$sql = "SELECT
          f.pic,
          m.displayname,
          COUNT(*) AS flight_count,
          SUM(f.land - f.start) AS total_ms,
          COUNT(DISTINCT f.p2) AS unique_p2s
        FROM flights f
        JOIN members m ON m.id = f.pic
        WHERE $orgWhere AND f.type = 1 AND f.deleted = 0
          AND f.p2 IS NOT NULL AND f.p2 > 0
          AND f.localdate >= $start AND f.localdate <= $end
        GROUP BY f.pic
        ORDER BY flight_count DESC";

$rows = [];
$r = mysqli_query($con, $sql);
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $rows[] = [
            'pic' => intval($row['pic']),
            'displayname' => $row['displayname'],
            'flight_count' => intval($row['flight_count']),
            'total_minutes' => intval($row['total_ms'] / 60000),
            'unique_p2s' => intval($row['unique_p2s'])
        ];
    }
}

$totalPeople = count($rows);
$totalFlights = 0;
$totalMinutes = 0;
$totalUniqueP2s = 0;
foreach ($rows as $r) {
    $totalFlights += $r['flight_count'];
    $totalMinutes += $r['total_minutes'];
    $totalUniqueP2s += $r['unique_p2s'];
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => [
    'rows' => $rows,
    'totals' => [
        'people' => $totalPeople,
        'flights' => $totalFlights,
        'total_hours' => round($totalMinutes / 60, 1),
        'avg_unique_p2s' => $totalPeople > 0 ? round($totalUniqueP2s / $totalPeople, 1) : 0
    ],
    'label' => $label
]]);
apiExit($con);
