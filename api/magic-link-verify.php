<?php
require_once __DIR__ . '/../helpers/api-base.php';

require __DIR__ . '/../config/session.php';
$remember = !isset($_GET['remember']) || $_GET['remember'] === '1';
session_set_cookie_params($remember ? SESSION_LIFETIME_REMEMBERED : SESSION_LIFETIME_NOT_REMEMBERED, "/");
ini_set('session.gc_maxlifetime', $remember ? SESSION_LIFETIME_REMEMBERED : 1440);
session_start();

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    header('Location: /Login.php?error=invalid_link');
    apiExit();
}

$con_params = require(__DIR__ . '/../config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);

if (mysqli_connect_errno()) {
    header('Location: /Login.php?error=server_error');
    apiExit();
}

$stmt = mysqli_prepare($con, "SELECT id, user_id, created_at, used_at FROM magic_link_tokens WHERE token = ?");
mysqli_stmt_bind_param($stmt, 's', $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    mysqli_close($con);
    header('Location: /Login.php?error=invalid_link');
    apiExit();
}

if ($row['used_at'] !== null) {
    mysqli_close($con);
    header('Location: /Login.php?error=link_used');
    apiExit();
}

$createdAt = strtotime($row['created_at']);
if (time() - $createdAt > 900) {
    mysqli_close($con);
    header('Location: /Login.php?error=link_expired');
    apiExit();
}

$stmt2 = mysqli_prepare($con, "UPDATE magic_link_tokens SET used_at = NOW() WHERE id = ?");
mysqli_stmt_bind_param($stmt2, 'i', $row['id']);
mysqli_stmt_execute($stmt2);

$stmt3 = mysqli_prepare($con, "SELECT id, usercode, member, org, name, force_pw_reset FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt3, 'i', $row['user_id']);
mysqli_stmt_execute($stmt3);
$userResult = mysqli_stmt_get_result($stmt3);
$user = mysqli_fetch_assoc($userResult);

if (!$user) {
    mysqli_close($con);
    header('Location: /Login.php?error=invalid_link');
    apiExit();
}

$_SESSION['userid'] = $user['id'];
$_SESSION['who'] = $user['usercode'];
$_SESSION['memberid'] = $user['member'];
$_SESSION['org'] = $user['org'];
if ($_SESSION['org'] === null) $_SESSION['org'] = 0;
require_once __DIR__ . '/../helpers/permissions.php';
$_SESSION['permissions'] = load_user_permissions($con, $user['id']);
$_SESSION['dispname'] = $user['name'];

if ($_SESSION['org'] != 0) {
    $tzQuery = mysqli_query($con, "SELECT timezone FROM organisations WHERE id = " . intval($_SESSION['org']));
    $tzRow = mysqli_fetch_array($tzQuery);
    $_SESSION['timezone'] = $tzRow ? $tzRow[0] : 'UTC';
}

$desc = 'Login via magic link';
if (!isset($_SESSION['memberid']) || $_SESSION['memberid'] === null) {
    $auditStmt = mysqli_prepare($con, "INSERT INTO audit (userid, description) VALUES (?, ?)");
    mysqli_stmt_bind_param($auditStmt, 'is', $user['id'], $desc);
} else {
    $auditStmt = mysqli_prepare($con, "INSERT INTO audit (userid, memberid, description) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($auditStmt, 'iis', $user['id'], $_SESSION['memberid'], $desc);
}
mysqli_stmt_execute($auditStmt);
session_regenerate_id(true);

mysqli_close($con);

$_SESSION['auth_via_magic_link'] = 1;

if ($user['force_pw_reset'] == 1) {
    header('Location: /PasswordChange');
} else {
    header('Location: /home');
}
apiExit();
