<?php
require_once __DIR__ . '/../helpers/api-base.php';

apiMaybeResumeSession();

require_once __DIR__ . '/../helpers/permissions.php';

require_perm('tracking.view');

$org = isset($_SESSION['org']) ? intval($_SESSION['org']) : 0;

$glider = isset($_GET['glider']) ? trim($_GET['glider']) : '';
$dateStr = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');

if (strlen($glider) < 2) {
    header('Content-Type: application/json');
    echo json_encode([]);
    apiExit();
}

$con_params = require dirname(__FILE__) . '/../config/database.php';
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);

if (mysqli_connect_errno()) {
    apiExitWithError('Database connection failed', $con);
}

$gliderSafe = mysqli_real_escape_string($con, $glider);
$dateStart = $dateStr . ' 00:00:00';
$dateEnd = $dateStr . ' 23:59:59';

// Get all tracks for this glider on this date, ordered by time
$query = "SELECT point_time, altitude
          FROM tracks
          WHERE org = $org
            AND glider = '$gliderSafe'
            AND point_time >= '$dateStart'
            AND point_time <= '$dateEnd'
          ORDER BY point_time ASC";

$result = mysqli_query($con, $query);
$segments = [];
$currentStart = null;
$bstarted = false;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $alt = floatval($row['altitude']);

        // Detected start: crossing above 150m threshold
        if (!$bstarted && $alt > 150) {
            $bstarted = true;
            $currentStart = $row['point_time'];
        }

        // Detected end: crossing below 150m threshold
        if ($bstarted && $alt < 150) {
            $segments[] = [
                'start' => strtotime($currentStart) * 1000,
                'land' => strtotime($row['point_time']) * 1000
            ];
            $bstarted = false;
            $currentStart = null;
        }
    }

    // Unfinished segment (still flying or no landing detected)
    if ($bstarted && $currentStart) {
        $segments[] = [
            'start' => strtotime($currentStart) * 1000,
            'land' => null
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($segments);
apiExit($con);
