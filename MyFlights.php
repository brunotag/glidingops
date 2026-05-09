<?php session_start();
$org = isset($_SESSION['org']) ? $_SESSION['org'] : 0;
if (!isset($_SESSION['security']) || $_SESSION['security'] < 1) {
    die("Security level too low for this page");
}
if (!isset($_SESSION['memberid'])) {
    header('Location: Login.php');
    die("Please logon");
}

require_once 'config/database.php';
require_once 'includes/classGlidingDB.php';
require_once 'helpers.php';

$db_params = require 'config/database.php';
$DB = new GlidingDB($db_params['gliding']);

$con = mysqli_connect($db_params['gliding']['hostname'], $db_params['gliding']['username'], $db_params['gliding']['password'], $db_params['gliding']['dbname']);
if (mysqli_connect_errno()) {
    die("Unable to connect to database");
}

$memberid = intval($_SESSION['memberid']);
$towlaunch = getTowLaunchType($con);
$selflaunch = getSelfLaunchType($con);
$winchlaunch = getWinchLaunchType($con);
$flightTypeGlider = getGlidingFlightType($con);
$istowy = IsMemberTowy($con, $memberid);
$memberInstructor = IsMemberInstructor($con, $memberid);

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

$flights = [];
$sql = "SELECT f.localdate, f.glider, f.height, f.pic, f.p2, f.comments, f.launchtype, f.location, f.start, f.land, f.id, f.billing_option,
               a.make_model
        FROM flights f 
        LEFT JOIN aircraft a ON a.rego_short = f.glider AND a.org = f.org
        WHERE f.type = $flightTypeGlider AND (f.pic = $memberid OR f.p2 = $memberid)
        ORDER BY f.localdate, f.seq ASC";

$r = mysqli_query($con, $sql);
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $flights[] = $row;
    }
}

$tows = [];
if ($istowy) {
    $towSql = "SELECT f.localdate, a.rego_short, f.glider, f.height 
               FROM flights f 
               LEFT JOIN aircraft a ON a.id = f.towplane 
               WHERE f.towpilot = $memberid 
               ORDER BY f.localdate, f.seq ASC";
    $tr = mysqli_query($con, $towSql);
    if ($tr) {
        while ($trow = mysqli_fetch_assoc($tr)) {
            $tows[] = $trow;
        }
    }
}

