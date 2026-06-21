<?php
session_start();
require_once __DIR__ . '/helpers/permissions.php';
require_perm('daily-sheet.access');

include './helpers/timehelpers.php';
include 'helpers.php';
header('Content-type: text/xml');

if (isset($_GET['org'])) {
  $org = $_GET['org'];
} else {
  exit();
}

$specific_date='';
if (isset($_GET['ds']) ) {
  $specific_date = $_GET['ds'];
}
$whatdt = 'now';
if (strlen($specific_date) > 0) {
  $whatdt=$specific_date;
}

require_once __DIR__ . '/helpers/database.php';
$con = open_gliding_db();

$dateTimeZone = new DateTimeZone(orgTimezone($con,$org));
$dateTime = new DateTime($whatdt, $dateTimeZone);


echo "<allmembers>";
echo getMemmbersXmlRows($con, $org, $dateTime);
echo "</allmembers>";

mysqli_close($con);
?>
