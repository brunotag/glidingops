<?php session_start(); ?>
<?php $org=0; if(isset($_SESSION['org'])) $org=$_SESSION['org'];?>
<?php include 'helpers.php'; ?>
<?php
require_once __DIR__ . '/helpers/permissions.php';
require_perm('treasurer-report.view');

require_once __DIR__ . '/helpers/billing-calc.php';

function isFirstWinchLaunchForMember($con, $org, $memberId, $localdate, $seq, $winchLaunchId)
{
    $memberId = intval($memberId);
    $seq = intval($seq);
    $winchLaunchId = intval($winchLaunchId);
    $q = "SELECT id FROM flights WHERE org = $org AND localdate = $localdate AND launchtype = $winchLaunchId AND deleted = 0 AND (billing_member1 = $memberId OR billing_member2 = $memberId) AND seq < $seq LIMIT 1";
    $r = mysqli_query($con, $q);
    if (!$r) return true;
    return mysqli_num_rows($r) == 0;
}

function getCSVField($val)
{
    $val = str_replace('"', '""', $val);
    return '"' . $val . '"';
}

// ---------------------------------------------------------------------------
// Month helpers
// ---------------------------------------------------------------------------

$months = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
];

$dateTimeZone = new DateTimeZone($_SESSION['timezone']);
$now = new DateTime('now', $dateTimeZone);
$currentYear = (int)$now->format('Y');
$currentMonth = (int)$now->format('m');

$defaultMonth = $currentMonth;
$defaultYear = $currentYear;

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    $selectedYear = (int)$_POST["year"];
    $selectedMonth = (int)$_POST["month"];
}
else
{
    $selectedYear = $defaultYear;
    $selectedMonth = $defaultMonth;
}

// ---------------------------------------------------------------------------
// CSV Export
// ---------------------------------------------------------------------------

