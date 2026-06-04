<?php session_start();
require_once __DIR__ . '/helpers/permissions.php'; require_perm('daily-sheet.access');
$org=0;
$dateStr = '';
$dateStr2='';
$diagtext="";
$emailsSent = false;

include 'helpers.php';
include './helpers/timehelpers.php';
include './helpers/mail.php';
include './helpers/email-templates.php';

$con_params = require('./config/database.php'); $con_params = $con_params['gliding'];
$con=mysqli_connect($con_params['hostname'],$con_params['username'],$con_params['password'],$con_params['dbname']);
if (mysqli_connect_errno())
{
 echo "<p>Unable to connect to database</p>";
 exit();
}

if ($_SERVER["REQUEST_METHOD"] == "GET")
{
 $org=$_GET['org'];
 if ($org < 1)
 {
    die("ERROR: No organisation specified");
 }
 $dateTimeZone = new DateTimeZone(orgTimezone($con,$org));
 $dateTime = new DateTime("now", $dateTimeZone);
 
 if(isset($_GET['date']))
 {
  $dateStr=$_GET['date'];
  $dateTime->setDate ( substr($dateStr,0,4), substr($dateStr,4,2), substr($dateStr,6,2) );
 }

 $dateStr = $dateTime->format('Ymd');
 $dateStr2=$dateTime->format('d/m/Y');
}

$towlaunch = getTowLaunchType($con);
$flightTypeGlider = getGlidingFlightType($con);
$flightTypeCheck = getCheckFlightType($con);
$flightTypeRetrieve = getRetrieveFlightType($con);
$orgname = getOrganisationName($con,$org);
$towChargeType = getTowChargeType($con,$org);

$r = mysqli_query($con,"SELECT * FROM billingoptions where bill_other = 1");
$billother=9999;
if (mysqli_num_rows($r) > 0)
{
    $row = mysqli_fetch_array($r);
    $billother=$row['id'];
    $diagtext .= "Bill Other = " . $billother ."<br>";
}

$stClass = getShortTermClass($con,$org);
$towLaunchType = getTowLaunchType($con);
$currentYm = substr($dateStr, 0, 6);

$resend = isset($_GET['resend']) && $_GET['resend'] === '1';

if ($resend) {
    $q = "SELECT members.id, members.email, members.displayname FROM members WHERE class <> " . $stClass;
} else {
    $q = "SELECT members.id, members.email, members.displayname FROM members WHERE class <> " . $stClass . " AND localdate_lastemail <> " . $dateStr;
}

$r = mysqli_query($con, $q);
while ($row = mysqli_fetch_array($r))
{
    $memberId = (int)$row[0];
    $memberEmail = $row[1];
    $memberDisplayName = $row[2];

    $isInstructor = IsMemberInstructor($con, $memberId);
    if (strlen($memberEmail) > 0)
    {
        $data = getMemberRecapData($con, $org, $memberId, $dateStr, $currentYm, $isInstructor);
        if (count($data['flights']) > 0)
        {
            $message = buildRecapEmail($orgname, $data['display_name'], $data['flights'], $dateStr2, $data['stats']);
            $subject = "Your WWGC flying recap - " . $dateStr2;
            Mail::SendMailHtml($memberEmail, $subject, $message);

            $q5 = "UPDATE members SET localdate_lastemail = " . $dateStr . " WHERE members.id = " . $memberId;
            mysqli_query($con, $q5);
            $emailsSent = true;
        }
    }
}

