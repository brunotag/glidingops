<?php
session_start();

// Check authentication - require security level 1 (member access)
if (!isset($_SESSION['security']) || !($_SESSION['security'] & 1)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Please log in']);
    exit;
}

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
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get member_id if in edit mode
$memberId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Get membership classes
$classes = [];
$q = "SELECT * FROM membership_class";
if ($org > 0) {
    $q .= " WHERE org = " . $org . " OR org = 0";
}
$q .= " ORDER BY class";
$r = mysqli_query($con, $q);
while ($row = mysqli_fetch_assoc($r)) {
    $classes[] = ['id' => $row['id'], 'class' => $row['class']];
}

// Get membership statuses
$statuses = [];
$q = "SELECT * FROM membership_status ORDER BY status";
$r = mysqli_query($con, $q);
while ($row = mysqli_fetch_assoc($r)) {
    $statuses[] = ['id' => $row['id'], 'status' => $row['status']];
}

// Get roles
$roles = [];
$q = "SELECT * FROM roles WHERE org = " . $org . " OR org = 0 ORDER BY name";
$r = mysqli_query($con, $q);
while ($row = mysqli_fetch_assoc($r)) {
    $roles[] = ['id' => $row['id'], 'name' => $row['name']];
}

// Get member data if editing
$member = null;
if ($memberId) {
    // Check security level for edit (need level 6)
    if (!isset($_SESSION['security']) || !($_SESSION['security'] & 6)) {
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
    // Check security level for edit (need level 6)
    if (!isset($_SESSION['security']) || !($_SESSION['security'] & 6)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Security level too low']);
        exit;
    }

    $con = mysqli_connect(
        $con_params['hostname'],
        $con_params['username'],
        $con_params['password'],
        $con_params['dbname']
    );

    if (mysqli_connect_errno()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    $memberId = isset($_POST['id']) ? intval($_POST['id']) : null;
    $isEdit = $memberId !== null;

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
    $gnzNumber = isset($_POST['gnz_number']) ? trim($_POST['gnz_number']) : '';
    $phoneMobile = isset($_POST['phone_mobile']) ? trim($_POST['phone_mobile']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $medicalExpire = isset($_POST['medical_expire']) && !empty($_POST['medical_expire']) ? $_POST['medical_expire'] : null;
    $bfrExpire = isset($_POST['bfr_expire']) && !empty($_POST['bfr_expire']) ? $_POST['bfr_expire'] : null;
    $goneSolo = isset($_POST['gone_solo']) ? 1 : 0;
    $officialObserver = isset($_POST['official_observer']) ? 1 : 0;
    $enableEmail = 1; // Always true
    $roles = isset($_POST['roles']) ? $_POST['roles'] : [];

    // Escape strings for SQL
    $firstnameEsc = mysqli_real_escape_string($con, $firstname);
    $surnameEsc = mysqli_real_escape_string($con, $surname);
    $displaynameEsc = mysqli_real_escape_string($con, $displayname);
    $gnzNumberEsc = mysqli_real_escape_string($con, $gnzNumber);
    $phoneMobileEsc = mysqli_real_escape_string($con, $phoneMobile);
    $emailEsc = mysqli_real_escape_string($con, $email);

    if ($isEdit) {
        // Check org access
        $q = "SELECT org FROM members WHERE id = " . $memberId;
        $r = mysqli_query($con, $q);
        $row = mysqli_fetch_assoc($r);
        if (!$row || ($org > 0 && $row['org'] != $org)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Record not found']);
            exit;
        }

        // Update
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
            official_observer = $officialObserver
            WHERE id = $memberId";
    } else {
        // Insert
        $q = "INSERT INTO members (
            org, firstname, surname, displayname, class, status,
            date_of_birth, gnz_number, phone_mobile, email,
            medical_expire, bfr_expire, gone_solo, enable_email, official_observer
        ) VALUES (
            $org, '$firstnameEsc', '$surnameEsc', '$displaynameEsc', $classId, $statusId,
            " . ($dateOfBirth ? "'$dateOfBirth'" : "NULL") . ", '$gnzNumberEsc', '$phoneMobileEsc', '$emailEsc',
            " . ($medicalExpire ? "'$medicalExpire'" : "NULL") . ", " . ($bfrExpire ? "'$bfrExpire'" : "NULL") . ",
            $goneSolo, $enableEmail, $officialObserver
        )";
    }

    if (mysqli_query($con, $q)) {
        $newId = $isEdit ? $memberId : mysqli_insert_id($con);

        // Update roles
        // Delete existing roles
        mysqli_query($con, "DELETE FROM role_member WHERE member_id = " . $newId);

        // Insert new roles
        foreach ($roles as $roleId) {
            $roleId = intval($roleId);
            if ($roleId > 0) {
                mysqli_query($con, "INSERT INTO role_member (org, role_id, member_id) VALUES ($org, $roleId, $newId)");
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $isEdit ? 'Member updated successfully' : 'Member created successfully',
            'member_id' => $newId
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error saving member: ' . mysqli_error($con)]);
    }

    mysqli_close($con);
    exit;
}

// Return form data
echo json_encode([
    'classes' => $classes,
    'statuses' => $statuses,
    'roles' => $roles,
    'member' => $member
]);