<?php session_start(); ?>
<?php
$org=0;
if(isset($_SESSION['org'])) $org=$_SESSION['org'];
require_once __DIR__ . '/helpers/permissions.php'; require_perm('spots.view');
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
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
$pageid=56;
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
if (true){echo '<th ';if ($colsort == 1) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='spots-list.php?col=1'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "ID";echo "</th>";}
if (true){echo '<th ';if ($colsort == 2) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='spots-list.php?col=2'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "REGO";echo "</th>";}
if (true){echo '<th ';if ($colsort == 3) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='spots-list.php?col=3'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "KEY";echo "</th>";}
if (true){echo '<th ';if ($colsort == 4) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='spots-list.php?col=4'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "POLL TIME FOR LAST (seconds)";echo "</th>";}
if (true){echo '<th ';if ($colsort == 5) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='spots-list.php?col=5'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "POLL TIME FOR ALL (seconds)";echo "</th>";}
if (true){echo '<th ';if ($colsort == 6) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='spots-list.php?col=6'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "LAST REQ";echo "</th>";}
if (true){echo '<th ';if ($colsort == 7) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='spots-list.php?col=7'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "LAST FULL LIST REQ";echo "</th>";}
?>
</tr>
<?php
$con_params = require('./config/database.php'); $con_params = $con_params['gliding']; 
$con=mysqli_connect($con_params['hostname'],$con_params['username'],$con_params['password'],$con_params['dbname']);
if (mysqli_connect_errno())
{
 echo "<p>Unable to connect to database</p>";
}
$sql= "SELECT spots.id,spots.rego_short,spots.spotkey,spots.polltimelast,spots.polltimeall,spots.lastreq,spots.lastlistreq FROM spots"; 
if ($_SESSION['org'] > 0){$sql .= " WHERE spots.org=".$_SESSION['org'];}
$sql.=" ORDER BY ";
switch ($colsort) {
 case 0:
   $sql .= "id";
break;
 case 1:
   $sql .= "id";
   break;
 case 2:
   $sql .= "rego_short";
   break;
 case 3:
   $sql .= "spotkey";
   break;
 case 4:
   $sql .= "polltimelast";
   break;
 case 5:
   $sql .= "polltimeall";
   break;
 case 6:
   $sql .= "lastreq";
   break;
 case 7:
   $sql .= "lastlistreq";
   break;
}
$sql .= " ASC";
$diagtext.= "SQL=".$sql;
$r = mysqli_query($con,$sql);
$rownum = 0;
while ($row = mysqli_fetch_array($r) )
{
 $rownum = $rownum + 1;
  echo "<tr class='";if (($rownum % 2) == 0)echo "even";else echo "odd";  echo "'>";if (true){$__e = (!isset($row[0]) || $row[0] === ''); echo "<td class='right' data-label='ID'" . ($__e ? " data-empty='1'" : "") . ">";echo "<a href='spots.php?id=";echo $row[0];echo "'>";echo $row[0];echo "</a>";echo "</td>";}
if (true){echo "<td data-label='REGO'" . ((!isset($row[1]) || $row[1] === '') ? " data-empty='1'" : "") . ">";echo $row[1];echo "</td>";}
if (true){echo "<td data-label='KEY'" . ((!isset($row[2]) || $row[2] === '') ? " data-empty='1'" : "") . ">";echo $row[2];echo "</td>";}
if (true){echo "<td class='right' data-label='POLL TIME FOR LAST (seconds)'" . ((!isset($row[3]) || $row[3] === '') ? " data-empty='1'" : "") . ">";echo $row[3];echo "</td>";}
if (true){echo "<td class='right' data-label='POLL TIME FOR ALL (seconds)'" . ((!isset($row[4]) || $row[4] === '') ? " data-empty='1'" : "") . ">";echo $row[4];echo "</td>";}
if (true){$__e = (!isset($row[5]) || $row[5] == 0); echo "<td data-label='LAST REQ'" . ($__e ? " data-empty='1'" : "") . ">";if ($row[5]!=0){$lastreq_d=new DateTime($row[5]); echo timeLocalFormat($lastreq_d,$_SESSION['timezone'],'d/m/Y H:i:s');}echo "</td>";}
if (true){$__e = (!isset($row[6]) || $row[6] == 0); echo "<td data-label='LAST FULL LIST REQ'" . ($__e ? " data-empty='1'" : "") . ">";if ($row[6]!=0){$lastlistreq_d=new DateTime($row[6]); echo timeLocalFormat($lastlistreq_d,$_SESSION['timezone'],'d/m/Y H:i:s');}echo "</td>";}
  echo "</tr>";
}
?>
</table>
</div>
</div>
</div>
<form id="form1" action='spots.php' method='GET'><input type='submit' value = 'Create New'>
</form>
<?php if($DEBUG>0) echo "<p>".$diagtext."</p>";?>
</body>
</html>
