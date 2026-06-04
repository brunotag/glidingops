<?php
require_once __DIR__ . '/../helpers/api-base.php';

apiMaybeResumeSession();

require_once __DIR__ . '/../helpers/permissions.php';

require_perm('members.list');

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

$query = "SELECT id, displayname FROM members
    WHERE org = $org
    AND (surname LIKE '%$searchSafe%' OR firstname LIKE '%$searchSafe%' OR displayname LIKE '%$searchSafe%')
    ORDER BY surname, firstname LIMIT 15";

$result = mysqli_query($con, $query);
$members = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $members[] = [
            'id' => intval($row['id']),
            'name' => $row['displayname']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($members);
apiExit($con);