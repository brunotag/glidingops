<?php
require_once __DIR__ . '/../helpers/api-base.php';
require_once __DIR__ . '/../helpers/logging.php';

if (!isLocal()) {
    apiExitWithError('Only available in dev environment');
}

apiMaybeResumeSession();
apiRequireAuth();

$org = isset($_SESSION['org']) ? intval($_SESSION['org']) : 0;
if ($org === 0) $org = 1;

$dateStr = isset($_GET['date']) ? trim($_GET['date']) : '';
if (!preg_match('/^[0-9]{8}$/', $dateStr)) {
    apiExitWithError('Invalid date');
}

require_once __DIR__ . '/../helpers/database.php';
$con = open_gliding_db();
if (mysqli_connect_errno()) {
    apiExitWithError('Database connection failed', $con);
}

$q = "SELECT DISTINCT m.id, m.displayname, m.firstname, m.surname "
    . "FROM flights f "
    . "JOIN members m ON m.id IN (f.pic, f.p2) "
    . "WHERE f.org = " . $org . " AND f.localdate = " . intval($dateStr)
    . " AND f.finalised = 1 AND f.deleted = 0 "
    . "ORDER BY m.surname, m.firstname";
$r = mysqli_query($con, $q);
$members = [];

if ($r) {
    while ($row = mysqli_fetch_array($r)) {
        $members[] = [
            'id' => (int)$row['id'],
            'displayname' => $row['displayname'],
            'name' => $row['firstname'] . ' ' . $row['surname'],
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($members);
apiExit($con);
