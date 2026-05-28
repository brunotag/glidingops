<?php session_start(); ?>
<?php
$org=0;
if(isset($_SESSION['org'])) $org=$_SESSION['org'];
if(isset($_SESSION['security'])){
 if (!($_SESSION['security'] & 128)) {die("Secruity level too low for this page");}
}else{
 header('Location: /Login.php');
 die("Please logon");
}
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
<style>
<?php $inc = "./orgs/" . $org . "/heading3.css"; include $inc; ?>
</style>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="styletable1.css">
<script>function goBack() {window.history.back()}</script>
<style>
body { min-height: 100vh; }
@media (max-width: 767px) {
    #list-section table thead { display: none; }
    #list-section table { display: block; }
    #list-section table tbody { display: flex; flex-wrap: wrap; gap: 8px; }
    #list-section table tr { width: calc(50% - 3px); min-width: 240px; flex: 1 1 auto; border: 1px solid #ddd; border-radius: 6px; padding: 5px 8px; background: #fff; box-sizing: border-box; }
    #list-section table > tbody > tr > td { display: block; border: none; padding: 2px 2px 2px 44%; text-align: left !important; font-size: 13px; position: relative; line-height: 1.35; overflow-wrap: break-word; word-break: break-word; }
    #list-section table td::before { content: attr(data-label); position: absolute; left: 4px; width: calc(44% - 12px); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 600; font-size: 12px; color: #555; line-height: 1.35; }
    #list-section table td[data-empty="1"] { display: none; }
    #list-section table .text-right { text-align: left !important; }
}
@media (max-width: 580px) {
    #list-section table tbody { flex-direction: column; gap: 8px; }
    #list-section table tr { width: 100%; min-width: 0; }
    #list-section table > tbody > tr > td:last-child { padding-bottom: 8px; }
}
</style>
</head>
<body>
<?php $inc = "./orgs/" . $org . "/heading3.txt"; include $inc; ?>
<?php
include './helpers/timehelpers.php';
function dtfmt($dt)
{
	if (substr($dt,0,4) != '0000')
		return substr($dt,8,2).'/'.substr($dt,5,2).'/'.substr($dt,0,4);
	else
		return '';
}
$DEBUG=0;
$diagtext="";
$pageid=2;
$pkcol=1;
$pagesortdata = $_SESSION['pagesortdata'];
$colsort = $pagesortdata[$pageid];
if ($_SERVER["REQUEST_METHOD"] == "GET")
{
 if(isset($_GET['col']))
 {
  if($_GET['col'] != "" && $_GET['col'] != null)
  {
   $colsort = $_GET['col'];
   $pagesortdata[$pageid] = $colsort;
   $_SESSION['pagesortdata'] = $pagesortdata;
  }
 }
}
if ($colsort == 0)
 	$colsort = $pkcol;
