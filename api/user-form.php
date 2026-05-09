<?php
require_once __DIR__ . '/../helpers/api-base.php';

session_start();

require_once __DIR__ . '/../helpers/logging.php';
logMsg("START method=" . $_SERVER['REQUEST_METHOD']);

// Check authentication - require security level 64 (system admin)
if (!isset($_SESSION['security']) || !($_SESSION['security'] & 64)) {
    logMsg("AUTH FAIL - security=" . ($_SESSION['security'] ?? 'null'));
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Security level too low']);
    exit;
}

logMsg("AUTH OK - memberid=" . ($_SESSION['memberid'] ?? 'null'));

$org = isset($_SESSION['org']) ? $_SESSION['org'] : 0;
if ($org === null) $org = 0;

header('Content-Type: application/json');

$con_params = require(__DIR__ . '/../config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect(
    $con_params['hostname'],
    $con_params['username'],
    $con_params['password'],
    $con_params['dbname']
);

if (mysqli_connect_errno()) {
    logMsg("DB CONNECTION ERROR: " . mysqli_connect_error());
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

logMsg("DB connected OK");

// Get member_id if in edit mode
$memberId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Get organisations (for dropdown)
$organisations = [];
$q = "SELECT id, name FROM organisations ORDER BY name";
$r = mysqli_query($con, $q);
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $organisations[] = ['id' => $row['id'], 'name' => $row['name']];
    }
}

// Get members (for dropdown)
$members = [];
$q = "SELECT id, displayname FROM members";
if ($org > 0) {
    $q .= " WHERE org = " . intval($org);
}
$q .= " ORDER BY displayname";
$r = mysqli_query($con, $q);
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $members[] = ['id' => $row['id'], 'displayname' => $row['displayname']];
    }
}

// Get user data if editing
$user = null;
if ($memberId) {
    $q = "SELECT * FROM users WHERE id = " . intval($memberId);
    $r = mysqli_query($con, $q);
    if ($r) {
        $user = mysqli_fetch_assoc($r);
    }
    if (!$user) {
        logMsg("USER NOT FOUND");
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    if ($org > 0 && $user['org'] != $org) {
        logMsg("ORG MISMATCH - user org=" . $user['org'] . " vs session org=$org");
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
}

mysqli_close($con);

// Handle POST (save user)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $con = mysqli_connect(
        $con_params['hostname'],
        $con_params['username'],
        $con_params['password'],
        $con_params['dbname']
    );

    if (mysqli_connect_errno()) {
        logMsg("POST DB CONNECTION FAILED");
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    logMsg("POST DB connected OK");

    $userId = isset($_POST['id']) ? intval($_POST['id']) : null;
    $isEdit = $userId !== null;

    logMsg("isEdit=$isEdit userId=$userId");

    // Validate required fields
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $usercode = isset($_POST['usercode']) ? trim($_POST['usercode']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $expire = isset($_POST['expire']) && !empty($_POST['expire']) ? $_POST['expire'] : null;
    $securitylevel = isset($_POST['securitylevel']) ? intval($_POST['securitylevel']) : 0;
    $member = isset($_POST['member']) ? intval($_POST['member']) : null;
    $force_pw_reset = isset($_POST['force_pw_reset']) ? 1 : 0;

    if ($_SESSION['security'] & 128) {
        $org = isset($_POST['org']) ? intval($_POST['org']) : 0;
    }

    if (empty($name) || empty($usercode) || empty($expire)) {
        logMsg("VALIDATION FAILED");
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit;
    }

    logMsg("name=$name usercode=$usercode securitylevel=$securitylevel");

    // Escape strings
    $nameEsc = mysqli_real_escape_string($con, $name);
    $usercodeEsc = mysqli_real_escape_string($con, $usercode);
    $expireEsc = mysqli_real_escape_string($con, $expire);

    if ($isEdit) {
        // Build UPDATE query
        $q = "UPDATE users SET
            name = '$nameEsc',
            usercode = '$usercodeEsc',
            expire = '$expireEsc',
            securitylevel = $securitylevel,
            member = " . ($member ? $member : 'NULL') . ",
            force_pw_reset = $force_pw_reset";

        if (!empty($password)) {
            $passwordEsc = mysqli_real_escape_string($con, md5($password));
            $q .= ", password = '$passwordEsc'";
        }

        if ($_SESSION['security'] & 128) {
            $q .= ", org = " . ($org ? $org : 'NULL');
        }

        $q .= " WHERE id = $userId";
    } else {
        // Build INSERT query
        $passwordEsc = mysqli_real_escape_string($con, md5($password));
        $q = "INSERT INTO users (name, usercode, password, org, expire, securitylevel, member, force_pw_reset) VALUES (
            '$nameEsc',
            '$usercodeEsc',
            '$passwordEsc',
            " . ($_SESSION['security'] & 128 ? ($org ? $org : 'NULL') : $_SESSION['org']) . ",
            '$expireEsc',
            $securitylevel,
            " . ($member ? $member : 'NULL') . ",
            $force_pw_reset
        )";
    }

    logMsg("Executing query: " . substr($q, 0, 200));
    if (mysqli_query($con, $q)) {
        logMsg("QUERY SUCCESS");
        $newId = $isEdit ? $userId : mysqli_insert_id($con);
        logMsg("newId=$newId");

        echo json_encode([
            'success' => true,
            'message' => $isEdit ? 'User updated successfully' : 'User created successfully',
            'user_id' => $newId
        ]);
    } else {
        logMsg("QUERY ERROR: " . mysqli_error($con));
        echo json_encode(['success' => false, 'message' => 'Error saving user: ' . mysqli_error($con)]);
    }

    mysqli_close($con);
    exit;
}

// Return form data
echo json_encode([
    'organisations' => $organisations,
    'members' => $members,
    'user' => $user
]);
apiExit();
