<?php session_start(); ?>
<?php $org=0; if(isset($_SESSION['org'])) $org=$_SESSION['org'];?>
<?php include 'helpers.php'; ?>
<?php
if(isset($_SESSION['security']))
{
 if (!($_SESSION['security'] & 8))
     die("Security level too low for this page");
}
else
{
 header('Location: /Login.php');
 die("Please logon");
}
?>
<?php
if (isset($_POST['export']))
{
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="TreasurerReportNew3.csv"');

    $con_params = require('./config/database.php'); $con_params = $con_params['gliding'];
    $con=mysqli_connect($con_params['hostname'],$con_params['username'],$con_params['password'],$con_params['dbname']);

    $dateStart = new DateTime();
    $dateEnd = new DateTime();
    $dateStart->setDate($_POST["year"], $_POST["month"], 1);
    $month = $_POST["month"];
    $year = $_POST["year"];
    $month = $month + 1;
    if ($month > 12) { $month = 1; $year = $year + 1; }
    $dateEnd->setDate($year,$month,1);
    $dateStart2 = $dateStart->format('Ymd');
    $dateEnd2 = $dateEnd->format('Ymd');

    $towlaunch = getTowLaunchType($con);
    $selflaunch = getSelfLaunchType($con);
    $winchlaunch = getWinchLaunchType($con);
    $flightTypeGlider = getGlidingFlightType($con);

    echo "SURNAME,FIRST NAME,DATE,LOCATION,GLIDER,PIC,P2,DURATION,LAUNCH,TYPE,NOTES\r\n";

    $trialFlightIds = getTrialFlightOpts($con);
    $trialFlightListString = implode("','",$trialFlightIds);

    $trialQuery = <<<SQL
        SELECT
            flights.localdate,
            flights.glider,
            (flights.land-flights.start) as duration,
            flights.height,
            flights.launchtype,
            flights.towplane,
            flights.location,
            flights.comments,
            a.name as billing_option,
            b.displayname as pic_name,
            c.displayname as p2_name
        FROM flights
        LEFT JOIN billingoptions a ON a.id = flights.billing_option
        LEFT JOIN members b ON b.id = flights.pic
        LEFT JOIN members c ON c.id = flights.p2
        WHERE flights.org = {$_SESSION['org']}
            AND flights.finalised > 0
            AND flights.billing_option IN ('{$trialFlightListString}')
            AND localdate >= '{$dateStart2}'
            AND localdate < '{$dateEnd2}'
        ORDER BY localdate,seq ASC;
SQL;
    $trialResult = mysqli_query($con,$trialQuery);

    while ($trial = mysqli_fetch_array($trialResult))
    {
        $datestr = $trial[0];
        $formattedDate = substr($datestr,6,2) . "/" . substr($datestr,4,2) . "/" . substr($datestr,0,4);

        $launchName = "";
        if ($trial[4] == $towlaunch)
            $launchName = "AEROTOW";
        else if ($trial[4] == $selflaunch)
            $launchName = "SELF";
        else if ($trial[4] == $winchlaunch)
            $launchName = "WINCH";

        echo '"Trial",';
        echo '"",';
        echo $formattedDate . ",";
        echo '"' . str_replace('"', '""', $trial[6]) . '",';
        echo '"' . str_replace('"', '""', $trial[1]) . '",';
        echo '"' . str_replace('"', '""', $trial[9]) . '",';
        echo '"' . str_replace('"', '""', $trial[10]) . '",';
        echo strDuration($trial[2]) . ",";
        echo $launchName . ",";
        echo '"' . str_replace('"', '""', $trial[8]) . '",';
        echo '"' . str_replace('"', '""', $trial[7]) . '"';
        echo "\r\n";
    }

    $membersQuery = "SELECT members.id, members.surname, members.firstname, members.displayname FROM members WHERE members.org = " .$_SESSION['org']. " order by surname,firstname ASC";
    $membersResult = mysqli_query($con,$membersQuery);

    while ($member = mysqli_fetch_array($membersResult))
    {
        $flightsQuery = <<<SQL
            SELECT
                flights.localdate,
                flights.glider,
                (flights.land-flights.start) as duration,
                flights.launchtype,
                flights.towplane,
                flights.location,
                flights.type,
                flights.comments,
                a.name as billing_option,
                b.displayname as pic_name,
                c.displayname as p2_name
            FROM flights
            LEFT JOIN billingoptions a ON a.id = flights.billing_option
            LEFT JOIN members b ON b.id = flights.pic
            LEFT JOIN members c ON c.id = flights.p2
            WHERE flights.org = {$_SESSION['org']}
                AND flights.finalised > 0
                AND localdate >= '{$dateStart2}'
                AND localdate < '{$dateEnd2}'
                AND (billing_member1 = {$member[0]} or billing_member2 = {$member[0]})
            ORDER BY localdate,seq ASC;
SQL;
        $flightsResult = mysqli_query($con,$flightsQuery);

        while ($flight = mysqli_fetch_array($flightsResult))
        {
            $datestr = $flight[0];
            $formattedDate = substr($datestr,6,2) . "/" . substr($datestr,4,2) . "/" . substr($datestr,0,4);

            $launchName = "";
            if ($flight[3] == $towlaunch && $flight[6] == $flightTypeGlider)
                $launchName = "AEROTOW";
            else if ($flight[3] == $selflaunch)
                $launchName = "SELF";
            else if ($flight[3] == $winchlaunch)
                $launchName = "WINCH";

            echo '"' . str_replace('"', '""', $member[1]) . '",';
            echo '"' . str_replace('"', '""', $member[2]) . '",';
            echo $formattedDate . ",";
            echo '"' . str_replace('"', '""', $flight[5]) . '",';
            echo '"' . str_replace('"', '""', $flight[1]) . '",';
            echo '"' . str_replace('"', '""', $flight[9]) . '",';
            echo '"' . str_replace('"', '""', $flight[10]) . '",';
            echo strDuration($flight[2]) . ",";
            echo $launchName . ",";
            echo '"' . str_replace('"', '""', $flight[8]) . '",';
            echo '"' . str_replace('"', '""', $flight[7]) . '"';
            echo "\r\n";
        }
    }

    mysqli_close($con);
    exit();
}
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<style><?php $inc = "./orgs/" . $org . "/heading2.css"; include $inc; ?></style>
<style><?php $inc = "./orgs/" . $org . "/menu1.css"; include $inc; ?></style>
<style>
.report-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5em;
    font-size: 12px;
}
.report-table th {
    background-color: #4a5568;
    color: white;
    padding: 8px 10px;
    text-align: left;
    font-weight: 600;
    border: 1px solid #2d3748;
}
.report-table td {
    padding: 6px 10px;
    border: 1px solid #e2e8f0;
    vertical-align: middle;
}
.report-table tr:nth-child(even) {
    background-color: #f7fafc;
}
.report-table tr:hover {
    background-color: #edf2f7;
}
.report-table .right {
    text-align: right;
}
.summary-table {
    margin-top: 2em;
    font-size: 13px;
}
.summary-table td:first-child {
    font-weight: 600;
    color: #4a5568;
}
.summary-table td:last-child {
    text-align: right;
    font-weight: 700;
    color: #2d3748;
}
@media print {
    .report-table th {font-size: 10px;}
    .report-table td {font-size: 10px;}
    .no-print {display: none;}
    @page {size: landscape;}
}
@media screen {
     h1 {font-size: 20px; color: #2d3748;}
     h2 {font-size: 16px; color: #4a5568;}
}
body {margin: 0px;font-family: Arial, Helvetica, sans-serif; background-color: #fafafa;}
#main-content {padding: 20px; max-width: 1400px; margin: 0 auto;}
</style>
<script>function goBack() {window.history.back()}</script>
<style>
body { min-height: 100vh; }
@media (max-width: 767px) {
    .report-table thead { display: none; }
    .report-table { display: block; }
    .report-table tbody { display: flex; flex-wrap: wrap; gap: 8px; }
    .report-table tr { width: calc(50% - 3px); min-width: 240px; flex: 1 1 auto; border: 1px solid #ddd; border-radius: 6px; padding: 5px 8px; background: #fff; box-sizing: border-box; }
    .report-table > tbody > tr > td { display: block; border: none; padding: 2px 2px 2px 44%; text-align: left !important; font-size: 13px; position: relative; line-height: 1.35; overflow-wrap: break-word; word-break: break-word; }
    .report-table td::before { content: attr(data-label); position: absolute; left: 4px; width: calc(44% - 12px); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 600; font-size: 12px; color: #555; line-height: 1.35; }
    .report-table td[data-empty="1"] { display: none; }
    .report-table .text-right { text-align: left !important; }
}
@media (max-width: 580px) {
    .report-table tbody { flex-direction: column; gap: 8px; }
    .report-table tr { width: 100%; min-width: 0; }
    .report-table > tbody > tr > td:last-child { padding-bottom: 8px; }
}
</style>
<script>
<?php
$dateTimeZone = new DateTimeZone($_SESSION['timezone']);
$dateForMonth = new DateTime('now', $dateTimeZone);
$currentYear = $dateForMonth->format('Y');

$dateForMonth->modify('-1 month');
$defaultMonth = $dateForMonth->format('m');
$defaultYear = $dateForMonth->format('Y');

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    $selectedYear = $_POST["year"];
    $selectedMonth = $_POST["month"];
}
else
{
    $selectedYear = $defaultYear;
    $selectedMonth = $defaultMonth;
}
?>
function printit(){window.print();}
</script>
</head>
<body>
<?php $inc = "./orgs/" . $org . "/heading2.txt"; include $inc; ?>
<?php $inc = "./orgs/" . $org . "/menu1.txt"; include $inc; ?>
<div id='main-content'>
<div id='divhdr' class='no-print'>
<form id='inform' method="post" action="/TreasurerReportNew3">
<h2>Select Month and Year</h2>
<select name='month' id='mts'>
<?php
    $months = array(
        1  =>"Jan",
        2  =>"Feb",
        3  =>"Mar",
        4  =>"Apr",
        5  =>"May",
        6  =>"Jun",
        7  =>"Jul",
        8  =>"Aug",
        9  =>"Sep",
        10 =>"Oct",
        11 =>"Nov",
        12 =>"Dec"
    );
    foreach ($months as $monthIndex => $monthName) {
?>
    <option value='<?=$monthIndex?>'
            <?=($monthIndex == $selectedMonth) ? "selected" : ""?>>
        <?=$monthName?>
    </option>
<?php
    }
?>
</select>

<select name='year' id='yrs'>
<?php
    for($y = $currentYear - 3; $y <= $currentYear; $y++) {
?>
    <option value='<?=$y?>'
            <?=($y == $selectedYear) ? "selected" : ""?>>
        <?=$y?>
    </option>
<?php
    }
?>
</select>

<input type='hidden' name='org' value='<?php echo $_SESSION['org'];?>'>
<input type='submit' name='view' value='View Report'>
<button form='inform' type='submit' name='export' onclick="document.getElementById('inform').action='/TreasurerReportNew3.csv'">Export to CSV</button>
</form>
</div>
<?php $inc = "./orgs/" . $org . "/accountrules.php"; include $inc; ?>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    $dateStart = new DateTime();
    $dateEnd = new DateTime();
    $dateStart->setDate($_POST["year"], $_POST["month"], 1);
    $month = $_POST["month"];
    $year = $_POST["year"];
    $month = $month + 1;
    if ($month > 12)
    {
        $month = 1;
        $year = $year + 1;
    }
    $dateEnd->setDate($year,$month,1);
    $dateStart2 = $dateStart->format('Ymd');
    $dateEnd2 = $dateEnd->format('Ymd');

    $con_params = require('./config/database.php'); $con_params = $con_params['gliding'];
    $con=mysqli_connect($con_params['hostname'],$con_params['username'],$con_params['password'],$con_params['dbname']);
    if (mysqli_connect_errno())
    {
        echo "<p>Unable to connect to database</p>";
        exit();
    }

    $towlaunch = getTowLaunchType($con);
    $selflaunch = getSelfLaunchType($con);
    $winchlaunch = getWinchLaunchType($con);
    $flightTypeGlider = getGlidingFlightType($con);

    echo "<h1>TREASURER'S REPORT - Option 3 (Flat)</h1>";
    echo "<h2>For " . $dateStart->format('F') . " " . $dateStart->format('Y') . "</h2>";

    $totalRows = [];

    $allFlights = [];

    $trialFlightIds = getTrialFlightOpts($con);
    $trialFlightListString = implode("','",$trialFlightIds);

    $trialQuery = <<<SQL
        SELECT
            flights.localdate,
            flights.glider,
            (flights.land-flights.start) as duration,
            flights.height,
            flights.launchtype,
            flights.towplane,
            flights.location,
            flights.comments,
            a.name as billing_option,
            b.displayname as pic_name,
            c.displayname as p2_name
        FROM flights
        LEFT JOIN billingoptions a ON a.id = flights.billing_option
        LEFT JOIN members b ON b.id = flights.pic
        LEFT JOIN members c ON c.id = flights.p2
        WHERE flights.org = {$_SESSION['org']}
            AND flights.finalised > 0
            AND flights.billing_option IN ('{$trialFlightListString}')
            AND localdate >= '{$dateStart2}'
            AND localdate < '{$dateEnd2}'
        ORDER BY localdate,seq ASC;
SQL;
    $trialResult = mysqli_query($con,$trialQuery);

    while ($trial = mysqli_fetch_array($trialResult))
    {
        $datestr = $trial[0];
        $formattedDate = substr($datestr,6,2) . "/" . substr($datestr,4,2) . "/" . substr($datestr,0,4);

        $launchName = "";
        if ($trial[4] == $towlaunch)
            $launchName = "AEROTOW";
        else if ($trial[4] == $selflaunch)
            $launchName = "SELF";
        else if ($trial[4] == $winchlaunch)
            $launchName = "WINCH";

        $allFlights[] = [
            'surname' => 'Trial',
            'firstname' => '',
            'date' => $formattedDate,
            'location' => $trial[6],
            'glider' => $trial[1],
            'pic' => $trial[9],
            'p2' => $trial[10],
            'duration' => strDuration($trial[2]),
            'launch' => $launchName,
            'type' => $trial[8],
            'notes' => $trial[7],
            'sortkey' => 'Trial' . $formattedDate
        ];
    }

    $membersQuery = "SELECT members.id, members.surname, members.firstname, members.displayname FROM members WHERE members.org = " .$_SESSION['org']. " order by surname,firstname ASC";
    $membersResult = mysqli_query($con,$membersQuery);

    while ($member = mysqli_fetch_array($membersResult))
    {
        $flightsQuery = <<<SQL
            SELECT
                flights.localdate,
                flights.glider,
                (flights.land-flights.start) as duration,
                flights.launchtype,
                flights.towplane,
                flights.location,
                flights.type,
                flights.comments,
                a.name as billing_option,
                b.displayname as pic_name,
                c.displayname as p2_name
            FROM flights
            LEFT JOIN billingoptions a ON a.id = flights.billing_option
            LEFT JOIN members b ON b.id = flights.pic
            LEFT JOIN members c ON c.id = flights.p2
            WHERE flights.org = {$_SESSION['org']}
                AND flights.finalised > 0
                AND localdate >= '{$dateStart2}'
                AND localdate < '{$dateEnd2}'
                AND (billing_member1 = {$member[0]} or billing_member2 = {$member[0]})
            ORDER BY localdate,seq ASC;
SQL;
        $flightsResult = mysqli_query($con,$flightsQuery);

        while ($flight = mysqli_fetch_array($flightsResult))
        {
            $datestr = $flight[0];
            $formattedDate = substr($datestr,6,2) . "/" . substr($datestr,4,2) . "/" . substr($datestr,0,4);

            $launchName = "";
            if ($flight[3] == $towlaunch && $flight[6] == $flightTypeGlider)
                $launchName = "AEROTOW";
            else if ($flight[3] == $selflaunch)
                $launchName = "SELF";
            else if ($flight[3] == $winchlaunch)
                $launchName = "WINCH";

            $allFlights[] = [
                'surname' => $member[1],
                'firstname' => $member[2],
                'date' => $formattedDate,
                'location' => $flight[5],
                'glider' => $flight[1],
                'pic' => $flight[9],
                'p2' => $flight[10],
                'duration' => strDuration($flight[2]),
                'launch' => $launchName,
                'type' => $flight[8],
                'notes' => $flight[7],
                'sortkey' => $member[1] . $member[2] . $formattedDate
            ];
        }
    }

    usort($allFlights, function($a, $b) {
        return strcmp($a['sortkey'], $b['sortkey']);
    });

    if (count($allFlights) > 0)
    {
        echo "<table class='report-table'>";
        echo "<tr>
            <th style='width:100px'>SURNAME</th>
            <th style='width:90px'>FIRST NAME</th>
            <th style='width:80px'>DATE</th>
            <th style='width:80px'>LOCATION</th>
            <th style='width:60px'>GLIDER</th>
            <th style='width:100px'>PIC</th>
            <th style='width:100px'>P2</th>
            <th style='width:70px'>DURATION</th>
            <th style='width:70px'>LAUNCH</th>
            <th style='width:120px'>TYPE</th>
            <th>NOTES</th>
        </tr>";

        foreach ($allFlights as $row)
        {
            echo "<tr>";
            echo "<td data-label='SURNAME'" . ((!isset($row['surname']) || $row['surname'] === '') ? " data-empty='1'" : "") . ">" . htmlspecialchars($row['surname']) . "</td>";
            echo "<td data-label='FIRST NAME'" . ((!isset($row['firstname']) || $row['firstname'] === '') ? " data-empty='1'" : "") . ">" . htmlspecialchars($row['firstname']) . "</td>";
            echo "<td data-label='DATE'" . ((!isset($row['date']) || $row['date'] === '') ? " data-empty='1'" : "") . ">" . $row['date'] . "</td>";
            echo "<td data-label='LOCATION'" . ((!isset($row['location']) || $row['location'] === '') ? " data-empty='1'" : "") . ">" . htmlspecialchars($row['location']) . "</td>";
            echo "<td class='right' data-label='GLIDER'" . ((!isset($row['glider']) || $row['glider'] === '') ? " data-empty='1'" : "") . ">" . htmlspecialchars($row['glider']) . "</td>";
            echo "<td data-label='PIC'" . ((!isset($row['pic']) || $row['pic'] === '') ? " data-empty='1'" : "") . ">" . htmlspecialchars($row['pic']) . "</td>";
            echo "<td data-label='P2'" . ((!isset($row['p2']) || $row['p2'] === '') ? " data-empty='1'" : "") . ">" . htmlspecialchars($row['p2']) . "</td>";
            echo "<td class='right' data-label='DURATION'" . ((!isset($row['duration']) || $row['duration'] === '') ? " data-empty='1'" : "") . ">" . $row['duration'] . "</td>";
            echo "<td data-label='LAUNCH'" . ((!isset($row['launch']) || $row['launch'] === '') ? " data-empty='1'" : "") . ">" . $row['launch'] . "</td>";
            echo "<td data-label='TYPE'" . ((!isset($row['type']) || $row['type'] === '') ? " data-empty='1'" : "") . ">" . htmlspecialchars($row['type']) . "</td>";
            echo "<td data-label='NOTES'" . ((!isset($row['notes']) || $row['notes'] === '') ? " data-empty='1'" : "") . ">" . htmlspecialchars($row['notes']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    else
    {
        echo "<p>No flights this month.</p>";
    }

    echo "<table class='summary-table'>";
    echo "<tr><td>TOTAL FLIGHTS FOR PERIOD</td><td>" . count($allFlights) . "</td></tr>";
    echo "</table>";

    echo "<h1>TREASURER'S REPORT - Option 3 (Flat)</h1>";
    echo "<h2>For " . $dateStart->format('F') . " " . $dateStart->format('Y') . "</h2>";
    echo "<p class='no-print'><button onclick='printit()' id='print-button'>Print Report</button></p>";

    mysqli_close($con);
}
?>
</div>
</body>
</html>