// Check if emails were already sent (for showing the resend button)
$alreadySent = false;
if (!$emailsSent && !$resend) {
    $checkQ = "SELECT COUNT(*) as cnt FROM members WHERE class <> " . $stClass . " AND localdate_lastemail = " . $dateStr;
    $checkR = mysqli_query($con, $checkQ);
    if ($checkR) {
        $checkRow = mysqli_fetch_array($checkR);
        if ((int)$checkRow['cnt'] > 0) {
            $alreadySent = true;
        }
    }
}
?>
<!DOCTYPE HTML>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daily Log Sheet - Gliding Ops</title>
  <?php include 'jsLibraies.php'; ?>
  <style>
    <?php $inc = "./orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
    <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
    body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f0f0ff; }
    .page-container { padding: 15px; max-width: 1400px; }
    .page-title { font-size: 18px; font-weight: bold; color: #063552; margin: 0 0 12px 0; }
    .page-actions { margin-bottom: 12px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .table-container { background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); overflow-x: auto; padding: 10px; }
    .table { margin-bottom: 0; }
    .table th { background: #063552; color: #f26120; font-size: 12px; padding: 8px 6px; white-space: nowrap; vertical-align: middle; }
    .table td { font-size: 13px; padding: 6px; vertical-align: middle; }
    .right { text-align: right; }
    .print-button { margin-top: 12px; }

    @media print {
        #print-button, .page-actions, #menu, .head-user { display: none; }
        @page { size: landscape; }
        .table-container { box-shadow: none; border: none; padding: 0; }
        .table th { background: #063552 !important; color: #f26120 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { background: #fff; }
        .page-container { padding: 0; }
    }

    .resend-bar { background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 10px 14px; margin-bottom: 12px; display: flex; align-items: center; gap: 12px; font-size: 13px; color: #856404; }
    .resend-bar .btn { white-space: nowrap; }
    .email-sent-bar { background: #d4edda; border: 1px solid #c3e6cb; border-radius: 6px; padding: 10px 14px; margin-bottom: 12px; font-size: 13px; color: #155724; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/helpers/dev_mode_banner.php'; ?>
  <?php $inc = "./orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
  <?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>

  <div class="page-container">
    <div class="page-title">Daily Log Sheet &mdash; <?php echo $dateStr2; ?></div>

    <?php if ($resend): ?>
      <div class="email-sent-bar">Recap emails re-sent for <?php echo $dateStr2; ?>.</div>
    <?php elseif ($alreadySent): ?>
      <div class="resend-bar">
        <span>Recap emails were already sent for this date.</span>
        <a href="CompletedSheet.php?org=<?php echo $org; ?>&date=<?php echo $dateStr; ?>&resend=1" class="btn btn-warning btn-sm">Resend Recap Email</a>
      </div>
    <?php endif; ?>

    <div class="table-container">
      <table class="table table-striped table-bordered">
        <thead>
          <tr>
            <th>SEQ</th>
            <th>TOWPLANE</th>
            <th>GLIDER</th>
            <th>VECTOR</th>
            <th>TOW PILOT</th>
            <th>PIC</th>
            <th>P2</th>
            <th class="right">DURATION</th>
            <th class="right">TOW HEIGHT</th>
            <th>CHARGE</th>
            <th>COMMENTS</th>
            <th>LOCATION</th>
          </tr>
        </thead>
        <tbody>
<?php
$sql= "SELECT flights.seq,e.rego_short,flights.glider, a.displayname,b.displayname,c.displayname, (flights.land - flights.start), flights.height, flights.billing_option, d.displayname,flights.billing_member2, comments, f.name , flights.launchtype, flights.type, flights.vector, flights.location from flights LEFT JOIN members a ON a.id = flights.towpilot LEFT JOIN members b ON b.id = flights.pic LEFT JOIN members c ON c.id = flights.p2 LEFT JOIN members d ON d.id = flights.billing_member1 LEFT JOIN aircraft e ON e.id = flights.towplane LEFT JOIN launchtypes f on f.id = flights.launchtype where flights.org = ".$org." and flights.finalised = 1 and flights.localdate=" . $dateStr . " order by flights.seq ASC";
$r = mysqli_query($con,$sql);
$rownum = 0;
while ($row = mysqli_fetch_array($r) )
{
  $rownum++;
  echo "<tr>";
  echo "<td>";echo $row[0];echo "</td>";
  if ($row[13] == $towlaunch)
  {
     echo "<td>";echo $row[1];echo "</td>";
  }
  else
  {
     echo "<td>";echo $row[12];echo "</td>";
  }
  echo "<td>";echo $row[2];echo "</td>";
  echo "<td>";echo $row[15];echo "</td>";
  echo "<td>";echo $row[3];echo "</td>";
  echo "<td>";echo $row[4];echo "</td>";
  echo "<td>";echo $row[5];echo "</td>";
  $duration = intval($row[6] / 1000);
  $hours = intval($duration / 3600);
  $mins = intval(($duration % 3600) / 60);
  $timeval = sprintf("%02d:%02d",$hours,$mins);
  echo "<td class='right'>";echo $timeval;echo "</td>";
  if ($row[13] == $towlaunch && $row[14] == $flightTypeGlider)
  {
      echo "<td class='right'>";echo $row[7];echo "</td>";
  }
  else
  {
      echo "<td></td>";
  }
  echo "<td>";
  if ($row[8] == $billother)
  {
      echo $row[9];
  }
  else
  {     $q1 = "SELECT name FROM billingoptions where id = " . $row[8];
  	$r1 = mysqli_query($con,$q1);
        $row2 = mysqli_fetch_array($r1);
        if ($row2)
           echo $row2[0];
  }
  echo "</td>";
  echo "<td>";
  echo $row[11];
  if ($row[14] == $flightTypeCheck)
  {
     if (strlen($row[11]) > 0)
         echo " ";
     echo "Tow plane check flight";
  }
  if ($row[14] == $flightTypeRetrieve)
  {
     if (strlen($row[11]) > 0)
         echo " ";
     echo "Retrieve";
  }
  echo "</td>";
  echo "<td>"; echo $row[16]; echo "</td>";
  echo "</tr>";
}
?>
        </tbody>
      </table>
    </div>

    <div class="print-button">
      <button onclick="window.print()" class="btn btn-default" id="print-button">Print Sheet</button>
    </div>

    <?php if($DEBUG>0) echo "<p>".$diagtext."</p>";?>
  </div>
</body>
</html>
<?php mysqli_close($con);?>
