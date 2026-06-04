<?php
session_start();

require_once __DIR__ . '/helpers/logging.php';
logMsg("START");

$org = isset($_SESSION['org']) ? $_SESSION['org'] : 0;

require_once __DIR__ . '/helpers/permissions.php'; require_perm('my-flights.export');
if (!isset($_SESSION['memberid'])) {
    logMsg("AUTH FAIL - no memberid");
    header('Location: /Login.php');
    die("Please logon");
}
logMsg("AUTH OK - memberid=" . $_SESSION['memberid']);

$memberid = intval($_SESSION['memberid']);

require_once 'config/database.php';
require_once 'helpers.php';

$db_params = require 'config/database.php';
$con = mysqli_connect($db_params['gliding']['hostname'], $db_params['gliding']['username'], $db_params['gliding']['password'], $db_params['gliding']['dbname']);
if (mysqli_connect_errno()) {
    die("Unable to connect to database");
}

$memberRes = mysqli_query($con, "SELECT members.displayname FROM members WHERE members.id = " . $memberid);
if (!$memberRes || mysqli_num_rows($memberRes) <= 0) {
    die("No member found");
}
$member = mysqli_fetch_assoc($memberRes);
$dispname = $member['displayname'];

$billingOptions = [];
$rs = mysqli_query($con, "SELECT id, name FROM billingoptions");
while ($bro = mysqli_fetch_assoc($rs)) {
    $billingOptions[$bro['id']] = $bro['name'];
}

$towlaunch = getTowLaunchType($con);
$selflaunch = getSelfLaunchType($con);
$winchlaunch = getWinchLaunchType($con);
$flightTypeGlider = getGlidingFlightType($con);

$flights = [];
$sql = "SELECT f.localdate, f.glider, f.height, f.pic, f.p2, f.comments, f.launchtype, f.location, f.start, f.land, f.id, f.billing_option,
               a.make_model
        FROM flights f 
        LEFT JOIN aircraft a ON a.rego_short = f.glider AND a.org = f.org
        WHERE f.type = " . intval($flightTypeGlider) . " AND (f.pic = " . intval($memberid) . " OR f.p2 = " . intval($memberid) . ")
        ORDER BY f.localdate, f.seq ASC";

$r = mysqli_query($con, $sql);
if (!$r) {
    die("Query error: " . mysqli_error($con));
}
while ($row = mysqli_fetch_assoc($r)) {
    $flights[] = $row;
}

if (count($flights) == 0) {
    die("No flights found");
}

$filename = str_replace(' ', '_', $dispname) . '_flights_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

fputcsv($output, ['Date', 'Glider', 'Make/Model', 'Location', 'Duration', 'Start', 'Land', 'Tow Height', 'Launch', 'Type', 'Charging', 'Comments']);

foreach ($flights as $row) {
    $durationMs = intval($row['land'] - $row['start']);
    $duration = '';
    if ($durationMs >= 0) {
        $hours = intval($durationMs / 3600000);
        $mins = intval(($durationMs % 3600000) / 60000);
        $duration = sprintf("%02d:%02d", $hours, $mins);
    }

    $type = '';
    $isP1 = intval($row['pic']) === $memberid;
    $isP2 = intval($row['p2']) === $memberid;
    
    if ($isP1 && (empty($row['p2']) || intval($row['p2']) === 0)) {
        $type = 'P';
    } elseif ($isP1) {
        $type = 'P1';
    } elseif ($isP2 && (empty($row['pic']) || intval($row['pic']) === 0)) {
        $type = 'P';
    } else {
        $type = 'P2';
    }

    $launchType = '';
    if (intval($row['launchtype']) === intval($towlaunch)) {
        $launchType = $row['height'] ?? '';
    } elseif (intval($row['launchtype']) === intval($selflaunch)) {
        $launchType = 'SELF LAUNCH';
    } elseif (intval($row['launchtype']) === intval($winchlaunch)) {
        $launchType = 'WINCH';
    }

    $startTime = '';
    $landTime = '';
    if (!empty($row['start'])) {
        $startTs = intval($row['start'] / 1000);
        $startDt = (new DateTime())->setTimestamp($startTs)->setTimezone(new DateTimeZone('Pacific/Auckland'));
        $startTime = $startDt->format('G:i:s');
    }
    if (!empty($row['land'])) {
        $landTs = intval($row['land'] / 1000);
        $landDt = (new DateTime())->setTimestamp($landTs)->setTimezone(new DateTimeZone('Pacific/Auckland'));
        $landTime = $landDt->format('G:i:s');
    }

    $billingName = isset($billingOptions[$row['billing_option']]) ? $billingOptions[$row['billing_option']] : '';
    $comments = $row['comments'] ?? '';

    $date = substr($row['localdate'], 6, 2) . '/' . substr($row['localdate'], 4, 2) . '/' . substr($row['localdate'], 0, 4);

    fputcsv($output, [$date, $row['glider'], $row['make_model'] ?? '', $row['location'], $duration, $startTime, $landTime, $launchType, $type, $billingName, $comments]);
}

fclose($output);