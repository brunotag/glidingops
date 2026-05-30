<?php
$org = 0;
if (isset($_GET['org']))
    $org = intval($_GET['org']);
else
    die("Organisation number not set");

$date = isset($_GET['date']) ? $_GET['date'] : null;

require_once __DIR__ . '/../helpers/logging.php';
$isDev = isLocal();

$con_params = require(__DIR__ . '/../config/database.php');
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
<link rel="stylesheet" href="/map/map.css?v=<?= filemtime(__DIR__ . '/map.css') ?>" />
</head>
<body>
<div id="container">
  <div id="sidebar">
    <div id="date-section">
      <div class="section-header" style="display:none">DATE</div>
      <div class="section-header" style="display:flex;font-weight:400;text-transform:none;letter-spacing:0;gap:8px">
        <label id="brightness-label" style="display:none;align-items:center;gap:4px;cursor:default;flex:1">
          <input type="range" id="brightness-slider" min="10" max="100" value="80" style="width:60px;height:3px;accent-color:#e94560;cursor:pointer" />
          <span id="brightness-icon" style="font-size:14px;color:#b4c7dc;line-height:1">&#9728;</span>
        </label>
        <span id="overlay-control" style="display:inline-flex;align-items:center;gap:4px;margin-left:auto">
          <input type="range" id="overlay-slider" min="0" max="80" value="25" style="width:70px;height:3px;accent-color:#e94560;cursor:pointer" />
          <span id="overlay-icon" style="font-size:14px;color:#b4c7dc;line-height:1">&#9680;</span>
        </span>
      </div>
      <div id="date-controls">
        <input type="date" id="date-picker" />
        <button id="refresh-btn" class="btn-filter">Refresh</button>
        <span id="last-updated" class="last-updated"></span>
      </div>
    </div>
    <div id="duties"></div>
    <div id="flying-section">
      <div class="section-header">
        FLYING NOW <button id="flying-only-btn" class="btn-filter">Flying only</button>
      </div>
      <div id="flying-list"></div>
    </div>
    <div id="completed-section">
      <div class="section-header">
        <span id="completed-header-label">COMPLETED TODAY</span>
        <button id="sidebar-show-all" class="btn-filter">Show all</button>
      </div>
      <div id="completed-list"></div>
    </div>
  </div>
  <div id="map-panel">
    <div id="map"></div>
    <div id="divider-handle"></div>
    <div id="overlay">
    <div id="overlay-header">
      <span id="overlay-slider-mob-wrap">
        <input type="range" id="overlay-slider-mob" min="0" max="80" value="25" />
        <span id="overlay-icon-mob">&#9680;</span>
      </span>
      <span id="brightness-mob-wrap" style="display:none;">
        <input type="range" id="brightness-slider-mob" min="10" max="100" value="80" />
        <span style="font-size:13px;color:#b4c7dc;line-height:1">&#9728;</span>
      </span>
    </div>
      <div id="overlay-date-row">
        <input type="date" id="date-picker-mob" />
        <button id="refresh-btn-mob" class="btn-filter">Refresh</button>
        <span id="last-updated-mob" class="last-updated"></span>
      </div>
      <div id="overlay-content"></div>
    </div>
  </div>
</div>

<?php if ($isDev): ?>
<div id="dev-panel">
  <div id="dev-header">DEV TOOLS</div>
  <div class="dev-row">
    <label>Overlay <span id="dev-overlay-val">0.25</span></label>
    <input type="range" id="dev-overlay" min="0" max="80" value="25" />
  </div>
  <div class="dev-row">
    <label>Tracks <span id="dev-track-val">1.0</span></label>
    <input type="range" id="dev-track" min="20" max="100" value="100" />
  </div>
</div>
<?php endif; ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var ORG = <?php echo json_encode($org); ?>;
var DATE_PARAM = <?php echo json_encode($date); ?>;
var TODAY_DATE = <?php echo json_encode($todayDate); ?>;
var IS_DEV = <?php echo json_encode($isDev); ?>;
var LAUNCH_LAT = <?php echo json_encode($orgLat); ?>;
var LAUNCH_LON = <?php echo json_encode($orgLon); ?>;
var MAP_LAT = <?php echo json_encode($mapLat); ?>;
var MAP_LON = <?php echo json_encode($mapLon); ?>;
var TIMEZONE = <?php echo json_encode($timezone); ?>;
var LAUNCH_ELEVATION = <?php echo json_encode($launchElevation); ?>;
</script>
<script src="/map/map.js?v=<?= filemtime(__DIR__ . '/map.js') ?>"></script>
</body>
</html>