?>
<div id="div1">
<div id="div2">
<div id="list-section">
<table><tr>
<?php
if (true){echo '<th ';if ($colsort == 1) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=1'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "ORG NUM";echo "</th>";}
if (true){echo '<th ';if ($colsort == 2) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=2'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "NAME";echo "</th>";}
if (true){echo '<th ';if ($colsort == 3) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=3'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "ADDR1";echo "</th>";}
if (true){echo '<th ';if ($colsort == 4) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=4'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "ADDR2";echo "</th>";}
if (true){echo '<th ';if ($colsort == 5) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=5'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "ADDR3";echo "</th>";}
if (true){echo '<th ';if ($colsort == 6) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=6'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "ADDR4";echo "</th>";}
if (true){echo '<th ';if ($colsort == 7) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=7'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "COUNTRY";echo "</th>";}
if (true){echo '<th ';if ($colsort == 8) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=8'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "CONTACT NAME";echo "</th>";}
if (true){echo '<th ';if ($colsort == 9) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=9'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "EMAIL";echo "</th>";}
if (true){echo '<th ';if ($colsort == 10) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=10'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "TIMEZONE";echo "</th>";}
if (true){echo '<th ';if ($colsort == 11) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=11'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "AIRCRAFT PREFIX";echo "</th>";}
if (true){echo '<th ';if ($colsort == 12) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=12'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "HEIGHT BASED TOW CHARGING";echo "</th>";}
if (true){echo '<th ';if ($colsort == 13) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=13'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "TIME BASED TOW CHARGING";echo "</th>";}
if (true){echo '<th ';if ($colsort == 14) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=14'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "DEFAULT LOCATION";echo "</th>";}
if (true){echo '<th ';if ($colsort == 15) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=15'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "NAME OF OTHER CHARGES";echo "</th>";}
if (true){echo '<th ';if ($colsort == 16) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=16'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "DEFAULT TRACK LATITUDE";echo "</th>";}
if (true){echo '<th ';if ($colsort == 17) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=17'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "DEFAULT TRACK LONGITUDE";echo "</th>";}
if (true){echo '<th ';if ($colsort == 18) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=18'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "MAP CENTRE LATITUDE";echo "</th>";}
if (true){echo '<th ';if ($colsort == 19) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=19'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "MAP CENTRE LONGITUDE";echo "</th>";}
if (true){echo '<th ';if ($colsort == 20) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=20'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "TWITTER CONSUMER KEY";echo "</th>";}
if (true){echo '<th ';if ($colsort == 21) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=21'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "TWITTER CONSUMER SECRET";echo "</th>";}
if (true){echo '<th ';if ($colsort == 22) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=22'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "TWITTER ACCESS TOKEN";echo "</th>";}
if (true){echo '<th ';if ($colsort == 23) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='organisations-list.php?col=23'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "TWITTER ACCESS TOKEN SECRET";echo "</th>";}
?>
</tr>
<?php
$con_params = require('./config/database.php'); $con_params = $con_params['gliding']; 
$con=mysqli_connect($con_params['hostname'],$con_params['username'],$con_params['password'],$con_params['dbname']);
if (mysqli_connect_errno())
{
 echo "<p>Unable to connect to database</p>";
}
$sql= "SELECT organisations.id,organisations.name,organisations.addr1,organisations.addr2,organisations.addr3,organisations.addr4,organisations.country,organisations.contact_name,organisations.email,organisations.timezone,organisations.aircraft_prefix,organisations.tow_height_charging,organisations.tow_time_based,organisations.default_location,organisations.name_othercharges,organisations.def_launch_lat,organisations.def_launch_lon,organisations.map_centre_lat,organisations.map_centre_lon,organisations.twitter_consumerKey,organisations.twitter_consumerSecret,organisations.twitter_accessToken,organisations.twitter_accessTokenSecret FROM organisations"; 
$sql.=" ORDER BY ";
switch ($colsort) {
 case 0:
   $sql .= "id";
break;
 case 1:
   $sql .= "id";
   break;
 case 2:
   $sql .= "name";
   break;
 case 3:
   $sql .= "addr1";
   break;
 case 4:
   $sql .= "addr2";
   break;
 case 5:
   $sql .= "addr3";
   break;
 case 6:
   $sql .= "addr4";
   break;
 case 7:
   $sql .= "country";
   break;
 case 8:
   $sql .= "contact_name";
   break;
 case 9:
   $sql .= "email";
   break;
 case 10:
   $sql .= "timezone";
   break;
 case 11:
   $sql .= "aircraft_prefix";
   break;
 case 12:
   $sql .= "tow_height_charging";
   break;
 case 13:
   $sql .= "tow_time_based";
   break;
 case 14:
   $sql .= "default_location";
   break;
 case 15:
   $sql .= "name_othercharges";
   break;
 case 16:
   $sql .= "def_launch_lat";
   break;
 case 17:
   $sql .= "def_launch_lon";
   break;
 case 18:
   $sql .= "map_centre_lat";
   break;
 case 19:
   $sql .= "map_centre_lon";
   break;
 case 20:
   $sql .= "twitter_consumerKey";
   break;
 case 21:
   $sql .= "twitter_consumerSecret";
   break;
 case 22:
   $sql .= "twitter_accessToken";
   break;
 case 23:
   $sql .= "twitter_accessTokenSecret";
   break;
}
$sql .= " ASC";
$diagtext.= "SQL=".$sql;
$r = mysqli_query($con,$sql);
$rownum = 0;
while ($row = mysqli_fetch_array($r) )
{
 $rownum = $rownum + 1;
  echo "<tr class='";if (($rownum % 2) == 0)echo "even";else echo "odd";  echo "'>";if (true){$__e = (!isset($row[0]) || $row[0] === ''); echo "<td class='right' data-label='ORG NUM'" . ($__e ? " data-empty='1'" : "") . ">";echo "<a href='Organisation?id=";echo $row[0];echo "'>";echo $row[0];echo "</a>";echo "</td>";}
if (true){echo "<td data-label='NAME'" . ((!isset($row[1]) || $row[1] === '') ? " data-empty='1'" : "") . ">";echo $row[1];echo "</td>";}
if (true){echo "<td data-label='ADDR1'" . ((!isset($row[2]) || $row[2] === '') ? " data-empty='1'" : "") . ">";echo $row[2];echo "</td>";}
if (true){echo "<td data-label='ADDR2'" . ((!isset($row[3]) || $row[3] === '') ? " data-empty='1'" : "") . ">";echo $row[3];echo "</td>";}
if (true){echo "<td data-label='ADDR3'" . ((!isset($row[4]) || $row[4] === '') ? " data-empty='1'" : "") . ">";echo $row[4];echo "</td>";}
if (true){echo "<td data-label='ADDR4'" . ((!isset($row[5]) || $row[5] === '') ? " data-empty='1'" : "") . ">";echo $row[5];echo "</td>";}
if (true){echo "<td data-label='COUNTRY'" . ((!isset($row[6]) || $row[6] === '') ? " data-empty='1'" : "") . ">";echo $row[6];echo "</td>";}
if (true){echo "<td data-label='CONTACT NAME'" . ((!isset($row[7]) || $row[7] === '') ? " data-empty='1'" : "") . ">";echo $row[7];echo "</td>";}
if (true){echo "<td data-label='EMAIL'" . ((!isset($row[8]) || $row[8] === '') ? " data-empty='1'" : "") . ">";echo $row[8];echo "</td>";}
if (true){echo "<td data-label='TIMEZONE'" . ((!isset($row[9]) || $row[9] === '') ? " data-empty='1'" : "") . ">";echo $row[9];echo "</td>";}
if (true){echo "<td data-label='AIRCRAFT PREFIX'" . ((!isset($row[10]) || $row[10] === '') ? " data-empty='1'" : "") . ">";echo $row[10];echo "</td>";}
if (true){echo "<td class='right' data-label='HEIGHT BASED TOW CHARGING'" . ((!isset($row[11]) || $row[11] === '') ? " data-empty='1'" : "") . ">";echo $row[11];echo "</td>";}
if (true){echo "<td class='right' data-label='TIME BASED TOW CHARGING'" . ((!isset($row[12]) || $row[12] === '') ? " data-empty='1'" : "") . ">";echo $row[12];echo "</td>";}
if (true){echo "<td data-label='DEFAULT LOCATION'" . ((!isset($row[13]) || $row[13] === '') ? " data-empty='1'" : "") . ">";echo $row[13];echo "</td>";}
if (true){echo "<td data-label='NAME OF OTHER CHARGES'" . ((!isset($row[14]) || $row[14] === '') ? " data-empty='1'" : "") . ">";echo $row[14];echo "</td>";}
if (true){echo "<td data-label='DEFAULT TRACK LATITUDE'" . ((!isset($row[15]) || $row[15] === '') ? " data-empty='1'" : "") . ">";echo $row[15];echo "</td>";}
if (true){echo "<td data-label='DEFAULT TRACK LONGITUDE'" . ((!isset($row[16]) || $row[16] === '') ? " data-empty='1'" : "") . ">";echo $row[16];echo "</td>";}
if (true){echo "<td data-label='MAP CENTRE LATITUDE'" . ((!isset($row[17]) || $row[17] === '') ? " data-empty='1'" : "") . ">";echo $row[17];echo "</td>";}
if (true){echo "<td data-label='MAP CENTRE LONGITUDE'" . ((!isset($row[18]) || $row[18] === '') ? " data-empty='1'" : "") . ">";echo $row[18];echo "</td>";}
if (true){echo "<td data-label='TWITTER CONSUMER KEY'" . ((!isset($row[19]) || $row[19] === '') ? " data-empty='1'" : "") . ">";echo $row[19];echo "</td>";}
if (true){echo "<td data-label='TWITTER CONSUMER SECRET'" . ((!isset($row[20]) || $row[20] === '') ? " data-empty='1'" : "") . ">";echo $row[20];echo "</td>";}
if (true){echo "<td data-label='TWITTER ACCESS TOKEN'" . ((!isset($row[21]) || $row[21] === '') ? " data-empty='1'" : "") . ">";echo $row[21];echo "</td>";}
if (true){echo "<td data-label='TWITTER ACCESS TOKEN SECRET'" . ((!isset($row[22]) || $row[22] === '') ? " data-empty='1'" : "") . ">";echo $row[22];echo "</td>";}
  echo "</tr>";
}
?>
</table>
</div>
</div>
</div>
<form id="form1" action='Organisation' method='GET'><input type='submit' value = 'Create New'>
</form>
<?php if($DEBUG>0) echo "<p>".$diagtext."</p>";?>
</body>
</html>
