<?php session_start(); ?>
<?php
$org=0;
if(isset($_SESSION['org'])) $org=$_SESSION['org'];
require_once __DIR__ . '/helpers/permissions.php'; require_perm('incentive-schemes.manage'); ?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
<title>Incentive Schemes</title>
<style>
<?php $inc = "./orgs/" . $org . "/heading2.css"; include $inc; ?>
</style>
<style>
<?php $inc = "./orgs/" . $org . "/menu1.css"; include $inc; ?></style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="styletable1.css">
<script>function goBack() {window.history.back()}</script>
<style>
/* Mobile card pattern */
body { min-height: 100vh; }
@media (max-width: 767px) {
    #list-section table thead { display: none; }
    #list-section table { display: block; }
    #list-section table tbody { display: flex; flex-wrap: wrap; gap: 6px; }
    #list-section table tr {
        width: calc(50% - 3px);
        min-width: 240px; flex: 1 1 auto;
        border: 1px solid #ddd; border-radius: 6px;
        padding: 5px 8px; background: #fff; box-sizing: border-box;
    }
    #list-section table > tbody > tr > td {
        display: block; border: none; padding: 2px 2px 2px 44%;
        text-align: left !important; font-size: 13px; position: relative;
        line-height: 1.35; overflow-wrap: break-word; word-break: break-word;
    }
    #list-section table td::before {
        content: attr(data-label); position: absolute; left: 4px;
        width: calc(44% - 12px); overflow: hidden; text-overflow: ellipsis;
        white-space: nowrap; font-weight: 600; font-size: 12px; color: #555;
        line-height: 1.35;
    }
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
<?php $inc = "./orgs/" . $org . "/heading2.txt"; include $inc; ?>
<?php $inc = "./orgs/" . $org . "/menu1.txt"; include $inc; ?>
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
$pageid=10;
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
if (true){echo '<th ';if ($colsort == 1) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='incentive_schemes-list.php?col=1'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "ID";echo "</th>";}
if (true){echo '<th ';if ($colsort == 2) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='incentive_schemes-list.php?col=2'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "SCHEME NAME";echo "</th>";}
if (true){echo '<th ';if ($colsort == 3) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='incentive_schemes-list.php?col=3'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "GLIDER LIST";echo "</th>";}
if (true){echo '<th ';if ($colsort == 4) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='incentive_schemes-list.php?col=4'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "GLIDER RATE";echo "</th>";}
if (true){echo '<th ';if ($colsort == 5) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='incentive_schemes-list.php?col=5'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "PAY FOR TOWS";echo "</th>";}
if (true){echo '<th ';if ($colsort == 6) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='incentive_schemes-list.php?col=6'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "PAY AIRWAYS CHARGE";echo "</th>";}
if (true){echo '<th ';if ($colsort == 7) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='incentive_schemes-list.php?col=7'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "COST";echo "</th>";}
?>
</tr>
<?php
require_once __DIR__ . '/helpers/database.php';
$con = open_gliding_db();
if (mysqli_connect_errno())
{
 echo "<p>Unable to connect to database</p>";
}
$sql= "SELECT incentive_schemes.id,incentive_schemes.name,incentive_schemes.specific_glider_list,incentive_schemes.rate_glider,incentive_schemes.charge_tow,incentive_schemes.charge_airways,incentive_schemes.cost FROM incentive_schemes"; 
if ($_SESSION['org'] > 0){$sql .= " WHERE incentive_schemes.org=".$_SESSION['org'];}
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
   $sql .= "specific_glider_list";
   break;
 case 4:
   $sql .= "rate_glider";
   break;
 case 5:
   $sql .= "charge_tow";
   break;
 case 6:
   $sql .= "charge_airways";
   break;
 case 7:
   $sql .= "cost";
   break;
}
$sql .= " ASC";
$diagtext.= "SQL=".$sql;
$r = mysqli_query($con,$sql);
$rownum = 0;
while ($row = mysqli_fetch_array($r) )
{
 $rownum = $rownum + 1;
  echo "<tr class='";if (($rownum % 2) == 0)echo "even";else echo "odd";  echo "'>";if (true){$__e = (!isset($row[0]) || $row[0] === ''); echo "<td class='right' data-label='ID'" . ($__e ? " data-empty='1'" : "") . ">";echo "<a href='IncentiveScheme?id=";echo $row[0];echo "'>";echo $row[0];echo "</a>";echo "</td>";}
if (true){echo "<td data-label='SCHEME NAME'" . ((!isset($row[1]) || $row[1] === '') ? " data-empty='1'" : "") . ">";echo $row[1];echo "</td>";}
if (true){echo "<td data-label='GLIDER LIST'" . ((!isset($row[2]) || $row[2] === '') ? " data-empty='1'" : "") . ">";echo $row[2];echo "</td>";}
if (true){echo "<td class='right' data-label='GLIDER RATE'" . ((!isset($row[3]) || $row[3] === '') ? " data-empty='1'" : "") . ">";echo $row[3];echo "</td>";}
if (true){echo "<td class='right' data-label='PAY FOR TOWS'" . ((!isset($row[4]) || $row[4] === '') ? " data-empty='1'" : "") . ">";echo $row[4];echo "</td>";}
if (true){echo "<td class='right' data-label='PAY AIRWAYS CHARGE'" . ((!isset($row[5]) || $row[5] === '') ? " data-empty='1'" : "") . ">";echo $row[5];echo "</td>";}
if (true){echo "<td class='right' data-label='COST'" . ((!isset($row[6]) || $row[6] === '') ? " data-empty='1'" : "") . ">";echo $row[6];echo "</td>";}
  echo "</tr>";
}
?>
</table>
</div>
</div>
</div>
<form id="form1" action='IncentiveScheme' method='GET'><input type='submit' value = 'Create New'>
</form>
<?php if($DEBUG>0) echo "<p>".$diagtext."</p>";?>
</body>
</html>
