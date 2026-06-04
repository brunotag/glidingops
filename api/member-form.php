<?php
require_once __DIR__ . '/../helpers/api-base.php';

session_start();

require_once __DIR__ . '/../helpers/logging.php';
require_once __DIR__ . '/../helpers/permissions.php';

logMsg("START method=" . $_SERVER['REQUEST_METHOD']);

if (!isset($_SESSION['userid']) || $_SESSION['userid'] <= 0) {
    logMsg("AUTH FAIL - userid=" . ($_SESSION['userid'] ?? 'null'));
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Please log in']);
    exit;
}
$hasMemberEdit = has_perm('member.edit');

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
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    logMsg("DB connected OK");

    // Get member_id if in edit mode
$memberId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Get membership classes
$classes = [];
$q = "SELECT * FROM membership_class";
if ($org > 0) {
    $q .= " WHERE org = " . intval($org) . " OR org = 0";
}
$q .= " ORDER BY class";
$r = mysqli_query($con, $q);
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $classes[] = ['id' => $row['id'], 'class' => $row['class']];
    }
} else {
    logMsg("membership_class query failed: " . mysqli_error($con));
}

// Get membership statuses
$statuses = [];
$q = "SELECT * FROM membership_status ORDER BY status_name";
$r = mysqli_query($con, $q);
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $statuses[] = ['id' => $row['id'], 'status' => $row['status_name']];
    }
} else {
    logMsg("membership_status query failed: " . mysqli_error($con));
}

// Get roles
$roles = [];
$q = "SELECT * FROM roles WHERE org = " . intval($org) . " OR org = 0 ORDER BY name";
$r = mysqli_query($con, $q);
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $roles[] = ['id' => $row['id'], 'name' => $row['name']];
    }
} else {
    logMsg("roles query failed: " . mysqli_error($con));
}

// Get member data if editing
$member = null;
if ($memberId) {
    // Check persona for editing other members
    if (!$hasMemberEdit) {
        echo json_encode(['error' => 'Unauthorized', 'message' => 'Security level too low for editing']);
        exit;
    }

    $q = "SELECT * FROM members WHERE id = " . $memberId;
    $r = mysqli_query($con, $q);
    if ($row = mysqli_fetch_assoc($r)) {
        // Check org access
        if ($org > 0 && $row['org'] != $org) {
            echo json_encode(['error' => 'Not found']);
            exit;
        }

        $member = [
            'id' => $row['id'],
            'firstname' => $row['firstname'],
            'surname' => $row['surname'],
            'displayname' => $row['displayname'],
            'date_of_birth' => $row['date_of_birth'],
            'class' => $row['class'],
            'status' => $row['status'],
            'gnz_number' => $row['gnz_number'],
            'phone_mobile' => $row['phone_mobile'],
            'email' => $row['email'],
            'medical_expire' => $row['medical_expire'],
            'bfr_expire' => $row['bfr_expire'],
            'gone_solo' => $row['gone_solo'],
            'official_observer' => $row['official_observer'],
            'enable_email' => $row['enable_email']
        ];

        // Get member roles
        $q = "SELECT role_id FROM role_member WHERE member_id = " . $memberId;
        $r = mysqli_query($con, $q);
        $memberRoles = [];
        while ($row = mysqli_fetch_assoc($r)) {
            $memberRoles[] = $row['role_id'];
        }
        $member['roles'] = $memberRoles;
    }
}

mysqli_close($con);

