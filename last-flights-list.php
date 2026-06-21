<?php session_start(); ?>
<?php
$org=0;
if(isset($_SESSION['org'])) $org=$_SESSION['org'];
require_once __DIR__ . '/helpers/permissions.php'; require_perm('engineer.view'); ?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
<title>Gliding - Currency</title>
<style>
<?php $inc = "./orgs/" . $org . "/heading2.css"; include $inc; ?>
</style>
<style>
<?php $inc = "./orgs/" . $org . "/menu1.css"; include $inc; ?></style>
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
?>
<div id="div1">
<p>Last flights for each active member, sortable by any column.</p>
<p>If you can't find a member, ensure they are marked as "Active" (If not, please go to Membership and fix that :)</p>
<p>If you can see members you shouldn't see, then please to to Membership and mark them as "Passive / Retired".</p>
<div id="div2">
<div id="list-section">
<table><tr>
<?php
$colsort = 0;
$descsort = 1;
if ($_SERVER["REQUEST_METHOD"] == "GET")
{
    if(isset($_GET['col']))
    if($_GET['col'] != "" && $_GET['col'] != null)
        $colsort = $_GET['col'];
    if(isset($_GET['descsort']))
    if($_GET['descsort'] != "" && $_GET['descsort'] != null)
        $descsort = $_GET['descsort'];
}

$renderHeaderCell = function($sequence, $header, $colsort, $descsort){
    echo '<th ';
    if ($colsort == $sequence) 
        echo "class='colsel' ";
    echo "onclick=\"location.href='last-flights-list.php?col=$sequence&descsort=";

    if ($colsort == $sequence) 
        echo -1*$descsort; 
    else echo 1; 
    echo "'\"";
    echo " style='cursor:pointer;'";
    echo ">";
    echo $header;
    echo "</th>";
};
$renderHeaderCell(0, "MEMBER", $colsort, $descsort);
$renderHeaderCell(1, "LAST FLIGHT", $colsort, $descsort);
$renderHeaderCell(2, "LAST SOLO", $colsort, $descsort);
$renderHeaderCell(3, "LAST AS P2", $colsort, $descsort);
$renderHeaderCell(4, "LAST AS P1 WITH OTHER P2", $colsort, $descsort);
$renderHeaderCell(5, "HAS FLOWN IN THE PAST 90 DAYS", $colsort, $descsort);
?>
</tr>
<?php
require_once __DIR__ . '/helpers/database.php';
$con = open_gliding_db();
if (mysqli_connect_errno())
{
 echo "<p>Unable to connect to database</p>";
}
$sql=<<<SQL
SELECT 
    m.displayname
    ,MAX(CASE WHEN f.pic = m.id OR f.p2 = m.id THEN localdate ELSE NULL END) as last_flight
    ,MAX(CASE WHEN f.p2 is null THEN localdate ELSE NULL END) as last_solo_flight
    ,MAX(CASE WHEN f.p2 = m.id THEN localdate ELSE NULL END) as last_flight_as_P2
    ,MAX(CASE WHEN f.pic = m.id and f.p2 is not null THEN localdate ELSE NULL END) as last_p1_with_p2_flight
    ,CASE WHEN DATEDIFF(CURDATE(), (MAX(CASE WHEN f.pic = m.id OR f.p2 = m.id THEN localdate ELSE NULL END))) <= 90 THEN TRUE ELSE FALSE END as has_flown_last_90_days
FROM gliding.flights f JOIN gliding.members m ON (f.pic = m.id OR f.p2 = m.id)
WHERE
    f.org = {$_SESSION['org']} AND m.class = 1 AND m.status = 1 AND f.deleted <> 1
GROUP BY m.id
SQL;
$sql.=" ORDER BY ";
switch ($colsort) {
 case 0:
   $sql .= "displayname";
   break;
 case 1:
    $sql .= "last_flight";
    break;
 case 2:
   $sql .= "last_solo_flight";
   break;
 case 3:
   $sql .= "last_flight_as_P2";
   break;
 case 4:
   $sql .= "last_p1_with_p2_flight";
   break;
 case 5:
   $sql .= "has_flown_last_90_days";
   break;
}
if ($descsort == 1)
    $sql .= " ASC";
else
    $sql .= " DESC";
$diagtext.= "SQL=".$sql;
$r = mysqli_query($con,$sql);
$rownum = 0;

$renderDateCell = function($column, $label){
    $empty = (!$column || $column == 0); echo "<td data-label='$label'" . ($empty ? " data-empty='1'" : "") . ">";
    if (!$empty){
        $date=new DateTime($column); 
        echo $date->format('D d/m/Y');
    }
    echo "</td>";
};

$renderBoolCell = function($column, $label){
    echo "<td data-label='$label'>";
    if ($column>0){
        echo "Yes";
    }else {
        echo "No";
    }
    echo "</td>";
};

while ($row = mysqli_fetch_array($r))
{
 $rownum = $rownum + 1;
  echo "<tr class='";if (($rownum % 2) == 0)echo "even";else echo "odd";  echo "'>";
    echo "<td class='right' data-label='MEMBER'" . ((!isset($row[0]) || $row[0] === '') ? " data-empty='1'" : "") . ">";echo $row[0];echo "</td>";
    $renderDateCell($row[1], "LAST FLIGHT");
    $renderDateCell($row[2], "LAST SOLO");
    $renderDateCell($row[3], "LAST AS P2");
    $renderDateCell($row[4], "LAST AS P1 WITH OTHER P2");
    $renderBoolCell($row[5], "HAS FLOWN IN THE PAST 90 DAYS");
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
