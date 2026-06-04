<?php session_start(); ?>
<?php $org=0; if(isset($_SESSION['org'])) $org=$_SESSION['org'];?>
<?php include 'helpers.php'; ?>
<?php require_once __DIR__ . '/helpers/permissions.php'; require_perm('treasurer-report.view'); ?>
<?php
if (isset($_POST['export']))
{
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="TreasurerReportNew4.csv"');

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
    <title>Treasurer Report</title>
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
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .controls-bar .filter-group {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .controls-bar .filter-group label {
            font-weight: bold;
            font-size: 12px;
            white-space: nowrap;
        }
        .controls-bar select {
            padding: 4px 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 14px;
            font-family: inherit;
        }
        th { background-color: #f5f5f5; }
        td, th { vertical-align: middle; }
        .right { text-align: right; }
        .summary-section {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 14px;
        }
        .summary-section table { width: auto; }
        .summary-section td { padding: 4px 12px; border: none; }
        .summary-section td:first-child { font-weight: bold; }
        .summary-section td:last-child { text-align: right; font-weight: bold; }
        @media print {
            .report-table th { font-size: 10px; }
            .report-table td { font-size: 10px; }
            .no-print { display: none; }
            @page { size: landscape; }
        }
    </style>
    <style>
/* --- MOBILE CARD PATTERN (flights-table) --- */
body { min-height: 100vh; }

@media (max-width: 767px) {
    #flights-table.table thead { display: none; }
    #flights-table.table { display: block; }
    #flights-table.table tbody { display: flex; flex-wrap: wrap; gap: 6px; }
    #flights-table.table tr {
        width: calc(50% - 3px);
        min-width: 240px; flex: 1 1 auto;
        border: 1px solid #ddd; border-radius: 6px;
        padding: 5px 8px; background: #fff; box-sizing: border-box;
    }
    #flights-table.table > tbody > tr > td {
        display: block; border: none; padding: 2px 2px 2px 44%;
        text-align: left !important; font-size: 13px; position: relative;
        line-height: 1.35; overflow-wrap: break-word; word-break: break-word;
    }
    #flights-table.table td::before {
        content: attr(data-label); position: absolute; left: 4px;
        width: calc(44% - 12px); overflow: hidden; text-overflow: ellipsis;
        white-space: nowrap; font-weight: 600; font-size: 12px; color: #555;
        line-height: 1.35;
    }
    #flights-table.table td[data-empty="1"] { display: none; }
    #flights-table.table .text-right { text-align: left !important; }
    #flights-table.table .hide-mobile { display: none !important; }
    #flights-table.table .show-mobile { display: block !important; }
    .padding-container { padding: 8px; }
    .title-row { flex-wrap: wrap; gap: 5px; margin-bottom: 5px; }
    .title-row h2 { font-size: 16px; margin-bottom: 0; }
    .controls-bar { padding: 6px 8px; gap: 6px; margin-bottom: 8px; font-size: 12px; }
    .controls-bar .filter-group label { font-size: 11px; }
    .controls-bar select { font-size: 12px; }
}

@media (max-width: 580px) {
    #flights-table.table tbody { flex-direction: column; gap: 8px; }
    #flights-table.table tr { width: 100%; min-width: 0; }
    #flights-table.table > tbody > tr > td:last-child { padding-bottom: 8px; }
}
    </style>
    <script>
    function printit(){ window.print(); }
    </script>
</head>
<body>
<?php $inc = "./orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
<?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>

<div class="padding-container">

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

<div class="title-row">
    <h2>Treasurer Report</h2>
</div>

<div class="controls-bar no-print">
    <form method="post" action="/TreasurerReportNew4" id="report-form" style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin:0;">
        <div class="filter-group">
            <label for="mts">Month:</label>
            <select name='month' id='mts'>