// Handle POST (save member)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    logMsg("POST - Opening new DB connection");
    $con = mysqli_connect(
        $con_params['hostname'],
        $con_params['username'],
        $con_params['password'],
        $con_params['dbname']
    );
    logMsg("POST mysqli_connect_errno: " . mysqli_connect_errno());
    logMsg("POST mysqli_connect_error: " . mysqli_connect_error());
    if (mysqli_connect_errno()) {
        logMsg("POST DB CONNECTION FAILED");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    logMsg("POST DB connected OK");
    logMsg("POST RECEIVED");
    $memberId = (!empty($_POST['id']) && intval($_POST['id']) > 0) ? intval($_POST['id']) : null;
    logMsg("memberId from POST=" . $memberId);
    $isEdit = $memberId !== null;
    $currentMemberId = isset($_SESSION['memberid']) ? intval($_SESSION['memberid']) : 0;
    logMsg("currentMemberId=$currentMemberId");
    
    // Check if editing someone else's data
    if ($isEdit && $memberId !== $currentMemberId) {
        logMsg("EDITING OTHER - memberId=$memberId vs current=$currentMemberId");
        // Only booking/daily-ops personas can edit other members
        if (!$hasMemberEdit) {
            logMsg("FAIL - not admin");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Security level too low']);
            exit;
        }
        logMsg("Admin confirmed, proceeding...");
    } else {
        logMsg("EDITING SELF OR NEW");
    }
    // Members can always edit their own details (level 1 is sufficient)

    // Validate required fields
    $firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
    $surname = isset($_POST['surname']) ? trim($_POST['surname']) : '';
    $displayname = isset($_POST['displayname']) ? trim($_POST['displayname']) : '';
    $classId = isset($_POST['class']) ? intval($_POST['class']) : 0;
    $statusId = isset($_POST['status']) ? intval($_POST['status']) : 0;

    if (empty($firstname) || empty($surname) || empty($displayname) || $classId === 0 || $statusId === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit;
    }

    // Get optional fields
    $dateOfBirth = isset($_POST['date_of_birth']) && !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $gnzNumber = isset($_POST['gnz_number']) && $_POST['gnz_number'] !== '' ? trim($_POST['gnz_number']) : '0';
    $phoneMobile = isset($_POST['phone_mobile']) ? trim($_POST['phone_mobile']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $medicalExpire = isset($_POST['medical_expire']) && !empty($_POST['medical_expire']) ? $_POST['medical_expire'] : null;
    $bfrExpire = isset($_POST['bfr_expire']) && !empty($_POST['bfr_expire']) ? $_POST['bfr_expire'] : null;
    $goneSolo = isset($_POST['gone_solo']) ? 1 : 0;
    $officialObserver = isset($_POST['official_observer']) ? 1 : 0;
    $enableEmail = 1; // Always true
    $roles = isset($_POST['roles']) ? $_POST['roles'] : [];
    
    // Address fields
    $memAddr1 = isset($_POST['mem_addr1']) ? trim($_POST['mem_addr1']) : '';
    $memAddr2 = isset($_POST['mem_addr2']) ? trim($_POST['mem_addr2']) : '';
    $memAddr3 = isset($_POST['mem_addr3']) ? trim($_POST['mem_addr3']) : '';
    $memCity = isset($_POST['mem_city']) ? trim($_POST['mem_city']) : '';
    $memCountry = isset($_POST['mem_country']) ? trim($_POST['mem_country']) : '';
    $memPostcode = isset($_POST['mem_postcode']) ? trim($_POST['mem_postcode']) : '';
    
    // Emergency contact fields
    $emergAddr1 = isset($_POST['emerg_addr1']) ? trim($_POST['emerg_addr1']) : '';
    $emergAddr2 = isset($_POST['emerg_addr2']) ? trim($_POST['emerg_addr2']) : '';
    $emergAddr3 = isset($_POST['emerg_addr3']) ? trim($_POST['emerg_addr3']) : '';

    // Escape strings for SQL
    $firstnameEsc = mysqli_real_escape_string($con, $firstname);
    $surnameEsc = mysqli_real_escape_string($con, $surname);
    $displaynameEsc = mysqli_real_escape_string($con, $displayname);
    $gnzNumberEsc = mysqli_real_escape_string($con, $gnzNumber);
    $phoneMobileEsc = mysqli_real_escape_string($con, $phoneMobile);
    $emailEsc = mysqli_real_escape_string($con, $email);
    $memAddr1Esc = mysqli_real_escape_string($con, $memAddr1);
    $memAddr2Esc = mysqli_real_escape_string($con, $memAddr2);
    $memAddr3Esc = mysqli_real_escape_string($con, $memAddr3);
    $memCityEsc = mysqli_real_escape_string($con, $memCity);
    $memCountryEsc = mysqli_real_escape_string($con, $memCountry);
    $memPostcodeEsc = mysqli_real_escape_string($con, $memPostcode);
    $emergAddr1Esc = mysqli_real_escape_string($con, $emergAddr1);
    $emergAddr2Esc = mysqli_real_escape_string($con, $emergAddr2);
    $emergAddr3Esc = mysqli_real_escape_string($con, $emergAddr3);

    logMsg("isEdit=$isEdit memberId=$memberId");
    logMsg("firstname=$firstname surname=$surname displayname=$displayname");
    logMsg("classId=$classId statusId=$statusId");

    // Validate required fields
    if (empty($firstname) || empty($surname) || empty($displayname) || $classId === 0 || $statusId === 0) {
        logMsg("VALIDATION FAILED");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit;
    }
    logMsg("Validation passed");

    if ($isEdit) {
        // Check org access
        $q = "SELECT org FROM members WHERE id = " . intval($memberId);
        logMsg("Looking for member " . intval($memberId));
        logMsg("Connection ping: " . ($con ? ($con->ping() ? 'OK' : 'FAIL') : 'NULL'));
        $r = mysqli_query($con, $q);
        if ($r === false) {
            logMsg("QUERY FAILED");
            logMsg("mysqli_error: '" . mysqli_error($con) . "'");
        } else {
            logMsg("Query OK, num_rows: " . mysqli_num_rows($r));
        }
        $row = $r ? mysqli_fetch_assoc($r) : null;
        if (!$row) {
            logMsg("MEMBER NOT FOUND");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Record not found']);
            exit;
        }
        if ($org > 0 && $row['org'] != $org) {
            logMsg("ORG MISMATCH - member org=" . $row['org'] . " vs session org=$org");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Record not found']);
            exit;
        }
        logMsg("Org check passed");

        // Build UPDATE query
        $q = "UPDATE members SET
            firstname = '$firstnameEsc',
            surname = '$surnameEsc',
            displayname = '$displaynameEsc',
            class = $classId,
            status = $statusId,
            date_of_birth = " . ($dateOfBirth ? "'$dateOfBirth'" : "NULL") . ",
            gnz_number = '$gnzNumberEsc',
            phone_mobile = '$phoneMobileEsc',
            email = '$emailEsc',
            medical_expire = " . ($medicalExpire ? "'$medicalExpire'" : "NULL") . ",
            bfr_expire = " . ($bfrExpire ? "'$bfrExpire'" : "NULL") . ",
            gone_solo = $goneSolo,
            enable_email = $enableEmail,
            official_observer = $officialObserver,
            mem_addr1 = '$memAddr1Esc',
            mem_addr2 = '$memAddr2Esc',
            mem_addr3 = '$memAddr3Esc',
            mem_city = '$memCityEsc',
            mem_country = '$memCountryEsc',
            mem_postcode = '$memPostcodeEsc',
            emerg_addr1 = '$emergAddr1Esc',
            emerg_addr2 = '$emergAddr2Esc',
            emerg_addr3 = '$emergAddr3Esc'
            WHERE id = $memberId";
    } else {
        // Build INSERT query
        $q = "INSERT INTO members (
            org, firstname, surname, displayname, class, status,
            date_of_birth, gnz_number, phone_mobile, email,
            medical_expire, bfr_expire, gone_solo, enable_email, official_observer,
            mem_addr1, mem_addr2, mem_addr3, mem_city, mem_country, mem_postcode,
            emerg_addr1, emerg_addr2, emerg_addr3
        ) VALUES (
            $org, '$firstnameEsc', '$surnameEsc', '$displaynameEsc', $classId, $statusId,
            " . ($dateOfBirth ? "'$dateOfBirth'" : "NULL") . ", '$gnzNumberEsc', '$phoneMobileEsc', '$emailEsc',
            " . ($medicalExpire ? "'$medicalExpire'" : "NULL") . ", " . ($bfrExpire ? "'$bfrExpire'" : "NULL") . ",
            $goneSolo, $enableEmail, $officialObserver,
            '$memAddr1Esc', '$memAddr2Esc', '$memAddr3Esc', '$memCityEsc', '$memCountryEsc', '$memPostcodeEsc',
            '$emergAddr1Esc', '$emergAddr2Esc', '$emergAddr3Esc'
        )";
    }

    logMsg("Executing query: " . substr($q, 0, 200));
    if (mysqli_query($con, $q)) {
        logMsg("QUERY SUCCESS");
        $newId = $isEdit ? $memberId : mysqli_insert_id($con);
        logMsg("newId=$newId");

        // Update roles
        $delR = mysqli_query($con, "DELETE FROM role_member WHERE member_id = " . intval($newId));
        logMsg("Deleted old roles" . ($delR ? "" : " FAILED: " . mysqli_error($con)));

        foreach ($roles as $roleId) {
            $roleId = intval($roleId);
            if ($roleId > 0) {
                $insR = mysqli_query($con, "INSERT INTO role_member (org, role_id, member_id) VALUES (" . intval($org) . ", $roleId, $newId)");
                logMsg("Inserted role $roleId" . ($insR ? "" : " FAILED: " . mysqli_error($con)));
            }
        }

        // Handle photo upload
        $photoUrl = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $maxSize = 2 * 1024 * 1024;
            $file = $_FILES['photo'];

            if ($file['size'] <= $maxSize && in_array($file['type'], $allowedTypes)) {
                $srcImage = null;
                switch ($file['type']) {
                    case 'image/jpeg': $srcImage = @imagecreatefromjpeg($file['tmp_name']); break;
                    case 'image/png':  $srcImage = @imagecreatefrompng($file['tmp_name']); break;
                    case 'image/webp': $srcImage = @imagecreatefromwebp($file['tmp_name']); break;
                }

                if ($srcImage) {
                    $w = imagesx($srcImage);
                    $h = imagesy($srcImage);
                    $maxDim = 400;
                    if ($w > $maxDim || $h > $maxDim) {
                        $ratio = $w / $h;
                        if ($w > $h) {
                            $newW = $maxDim;
                            $newH = intval($maxDim / $ratio);
                        } else {
                            $newH = $maxDim;
                            $newW = intval($maxDim * $ratio);
                        }
                        $dstImage = imagecreatetruecolor($newW, $newH);
                        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newW, $newH, $w, $h);
                        imagedestroy($srcImage);
                        $srcImage = $dstImage;
                    }

                    $destPath = __DIR__ . '/../img/members/' . $newId . '.jpg';
                    imagejpeg($srcImage, $destPath, 80);
                    imagedestroy($srcImage);
                    $photoUrl = 'img/members/' . $newId . '.jpg';
                    logMsg("Photo saved to $destPath");
                }
            } else {
                logMsg("Photo rejected: type=" . $file['type'] . " size=" . $file['size']);
            }
        }

        header('Content-Type: application/json');
        logMsg("ABOUT TO ECHO JSON RESPONSE");
        echo json_encode([
            'success' => true,
            'message' => $isEdit ? 'Member updated successfully' : 'Member created successfully',
            'member_id' => $newId,
            'photo_url' => $photoUrl
        ]);
        logMsg("JSON RESPONSE SENT");
    } else {
        logMsg("QUERY ERROR: " . mysqli_error($con));
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error saving member: ' . mysqli_error($con)]);
    }

    mysqli_close($con);
    apiExit();
}

// Return form data
header('Content-Type: application/json');
echo json_encode([
    'classes' => $classes,
    'statuses' => $statuses,
    'roles' => $roles,
    'member' => $member
]);
apiExit();