if (isset($_POST['export']))
{
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="BillingReport-' . $selectedYear . '-' . sprintf('%02d', $selectedMonth) . '.csv"');

    $con_params = require('./config/database.php'); $con_params = $con_params['gliding'];
    $con = mysqli_connect($con_params['hostname'],$con_params['username'],$con_params['password'],$con_params['dbname']);
    if (mysqli_connect_errno()) { echo "Error"; exit(); }

    $dateStart = new DateTime();
    $dateEnd = new DateTime();
    $dateStart->setDate($selectedYear, $selectedMonth, 1);
    $m = $selectedMonth + 1;
    $y = $selectedYear;
    if ($m > 12) { $m = 1; $y++; }
    $dateEnd->setDate($y, $m, 1);
    $dateStart2 = $dateStart->format('Ymd');
    $dateEnd2 = $dateEnd->format('Ymd');

    $towLaunch = getTowLaunchType($con);
    $selfLaunch = getSelfLaunchType($con);
    $winchLaunch = getWinchLaunchType($con);
    $gliderType = getGlidingFlightType($con);
    $noChargeId = getNoChargeOpt($con);
    $trialFlightIds = getTrialFlightOpts($con);
    $trialFlightList = implode("','", $trialFlightIds);

    // Header row
    echo "SURNAME,FIRSTNAME,DATE,LOCATION,GLIDER,PIC,P2,LAUNCH,DURATION,BILING_OPTION,GLIDER_CHARGE,LAUNCH_CHARGE,TOTAL,NOTES,SECTION\r\n";

    // --- Trial flights section ---
    $tq = "SELECT f.localdate, f.glider, (f.land-f.start) as duration, f.seq,
                  pic_m.displayname as pic_name, p2_m.displayname as p2_name,
                  f.launchtype, f.location, f.comments, bo.name as billing_opt
           FROM flights f
           LEFT JOIN members pic_m ON pic_m.id = f.pic
           LEFT JOIN members p2_m ON p2_m.id = f.p2
           LEFT JOIN billingoptions bo ON bo.id = f.billing_option
           WHERE f.org = $org AND f.finalised > 0
             AND f.localdate >= '$dateStart2' AND f.localdate < '$dateEnd2'
             AND f.billing_option IN ('$trialFlightList')
           ORDER BY f.localdate, f.seq";
    $tr = mysqli_query($con, $tq);
    while ($row = mysqli_fetch_array($tr))
    {
        $d = substr($row[0], 6, 2) . '/' . substr($row[0], 4, 2) . '/' . substr($row[0], 0, 4);
        $launch = getLaunchLabel($row[6], $towLaunch, $winchLaunch, $selfLaunch);
        $dur = strDuration($row[2]);
        echo getCSVField('Trial') . ',';
        echo getCSVField('') . ',';
        echo getCSVField($d) . ',';
        echo getCSVField($row[7]) . ',';
        echo getCSVField($row[1]) . ',';
        echo getCSVField($row[4]) . ',';
        echo getCSVField($row[5]) . ',';
        echo getCSVField($launch) . ',';
        echo getCSVField($dur) . ',';
        echo getCSVField($row[9]) . ',';
        echo '0.00,0.00,0.00,';
        echo getCSVField($row[8]) . ',';
        echo getCSVField('Trial') . "\r\n";
    }

    // --- Member flights section ---
    $mq = "SELECT DISTINCT m.id, m.surname, m.firstname, m.displayname
           FROM members m
           INNER JOIN flights f ON (f.billing_member1 = m.id OR f.billing_member2 = m.id)
           WHERE f.org = $org AND f.finalised > 0
             AND f.localdate >= '$dateStart2' AND f.localdate < '$dateEnd2'
             AND (f.billing_option IS NULL OR f.billing_option NOT IN ('$trialFlightList'))
           ORDER BY m.surname, m.firstname";
    $mr = mysqli_query($con, $mq);
    while ($member = mysqli_fetch_array($mr))
    {
        $fq = "SELECT f.localdate, f.glider, (f.land-f.start) as duration, f.height, f.seq, f.launchtype, f.towplane,
                      f.location, f.comments, f.billing_option, f.billing_member1, f.billing_member2, f.type,
                      bo.name as billing_opt, bo.bill_pic, bo.bill_p2, bo.bill_other,
                      pic_m.displayname as pic_name, p2_m.displayname as p2_name,
                      a.rego_short, a.club_glider, a.charge_per_minute, a.max_perflight_charge
               FROM flights f
               LEFT JOIN billingoptions bo ON bo.id = f.billing_option
               LEFT JOIN members pic_m ON pic_m.id = f.pic
               LEFT JOIN members p2_m ON p2_m.id = f.p2
               LEFT JOIN aircraft a ON a.rego_short = f.glider AND a.org = f.org
               WHERE f.org = $org AND f.finalised > 0
                 AND f.localdate >= '$dateStart2' AND f.localdate < '$dateEnd2'
                 AND (f.billing_member1 = {$member[0]} OR f.billing_member2 = {$member[0]})
                 AND (f.billing_option IS NULL OR f.billing_option NOT IN ('$trialFlightList'))
               ORDER BY f.localdate, f.seq";
        $fr = mysqli_query($con, $fq);
        while ($flight = mysqli_fetch_array($fr))
        {
            $d = substr($flight[0], 6, 2) . '/' . substr($flight[0], 4, 2) . '/' . substr($flight[0], 0, 4);
            $launch = getLaunchLabel($flight[5], $towLaunch, $winchLaunch, $selfLaunch);
            $dur = strDuration($flight[2]);
            $totMins = max(1, (int)floor($flight[2] / 60000));
            $isCompetition = isCompetitionFlight($flight[5], $towLaunch);
            $is5050 = isFiftyFifty($flight[14], $flight[15]);
            $splitFactor = $is5050 ? 0.5 : 1.0;

            $gliderCharge = calcGliderCharge($flight[20], $flight[19], $totMins, $member[3] ?? '', $flight[21], $flight[22]);
            $gliderCharge = round($gliderCharge * $splitFactor, 2);

            $isFirstWinch = isFirstWinchLaunchForMember($con, $org, $member[0], $flight[0], $flight[4], $winchLaunch);
            $launchCharge = calcLaunchCharge($flight[5], $isFirstWinch, $towLaunch, $winchLaunch, $selfLaunch);
            $launchCharge = round($launchCharge * $splitFactor, 2);

            $total = $isCompetition ? 0.00 : round($gliderCharge + $launchCharge, 2);

            echo getCSVField($member[1]) . ',';
            echo getCSVField($member[2]) . ',';
            echo getCSVField($d) . ',';
            echo getCSVField($flight[7]) . ',';
            echo getCSVField($flight[1]) . ',';
            echo getCSVField($flight[17]) . ',';
            echo getCSVField($flight[18]) . ',';
            echo getCSVField($launch) . ',';
            echo getCSVField($dur) . ',';
            echo getCSVField($flight[13]) . ',';
            echo sprintf('%.2f', $gliderCharge) . ',';
            echo sprintf('%.2f', $launchCharge) . ',';
            echo sprintf('%.2f', $total) . ',';
            $notes = $flight[8];
            if ($isCompetition) $notes = ($notes ? $notes . '; ' : '') . 'Competition - tow separate';
            echo getCSVField($notes) . ',';
            echo getCSVField($isCompetition ? 'Competition' : 'Member') . "\r\n";
        }
    }

    mysqli_close($con);
    exit();
}