<?php
    $months = array(
        1  =>"Jan", 2  =>"Feb", 3  =>"Mar", 4  =>"Apr",
        5  =>"May", 6  =>"Jun", 7  =>"Jul", 8  =>"Aug",
        9  =>"Sep", 10 =>"Oct", 11 =>"Nov", 12 =>"Dec"
    );
    foreach ($months as $monthIndex => $monthName) {
?>
                <option value='<?=$monthIndex?>' <?=($monthIndex == $selectedMonth) ? "selected" : ""?>><?=$monthName?></option>
<?php
    }
?>
            </select>
        </div>
        <div class="filter-group">
            <label for="yrs">Year:</label>
            <select name='year' id='yrs'>
<?php
    for($y = $currentYear - 3; $y <= $currentYear; $y++) {
?>
                <option value='<?=$y?>' <?=($y == $selectedYear) ? "selected" : ""?>><?=$y?></option>
<?php
    }
?>
            </select>
        </div>
        <input type='hidden' name='org' value='<?php echo $_SESSION['org'];?>'>
        <input type='submit' name='view' class="btn btn-primary btn-sm" value='View Report'>
        <button type='submit' name='export' class="btn btn-default btn-sm" onclick="document.getElementById('report-form').action='/TreasurerReportNew4.csv'">Export CSV</button>
    </form>
</div>

<?php $inc = "./orgs/" . $org . "/accountrules.php"; if (file_exists($inc)) include $inc; ?>

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
        echo "<p style='margin-bottom:10px;font-size:14px;color:#666;'><strong>" . $dateStart->format('F Y') . "</strong> &mdash; " . count($allFlights) . " flights</p>";
?>
        <table id="flights-table" class="table table-striped table-bordered report-table" style="width:100%;">
        <thead>
            <tr>
                <th>SURNAME</th>
                <th>FIRST NAME</th>
                <th>DATE</th>
                <th>LOCATION</th>
                <th class="right">GLIDER</th>
                <th>PIC</th>
                <th>P2</th>
                <th class="right">DURATION</th>
                <th>LAUNCH</th>
                <th>TYPE</th>
                <th>NOTES</th>
            </tr>
        </thead>
        <tbody>
<?php
        foreach ($allFlights as $row)
        {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['surname']) . "</td>";
            echo "<td>" . htmlspecialchars($row['firstname']) . "</td>";
            echo "<td>" . $row['date'] . "</td>";
            echo "<td>" . htmlspecialchars($row['location']) . "</td>";
            echo "<td class='right'>" . htmlspecialchars($row['glider']) . "</td>";
            echo "<td>" . htmlspecialchars($row['pic']) . "</td>";
            echo "<td>" . htmlspecialchars($row['p2']) . "</td>";
            echo "<td class='right'>" . $row['duration'] . "</td>";
            echo "<td>" . $row['launch'] . "</td>";
            echo "<td>" . htmlspecialchars($row['type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['notes']) . "</td>";
            echo "</tr>";
        }
?>
        </tbody>
        </table>

        <div class="summary-section no-print">
            <table>
                <tr><td>Total flights for period</td><td><?php echo count($allFlights); ?></td></tr>
            </table>
        </div>

        <p class="no-print" style="margin-top:15px;">
            <button onclick="printit()" class="btn btn-default btn-sm">Print Report</button>
        </p>
<?php
    }
    else
    {
        echo "<p style='color:#888;font-size:14px;margin:20px 0;'>No flights this month.</p>";
    }

    mysqli_close($con);
}
?>

</div>

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    if ($('#flights-table tbody tr').length > 0) {
        $('#flights-table').DataTable({
            order: [[0, 'asc'], [2, 'asc']],
            pageLength: 50,
            lengthMenu: [[25, 50, 100, 200], [25, 50, 100, 200]],
            paging: true,
            info: true,
            stateSave: false,
            createdRow: function(row, data, dataIndex) {
                var headers = $('#flights-table thead th');
                $(row).children('td').each(function(i) {
                    var label = $(headers[i]).text().trim();
                    $(this).attr('data-label', label);
                    if ($(this).text().trim() === '' && !$(this).find('img').length) {
                        $(this).attr('data-empty', '1');
                    }
                });
            }
        });
    }
});
</script>
</body>
</html>
