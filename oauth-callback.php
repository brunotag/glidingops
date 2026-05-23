<?php
session_start();

require_once __DIR__ . '/helpers/logging.php';
require_once __DIR__ . '/helpers/oauth-photo-helper.php';

$error = $_GET['error'] ?? '';
$state = $_GET['state'] ?? '';
$code = $_GET['code'] ?? '';

if ($error) {
    logMsg("OAuth provider error: $error");
    unset($_SESSION['oauth_state'], $_SESSION['oauth_provider']);
    header('Location: Login.php?error=oauth_provider_error');
    exit;
}

if (empty($state) || $state !== ($_SESSION['oauth_state'] ?? '')) {
    logMsg("OAuth state mismatch: expected=" . ($_SESSION['oauth_state'] ?? 'none') . " got=$state");
    unset($_SESSION['oauth_state'], $_SESSION['oauth_provider']);
    header('Location: Login.php?error=oauth_state_mismatch');
    exit;
}

$provider = $_SESSION['oauth_provider'] ?? '';
if (!in_array($provider, ['google', 'facebook'])) {
    logMsg("OAuth invalid provider: $provider");
    unset($_SESSION['oauth_state'], $_SESSION['oauth_provider']);
    header('Location: Login.php?error=oauth_provider_error');
    exit;
}

unset($_SESSION['oauth_state'], $_SESSION['oauth_provider']);

$oauth_config = require __DIR__ . '/config/oauth.php';
if (empty($oauth_config[$provider]['client_id']) && empty($oauth_config[$provider]['app_id'])) {
    logMsg("OAuth provider not configured: $provider");
    header('Location: Login.php?error=oauth_not_configured');
    exit;
}

$config = $oauth_config[$provider];
$redirect_uri = $oauth_config['redirect_base'] . '/oauth-callback';

$con_params = require __DIR__ . '/config/database.php';
$con_params = $con_params['gliding'];
$con = @mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);
if (mysqli_connect_errno()) {
    logMsg("OAuth DB connection failed: " . mysqli_connect_error());
    header('Location: Login.php?error=server_error');
    exit;
}

$email = '';
$provider_id = '';

switch ($provider) {
    case 'google':
        $token_response = @file_get_contents($config['token_url'], false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query([
                    'code' => $code,
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'redirect_uri' => $redirect_uri,
                    'grant_type' => 'authorization_code',
                ]),
                'timeout' => 10,
            ],
        ]));

        if ($token_response === false) {
            logMsg("Google token exchange failed");
            mysqli_close($con);
            header('Location: Login.php?error=oauth_token_exchange');
            exit;
        }

        $token_data = json_decode($token_response, true);
        $access_token = $token_data['access_token'] ?? '';

        if (empty($access_token)) {
            logMsg("Google no access_token in response");
            mysqli_close($con);
            header('Location: Login.php?error=oauth_token_exchange');
            exit;
        }

        $userinfo = @file_get_contents($config['userinfo_url'], false, stream_context_create([
            'http' => [
                'header' => "Authorization: Bearer $access_token\r\n",
                'timeout' => 10,
            ],
        ]));

        if ($userinfo === false) {
            logMsg("Google userinfo request failed");
            mysqli_close($con);
            header('Location: Login.php?error=oauth_token_exchange');
            exit;
        }

        $user_data = json_decode($userinfo, true);
        $email = $user_data['email'] ?? '';
        $provider_id = $user_data['id'] ?? '';
        break;

    case 'facebook':
        $token_url = $config['token_url'] . '?' . http_build_query([
            'client_id' => $config['app_id'],
            'redirect_uri' => $redirect_uri,
            'client_secret' => $config['app_secret'],
            'code' => $code,
        ]);

        $token_response = @file_get_contents($token_url, false, stream_context_create([
            'http' => ['timeout' => 10],
        ]));

        if ($token_response === false) {
            logMsg("Facebook token exchange failed");
            mysqli_close($con);
            header('Location: Login.php?error=oauth_token_exchange');
            exit;
        }

        $token_data = json_decode($token_response, true);
        $access_token = $token_data['access_token'] ?? '';

        if (empty($access_token)) {
            logMsg("Facebook no access_token in response");
            mysqli_close($con);
            header('Location: Login.php?error=oauth_token_exchange');
            exit;
        }

        $userinfo_url = 'https://graph.facebook.com/me?fields=id,name,email,picture.type(large)&access_token=' . urlencode($access_token);
        $userinfo = @file_get_contents($userinfo_url, false, stream_context_create([
            'http' => ['timeout' => 10],
        ]));

        if ($userinfo === false) {
            logMsg("Facebook userinfo request failed");
            mysqli_close($con);
            header('Location: Login.php?error=oauth_token_exchange');
            exit;
        }

        $user_data = json_decode($userinfo, true);
        $email = $user_data['email'] ?? '';
        $provider_id = $user_data['id'] ?? '';
        break;
}

