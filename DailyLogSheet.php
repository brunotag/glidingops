<?php session_start(); ?>
<?php
include 'helpers.php';
include './helpers/timehelpers.php';
$org=0;
$con_params = require('./config/database.php'); $con_params = $con_params['gliding'];
$con=mysqli_connect($con_params['hostname'],$con_params['username'],$con_params['password'],$con_params['dbname']);
if (mysqli_connect_errno())
{
    echo "<p>Unable to connect to database</p>";
    exit();
}
$DEBUG=0;
$diagtext="";
$flightTypeGlider = getGlidingFlightType($con);
$flightTypeCheck = getCheckFlightType($con);
$flightTypeRetrieve = getRetrieveFlightType($con);

if ($_SERVER["REQUEST_METHOD"] == "GET")
{
   $org=intval($_GET['org']);
}
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
 $org = intval($_POST['org']);
}
if ($org == 0)
{
   echo "<p>Error: No organisation specified</p>";
   exit();
}
$dateTimeZone = new DateTimeZone(orgTimezone($con,$org));
$dateTime = new DateTime("now", $dateTimeZone);
$dateStr = $dateTime->format('Ymd');
$dateStr2 = $dateTime->format('Y-m-d');
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width">
<meta name="viewport" content="initial-scale=1.0">
<head>
<style><?php $inc = "./orgs/" . $org . "/heading2.css"; include $inc; ?></style>
<style><?php $inc = "./orgs/" . $org . "/menu1.css"; include $inc; ?></style>
<style>
* {box-sizing: border-box;}
body {margin: 0;font-family: Arial, Helvetica, sans-serif;background: #f0f0ff;}
#content {max-width: 1400px;margin: 0 auto;padding: 16px;}
#divform {background: #fff;border-radius: 8px;padding: 16px 20px;margin-bottom: 16px;box-shadow: 0 1px 4px rgba(0,0,0,0.08);}
#inform {margin: 0;}
#inform table {border-collapse: collapse;}
#inform td {padding: 4px 8px 4px 0;}
#fmdate {font-size: 16px;padding: 6px 10px;border: 2px solid #ccc;border-radius: 6px;}
#fmdate:focus {border-color: #000080;outline: none;box-shadow: 0 0 0 3px rgba(0,0,128,0.1);}
input[type=submit] {font-size: 14px;font-weight: bold;padding: 8px 20px;color: #fff;background: #000080;border: none;border-radius: 6px;cursor: pointer;}
input[type=submit]:hover {background: #0000b0;}
h1 {font-size: 18px;color: #000080;margin: 16px 0 8px 0;}
table.flights {width: 100%;border-collapse: collapse;font-size: 13px;background: #fff;border-radius: 8px;overflow: hidden;box-shadow: 0 1px 4px rgba(0,0,0,0.08);}
table.flights th {background: #000080;color: #fff;padding: 8px 6px;text-align: left;font-size: 12px;white-space: nowrap;}
table.flights td {padding: 6px;border-bottom: 1px solid #e0e0e0;white-space: nowrap;}
table.flights tr:nth-child(even) {background: #f8f8ff;}
table.flights tr:hover {background: #e8e8ff;}
.right {text-align: right;}
#print-button {font-size: 14px;padding: 8px 20px;color: #fff;background: #28a745;border: none;border-radius: 6px;cursor: pointer;margin-top: 12px;}
#print-button:hover {background: #218838;}
.diag {font-size: 11px;color: #888;word-break: break-all;}
@media print {
    #divform {display: none;}
    #head {display: none;}
    #menu {display: none;}
    #print-button {display: none;}
    body {background: #fff;}
    #content {max-width: 100%;padding: 0;}
    table.flights {box-shadow: none;}
    table.flights th {background: #000080 !important;color: #fff !important;}
    @page {size: landscape;margin: 12mm;}
}
</style>
<script>function goBack() {window.history.back()}</script>
<script>
function printit(){window.print();}
</script>
</head>
<body>
<?php $inc = "./orgs/" . $org . "/heading2.txt"; include $inc; ?>
<?php $inc = "./orgs/" . $org . "/menu1.txt"; include $inc; ?>
<div id="content">
<div id='divform'>
<form id='inform' method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<table>
<tr><td><label for='fmdate'><strong>Enter Date:</strong></label></td><td><input type="date" id='fmdate' name="date" value='<?php echo $dateStr2;?>'></td>
<td><input type='submit' name='view' value='View'></td></tr>
</table>
<input type='hidden' name='org' value ='<?php echo $org;?>'>
</form>
</div>
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
 if($_POST['date'] != "" )
 {
  $dateTime = new DateTime($_POST['date']);
  $dateStr=$dateTime->format('Ymd');
  $towluanch =  getTowLaunchType($con);
  $r = mysqli_query($con,"SELECT * FROM billingoptions where bill_other = 1");
  $billother=9999;
  if (mysqli_num_rows($r) > 0)
  {
	$row = mysqli_fetch_array($r);
	$billother=$row['id'];
	$diagtext .= "Bill Other = " . $billother ."<br>";
  }
  echo "<h1>Daily Time Sheet for: ";
  echo $dateTime->format('d/m/Y');
  echo "</h1>";


  echo "<table class='flights'><tr><th>SEQ</th><th>TOWPLANE</th><th>GLIDER</th><th>Vector</th><th>TOW PILOT</th><th>PIC</th><th>P2</th><th>DURATION</th><th>CHARGE</th><th>COMMENTS</th>";
  $sql= "SELECT flights.seq,e.rego_short,flights.glider, a.displayname,b.displayname,c.displayname, (flights.land - flights.start), flights.height, flights.billing_option, d.displayname,flights.billing_member2, comments, f.name , flights.launchtype, flights.location , flights.type, flights.start, flights.land, flights.vector from flights LEFT JOIN members a ON a.id = flights.towpilot LEFT JOIN members b ON b.id = flights.pic LEFT JOIN members c ON c.id = flights.p2 LEFT JOIN members d ON d.id = flights.billing_member1 LEFT JOIN aircraft e ON e.id = flights.towplane LEFT JOIN launchtypes f ON f.id = flights.launchtype where flights.org = ".$org." and flights.localdate=" . $dateStr . " order by flights.seq ASC";
  $diagtext .= $sql . "<br>";
  $r = mysqli_query($con,$sql);
  $rownum = 0;
  while ($row = mysqli_fetch_array($r) )
  {
   if ($rownum == 0)
     echo "<h1>LOCATION: ".$row[14]."</h1>";
   $rownum = $rownum + 1;
   echo "<tr>";
   echo "<td>";echo $row[0];echo "</td>";
   if ($towluanch == $row[13])
   {
       echo "<td>";echo $row[1];echo "</td>";
   }
   else
   {
	     echo "<td>";echo $row[12];echo "</td>";
   }
   echo "<td>";echo $row[2];echo "</td>";
   echo "<td>";echo $row[18];echo "</td>";
   echo "<td>";echo $row[3];echo "</td>";
   echo "<td>";echo $row[4];echo "</td>";
   echo "<td>";echo $row[5];echo "</td>";
   $duration = intval($row[6] / 1000);
   $hours = intval($duration / 3600);
   $mins = intval(($duration % 3600) / 60);
   $timeval = sprintf("%02d:%02d",$hours,$mins);
   echo "<td>";echo $timeval;echo "</td>";

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
  if ($row[15] == $flightTypeCheck)
  {
     if (strlen($row[11]) > 0)
         echo " ";
     echo "Tow plane check flight";
  }
  if ($row[15] == $flightTypeRetrieve)
  {
     if (strlen($row[11]) > 0)
         echo " ";
     echo "Retrieve";
  }     echo "</td>";
 }
 echo "</table>";
 echo "<p></p>";
 echo "<button onclick='printit()' id='print-button'>Print Sheet</button>";
 mysqli_close($con);
}
}
?>
<?php if($DEBUG>0) echo "<p class='diag'>".$diagtext."</p>";?>
</div>
</body>
</html>
