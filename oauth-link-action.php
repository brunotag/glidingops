<?php
session_start();

require_once __DIR__ . '/helpers/logging.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Login.php');
    exit;
}

$pending_email = $_SESSION['oauth_pending_email'] ?? '';
$pending_provider = $_SESSION['oauth_pending_provider'] ?? '';
$pending_provider_id = $_SESSION['oauth_pending_provider_id'] ?? '';

if (empty($pending_email) || empty($pending_provider) || empty($pending_provider_id)) {
    header('Location: oauth-link.php');
    exit;
}

$existing_user = trim($_POST['existing_user'] ?? '');
$existing_password = md5(trim($_POST['existing_password'] ?? ''));

if (empty($existing_user) || empty($existing_password)) {
    header('Location: oauth-link.php?error=wrong_password');
    exit;
}

require_once __DIR__ . '/helpers/database.php';
$con = open_gliding_db();
if (mysqli_connect_errno()) {
    logMsg("oauth-link-action: DB connection failed: " . mysqli_connect_error());
    header('Location: oauth-link.php?error=db_error');
    exit;
}

$stmt = mysqli_prepare($con, "SELECT id, password, member, org, name FROM users WHERE usercode = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $existing_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    mysqli_close($con);
    header('Location: oauth-link.php?error=no_user');
    exit;
}

if ($user['password'] !== $existing_password) {
    mysqli_close($con);
    header('Location: oauth-link.php?error=wrong_password');
    exit;
}

$now = date('Y-m-d H:i:s');
$stmt = mysqli_prepare($con, "INSERT INTO user_providers (user_id, provider, provider_id, created_at, last_login)
                               VALUES (?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE last_login = VALUES(last_login)");
mysqli_stmt_bind_param($stmt, 'issss', $user['id'], $pending_provider, $pending_provider_id, $now, $now);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$desc = 'Linked ' . ucfirst($pending_provider) . ' account to user';
if (!isset($user['member']) || $user['member'] === null) {
    $q = "INSERT INTO audit (userid, description) VALUES (" . intval($user['id']) . ", '" . mysqli_real_escape_string($con, $desc) . "')";
} else {
    $q = "INSERT INTO audit (userid, memberid, description) VALUES (" . intval($user['id']) . ", " . intval($user['member']) . ", '" . mysqli_real_escape_string($con, $desc) . "')";
}
mysqli_query($con, $q);

$_SESSION['userid'] = $user['id'];
$_SESSION['who'] = $existing_user;
$_SESSION['memberid'] = $user['member'];
$_SESSION['org'] = $user['org'];
if ($_SESSION['org'] === null) $_SESSION['org'] = 0;
require_once __DIR__ . '/helpers/permissions.php';
$_SESSION['permissions'] = load_user_permissions($con, $user['id']);
$_SESSION['pagesortdata'] = array_fill(0, 65, 0);
$_SESSION['dispname'] = $user['name'];

if ($_SESSION['org'] != 0) {
    $q = "SELECT timezone FROM organisations WHERE id = " . intval($_SESSION['org']);
    $r2 = mysqli_query($con, $q);
    if ($r2) {
        $row2 = mysqli_fetch_array($r2);
        $_SESSION['timezone'] = $row2[0];
    }
}

$pending_photo_url = $_SESSION['oauth_pending_photo_url'] ?? '';
$pending_provider_for_photo = $pending_provider;

unset(
    $_SESSION['oauth_pending_email'],
    $_SESSION['oauth_pending_provider'],
    $_SESSION['oauth_pending_provider_id'],
    $_SESSION['oauth_pending_photo_url']
);

if (!empty($pending_photo_url) && !empty($user['member'])) {
    require_once __DIR__ . '/helpers/oauth-photo-helper.php';
    $existingPhoto = __DIR__ . '/img/members/' . intval($user['member']) . '.jpg';
    if (!file_exists($existingPhoto)) {
        saveSocialPhoto($pending_photo_url, $user['member']);
    } else {
        $_SESSION['social_photo_url'] = $pending_photo_url;
        $_SESSION['social_photo_provider'] = $pending_provider_for_photo;
        mysqli_close($con);
        header('Location: oauth-photo.php?linked=1');
        exit;
    }
}

mysqli_close($con);
header('Location: oauth-link.php?success=linked');
exit;