$photo_url = getSocialPhotoUrl($provider, $user_data);

if (empty($provider_id)) {
    logMsg("OAuth $provider: no provider_id returned");
    mysqli_close($con);
    header('Location: Login.php?error=oauth_email_not_found');
    exit;
}

if (empty($email) && $provider === 'facebook') {
    logMsg("OAuth facebook: no email returned, redirecting to link page");
    $_SESSION['oauth_pending_email'] = '';
    $_SESSION['oauth_pending_provider'] = $provider;
    $_SESSION['oauth_pending_provider_id'] = $provider_id;
    $_SESSION['oauth_pending_photo_url'] = $photo_url;
    mysqli_close($con);
    header('Location: oauth-link.php?no_email=1');
    exit;
}

if (empty($email)) {
    logMsg("OAuth $provider: no email returned");
    $providerName = ucfirst($provider);
    mysqli_close($con);
    header("Location: Login.php?error=oauth_email_not_found&provider=$providerName");
    exit;
}

$stmt = mysqli_prepare($con, "SELECT u.id, u.usercode, u.member, u.org, u.securitylevel, u.name
                               FROM users u
                               WHERE u.usercode = ?
                               LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    $stmt = mysqli_prepare($con, "SELECT u.id, u.usercode, u.member, u.org, u.securitylevel, u.name
                                   FROM users u
                                   JOIN user_providers up ON up.user_id = u.id
                                   WHERE up.provider = ? AND up.provider_id = ?
                                   LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ss', $provider, $provider_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$user) {
    $stmt = mysqli_prepare($con, "SELECT u.id, u.usercode, u.member, u.org, u.securitylevel, u.name
                                   FROM users u
                                   JOIN members m ON m.id = u.member
                                   WHERE m.email = ?
                                   LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$user) {
    logMsg("OAuth $provider: no user found for email=$email provider_id=$provider_id");
    $_SESSION['oauth_pending_email'] = $email;
    $_SESSION['oauth_pending_provider'] = $provider;
    $_SESSION['oauth_pending_provider_id'] = $provider_id;
    $_SESSION['oauth_pending_photo_url'] = $photo_url;
    mysqli_close($con);
    header('Location: oauth-link.php');
    exit;
}

$_SESSION['userid'] = $user['id'];
$_SESSION['who'] = $user['usercode'];
$_SESSION['memberid'] = $user['member'];
$_SESSION['org'] = $user['org'];
if ($_SESSION['org'] === null) $_SESSION['org'] = 0;
$_SESSION['security'] = $user['securitylevel'];
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

$now = date('Y-m-d H:i:s');

$first_social_login = false;
if (!empty($provider_id)) {
    $stmt = mysqli_prepare($con, "SELECT id FROM user_providers WHERE user_id = ? AND provider = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'is', $user['id'], $provider);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $first_social_login = mysqli_stmt_num_rows($stmt) === 0;
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($con, "INSERT INTO user_providers (user_id, provider, provider_id, created_at, last_login)
                                   VALUES (?, ?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE last_login = VALUES(last_login)");
    mysqli_stmt_bind_param($stmt, 'issss', $user['id'], $provider, $provider_id, $now, $now);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

$desc = 'Login via ' . ucfirst($provider);
if (!isset($_SESSION['memberid']) || $_SESSION['memberid'] === null) {
    $q = "INSERT INTO audit (userid, description) VALUES (" . intval($user['id']) . ", '" . mysqli_real_escape_string($con, $desc) . "')";
} else {
    $q = "INSERT INTO audit (userid, memberid, description) VALUES (" . intval($user['id']) . ", " . intval($user['member']) . ", '" . mysqli_real_escape_string($con, $desc) . "')";
}
mysqli_query($con, $q);

if ($user['force_pw_reset'] ?? 0 > 0) {
    $_SESSION['force_pw_reset'] = 1;
    mysqli_close($con);
    header('Location: PasswordChange');
    exit;
}

if ($first_social_login && !empty($photo_url) && !empty($_SESSION['memberid'])) {
    $existingPhoto = __DIR__ . '/img/members/' . intval($_SESSION['memberid']) . '.jpg';
    if (!file_exists($existingPhoto)) {
        saveSocialPhoto($photo_url, $_SESSION['memberid']);
    } else {
        $_SESSION['social_photo_url'] = $photo_url;
        $_SESSION['social_photo_provider'] = $provider;
        mysqli_close($con);
        header('Location: oauth-photo.php');
        exit;
    }
}

mysqli_close($con);
header('Location: home');
exit;
