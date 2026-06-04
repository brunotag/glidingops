<?php
require_once __DIR__ . '/../helpers/api-base.php';

session_start();

require_once __DIR__ . '/../helpers/logging.php';
require_once __DIR__ . '/../helpers/permissions.php';
logMsg("START method=" . $_SERVER['REQUEST_METHOD']);

require_perm('api.user-form');

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

$assignableIds = getAssignablePersonaIds($con, $_SESSION['permissions'] ?? []);

// Pre-fetch personas (filtered by what the editor can assign)
$personas = [];
$pr = mysqli_query($con, "SELECT id, name, description FROM personas ORDER BY name");
if ($pr) {
    while ($prow = mysqli_fetch_assoc($pr)) {
        if (in_array(intval($prow['id']), $assignableIds)) {
            $personas[] = ['id' => $prow['id'], 'name' => $prow['name'], 'description' => $prow['description']];
        }
    }
}

$userPersonaIds = [];
if ($user) {
    $upr = mysqli_query($con, "SELECT persona_id FROM user_personas WHERE user_id = " . intval($user['id']));
    if ($upr) {
        while ($uprow = mysqli_fetch_assoc($upr)) {
            $userPersonaIds[] = intval($uprow['persona_id']);
        }
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

    $userId = isset($_POST['id']) && $_POST['id'] !== '' ? intval($_POST['id']) : null;
    $isEdit = $userId !== null;

    logMsg("isEdit=$isEdit userId=$userId");

    // Validate required fields
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $usercode = isset($_POST['usercode']) ? trim($_POST['usercode']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $expire = isset($_POST['expire']) && !empty($_POST['expire']) ? $_POST['expire'] : null;
    $member = isset($_POST['member']) ? intval($_POST['member']) : null;
    $force_pw_reset = isset($_POST['force_pw_reset']) ? 1 : 0;

    if (has_perm('organisations.manage')) {
        $org = isset($_POST['org']) ? intval($_POST['org']) : 0;
    }

    if (empty($name) || empty($usercode) || empty($expire)) {
        logMsg("VALIDATION FAILED");
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit;
    }

    logMsg("name=$name usercode=$usercode");

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
            member = " . ($member ? $member : 'NULL') . ",
            force_pw_reset = $force_pw_reset";

        if (!empty($password)) {
            $passwordEsc = mysqli_real_escape_string($con, md5($password));
            $q .= ", password = '$passwordEsc'";
        }

        if (has_perm('organisations.manage')) {
            $q .= ", org = " . ($org ? $org : 'NULL');
        }

        $q .= " WHERE id = $userId";
    } else {
        // Build INSERT query
        $passwordEsc = mysqli_real_escape_string($con, md5($password));
        $q = "INSERT INTO users (name, usercode, password, org, expire, member, force_pw_reset) VALUES (
            '$nameEsc',
            '$usercodeEsc',
            '$passwordEsc',
            " . (has_perm('organisations.manage') ? ($org ? $org : 'NULL') : $_SESSION['org']) . ",
            '$expireEsc',
            " . ($member ? $member : 'NULL') . ",
            $force_pw_reset
        )";
    }

    logMsg("Executing query: " . substr($q, 0, 200));
    if (mysqli_query($con, $q)) {
        logMsg("QUERY SUCCESS");
        $newId = $isEdit ? $userId : mysqli_insert_id($con);
        logMsg("newId=$newId");

        // Save persona assignments (only assignable personas)
        $assignableIds = getAssignablePersonaIds($con, $_SESSION['permissions'] ?? []);
        mysqli_query($con, "DELETE FROM user_personas WHERE user_id = " . intval($newId));
        $selectedPersonas = isset($_POST['personas']) ? $_POST['personas'] : [];
        if (is_array($selectedPersonas)) {
            $validPersonas = [];
            foreach ($selectedPersonas as $pid) {
                $pid = intval($pid);
                if ($pid > 0 && in_array($pid, $assignableIds)) {
                    $validPersonas[] = $pid;
                } elseif ($pid > 0) {
                    logMsg("REJECTED persona_id=$pid not assignable by editor");
                }
            }
            foreach ($validPersonas as $pid) {
                mysqli_query($con, "INSERT INTO user_personas (user_id, persona_id) VALUES (" . intval($newId) . ", $pid)");
            }
        }

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

// Return form data for GET requests
echo json_encode([
    'organisations' => $organisations,
    'members' => $members,
    'user' => $user,
    'personas' => $personas,
    'user_personas' => $userPersonaIds
]);
apiExit();
