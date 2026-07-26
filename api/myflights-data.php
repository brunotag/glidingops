<?php
require_once __DIR__ . '/../helpers/api-base.php';

session_start();

require_once __DIR__ . '/../helpers/logging.php';
require_once __DIR__ . '/../helpers/permissions.php';

logMsg("START");

require_perm('my-flights.view');
if (!isset($_SESSION['memberid'])) {
    http_response_code(401);
    logMsg("AUTH FAIL - no memberid");
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    apiExit();
}

logMsg("AUTH OK - memberid=" . $_SESSION['memberid']);

$memberid = intval($_SESSION['memberid']);
$org = isset($_SESSION['org']) ? $_SESSION['org'] : 0;

require_once __DIR__ . '/../helpers/database.php';
require_once '../helpers.php';

$con = open_gliding_db();
if (mysqli_connect_errno()) {
    http_response_code(500);
    logMsg("DB CONNECTION FAILED: " . mysqli_connect_error());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection error']);
    apiExit($con);
}

$towlaunch = getTowLaunchType($con);
$selflaunch = getSelfLaunchType($con);
$winchlaunch = getWinchLaunchType($con);
$flightTypeGlider = getGlidingFlightType($con);
$istowy = IsMemberTowy($con, $memberid);
$memberInstructor = IsMemberInstructor($con, $memberid);

$memberRes = mysqli_query($con, "SELECT displayname FROM members WHERE id = " . $memberid);
$dispname = mysqli_fetch_assoc($memberRes)['displayname'] ?? '';

$billingOptions = [];
$rs = mysqli_query($con, "SELECT id, name FROM billingoptions");
while ($bro = mysqli_fetch_assoc($rs)) {
    $billingOptions[$bro['id']] = $bro['name'];
}

$flights = [];
$sql = "SELECT f.localdate, f.glider, f.height, f.pic, f.p2, f.comments, f.launchtype, f.location, f.start, f.land, f.id, f.billing_option,
               a.make_model,
               pic_m.displayname AS pic_name, p2_m.displayname AS p2_name
        FROM flights f 
        LEFT JOIN aircraft a ON a.rego_short = f.glider AND a.org = f.org
        LEFT JOIN members pic_m ON pic_m.id = f.pic
        LEFT JOIN members p2_m ON p2_m.id = f.p2
        WHERE f.type = " . intval($flightTypeGlider) . " AND (f.pic = " . intval($memberid) . " OR f.p2 = " . intval($memberid) . ")
        ORDER BY f.localdate, f.seq ASC";

$r = mysqli_query($con, $sql);
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $startDt = (new DateTime())->setTimestamp(intval(floor($row['start'] / 1000)));
        $landDt = (new DateTime())->setTimestamp(intval(floor($row['land'] / 1000)));
        $g = mysqli_real_escape_string($con, $row['glider']);
        $startStr = $startDt->format('Y-m-d H:i:s');
        $landStr = $landDt->format('Y-m-d H:i:s');
        $tr = mysqli_query($con, "SELECT 1 FROM tracks WHERE glider = '$g' AND point_time >= '$startStr' AND point_time <= '$landStr' LIMIT 1");
        $row['has_tracks'] = ($tr && mysqli_num_rows($tr) > 0) ? 1 : 0;
        $flights[] = $row;
    }
}

$tows = [];
if ($istowy) {
    $towSql = "SELECT f.localdate, a.rego_short, f.glider, f.height 
               FROM flights f 
               LEFT JOIN aircraft a ON a.id = f.towplane 
               WHERE f.towpilot = " . intval($memberid) . " 
               ORDER BY f.localdate, f.seq ASC";
    $tr = mysqli_query($con, $towSql);
    if ($tr) {
        while ($trow = mysqli_fetch_assoc($tr)) {
            $tows[] = $trow;
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'dispname' => $dispname,
    'istowy' => $istowy,
    'memberInstructor' => $memberInstructor,
    'billingOptions' => $billingOptions,
    'towlaunch' => $towlaunch,
    'selflaunch' => $selflaunch,
    'winchlaunch' => $winchlaunch,
    'flights' => $flights,
    'tows' => $tows
]);
apiExit($con);