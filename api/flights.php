<?php
require_once __DIR__ . '/../helpers/api-base.php';

session_start();

require_once __DIR__ . '/../helpers/logging.php';
require_once __DIR__ . '/../helpers/permissions.php';

logMsg("START method=" . $_SERVER['REQUEST_METHOD']);

if (!isset($_SESSION['userid']) || $_SESSION['userid'] <= 0) {
    logMsg("AUTH FAIL - userid=" . ($_SESSION['userid'] ?? 'null'));
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Not logged in']);
    exit;
}
if (!has_perm('api.flights')) {
    logMsg("PERM FAIL - no api.flights permission");
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Forbidden', 'message' => 'Not authorized']);
    exit;
}

logMsg("AUTH OK - memberid=" . ($_SESSION['memberid'] ?? 'null'));

header('Content-Type: application/json');

require_once __DIR__ . '/../helpers/database.php';
$con = open_gliding_db();
if (mysqli_connect_errno()) {
    logMsg("DB CONNECTION ERROR: " . mysqli_connect_error());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$org = isset($_SESSION['org']) ? intval($_SESSION['org']) : 0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed']);
    apiExit($con);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    apiExit($con);
}

logMsg("POST received: " . json_encode($input));

$action = isset($input['action']) ? trim($input['action']) : '';
$editingId = isset($input['id']) ? intval($input['id']) : 0;

// --- DELETE ---
if ($action === 'delete' && $editingId > 0) {
    $check = mysqli_query($con, "SELECT id FROM flights WHERE id = $editingId AND org = $org AND deleted <> 1");
    if (!$check || mysqli_num_rows($check) === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Flight not found']);
        apiExit($con);
    }
    if (mysqli_query($con, "UPDATE flights SET deleted = 1 WHERE id = $editingId")) {
        logMsg("Flight deleted: id=$editingId");
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Flight deleted']);
    } else {
        logMsg("DELETE ERROR: " . mysqli_error($con));
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Delete failed']);
    }
    apiExit($con);
}

// --- CREATE / UPDATE ---
$glider = isset($input['glider']) ? trim($input['glider']) : '';
$pic = isset($input['pic']) ? intval($input['pic']) : 0;
$p2 = isset($input['p2']) && $input['p2'] ? intval($input['p2']) : null;
$start = isset($input['start']) ? intval($input['start']) : 0;
$land = isset($input['land']) ? intval($input['land']) : 0;
$billingOption = isset($input['billingOption']) ? intval($input['billingOption']) : 0;
$comments = isset($input['comments']) ? trim($input['comments']) : '';
$dateStr = isset($input['date']) ? trim($input['date']) : date('Y-m-d');
$location = isset($input['location']) ? trim($input['location']) : '';
$launchType = isset($input['launchType']) ? intval($input['launchType']) : 0;
$flightType = isset($input['type']) ? intval($input['type']) : 1;
$towpilot = isset($input['towpilot']) && $input['towpilot'] ? intval($input['towpilot']) : null;
$towplane = isset($input['towplane']) && $input['towplane'] ? intval($input['towplane']) : null;
$towland = isset($input['towland']) && $input['towland'] ? intval($input['towland']) : null;
$height = isset($input['height']) && $input['height'] !== null ? intval($input['height']) : null;
$vector = isset($input['vector']) ? trim($input['vector']) : '';

if (empty($glider) || $pic === 0 || $start === 0 || $land === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Required fields missing: glider, pic, start, land']);
    apiExit($con);
}

if ($billingOption === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Billing option required']);
    apiExit($con);
}

// Determine localdate (YYYYMMDD int) from date string
$localDate = intval(date('Ymd', strtotime($dateStr)));

// Get default location if not provided
if (empty($location)) {
    $locQ = "SELECT default_location FROM organisations WHERE id = $org";
    $locR = mysqli_query($con, $locQ);
    if ($locR && ($locRow = mysqli_fetch_assoc($locR))) {
        $location = $locRow['default_location'];
    }
    if (empty($location)) $location = 'Greytown';
}