// ---------------------------------------------------------------------------
// HTML View
// ---------------------------------------------------------------------------
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
    <title>Treasurer Monthly Report</title>
    <?php include 'jsLibraies.php'; ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
    <style>
        <?php $inc = "./orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
        <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
    </style>
    <style>
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background-color: #fafafa; min-height: 100vh; }
        .padding-container { padding: 15px; }
        .title-row { display: flex; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 10px; }
        .title-row h2 { margin: 0 15px 0 0; }
        .controls-bar {
            display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
            padding: 10px; background: #f8f9fa; border-radius: 4px; margin-bottom: 15px; font-size: 14px;
        }
        .controls-bar .filter-group { display: flex; align-items: center; gap: 5px; }
        .controls-bar .filter-group label { font-weight: bold; font-size: 12px; white-space: nowrap; }
        .controls-bar select { padding: 4px 8px; border: 1px solid #ccc; border-radius: 3px; font-size: 14px; font-family: inherit; }

        .section-panel { margin-bottom: 25px; }
        .section-panel h3 { margin: 0 0 10px 0; font-size: 16px; font-weight: bold; }
        .section-panel .subtitle { font-size: 12px; color: #888; margin-bottom: 10px; }

        .member-header {
            background-color: #e8e8e8; font-weight: bold; font-size: 14px; padding: 6px 10px;
            border-radius: 4px; margin: 15px 0 5px 0; display: flex; justify-content: space-between; align-items: center;
        }
        .member-header .member-class { font-weight: normal; font-size: 12px; color: #666; }
        .member-header .member-total { font-weight: bold; font-size: 14px; }
        .member-subtotal td { font-weight: bold; border-top: 2px solid #333; background: #f0f0f0; }

        .comp-row { background-color: #fffde7 !important; }
        .comp-badge {
            display: inline-block; background: #ffc107; color: #333; font-size: 10px;
            padding: 1px 5px; border-radius: 3px; font-weight: bold; margin-left: 4px;
        }
        .no-charge-row td { color: #999; }
        .text-right { text-align: right; }
        .charge-col { text-align: right; white-space: nowrap; }

        .summary-section {
            margin-top: 20px; padding: 15px; background: #f8f9fa;
            border-radius: 4px; font-size: 14px;
        }
        .summary-section table { width: auto; }
        .summary-section td { padding: 4px 12px; border: none; }
        .summary-section td:first-child { font-weight: bold; }
        .summary-section td:last-child { text-align: right; font-weight: bold; }

        .summary-bar {
            display: flex; flex-wrap: wrap; gap: 15px; padding: 10px 15px;
            background: #e3f2fd; border-radius: 4px; margin-bottom: 15px; font-size: 13px;
        }
        .summary-bar .stat { display: flex; align-items: baseline; gap: 5px; }
        .summary-bar .stat .num { font-weight: bold; font-size: 18px; color: #1565c0; }
        .summary-bar .stat .lbl { color: #555; font-size: 12px; }

        @media print {
            .report-table th { font-size: 10px; }
            .report-table td { font-size: 10px; }
            .no-print { display: none; }
            @page { size: landscape; }
        }

        @media (max-width: 767px) {
            .mobile-hide { display: none !important; }
            .member-header { font-size: 12px; padding: 4px 6px; }
        }

        th { background-color: #f5f5f5; }
        td, th { vertical-align: middle; }
        .member-summary { cursor: pointer; }
        .member-summary tr { background-color: #e8e8e8 !important; }
        .member-summary tr:hover { background-color: #dcdcdc !important; }
        .member-summary td { border-bottom: 2px solid #bbb !important; padding: 8px 8px !important; }
        .toggle-cell { width: 30px; text-align: center; }
        #members-table th, #members-table td { vertical-align: middle; }
        #members-table .col-narrow { width: 1px; white-space: nowrap; }
        .toggle-icon {
            display: inline-block; font-size: 12px; color: #666;
            transition: transform 0.2s ease;
        }
        .member-summary.expanded .toggle-icon { transform: rotate(90deg); }
        .member-details { border-bottom: 2px solid #e0e0e0; }
        .panel-toggle {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 14px; border: 1px solid #bbb; border-radius: 4px;
            background: #fff; color: #333; font-size: 12px; cursor: pointer;
            margin-bottom: 10px; user-select: none;
        }
        .panel-toggle:hover { background: #f0f0f0; border-color: #999; }
        .panel-toggle.expanded { background: #e3f2fd; border-color: #1565c0; color: #1565c0; font-weight: bold; }
    </style>
</head>
<body>
<?php
$inc = "./orgs/" . $org . "/heading2.txt";
if (file_exists($inc)) include $inc;
$inc = "./orgs/" . $org . "/menu1.txt";
if (file_exists($inc)) include $inc;
?>

<div class="padding-container">

<?php
$dateStart = new DateTime();
$dateEnd = new DateTime();
$dateStart->setDate($selectedYear, $selectedMonth, 1);
$m2 = $selectedMonth + 1;
$y2 = $selectedYear;
if ($m2 > 12) { $m2 = 1; $y2++; }
$dateEnd->setDate($y2, $m2, 1);
$dateStart2 = $dateStart->format('Ymd');
$dateEnd2 = $dateEnd->format('Ymd');

$con_params = require('./config/database.php'); $con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'],$con_params['username'],$con_params['password'],$con_params['dbname']);
if (mysqli_connect_errno()) { echo "<p>Unable to connect to database</p>"; exit(); }

$towLaunch = getTowLaunchType($con);
$selfLaunch = getSelfLaunchType($con);
$winchLaunch = getWinchLaunchType($con);
$gliderType = getGlidingFlightType($con);
$noChargeId = getNoChargeOpt($con);
$trialFlightIds = getTrialFlightOpts($con);
$trialFlightList = implode("','", $trialFlightIds);

// Count stats
$totalFlightsAll = 0;
$totalComp = 0;
$totalChargeable = 0;
$totalBilled = 0.0;
$memberCount = 0;
?>

<div class="title-row no-print">
    <h2>Treasurer Monthly Report</h2>
</div>

<div class="controls-bar no-print">
    <form method="post" action="/BillingReport" id="report-form" style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin:0;">
        <div class="filter-group">
            <label for="mts">Month:</label>
            <select name='month' id='mts'>
<?php foreach ($months as $idx => $name): ?>
                <option value='<?=$idx?>' <?=$idx == $selectedMonth ? 'selected' : ''?>><?=$name?></option>
<?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label for="yrs">Year:</label>
            <select name='year' id='yrs'>
<?php for ($y = $currentYear - 3; $y <= $currentYear; $y++): ?>
                <option value='<?=$y?>' <?=$y == $selectedYear ? 'selected' : ''?>><?=$y?></option>
<?php endfor; ?>
            </select>
        </div>
        <input type='hidden' name='org' value='<?=$org?>'>
        <input type='submit' name='view' class="btn btn-primary btn-sm" value='View Report'>
        <button type='submit' name='export' class="btn btn-default btn-sm" onclick="this.form.action='/BillingReport.csv'">Export CSV</button>
    </form>
</div>

<?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>

<?php
// =====================================================================
// Panel 2: Trial Flights
// =====================================================================
$trialQuery = "SELECT f.localdate, f.glider, (f.land-f.start) as duration, f.height, f.seq,
                      f.launchtype, f.location, f.comments, f.billing_option,
                      bo.name as billing_opt,
                      pic_m.displayname as pic_name, p2_m.displayname as p2_name
               FROM flights f
               LEFT JOIN billingoptions bo ON bo.id = f.billing_option
               LEFT JOIN members pic_m ON pic_m.id = f.pic
               LEFT JOIN members p2_m ON p2_m.id = f.p2
               WHERE f.org = $org AND f.finalised > 0
                 AND f.localdate >= '$dateStart2' AND f.localdate < '$dateEnd2'
                 AND f.billing_option IN ('$trialFlightList')
               ORDER BY f.localdate, f.seq";
$trialResult = mysqli_query($con, $trialQuery);
$trials = [];
while ($t = mysqli_fetch_array($trialResult))
{
    $d = substr($t[0], 6, 2) . '/' . substr($t[0], 4, 2) . '/' . substr($t[0], 0, 4);
    $trials[] = [
        'date' => $d,
        'localdate' => $t[0],
        'glider' => $t[1],
        'duration' => strDuration($t[2]),
        'totMins' => max(1, (int)floor($t[2] / 60000)),
        'launchtype' => $t[5],
        'location' => $t[6],
        'notes' => $t[7],
        'billing_opt' => $t[9],
        'pic' => $t[10],
        'p2' => $t[11],
        'launch' => getLaunchLabel($t[5], $towLaunch, $winchLaunch, $selfLaunch)
    ];
}

// =====================================================================
// Panel 3: Member Flight Charges
// =====================================================================
$membersQuery = "SELECT DISTINCT m.id, m.surname, m.firstname, m.displayname, m.class,
                        mc.class as class_name
                 FROM members m
                 INNER JOIN flights f ON (f.billing_member1 = m.id OR f.billing_member2 = m.id)
                 LEFT JOIN membership_class mc ON mc.id = m.class
                 WHERE f.org = $org AND f.finalised > 0
                   AND f.localdate >= '$dateStart2' AND f.localdate < '$dateEnd2'
                   AND (f.billing_option IS NULL OR f.billing_option NOT IN ('$trialFlightList'))
                 ORDER BY m.surname, m.firstname";
$membersResult = mysqli_query($con, $membersQuery);

$memberData = [];
while ($member = mysqli_fetch_array($membersResult))
{
    $mid = $member[0];
    $class_name = $member[5] ?? '';

    $flightsQuery = "SELECT f.localdate, f.glider, (f.land-f.start) as duration, f.height, f.seq,
                            f.launchtype, f.towplane, f.location, f.comments, f.billing_option,
                            f.billing_member1, f.billing_member2, f.type,
                            bo.name as billing_opt, bo.bill_pic, bo.bill_p2, bo.bill_other,
                            pic_m.displayname as pic_name, p2_m.displayname as p2_name,
                            a.rego_short, a.club_glider, a.charge_per_minute, a.max_perflight_charge
                     FROM flights f
                     LEFT JOIN billingoptions bo ON bo.id = f.billing_option
                     LEFT JOIN members pic_m ON pic_m.id = f.pic
                     LEFT JOIN members p2_m ON p2_m.id = f.p2
                     LEFT JOIN aircraft a ON a.rego_short = f.glider AND a.org = f.org
                     WHERE f.org = $org AND f.finalised > 0
                       AND f.localdate >= '$dateStart2' AND f.localdate < '$dateEnd2'
                       AND (f.billing_member1 = $mid OR f.billing_member2 = $mid)
                       AND (f.billing_option IS NULL OR f.billing_option NOT IN ('$trialFlightList'))
                     ORDER BY f.localdate, f.seq";
    $flightsResult = mysqli_query($con, $flightsQuery);
    $flights = [];
    $memberTotal = 0.0;
    $memberChargeableFlights = 0;

    while ($f = mysqli_fetch_array($flightsResult))
    {
        $d = substr($f[0], 6, 2) . '/' . substr($f[0], 4, 2) . '/' . substr($f[0], 0, 4);
        $totMins = max(1, (int)floor($f[2] / 60000));
        $isComp = isCompetitionFlight($f[5], $towLaunch);
        $is5050 = isFiftyFifty($f[14], $f[15]);
        $splitFactor = $is5050 ? 0.5 : 1.0;

        $gliderCharge = calcGliderCharge($f[20], $f[19], $totMins, $class_name, $f[21], $f[22]);
        $gliderCharge = round($gliderCharge * $splitFactor, 2);

        $isFirstWinch = isFirstWinchLaunchForMember($con, $org, $mid, $f[0], $f[4], $winchLaunch);
        $launchCharge = calcLaunchCharge($f[5], $isFirstWinch, $towLaunch, $winchLaunch, $selfLaunch);
        $launchCharge = round($launchCharge * $splitFactor, 2);

        $total = $isComp ? 0.00 : round($gliderCharge + $launchCharge, 2);

        if (!$isComp) {
            $memberTotal += $total;
            $memberChargeableFlights++;
        }

        $flights[] = [
            'date' => $d,
            'localdate' => $f[0],
            'seq' => $f[4],
            'glider' => $f[1],
            'duration' => strDuration($f[2]),
            'totMins' => $totMins,
            'height' => $f[3],
            'launchtype' => $f[5],
            'launch' => getLaunchLabel($f[5], $towLaunch, $winchLaunch, $selfLaunch),
            'location' => $f[7],
            'pic' => $f[17],
            'p2' => $f[18],
            'billing_opt' => $f[13],
            'isNoCharge' => ($f[9] == $noChargeId),
            'is5050' => $is5050,
            'isComp' => $isComp,
            'isFirstWinch' => $isFirstWinch,
            'clubGlider' => $f[20],
            'regoShort' => $f[19],
            'gliderCharge' => $gliderCharge,
            'launchCharge' => $launchCharge,
            'total' => $total,
            'notes' => $f[8]
        ];
    }

    $memberData[] = [
        'id' => $mid,
        'surname' => $member[1],
        'firstname' => $member[2],
        'displayname' => $member[3],
        'class_name' => $class_name,
        'flights' => $flights,
        'memberTotal' => round($memberTotal, 2),
        'memberChargeableFlights' => $memberChargeableFlights
    ];

    $totalChargeable += $memberChargeableFlights;
    $totalBilled += $memberTotal;
    $memberCount++;
}

// Also get unallocated flights (no billing member)
$unallocQuery = "SELECT f.localdate, f.glider, (f.land-f.start) as duration, f.height, f.seq,
                        f.launchtype, f.towplane, f.location, f.comments, f.billing_option,
                        bo.name as billing_opt,
                        pic_m.displayname as pic_name, p2_m.displayname as p2_name,
                        a.rego_short, a.club_glider, a.charge_per_minute, a.max_perflight_charge
                 FROM flights f
                 LEFT JOIN billingoptions bo ON bo.id = f.billing_option
                 LEFT JOIN members pic_m ON pic_m.id = f.pic
                 LEFT JOIN members p2_m ON p2_m.id = f.p2
                 LEFT JOIN aircraft a ON a.rego_short = f.glider AND a.org = f.org
                 WHERE f.org = $org AND f.finalised > 0
                   AND f.localdate >= '$dateStart2' AND f.localdate < '$dateEnd2'
                   AND f.billing_member1 IS NULL AND f.billing_member2 IS NULL
                   AND f.billing_option NOT IN ('$trialFlightList')
                 ORDER BY f.localdate, f.seq";
$unallocResult = mysqli_query($con, $unallocQuery);

$unallocFlights = [];
while ($f = mysqli_fetch_array($unallocResult))
{
    $d = substr($f[0], 6, 2) . '/' . substr($f[0], 4, 2) . '/' . substr($f[0], 0, 4);
    $totMins = max(1, (int)floor($f[2] / 60000));
    $isComp = isCompetitionFlight($f[5], $towLaunch);

    $unallocFlights[] = [
        'date' => $d,
        'glider' => $f[1],
        'duration' => strDuration($f[2]),
        'totMins' => $totMins,
        'launch' => getLaunchLabel($f[5], $towLaunch, $winchLaunch, $selfLaunch),
        'location' => $f[7],
        'pic' => $f[11],
        'p2' => $f[12],
        'billing_opt' => $f[9],
        'isComp' => $isComp,
        'notes' => $f[8],
        'isNoCharge' => ($f[9] == $noChargeId)
    ];
}

// Count totals for summary
$totalFlightsAll = count($trials);
foreach ($memberData as $md) {
    $totalFlightsAll += count($md['flights']);
    foreach ($md['flights'] as $fl) {
        if ($fl['isComp']) $totalComp++;
    }
}
$totalFlightsAll += count($unallocFlights);

// =====================================================================
// Panel 4: Quarterly Membership
// =====================================================================
$showQuarterly = ($selectedMonth == 3 || $selectedMonth == 6 || $selectedMonth == 9 || $selectedMonth == 12);
$showAnnual = ($selectedMonth == 7);
$quarterlyMembers = [];
$shortTermMembers = [];

if ($showQuarterly || $showAnnual)
{
    $qm = "SELECT m.displayname, m.surname, m.firstname, mc.class
           FROM members m
           LEFT JOIN membership_class mc ON mc.id = m.class
           WHERE m.org = $org AND (m.status IS NULL OR m.status = 1)
           ORDER BY mc.class, m.surname, m.firstname";
    $qr = mysqli_query($con, $qm);
    while ($q = mysqli_fetch_array($qr))
    {
        $c = strtolower($q[3] ?? '');
        if ($c == 'flying' && $showQuarterly)
            $quarterlyMembers[] = ['name' => $q[0], 'class' => 'Flying', 'amount' => 337.50];
        elseif ($c == 'youth' && $showQuarterly)
            $quarterlyMembers[] = ['name' => $q[0], 'class' => 'Youth', 'amount' => 55.00];
        elseif ($c == 'short term')
            $shortTermMembers[] = ['name' => $q[0], 'class' => 'Short Term', 'amount' => 150.00];
    }
}

$pageTitle = $dateStart->format('F Y');
?>

<!-- ================================================================= -->
<!-- Summary Bar -->
<!-- ================================================================= -->
<div class="summary-bar">
    <div class="stat"><span class="num"><?=$totalFlightsAll?></span><span class="lbl">Flights</span></div>
    <div class="stat"><span class="num"><?=$totalComp?></span><span class="lbl">Competition</span></div>
    <div class="stat"><span class="num"><?=$totalChargeable?></span><span class="lbl">Billable</span></div>
    <div class="stat"><span class="num">$<?=number_format($totalBilled, 2)?></span><span class="lbl">Total Charges</span></div>
    <div class="stat"><span class="num"><?=$memberCount?></span><span class="lbl">Members Billed</span></div>
</div>

<p style="font-size:14px;color:#666;margin-bottom:15px;"><strong><?=$pageTitle?></strong></p>

<!-- ================================================================= -->
<!-- Panel 2: Trial Flights -->
<!-- ================================================================= -->
<?php if (count($trials) > 0): ?>
<div class="section-panel">
    <h3>Trial Flights <span class="subtitle">(<?=count($trials)?> flights — no charge)</span></h3>
    <table id="trial-table" class="table table-striped table-bordered" style="width:100%;">
        <thead>
            <tr>
                <th>Date</th>
                <th>Location</th>
                <th>Glider</th>
                <th>PIC</th>
                <th>P2</th>
                <th>Launch</th>
                <th class="text-right">Duration</th>
                <th>Trial Type</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($trials as $t): ?>
            <tr>
                <td><?=$t['date']?></td>
                <td><?=htmlspecialchars($t['location'])?></td>
                <td><?=htmlspecialchars($t['glider'])?></td>
                <td><?=htmlspecialchars($t['pic'])?></td>
                <td><?=htmlspecialchars($t['p2'])?></td>
                <td><?=$t['launch']?></td>
                <td class="text-right"><?=$t['duration']?></td>
                <td><?=htmlspecialchars($t['billing_opt'])?></td>
                <td><?=htmlspecialchars($t['notes'])?></td>
            </tr>
<?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ================================================================= -->
<!-- Panel 3: Member Flight Charges -->
<!-- ================================================================= -->
<div class="section-panel">
    <h3>Member Flight Charges <span class="subtitle">(<?=count($memberData)?> members)</span></h3>

<?php if (count($memberData) > 0): ?>
    <button class="panel-toggle" onclick="toggleAllMembers()" id="toggle-all-btn">Expand All</button>
<?php endif; ?>

<?php if (count($memberData) == 0 && count($unallocFlights) == 0): ?>
    <p style="color:#888;font-size:14px;">No flights this month.</p>
<?php else: ?>

    <table id="members-table" class="table table-bordered report-table" style="width:100%;">
        <thead>
            <tr>
                <th>Member</th>
                <th class="col-narrow">Class</th>
                <th class="col-narrow">Date</th>
                <th>Location</th>
                <th class="col-narrow">Glider</th>
                <th>PIC</th>
                <th>P2</th>
                <th class="col-narrow">Launch</th>
                <th class="col-narrow text-right">Time</th>
                <th>Billing</th>
                <th class="col-narrow text-right">Glider $</th>
                <th class="col-narrow text-right">Launch $</th>
                <th class="text-right">Total $</th>
                <th class="col-narrow">Notes</th>
            </tr>
        </thead>
<?php foreach ($memberData as $md): ?>
        <tbody class="member-summary" onclick="toggleMember(<?=$md['id']?>)" data-member="<?=$md['id']?>">
            <tr>
                <td><strong><?=htmlspecialchars($md['displayname'])?></strong></td>
                <td><?=htmlspecialchars($md['class_name'])?></td>
                <td colspan="4"><?=count($md['flights'])?> flights</td>
                <td colspan="3"></td>
                <td colspan="2"></td>
                <td class="text-right"></td>
                <td class="text-right charge-col"><strong>$<?=number_format($md['memberTotal'], 2)?></strong></td>
                <td class="toggle-cell"><span class="toggle-icon">&#9654;</span></td>
            </tr>
        </tbody>
        <tbody class="member-details" id="member-<?=$md['id']?>" style="display:none;">
    <?php foreach ($md['flights'] as $fi): ?>
            <tr class="<?=$fi['isComp'] ? 'comp-row' : ''?> <?=$fi['isNoCharge'] ? 'no-charge-row' : ''?>">
                <td><strong><?=htmlspecialchars($md['displayname'])?></strong></td>
                <td class="col-narrow"><?=htmlspecialchars($md['class_name'])?></td>
                <td class="col-narrow"><?=$fi['date']?></td>
                <td><?=htmlspecialchars($fi['location'])?></td>
                <td class="col-narrow"><?=htmlspecialchars($fi['glider'])?>
                    <?php if ($fi['isComp']): ?><span class="comp-badge">COMP</span><?php endif; ?>
                </td>
                <td><?=htmlspecialchars($fi['pic'])?></td>
                <td><?=htmlspecialchars($fi['p2'])?></td>
                <td class="col-narrow"><?=$fi['launch']?>
                    <?php if ($fi['isFirstWinch'] && $fi['launchtype'] == $winchLaunch): ?>*<?php endif; ?>
                </td>
                <td class="col-narrow text-right"><?=$fi['duration']?></td>
                <td><?=htmlspecialchars($fi['billing_opt'])?>
                    <?php if ($fi['is5050']): ?> (50/50)<?php endif; ?>
                </td>
                <td class="col-narrow text-right charge-col"><?=number_format($fi['gliderCharge'], 2)?></td>
                <td class="col-narrow text-right charge-col">
                    <?php if ($fi['isComp']): ?>
                        <span style="color:#999;">-</span>
                    <?php else: ?>
                        <?=number_format($fi['launchCharge'], 2)?>
                    <?php endif; ?>
                </td>
                <td class="text-right charge-col">
                    <?php if ($fi['isComp']): ?>
                        <span style="color:#999;">Comp</span>
                    <?php else: ?>
                        <strong><?=number_format($fi['total'], 2)?></strong>
                    <?php endif; ?>
                </td>
                <td class="col-narrow"><?=htmlspecialchars($fi['notes'])?>
                    <?php if ($fi['isComp']): ?><span style="color:#e65100;">Tow billed separately</span><?php endif; ?>
                </td>
            </tr>
    <?php endforeach; ?>
            <tr class="member-subtotal">
                <td colspan="9"><?=htmlspecialchars($md['displayname'])?> — <?=$md['memberChargeableFlights']?> flights</td>
                <td></td>
                <td class="text-right charge-col"><?=number_format(array_sum(array_column($md['flights'], 'gliderCharge')), 2)?></td>
                <td class="text-right charge-col"><?=number_format(array_sum(array_column(array_filter($md['flights'], fn($f) => !$f['isComp']), 'launchCharge')), 2)?></td>
                <td class="text-right charge-col"><strong>$<?=number_format($md['memberTotal'], 2)?></strong></td>
                <td></td>
            </tr>
        </tbody>
<?php endforeach; ?>

<?php if (count($unallocFlights) > 0): ?>
    <?php foreach ($unallocFlights as $fi): ?>
            <tr class="<?=$fi['isComp'] ? 'comp-row' : ''?>">
                <td><em style="color:#999;">(Unallocated)</em></td>
                <td class="col-narrow">-</td>
                <td class="col-narrow"><?=$fi['date']?></td>
                <td><?=htmlspecialchars($fi['location'])?></td>
                <td class="col-narrow"><?=htmlspecialchars($fi['glider'])?></td>
                <td><?=htmlspecialchars($fi['pic'])?></td>
                <td><?=htmlspecialchars($fi['p2'])?></td>
                <td class="col-narrow"><?=$fi['launch']?></td>
                <td class="col-narrow text-right"><?=$fi['duration']?></td>
                <td><?=htmlspecialchars($fi['billing_opt'])?></td>
                <td class="col-narrow text-right charge-col">-</td>
                <td class="col-narrow text-right charge-col">-</td>
                <td class="text-right charge-col">-</td>
                <td class="col-narrow"><?=htmlspecialchars($fi['notes'])?></td>
            </tr>
    <?php endforeach; ?>
<?php endif; ?>
        </tbody>
    </table>

<?php endif; ?>
</div>

<!-- ================================================================= -->
<!-- Panel 4: Quarterly Membership -->
<!-- ================================================================= -->
<?php if ($showQuarterly || $showAnnual || count($shortTermMembers) > 0): ?>
<div class="section-panel">
    <h3>Membership Subscriptions <span class="subtitle">(informational — for reference only)</span></h3>

    <?php if ($showQuarterly && count($quarterlyMembers) > 0): ?>
    <p><strong>Quarterly billing due (<?=$pageTitle?>):</strong></p>
    <table class="table table-striped table-bordered" style="width:auto;">
        <thead><tr><th>Member</th><th>Class</th><th class="text-right">Quarterly Amount</th></tr></thead>
        <tbody>
        <?php foreach ($quarterlyMembers as $qm): ?>
            <tr>
                <td><?=htmlspecialchars($qm['name'])?></td>
                <td><?=$qm['class']?></td>
                <td class="text-right">$<?=number_format($qm['amount'], 2)?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if ($showAnnual): ?>
    <p><strong>Annual billing due (July):</strong> Additional Family Member ($250/yr), Associate ($100/yr)</p>
    <?php endif; ?>

    <?php if (count($shortTermMembers) > 0): ?>
    <p><strong>Short Term members ($150/month):</strong></p>
    <table class="table table-striped table-bordered" style="width:auto;">
        <thead><tr><th>Member</th><th>Class</th><th class="text-right">Monthly Amount</th></tr></thead>
        <tbody>
        <?php foreach ($shortTermMembers as $sm): ?>
            <tr>
                <td><?=htmlspecialchars($sm['name'])?></td>
                <td><?=$sm['class']?></td>
                <td class="text-right">$<?=number_format($sm['amount'], 2)?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php endif; // end POST check ?>
</div>

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js"></script>
<script>
function toggleMember(id) {
    var details = $('#member-' + id);
    var summary = $('[data-member=' + id + ']');
    var icon = summary.find('.toggle-icon');
    if (details.is(':visible')) {
        details.slideUp(150);
        summary.removeClass('expanded');
    } else {
        details.slideDown(150);
        summary.addClass('expanded');
    }
    updateToggleAllBtn();
}

function toggleAllMembers() {
    var btn = $('#toggle-all-btn');
    var expand = btn.text() === 'Expand All';
    $('.member-details').each(function() {
        var id = this.id.replace('member-', '');
        var summary = $('[data-member=' + id + ']');
        if (expand) {
            $(this).slideDown(150);
            summary.addClass('expanded');
        } else {
            $(this).slideUp(150);
            summary.removeClass('expanded');
        }
    });
    btn.text(expand ? 'Collapse All' : 'Expand All');
    btn.toggleClass('expanded', expand);
}

function updateToggleAllBtn() {
    var allExpanded = $('.member-details').length > 0 && $('.member-details:visible').length === $('.member-details').length;
    var btn = $('#toggle-all-btn');
    btn.text(allExpanded ? 'Collapse All' : 'Expand All');
    btn.toggleClass('expanded', allExpanded);
}

$(document).ready(function() {
    updateToggleAllBtn();
    if ($('#trial-table tbody tr').length > 0) {
        $('#trial-table').DataTable({
            order: [[0, 'asc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            paging: true,
            info: true
        });
    }
});
</script>
</body>
</html>
<?php mysqli_close($con); ?>