$totMins = 0;
$cntP = $cntP1 = $cntP2 = $cntI = 0;
$totMinsP = $totMinsP1 = $totMinsP2 = $totMinsI = 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Flights</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <style>
        <?php $inc = "./orgs/" . $org . "/heading2.css"; include $inc; ?>
        <?php $inc = "./orgs/" . $org . "/menu1.css"; include $inc; ?>
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; }
        h1, h2 { font-family: Calibri, Arial, Helvetica, sans-serif; }
        .section { margin: 20px 12px; padding: 15px; border-radius: 8px; box-shadow: 5px 5px 10px #888; }
        .flights-section { background-color: #e0e0f0; }
        .summary-section { background-color: #f0f0e0; }
        .table { margin-bottom: 0; }
        .table th, .table td { vertical-align: middle; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .border-top { border-top: 2px solid #000; }
        @media print {
            .no-print { display: none; }
            .section { box-shadow: none; break-inside: avoid; }
            @page { size: landscape; }
        }
    </style>
</head>
<body>
    <?php $inc = "./orgs/" . $org . "/heading2.txt"; include $inc; ?>
    <?php $inc = "./orgs/" . $org . "/menu1.txt"; include $inc; ?>

    <div class="container-fluid">
        <div class="row" style="display: flex; align-items: center;">
            <div class="col-xs-6">
                <h1 style="margin: 0;"><?= htmlspecialchars($dispname) ?>'s Flights</h1>
            </div>
            <div class="col-xs-6 text-right no-print">
                <a href="MyFlightsCSV" class="btn btn-primary">Export CSV</a>
                <button class="btn btn-primary" onclick="window.print()">Print</button>
            </div>
        </div>

        <?php if (empty($flights)): ?>
            <p>No flights found.</p>
        <?php else: ?>
        <div class="section flights-section">
            <div class="table-responsive">
                <table class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-right">Glider</th>
                            <th class="text-right">Make/Model</th>
                            <th>Location</th>
                            <th class="text-right">Duration</th>
                            <th class="text-right">Start</th>
                            <th class="text-right">Land</th>
                            <th class="text-right">Tow Height</th>
                            <th class="text-right">Launch</th>
                            <th class="text-right">Type</th>
                            <th>Comments</th>
                            <th class="text-center">Track</th>
                            <th class="text-center">IGC</th>
                            <th class="text-center">CSV</th>
                            <th>Charging</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($flights as $idx => $row): ?>
                            <?php
                            $durationMs = intval($row['land'] - $row['start']);
                            $duration = '';
                            if ($durationMs >= 0) {
                                $hours = intval($durationMs / 3600000);
                                $mins = intval(($durationMs % 3600000) / 60000);
                                $duration = sprintf("%02d:%02d", $hours, $mins);
                                $totMins += ($hours * 60) + $mins;
                            }

                            $type = '';
                            $isP1 = intval($row['pic']) === $memberid;
                            $isP2 = intval($row['p2']) === $memberid;
                            
                            if ($isP1 && (empty($row['p2']) || intval($row['p2']) === 0)) {
                                $type = 'P';
                                $totMinsP += ($hours ?? 0) * 60 + ($mins ?? 0);
                                $cntP++;
                            } elseif ($isP1) {
                                if ($memberInstructor) {
                                    $type = 'I';
                                    $cntI++;
                                    $totMinsI += ($hours ?? 0) * 60 + ($mins ?? 0);
                                } else {
                                    $type = 'P1';
                                    $cntP1++;
                                    $totMinsP1 += ($hours ?? 0) * 60 + ($mins ?? 0);
                                }
                            } elseif ($isP2 && (empty($row['pic']) || intval($row['pic']) === 0)) {
                                $type = 'P';
                                $totMinsP += ($hours ?? 0) * 60 + ($mins ?? 0);
                                $cntP++;
                            } else {
                                $type = 'P2';
                                $cntP2++;
                                $totMinsP2 += ($hours ?? 0) * 60 + ($mins ?? 0);
                            }

                            $launchType = '';
                            $launchCode = '';
                            $towHeight = '';
                            if (intval($row['launchtype']) === intval($towlaunch)) {
                                $launchType = $row['height'] ?? '';
                                $launchCode = 'A';
                            } elseif (intval($row['launchtype']) === intval($selflaunch)) {
                                $launchType = 'SELF LAUNCH';
                                $launchCode = 'S';
                            } elseif (intval($row['launchtype']) === intval($winchlaunch)) {
                                $launchType = 'WINCH';
                                $launchCode = 'W';
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

                            $trDateStart = (new DateTime())->setTimestamp(intval(floor($row['start'] / 1000)));
                            $trDateLand = (new DateTime())->setTimestamp(intval(floor($row['land'] / 1000)));
                            $hasTracks = $DB->numTracksForFlight($trDateStart, $trDateLand, $row['glider']) > 0;

                            $billingName = isset($billingOptions[$row['billing_option']]) ? htmlspecialchars($billingOptions[$row['billing_option']]) : '';
                            $comments = '';
                            if ($type !== 'P') {
                                $otherId = ($isP1 && !empty($row['p2'])) ? $row['p2'] : $row['pic'];
                                if ($otherId) {
                                    $otherRes = mysqli_query($con, "SELECT displayname FROM members WHERE id = " . intval($otherId));
                                    if ($otherRes && $otherRow = mysqli_fetch_assoc($otherRes)) {
                                        $comments = 'Other POB: ' . htmlspecialchars($otherRow['displayname']) . ' ' . htmlspecialchars($row['comments'] ?? '');
                                    }
                                }
                            } else {
                                $comments = htmlspecialchars($row['comments'] ?? '');
                            }
                            ?>
                            <tr class="<?= ($idx % 2 === 0) ? 'even' : 'odd' ?>">
                                <td><?= substr($row['localdate'], 6, 2) . '/' . substr($row['localdate'], 4, 2) . '/' . substr($row['localdate'], 0, 4) ?></td>
                                <td class="text-right"><?= htmlspecialchars($row['glider']) ?></td>
                                <td class="text-right"><?= htmlspecialchars($row['make_model'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['location']) ?></td>
                                <td class="text-right"><?= $duration ?: 'In Progress' ?></td>
                                <td class="text-right"><?= $startTime ?></td>
                                <td class="text-right"><?= $landTime ?></td>
                                <td class="text-right"><?= htmlspecialchars($launchType) ?></td>
                                <td class="text-right"><?= $launchCode ?></td>
                                <td class="text-right"><?= $type ?></td>
                                <td><?= $comments ?></td>
                                <td class="text-center">
                                    <?php if ($hasTracks): ?>
                                        <a href="MyFlightMap.php?glider=<?= urlencode($row['glider']) ?>&from=<?= $trDateStart->format('Y-m-d H:i:s') ?>&to=<?= $trDateLand->format('Y-m-d H:i:s') ?>&flightid=<?= $row['id'] ?>">MAP</a>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($hasTracks): ?>
                                        <a href="OlcFile.igc?flightid=<?= $row['id'] ?>">IGC</a>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($hasTracks): ?>
                                        <a href="googlemapsgenerate.php?flightid=<?= $row['id'] ?>">CSV</a>
                                    <?php endif; ?>
                                </td>
                                <td><?= $billingName ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($istowy && !empty($tows)): ?>
        <div class="section flights-section">
            <h2>Tows</h2>
            <div class="table-responsive">
                <table class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-right">Plane</th>
                            <th class="text-right">Glider</th>
                            <th class="text-right">Tow Height</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tows as $idx => $row): ?>
                        <tr class="<?= ($idx % 2 === 0) ? 'even' : 'odd' ?>">
                            <td><?= substr($row['localdate'], 6, 2) . '/' . substr($row['localdate'], 4, 2) . '/' . substr($row['localdate'], 0, 4) ?></td>
                            <td class="text-right"><?= htmlspecialchars($row['rego_short'] ?? '') ?></td>
                            <td class="text-right"><?= htmlspecialchars($row['glider']) ?></td>
                            <td class="text-right"><?= htmlspecialchars($row['height']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="section summary-section">
            <h2>Flights Summary</h2>
            <table class="table table-bordered table-condensed" style="max-width: 300px;">
                <tr>
                    <td>I</td>
                    <td class="text-right"><?= $cntI ?></td>
                    <td class="text-right"><?= sprintf("%02d:%02d", intval($totMinsI / 60), $totMinsI % 60) ?></td>
                </tr>
                <tr>
                    <td>P</td>
                    <td class="text-right"><?= $cntP ?></td>
                    <td class="text-right"><?= sprintf("%02d:%02d", intval($totMinsP / 60), $totMinsP % 60) ?></td>
                </tr>
                <tr>
                    <td>P1</td>
                    <td class="text-right"><?= $cntP1 ?></td>
                    <td class="text-right"><?= sprintf("%02d:%02d", intval($totMinsP1 / 60), $totMinsP1 % 60) ?></td>
                </tr>
                <tr>
                    <td>P2</td>
                    <td class="text-right"><?= $cntP2 ?></td>
                    <td class="text-right"><?= sprintf("%02d:%02d", intval($totMinsP2 / 60), $totMinsP2 % 60) ?></td>
                </tr>
                <tr>
                    <td class="border-top">TOTAL</td>
                    <td class="text-right border-top"><?= $cntP + $cntP1 + $cntP2 ?></td>
                    <td class="text-right border-top"><?= sprintf("%02d:%02d", intval($totMins / 60), $totMins % 60) ?></td>
                </tr>
                <?php if ($istowy): ?>
                <tr>
                    <td>TOTAL TOWS</td>
                    <td class="text-right"><?= count($tows) ?></td>
                    <td></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        </div>
</body>
</html>