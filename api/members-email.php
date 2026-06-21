<?php
require_once __DIR__ . '/../helpers/api-base.php';

apiMaybeResumeSession();
apiRequireAuth();

$org = isset($_SESSION['org']) ? $_SESSION['org'] : 0;

if (!isset($_GET['search']) || strlen($_GET['search']) < 2) {
    header('Content-Type: application/json');
    echo json_encode([]);
    apiExit();
}

$search = $_GET['search'];

require_once __DIR__ . '/../helpers/database.php';
$con = open_gliding_db();
if (mysqli_connect_errno()) {
    apiExitWithError('Database connection failed', $con);
}

$search_safe = mysqli_real_escape_string($con, $search);

$status_query = mysqli_query($con, "SELECT id FROM membership_status WHERE status_name = 'Active' LIMIT 1");
$active_status_id = 1;
if ($row = mysqli_fetch_array($status_query)) {
    $active_status_id = $row['id'];
}

$query = "SELECT displayname, email FROM members WHERE org = $org AND (surname LIKE '%$search_safe%' OR firstname LIKE '%$search_safe%' OR displayname LIKE '%$search_safe%') AND status = $active_status_id ORDER BY surname, firstname LIMIT 10";

$result = mysqli_query($con, $query);
$members = [];

if ($result) {
    while ($row = mysqli_fetch_array($result)) {
        if (!empty($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $members[] = [
                'name' => $row['displayname'],
                'email' => $row['email']
            ];
        }
    }
}

header('Content-Type: application/json');
echo json_encode($members);
apiExit($con);