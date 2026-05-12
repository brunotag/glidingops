<?php
$org = 0;
if (isset($_GET['org']))
    $org = intval($_GET['org']);
else
    die("Organisation number not set");

$date = isset($_GET['date']) ? $_GET['date'] : null;

$con_params = require(__DIR__ . '/config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);

$q = "SELECT def_launch_lat,def_launch_lon,map_centre_lat,map_centre_lon,timezone FROM organisations WHERE id = " . $org;
$r = mysqli_query($con, $q);
$row = mysqli_fetch_array($r);
$orgLat = floatval($row[0]);
$orgLon = floatval($row[1]);
$mapLat = floatval($row[2]);
$mapLon = floatval($row[3]);
$timezone = $row[4];

mysqli_close($con);

$launchElevation = 50;

$tz = new DateTimeZone($timezone);
$now = new DateTime('now', $tz);
$todayDate = $now->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Real-Time Map</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="/css/map.css" />
</head>
<body>
<div id="container">
  <div id="sidebar">
    <div id="date-section">
      <div class="section-header">DATE</div>
      <div id="date-controls">
        <input type="date" id="date-picker" />
        <button id="date-today-btn">Today</button>
      </div>
    </div>
    <div id="alt-color-area" class="hidden">
      <label id="alt-color-label">
        <input type="checkbox" id="alt-color-cb" /> Altitude colours
      </label>
    </div>
    <div id="duties"></div>
    <div id="flying-section">
      <div class="section-header">FLYING NOW</div>
      <div id="flying-list"></div>
    </div>
    <div id="completed-section">
      <div class="section-header" id="completed-header">COMPLETED TODAY <span id="sidebar-show-all" class="hidden">Show all</span></div>
      <div id="completed-list"></div>
    </div>
  </div>
  <div id="map"></div>
</div>

<div id="overlay-toggle" title="Toggle flights list">&#9776;</div>
<div id="overlay">
  <div id="overlay-header">
    <span>Flights</span>
    <button id="overlay-close">&times;</button>
  </div>
  <div id="overlay-content"></div>
</div>

<button id="show-all-btn" class="hidden">Show All</button>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var ORG = <?php echo json_encode($org); ?>;
var DATE_PARAM = <?php echo json_encode($date); ?>;
var TODAY_DATE = <?php echo json_encode($todayDate); ?>;
var LAUNCH_LAT = <?php echo json_encode($orgLat); ?>;
var LAUNCH_LON = <?php echo json_encode($orgLon); ?>;
var MAP_LAT = <?php echo json_encode($mapLat); ?>;
var MAP_LON = <?php echo json_encode($mapLon); ?>;
var TIMEZONE = <?php echo json_encode($timezone); ?>;
var LAUNCH_ELEVATION = <?php echo json_encode($launchElevation); ?>;
</script>
<script src="/js/map.js"></script>
</body>
</html>
