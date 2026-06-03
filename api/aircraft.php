<?php
require_once __DIR__ . '/../helpers/api-base.php';

apiMaybeResumeSession();

if (!isset($_SESSION['security']) || !($_SESSION['security'] & 1)) {
    apiExitWithError('Not logged in');
}

$org = isset($_SESSION['org']) ? intval($_SESSION['org']) : 0;

if (!isset($_GET['search']) || strlen($_GET['search']) < 2) {
    header('Content-Type: application/json');
    echo json_encode([]);
    apiExit();
}

$search = $_GET['search'];

$con_params = require dirname(__FILE__) . '/../config/database.php';
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);

if (mysqli_connect_errno()) {
    apiExitWithError('Database connection failed', $con);
}

$searchSafe = mysqli_real_escape_string($con, $search);

$query = "SELECT a.id, a.rego_short, a.registration, a.make_model, a.club_glider,
                  a.charge_per_minute, a.max_perflight_charge
          FROM aircraft a
          JOIN aircrafttype t ON t.id = a.type
          WHERE a.org = $org
            AND t.name = 'Glider'
            AND (a.rego_short LIKE '%$searchSafe%' OR a.registration LIKE '%$searchSafe%')
          ORDER BY a.rego_short
          LIMIT 15";

$result = mysqli_query($con, $query);
$aircraft = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $aircraft[] = [
            'id' => intval($row['id']),
            'rego_short' => $row['rego_short'],
            'registration' => $row['registration'],
            'make_model' => $row['make_model'],
            'club_glider' => intval($row['club_glider']) === 1,
            'charge_per_minute' => floatval($row['charge_per_minute']),
            'max_perflight_charge' => floatval($row['max_perflight_charge'])
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($aircraft);
apiExit($con);