// Look up billing option to determine billing_member1 and billing_member2
$billOptQ = "SELECT * FROM billingoptions WHERE id = $billingOption";
$billOptR = mysqli_query($con, $billOptQ);
$bill1 = 'null';
$bill2 = 'null';
if ($billOptR && ($billOptRow = mysqli_fetch_assoc($billOptR))) {
    if (intval($billOptRow['bill_pic']) > 0) $bill1 = $pic;
    if (intval($billOptRow['bill_p2']) > 0 && $p2) $bill2 = $p2;
}

// Escape strings
$gliderEsc = mysqli_real_escape_string($con, $glider);
$locationEsc = mysqli_real_escape_string($con, $location);
$commentsEsc = mysqli_real_escape_string($con, $comments);
$vectorEsc = mysqli_real_escape_string($con, $vector);

if ($editingId > 0) {
    // --- UPDATE existing flight ---
    $existingQ = "SELECT seq FROM flights WHERE id = $editingId AND org = $org AND deleted <> 1";
    $existingR = mysqli_query($con, $existingQ);
    if (!$existingR || !($existingRow = mysqli_fetch_assoc($existingR))) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Flight not found for update']);
        apiExit($con);
    }
    $nextSeq = intval($existingRow['seq']);

    $q = "UPDATE flights SET
        localdate = $localDate, date = NOW(), location = '$locationEsc',
        type = $flightType, launchtype = " . ($launchType ? $launchType : 'NULL') . ",
        glider = '$gliderEsc', pic = $pic,
        p2 = " . ($p2 ? $p2 : 'NULL') . ",
        start = $start, land = $land,
        towplane = " . ($towplane ? $towplane : 'NULL') . ",
        towpilot = " . ($towpilot ? $towpilot : 'NULL') . ",
        towland = " . ($towland ? $towland : 'NULL') . ",
        height = " . ($height !== null ? $height : 'NULL') . ",
        billing_option = $billingOption,
        billing_member1 = $bill1, billing_member2 = $bill2,
        comments = '$commentsEsc',
        vector = " . (empty($vector) ? "''" : "'$vectorEsc'") . "
        WHERE id = $editingId";

    logMsg("UPDATE: " . substr($q, 0, 300));
    if (mysqli_query($con, $q)) {
        logMsg("Flight updated: id=$editingId seq=$nextSeq");
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'flightId' => $editingId,
            'seq' => $nextSeq,
            'message' => 'Flight updated successfully'
        ]);
    } else {
        logMsg("UPDATE ERROR: " . mysqli_error($con));
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($con)]);
    }
} else {
    // --- CREATE new flight ---
    $seqQ = "SELECT COALESCE(MAX(seq), 0) + 1 AS next_seq FROM flights WHERE org = $org AND localdate = $localDate";
    $seqR = mysqli_query($con, $seqQ);
    $nextSeq = 1;
    if ($seqR && ($seqRow = mysqli_fetch_assoc($seqR))) {
        $nextSeq = intval($seqRow['next_seq']);
    }

    $q = "INSERT INTO flights (
        org, localdate, date, location, seq, type, updseq,
        launchtype, glider, pic, p2, start, land,
        towplane, towpilot, towland, height,
        billing_option, billing_member1, billing_member2,
        comments, vector, finalised, deleted
    ) VALUES (
        $org, $localDate, NOW(), '$locationEsc', $nextSeq, $flightType, 0,
        " . ($launchType ? $launchType : 'NULL') . ", '$gliderEsc',
        $pic, " . ($p2 ? $p2 : 'NULL') . ",
        $start, $land,
        " . ($towplane ? $towplane : 'NULL') . ", " . ($towpilot ? $towpilot : 'NULL') . ",
        " . ($towland ? $towland : 'NULL') . ", " . ($height !== null ? $height : 'NULL') . ",
        $billingOption, $bill1, $bill2,
        '$commentsEsc', " . (empty($vector) ? "''" : "'$vectorEsc'") . ", 0, 0
    )";

    logMsg("INSERT: " . substr($q, 0, 300));
    if (mysqli_query($con, $q)) {
        $flightId = mysqli_insert_id($con);
        logMsg("Flight created: id=$flightId seq=$nextSeq");
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'flightId' => $flightId,
            'seq' => $nextSeq,
            'message' => 'Flight created successfully'
        ]);
    } else {
        logMsg("INSERT ERROR: " . mysqli_error($con));
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($con)]);
    }
}

apiExit($con);
