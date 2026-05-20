<?php
require_once __DIR__ . '/../helpers/api-base.php';
require_once __DIR__ . '/../helpers.php';

session_start();

if (!isset($_SESSION['memberid'])) {
    apiExitWithError('Not logged in');
}

$org = isset($_SESSION['org']) ? (int)$_SESSION['org'] : 0;
if ($org == 0) {
    apiExitWithError('No organisation');
}

$date = isset($_GET['date']) ? trim($_GET['date']) : '';
if (empty($date)) {
    apiExitWithError('Date is required');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    apiExitWithError('Invalid date format');
}

$dateStr = str_replace('-', '', $date);
logMsg("API daily-flights: date=$date dateStr=$dateStr org=$org");

$con_params = require(__DIR__ . '/../config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);

if (mysqli_connect_errno()) {
    apiExitWithError('Database connection failed');
}

$towluanch = getTowLaunchType($con);
$r = mysqli_query($con, "SELECT id FROM billingoptions where bill_other = 1");
$billother = 9999;
if (mysqli_num_rows($r) > 0) {
    $row = mysqli_fetch_array($r);
    $billother = $row['id'];
}

$sql = "SELECT flights.seq, e.rego_short, flights.glider, a.displayname as towpilot_name,
        b.displayname as pic_name, c.displayname as p2_name,
        (flights.land - flights.start) as duration_ms, flights.height, flights.billing_option,
        d.displayname as billing_name, flights.comments, f.name as launch_name,
        flights.launchtype, flights.location, flights.type, flights.start, flights.land, flights.vector
        FROM flights
        LEFT JOIN members a ON a.id = flights.towpilot
        LEFT JOIN members b ON b.id = flights.pic
        LEFT JOIN members c ON c.id = flights.p2
        LEFT JOIN members d ON d.id = flights.billing_member1
        LEFT JOIN aircraft e ON e.id = flights.towplane
        LEFT JOIN launchtypes f ON f.id = flights.launchtype
        WHERE flights.org = " . $org . " AND flights.localdate = " . $dateStr . "
        ORDER BY flights.seq ASC";

$r = mysqli_query($con, $sql);
if (!$r) {
    mysqli_close($con);
    apiExitWithError('Query failed: ' . mysqli_error($con));
}

logMsg("Query executed, rows: " . mysqli_num_rows($r));

$flights = [];
$location = '';

while ($row = mysqli_fetch_assoc($r)) {
    if ($location === '') $location = $row['location'];

    $durationMs = intval($row['duration_ms']);
    $seconds = intval($durationMs / 1000);
    $hours = intval($seconds / 3600);
    $mins = intval(($seconds % 3600) / 60);
    $duration = sprintf("%02d:%02d", $hours, $mins);

    $billingOpt = $row['billing_option'];
    $billingDisplay = '';
    if ($billingOpt == $billother) {
        $billingDisplay = $row['billing_name'] ?: '';
    } else {
        $q1 = "SELECT name FROM billingoptions where id = " . $billingOpt;
        $r1 = mysqli_query($con, $q1);
        $row2 = mysqli_fetch_array($r1);
        if ($row2) $billingDisplay = $row2['name'];
    }

    $flightType = $row['type'];
    $typeDisplay = $row['comments'] ?: '';
    if ($flightType == getCheckFlightType($con)) {
        $typeDisplay = $typeDisplay ? $typeDisplay . ' - Tow plane check flight' : 'Tow plane check flight';
    } else if ($flightType == getRetrieveFlightType($con)) {
        $typeDisplay = $typeDisplay ? $typeDisplay . ' - Retrieve' : 'Retrieve';
    }

    $towDisplay = ($towluanch == $row['launchtype']) ? ($row['rego_short'] ?: '') : ($row['launch_name'] ?: '');

    $flights[] = [
        'seq' => (int)$row['seq'],
        'towplane' => $towDisplay,
        'glider' => $row['glider'],
        'vector' => $row['vector'],
        'towpilot' => $row['towpilot_name'] ?: '',
        'pic' => $row['pic_name'] ?: '',
        'p2' => $row['p2_name'] ?: '',
        'duration' => $duration,
        'billing' => $billingDisplay,
        'comments' => $typeDisplay,
        'location' => $row['location']
    ];
}

mysqli_close($con);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'flights' => $flights, 'location' => $location]);
apiExit(null);