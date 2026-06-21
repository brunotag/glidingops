<?php
require_once __DIR__ . '/../helpers/api-base.php';
require_once __DIR__ . '/../helpers/mail.php';

session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiExitWithError('Method not allowed');
}

$input = isset($_POST['email']) ? trim(strtolower($_POST['email'])) : '';
$remember = !empty($_POST['remember']);
if (empty($input) || strlen($input) < 2) {
    echo json_encode(['success' => true]);
    apiExit();
}

require_once __DIR__ . '/../helpers/database.php';
$con = open_gliding_db();
if (mysqli_connect_errno()) {
    apiExitWithError('Database connection failed');
}

function sendMagicLink($con, $userId, $userName, $memberEmail, $usercode, $remember) {
    $countStmt = mysqli_prepare($con, "SELECT COUNT(*) as cnt FROM magic_link_tokens WHERE user_id = ? AND used_at IS NULL AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    mysqli_stmt_bind_param($countStmt, 'i', $userId);
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $countRow = mysqli_fetch_assoc($countResult);
    if ($countRow && intval($countRow['cnt']) >= 3) {
        return;
    }

    $token = bin2hex(random_bytes(32));
    $stmt = mysqli_prepare($con, "INSERT INTO magic_link_tokens (user_id, token, created_at) VALUES (?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, 'is', $userId, $token);
    if (!mysqli_stmt_execute($stmt)) {
        logMsg("magic-link-request: INSERT token failed for user $userId: " . mysqli_stmt_error($stmt));
        return;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $link = "$scheme://$host/api/magic-link-verify?token=" . urlencode($token) . "&remember=" . ($remember ? '1' : '0');

    $message = "Hi $userName,\n\nClick the link below to log in to Gliding Ops:\n$link\n\nYour username is: $usercode\n\nThis link expires in 15 minutes and can only be used once.\n\nIf you did not request this link, please ignore this email.";

    $recipients = [];
    if (!empty($memberEmail) && filter_var($memberEmail, FILTER_VALIDATE_EMAIL)) {
        $recipients[$memberEmail] = true;
    }
    if ($usercode !== null && filter_var($usercode, FILTER_VALIDATE_EMAIL) && !isset($recipients[$usercode])) {
        $recipients[$usercode] = true;
    }

    foreach ($recipients as $addr => $_) {
        Mail::SendMailPlainText($addr, 'Your Gliding Ops Login Link', $message);
    }
}

$stmt = mysqli_prepare($con, "SELECT DISTINCT u.id, u.name, u.usercode, m.email as member_email FROM users u LEFT JOIN members m ON m.id = u.member WHERE u.usercode = ? OR (m.email IS NOT NULL AND m.email = ?)");
mysqli_stmt_bind_param($stmt, 'ss', $input, $input);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$foundUser = false;
while ($row = mysqli_fetch_assoc($result)) {
    $foundUser = true;
    sendMagicLink($con, $row['id'], $row['name'], $row['member_email'], $row['usercode'], $remember);
}

if (!$foundUser) {
    $memberStmt = mysqli_prepare($con, "
        SELECT m.id, m.displayname, m.email, m.org
        FROM members m
        WHERE m.email = ?
          AND NOT EXISTS (SELECT 1 FROM users WHERE users.member = m.id)
        LIMIT 1
    ");
    mysqli_stmt_bind_param($memberStmt, 's', $input);
    mysqli_stmt_execute($memberStmt);
    $memberResult = mysqli_stmt_get_result($memberStmt);

    if ($memberRow = mysqli_fetch_assoc($memberResult)) {
        $tempPw = md5(bin2hex(random_bytes(16)));
        $memOrg = intval($memberRow['org']);
        $memId = intval($memberRow['id']);
        $insertStmt = mysqli_prepare($con, "
            INSERT INTO users (name, org, usercode, password, force_pw_reset, member)
            VALUES (?, ?, ?, ?, 1, ?)
        ");
        mysqli_stmt_bind_param($insertStmt, 'sissi',
            $memberRow['displayname'], $memOrg, $memberRow['email'],
            $tempPw, $memId
        );
        if (mysqli_stmt_execute($insertStmt)) {
            $newUserId = mysqli_insert_id($con);
            sendMagicLink($con, $newUserId, $memberRow['displayname'], $memberRow['email'], $memberRow['email'], $remember);
        } else {
            logMsg("magic-link-request: INSERT user failed for member {$memberRow['id']}: " . mysqli_stmt_error($insertStmt));
        }
    }
}

mysqli_close($con);

echo json_encode(['success' => true]);
apiExit();
