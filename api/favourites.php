<?php
require_once __DIR__ . '/../helpers/api-base.php';

apiMaybeResumeSession();

require_once __DIR__ . '/../helpers/permissions.php';

require_perm('my-flights.view');

header('Content-Type: application/json');

require_once __DIR__ . '/../helpers/database.php';
$con = open_gliding_db();
if (mysqli_connect_errno()) {
    apiExitWithError('Database connection failed', $con);
}

$org = isset($_SESSION['org']) ? intval($_SESSION['org']) : 0;
$myMemberId = isset($_SESSION['memberid']) ? intval($_SESSION['memberid']) : 0;

// GET — list favourites for a member
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $memberId = isset($_GET['member_id']) ? intval($_GET['member_id']) : $myMemberId;

    // If requesting another member's favourites, must be god persona
    if ($memberId !== $myMemberId && !has_perm('favourites.admin')) {
        apiExitWithError('Not authorized', $con);
    }

    $result = mysqli_query($con, "SELECT href, label FROM user_favourites WHERE member_id = $memberId ORDER BY created_at ASC");
    $favs = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $favs[] = ['href' => $row['href'], 'label' => $row['label']];
        }
    }

    echo json_encode($favs);
    apiExit($con);
}

// POST — toggle favourite
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['href']) || empty($input['label'])) {
        apiExitWithError('href and label required', $con);
    }

    $memberId = isset($input['member_id']) ? intval($input['member_id']) : $myMemberId;

    // If toggling for another member, must be god persona
    if ($memberId !== $myMemberId && !has_perm('favourites.admin')) {
        apiExitWithError('Not authorized', $con);
    }

    $href = mysqli_real_escape_string($con, $input['href']);
    $label = mysqli_real_escape_string($con, $input['label']);

    // Check if already favourited
    $check = mysqli_query($con, "SELECT id FROM user_favourites WHERE member_id = $memberId AND href = '$href'");
    $exists = $check && mysqli_num_rows($check) > 0;

    if ($exists) {
        mysqli_query($con, "DELETE FROM user_favourites WHERE member_id = $memberId AND href = '$href'");
        echo json_encode(['favourited' => false]);
    } else {
        mysqli_query($con, "INSERT INTO user_favourites (member_id, href, label) VALUES ($memberId, '$href', '$label')");
        echo json_encode(['favourited' => true]);
    }

    apiExit($con);
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
apiExit($con);
