<?php session_start(); ?>
<?php
$org=0;
if(isset($_SESSION['org'])) $org=$_SESSION['org'];
require_once __DIR__ . '/helpers/permissions.php'; require_perm('flights.manage'); ?>
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
$pageid=34;
$pkcol=2;
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
if (true){echo '<th ';if ($colsort == 1) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=1'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "ID";echo "</th>";}
if (true){echo '<th ';if ($colsort == 2) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=2'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "DATE";echo "</th>";}
if (true){echo '<th ';if ($colsort == 3) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=3'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "LOCATION";echo "</th>";}
if (true){echo '<th ';if ($colsort == 4) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=4'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "SEQ";echo "</th>";}
if (true){echo '<th ';if ($colsort == 5) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=5'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "TYPE";echo "</th>";}
if (true){echo '<th ';if ($colsort == 6) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=6'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "LAUNCH TYPE";echo "</th>";}
if (true){echo '<th ';if ($colsort == 7) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=7'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "TOW PLANE";echo "</th>";}
if (true){echo '<th ';if ($colsort == 8) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=8'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "GLIDER";echo "</th>";}
if (true){echo '<th ';if ($colsort == 9) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=9'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "TOW PILOT";echo "</th>";}
if (true){echo '<th ';if ($colsort == 10) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=10'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "PIC";echo "</th>";}
if (true){echo '<th ';if ($colsort == 11) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=11'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "P2";echo "</th>";}
if (true){echo '<th ';if ($colsort == 12) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=12'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "TAKEOFF";echo "</th>";}
if (true){echo '<th ';if ($colsort == 13) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=13'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "TOW LAND";echo "</th>";}
if (true){echo '<th ';if ($colsort == 14) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=14'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "LAND";echo "</th>";}
if (true){echo '<th ';if ($colsort == 15) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=15'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "HEIGHT";echo "</th>";}
if (true){echo '<th ';if ($colsort == 16) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=16'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "BILLING OPTION";echo "</th>";}
if (true){echo '<th ';if ($colsort == 17) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=17'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "BILLING 1";echo "</th>";}
if (true){echo '<th ';if ($colsort == 18) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=18'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "BILLING 2";echo "</th>";}
if (true){echo '<th ';if ($colsort == 19) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=19'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "COMMENTS";echo "</th>";}
if (true){echo '<th ';if ($colsort == 20) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=20'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "FINALISED";echo "</th>";}
if (true){echo '<th ';if ($colsort == 21) echo "class='colsel'";echo " onclick=";echo "\"";echo "location.href='flights-list.php?col=21'";echo "\"";echo " style='cursor:pointer;'";echo ">";echo "DELETED";echo "</th>";}
?>
</tr>
<?php
$con_params = require('./config/database.php'); $con_params = $con_params['gliding']; 
$con=mysqli_connect($con_params['hostname'],$con_params['username'],$con_params['password'],$con_params['dbname']);
if (mysqli_connect_errno())
{
 echo "<p>Unable to connect to database</p>";
}
$sql= "SELECT flights.id,flights.localdate,flights.location,flights.seq,b.name,c.name,d.rego_short,flights.glider,e.displayname,f.displayname,g.displayname,flights.start,flights.towland,flights.land,flights.height,h.name,i.displayname,j.displayname,flights.comments,flights.finalised,flights.deleted FROM flights LEFT JOIN flighttypes b ON b.id = flights.type LEFT JOIN launchtypes c ON c.id = flights.launchtype LEFT JOIN aircraft d ON d.id = flights.towplane LEFT JOIN members e ON e.id = flights.towpilot LEFT JOIN members f ON f.id = flights.pic LEFT JOIN members g ON g.id = flights.p2 LEFT JOIN billingoptions h ON h.id = flights.billing_option LEFT JOIN members i ON i.id = flights.billing_member1 LEFT JOIN members j ON j.id = flights.billing_member2"; 
if ($_SESSION['org'] > 0){$sql .= " WHERE flights.org=".$_SESSION['org'];}
$sql.=" ORDER BY ";
switch ($colsort) {
 case 0:
   $sql .= "localdate";
break;
 case 1:
   $sql .= "id";
   break;
 case 2:
   $sql .= "localdate";
   break;
 case 3:
   $sql .= "location";
   break;
 case 4:
   $sql .= "seq";
   break;
 case 5:
   $sql .= "b.name";
   break;
 case 6:
   $sql .= "c.name";
   break;
 case 7:
   $sql .= "d.rego_short";
   break;
 case 8:
   $sql .= "glider";
   break;
 case 9:
   $sql .= "e.displayname";
   break;
 case 10:
   $sql .= "f.displayname";
   break;
 case 11:
   $sql .= "g.displayname";
   break;
 case 12:
   $sql .= "start";
   break;
 case 13:
   $sql .= "towland";
   break;
 case 14:
   $sql .= "land";
   break;
 case 15:
   $sql .= "height";
   break;
 case 16:
   $sql .= "h.name";
   break;
 case 17:
   $sql .= "i.displayname";
   break;
 case 18:
   $sql .= "j.displayname";
   break;
 case 19:
   $sql .= "comments";
   break;
 case 20:
   $sql .= "finalised";
   break;
 case 21:
   $sql .= "deleted";
   break;
}
$sql .= " ASC";
$diagtext.= "SQL=".$sql;
$r = mysqli_query($con,$sql);
$rownum = 0;
while ($row = mysqli_fetch_array($r) )
{
 $rownum = $rownum + 1;
  echo "<tr class='";if (($rownum % 2) == 0)echo "even";else echo "odd";  echo "'>";if (true){$__e = (!isset($row[0]) || $row[0] === ''); echo "<td class='right' data-label='ID'" . ($__e ? " data-empty='1'" : "") . ">";echo "<a href='flights.php?id=";echo $row[0];echo "'>";echo $row[0];echo "</a>";echo "</td>";}
if (true){echo "<td class='right' data-label='DATE'" . ((!isset($row[1]) || $row[1] === '') ? " data-empty='1'" : "") . ">";echo $row[1];echo "</td>";}
if (true){echo "<td data-label='LOCATION'" . ((!isset($row[2]) || $row[2] === '') ? " data-empty='1'" : "") . ">";echo $row[2];echo "</td>";}
if (true){echo "<td class='right' data-label='SEQ'" . ((!isset($row[3]) || $row[3] === '') ? " data-empty='1'" : "") . ">";echo $row[3];echo "</td>";}
if (true){echo "<td data-label='TYPE'" . ((!isset($row[4]) || $row[4] === '') ? " data-empty='1'" : "") . ">";echo $row[4];echo "</td>";}
if (true){echo "<td data-label='LAUNCH TYPE'" . ((!isset($row[5]) || $row[5] === '') ? " data-empty='1'" : "") . ">";echo $row[5];echo "</td>";}
if (true){echo "<td data-label='TOW PLANE'" . ((!isset($row[6]) || $row[6] === '') ? " data-empty='1'" : "") . ">";echo $row[6];echo "</td>";}
if (true){echo "<td data-label='GLIDER'" . ((!isset($row[7]) || $row[7] === '') ? " data-empty='1'" : "") . ">";echo $row[7];echo "</td>";}
if (true){echo "<td data-label='TOW PILOT'" . ((!isset($row[8]) || $row[8] === '') ? " data-empty='1'" : "") . ">";echo $row[8];echo "</td>";}
if (true){echo "<td data-label='PIC'" . ((!isset($row[9]) || $row[9] === '') ? " data-empty='1'" : "") . ">";echo $row[9];echo "</td>";}
if (true){echo "<td data-label='P2'" . ((!isset($row[10]) || $row[10] === '') ? " data-empty='1'" : "") . ">";echo $row[10];echo "</td>";}
if (true){echo "<td data-label='TAKEOFF'" . ((!isset($row[11]) || $row[11] === '') ? " data-empty='1'" : "") . ">";echo $row[11];echo "</td>";}
if (true){echo "<td data-label='TOW LAND'" . ((!isset($row[12]) || $row[12] === '') ? " data-empty='1'" : "") . ">";echo $row[12];echo "</td>";}
if (true){echo "<td data-label='LAND'" . ((!isset($row[13]) || $row[13] === '') ? " data-empty='1'" : "") . ">";echo $row[13];echo "</td>";}
if (true){echo "<td class='right' data-label='HEIGHT'" . ((!isset($row[14]) || $row[14] === '') ? " data-empty='1'" : "") . ">";echo $row[14];echo "</td>";}
if (true){echo "<td data-label='BILLING OPTION'" . ((!isset($row[15]) || $row[15] === '') ? " data-empty='1'" : "") . ">";echo $row[15];echo "</td>";}
if (true){echo "<td data-label='BILLING 1'" . ((!isset($row[16]) || $row[16] === '') ? " data-empty='1'" : "") . ">";echo $row[16];echo "</td>";}
if (true){echo "<td data-label='BILLING 2'" . ((!isset($row[17]) || $row[17] === '') ? " data-empty='1'" : "") . ">";echo $row[17];echo "</td>";}
if (true){echo "<td data-label='COMMENTS'" . ((!isset($row[18]) || $row[18] === '') ? " data-empty='1'" : "") . ">";echo $row[18];echo "</td>";}
if (true){echo "<td class='right' data-label='FINALISED'" . ((!isset($row[19]) || $row[19] === '') ? " data-empty='1'" : "") . ">";echo $row[19];echo "</td>";}
if (true){echo "<td class='right' data-label='DELETED'" . ((!isset($row[20]) || $row[20] === '') ? " data-empty='1'" : "") . ">";echo $row[20];echo "</td>";}
  echo "</tr>";
}
?>
</table>
</div>
</div>
</div>
<?php if($DEBUG>0) echo "<p>".$diagtext."</p>";?>
</body>
</html>
