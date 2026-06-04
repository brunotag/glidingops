<?php
session_start();

$org = isset($_SESSION['org']) ? $_SESSION['org'] : 0;

require_once __DIR__ . '/helpers/permissions.php'; require_perm('users.invite');

require_once __DIR__ . '/helpers/logging.php';

$con_params = require(__DIR__ . '/config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);

if (mysqli_connect_errno()) {
    die("Database connection failed: " . mysqli_connect_error());
}

require_once __DIR__ . '/helpers/mail.php';

$flyingClasses = ['Flying','A Scheme','B Scheme','Youth','Youth-A','Youth-B','Tow Pilot','Life Flying','Dual-A'];
$classList = "'" . implode("','", $flyingClasses) . "'";

function sendInviteCustom($con, $userId, $userName, $email, $usercode, $subjectTemplate, $bodyTemplate) {
    $countStmt = mysqli_prepare($con, "SELECT COUNT(*) as cnt FROM magic_link_tokens WHERE user_id = ? AND used_at IS NULL AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    mysqli_stmt_bind_param($countStmt, 'i', $userId);
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $countRow = mysqli_fetch_assoc($countResult);
    if ($countRow && intval($countRow['cnt']) >= 3) {
        return ['ok' => false, 'reason' => 'Rate limited (3 unused tokens)'];
    }

    $token = bin2hex(random_bytes(32));
    $stmt = mysqli_prepare($con, "INSERT INTO magic_link_tokens (user_id, token, created_at) VALUES (?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, 'is', $userId, $token);
    if (!mysqli_stmt_execute($stmt)) {
        logMsg("invite-users: INSERT token failed for user $userId: " . mysqli_stmt_error($stmt));
        return ['ok' => false, 'reason' => 'Token insert failed'];
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $link = "$scheme://$host/api/magic-link-verify?token=" . urlencode($token);

    $replacements = [
        '[Name]' => $userName,
        '[email]' => $email,
        '[usercode]' => $usercode,
        '[magic link]' => $link,
    ];
    $subject = str_replace(array_keys($replacements), array_values($replacements), $subjectTemplate);
    $body = str_replace(array_keys($replacements), array_values($replacements), $bodyTemplate);

    $sent = Mail::SendMailPlainText($email, $subject, $body);
    if ($sent) {
        return ['ok' => true];
    } else {
        return ['ok' => false, 'reason' => 'Mail send rejected'];
    }
}

$defaultG1Subject = 'Your Gliding Ops account is ready';
$defaultG1Body = "Hi [Name],\n\nYou've been flying this season, so we've set up your Gliding Ops account.\n\nClick the link below to log in and set your password:\n[magic link]\n\nYour username is: [email]\n\nThis link expires in 15 minutes.\n\nWith Gliding Ops you can:\n- View and download your complete flight history\n- Check live tracking of gliders on the field\n- See your membership details and medical/currency expiry dates\n- Browse the club roster and duty schedule\n- Receive club broadcasts and messages\n- Edit your personal details anytime\n\nLog in at https://gops.wwgc.co.nz to get started.";

$defaultG2Subject = 'Still flying? Your Gliding Ops account is waiting';
$defaultG2Body = "Hi [Name],\n\nYou've been flying this season but haven't logged into Gliding Ops for a while. Click below to get back in:\n[magic link]\n\nYour username is: [usercode]\n\nThis link expires in 15 minutes.\n\nWith Gliding Ops you can:\n- View and download your complete flight history\n- Check live tracking of gliders on the field\n- See your membership details and medical/currency expiry dates\n- Browse the club roster and duty schedule\n- Receive club broadcasts and messages\n- Edit your personal details anytime\n\nNeed to reset your password? The magic link will let you set a new one.";

$g1 = [];
$g1q = "SELECT m.id, m.firstname, m.surname, m.email, m.org, mc.class,
  (SELECT COUNT(*) FROM flights f WHERE (f.pic = m.id OR f.p2 = m.id) AND f.localdate >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 YEAR), '%Y%m%d')) as flights
FROM members m
JOIN membership_class mc ON mc.id = m.class
WHERE m.status = 1
  AND m.email IS NOT NULL AND m.email != ''
  AND mc.class IN ($classList)
  AND NOT EXISTS (SELECT 1 FROM users u WHERE u.member = m.id)
HAVING flights > 0
ORDER BY m.surname, m.firstname";
$g1r = mysqli_query($con, $g1q);
if ($g1r) {
    while ($row = mysqli_fetch_assoc($g1r)) {
        $g1[] = $row;
    }
}

$g2 = [];
$g2q = "SELECT u.id as user_id, u.name as user_name, u.usercode, m.firstname, m.surname, m.email, mc.class,
  (SELECT MAX(a.eventtime) FROM audit a WHERE a.userid = u.id AND a.description = 'Login') as last_login,
  (SELECT COUNT(*) FROM flights f WHERE (f.pic = u.member OR f.p2 = u.member) AND f.localdate >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y%m%d')) as flights
FROM users u
JOIN members m ON m.id = u.member
JOIN membership_class mc ON mc.id = m.class
WHERE u.member IS NOT NULL
  AND (
    (SELECT MAX(a.eventtime) FROM audit a WHERE a.userid = u.id AND a.description = 'Login') IS NULL
    OR (SELECT MAX(a.eventtime) FROM audit a WHERE a.userid = u.id AND a.description = 'Login') < DATE_SUB(NOW(), INTERVAL 1 YEAR)
  )
  AND (SELECT COUNT(*) FROM flights f WHERE (f.pic = u.member OR f.p2 = u.member) AND f.localdate >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y%m%d')) > 0
ORDER BY m.surname, m.firstname";
$g2r = mysqli_query($con, $g2q);
if ($g2r) {
    while ($row = mysqli_fetch_assoc($g2r)) {
        $g2[] = $row;
    }
}

$totalG1 = count($g1);
$totalG2 = count($g2);
$grandTotal = $totalG1 + $totalG2;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === '1') {
    $selectedG1 = isset($_POST['g1_ids']) ? $_POST['g1_ids'] : [];
    $selectedG2 = isset($_POST['g2_ids']) ? $_POST['g2_ids'] : [];
    $g1Subject = isset($_POST['g1_subject']) ? trim($_POST['g1_subject']) : $defaultG1Subject;
    $g1Body = isset($_POST['g1_body']) ? $_POST['g1_body'] : $defaultG1Body;
    $g2Subject = isset($_POST['g2_subject']) ? trim($_POST['g2_subject']) : $defaultG2Subject;
    $g2Body = isset($_POST['g2_body']) ? $_POST['g2_body'] : $defaultG2Body;

    @ini_set('output_buffering', '0');
    @ini_set('implicit_flush', '1');
    while (ob_get_level()) ob_end_clean();

    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE HTML>
<html>
<head><title>Sending Invitations...</title>
<style>
body { font-family: Arial, sans-serif; background: #f0f0ff; margin: 20px; }
.container { max-width: 800px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
h1 { color: #063552; font-size: 20px; border-bottom: 2px solid #f26120; padding-bottom: 8px; }
.ok { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
.skip { color: #999; font-style: italic; }
.summary { margin-top: 20px; padding: 15px; background: #e8f5e9; border-radius: 6px; }
.fail-section { margin-top: 15px; padding: 15px; background: #ffebee; border-radius: 6px; }
</style>
</head>
<body>
<div class="container">
<h1>Sending Invitations</h1>
<?php
    echo str_repeat(' ', 4096) . "\n";
    flush();

    $selectedG1Set = [];
    foreach ($selectedG1 as $id) { $selectedG1Set[intval($id)] = true; }
    $selectedG2Set = [];
    foreach ($selectedG2 as $id) { $selectedG2Set[intval($id)] = true; }

    $sent = 0;
    $failed = 0;
    $totalSelected = count($selectedG1) + count($selectedG2);
    $failDetails = [];

    echo "<h2>Group 1: New account invitations</h2>\n";
    flush();

    foreach ($g1 as $m) {
        $mid = intval($m['id']);
        $fullName = $m['firstname'] . ' ' . $m['surname'];
        if (!isset($selectedG1Set[$mid])) {
            echo "<span class='skip'>" . htmlspecialchars($fullName) . " &lt;" . htmlspecialchars($m['email']) . "&gt; ... SKIPPED (not selected)</span><br>\n";
            flush();
            continue;
        }
        echo htmlspecialchars($fullName) . " &lt;" . htmlspecialchars($m['email']) . "&gt; ... ";
        flush();

        $tempPw = md5(bin2hex(random_bytes(16)));
        $memId = $mid;
        $memOrg = intval($m['org']);
        $email = $m['email'];
        $displayname = $fullName;

        $insertStmt = mysqli_prepare($con, "INSERT INTO users (name, org, usercode, password, force_pw_reset, member) VALUES (?, ?, ?, ?, 1, ?)");
        mysqli_stmt_bind_param($insertStmt, 'sissi', $displayname, $memOrg, $email, $tempPw, $memId);
        if (mysqli_stmt_execute($insertStmt)) {
            $newUserId = mysqli_insert_id($con);
            $result = sendInviteCustom($con, $newUserId, $fullName, $email, $email, $g1Subject, $g1Body);
            if ($result['ok']) {
                echo "<span class='ok'>OK</span><br>\n";
                $sent++;
            } else {
                echo "<span class='fail'>FAILED - " . htmlspecialchars($result['reason']) . "</span><br>\n";
                $failed++;
                $failDetails[] = $fullName . " (" . $email . "): " . $result['reason'];
            }
        } else {
            echo "<span class='fail'>FAILED - user creation error</span><br>\n";
            $failed++;
            $failDetails[] = $fullName . " (" . $email . "): user insert failed - " . mysqli_stmt_error($insertStmt);
        }
        flush();
    }

    echo "<h2>Group 2: Reminder invitations</h2>\n";
    flush();

    foreach ($g2 as $u) {
        $uid = intval($u['user_id']);
        $fullName = $u['firstname'] . ' ' . $u['surname'];
        if (!isset($selectedG2Set[$uid])) {
            echo "<span class='skip'>" . htmlspecialchars($fullName) . " ... SKIPPED (not selected)</span><br>\n";
            flush();
            continue;
        }
        $sendEmail = !empty($u['email']) ? $u['email'] : $u['usercode'];
        echo htmlspecialchars($fullName) . " &lt;" . htmlspecialchars($sendEmail) . "&gt; ... ";
        flush();

        $result = sendInviteCustom($con, $uid, $u['user_name'], $sendEmail, $u['usercode'], $g2Subject, $g2Body);
        if ($result['ok']) {
            echo "<span class='ok'>OK</span><br>\n";
            $sent++;
        } else {
            echo "<span class='fail'>FAILED - " . htmlspecialchars($result['reason']) . "</span><br>\n";
            $failed++;
            $failDetails[] = $fullName . " (" . $sendEmail . "): " . $result['reason'];
        }
        flush();
    }

    echo "<div class='summary'>";
    echo "<strong>Done.</strong> Sent: $sent, Failed: $failed of $totalSelected selected<br>";
    echo "<a href='/InviteUsers'>Back to preview</a>";
    echo "</div>\n";

    if ($failDetails) {
        echo "<div class='fail-section'><strong>Failed details:</strong><br>\n";
        foreach ($failDetails as $d) {
            echo htmlspecialchars($d) . "<br>\n";
        }
        echo "</div>\n";
    }

    mysqli_close($con);
    echo "</div></body></html>";
    exit;
}

mysqli_close($con);
?>
<!DOCTYPE HTML>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invite Users to Gliding Ops</title>
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { margin: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f0f0ff; padding: 20px; }
    .container { max-width: 960px; margin: auto; }
    h1 { color: #063552; font-size: 24px; border-bottom: 2px solid #f26120; padding-bottom: 8px; margin-bottom: 20px; }
    h2 { color: #063552; font-size: 18px; margin-top: 24px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    th { background: #063552; color: #f26120; padding: 8px 10px; text-align: left; font-size: 13px; }
    td { padding: 6px 10px; border-bottom: 1px solid #ddd; font-size: 13px; }
    tr:hover td { background: #f5f5f5; }
    .count-badge { display: inline-block; background: #f26120; color: #fff; border-radius: 12px; padding: 2px 10px; font-size: 14px; font-weight: bold; margin-left: 8px; }
    .confirm-box { background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 20px; margin: 20px 0; text-align: center; }
    .confirm-box p { font-size: 15px; color: #856404; margin-bottom: 15px; }
    .confirm-box .selected-count { font-weight: bold; color: #063552; }
    .no-data { color: #888; font-style: italic; padding: 12px; }
    .back-link { display: inline-block; margin-top: 16px; color: #063552; }
    .toggle-link { font-size: 13px; color: #063552; cursor: pointer; margin-left: 10px; text-decoration: underline; }
    .template-section { margin: 20px 0; }
    .template-section summary { color: #063552; font-size: 15px; font-weight: bold; cursor: pointer; padding: 8px 0; }
    .template-section label { font-weight: bold; font-size: 13px; margin-top: 10px; display: block; }
    .template-section input[type="text"] { width: 100%; padding: 8px; font-size: 14px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    .template-section textarea { width: 100%; padding: 8px; font-size: 13px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-family: monospace; }
    .placeholder-hint { font-size: 12px; color: #888; margin-bottom: 4px; }
    .placeholder-hint code { background: #f5f5f5; padding: 1px 4px; border-radius: 2px; }
  </style>
</head>
<body>
<div class="container">
  <h1>Invite Users to Gliding Ops</h1>

  <form method="post" action="/InviteUsers" id="inviteForm">

  <h2>
    Group 1: Members without user accounts
    <span class="count-badge"><?php echo $totalG1; ?></span>
    <span class="toggle-link" onclick="toggleAll('g1', true)">Select all</span>
    <span class="toggle-link" onclick="toggleAll('g1', false)">Deselect all</span>
  </h2>
  <p style="color:#666;font-size:13px;">Active members in flying classes who have flown in the last 2 years but have no user account. An account will be auto-created with a random password (force reset on first login).</p>
  <?php if ($totalG1 > 0): ?>
  <table>
    <thead><tr><th style="width:36px"><input type="checkbox" id="g1_all" onchange="toggleAll('g1', this.checked)"></th><th>Name</th><th>Email</th><th>Class</th><th>Flights (2yr)</th></tr></thead>
    <tbody>
    <?php foreach ($g1 as $m): ?>
      <tr>
        <td><input type="checkbox" name="g1_ids[]" value="<?php echo intval($m['id']); ?>" class="g1-chk" checked></td>
        <td><?php echo htmlspecialchars($m['firstname'] . ' ' . $m['surname']); ?></td>
        <td><?php echo htmlspecialchars($m['email']); ?></td>
        <td><?php echo htmlspecialchars($m['class']); ?></td>
        <td><?php echo intval($m['flights']); ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p class="no-data">No members found.</p>
  <?php endif; ?>

  <h2>
    Group 2: Users who haven't logged in recently
    <span class="count-badge"><?php echo $totalG2; ?></span>
    <span class="toggle-link" onclick="toggleAll('g2', true)">Select all</span>
    <span class="toggle-link" onclick="toggleAll('g2', false)">Deselect all</span>
  </h2>
  <p style="color:#666;font-size:13px;">Users with a linked member who haven't logged in for over a year but have flown in the last year. A magic link login will be sent.</p>
  <?php if ($totalG2 > 0): ?>
  <table>
    <thead><tr><th style="width:36px"><input type="checkbox" id="g2_all" onchange="toggleAll('g2', this.checked)"></th><th>Name</th><th>Email</th><th>Class</th><th>Last Login</th><th>Flights (1yr)</th></tr></thead>
    <tbody>
    <?php foreach ($g2 as $u): ?>
      <tr>
        <td><input type="checkbox" name="g2_ids[]" value="<?php echo intval($u['user_id']); ?>" class="g2-chk" checked></td>
        <td><?php echo htmlspecialchars($u['firstname'] . ' ' . $u['surname']); ?></td>
        <td><?php echo htmlspecialchars(!empty($u['email']) ? $u['email'] : $u['usercode']); ?></td>
        <td><?php echo htmlspecialchars($u['class']); ?></td>
        <td><?php echo $u['last_login'] ? htmlspecialchars($u['last_login']) : '<em>Never</em>'; ?></td>
        <td><?php echo intval($u['flights']); ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p class="no-data">No users found.</p>
  <?php endif; ?>

  <details class="template-section">
    <summary>Edit Email Templates <span style="font-weight:normal;color:#888;font-size:13px;">(click to expand)</span></summary>

    <label>Group 1 — New Account Invitation Subject:</label>
    <input type="text" name="g1_subject" value="<?php echo htmlspecialchars($defaultG1Subject); ?>">

    <label>Group 1 — Body:</label>
    <div class="placeholder-hint">Available placeholders: <code>[Name]</code> <code>[email]</code> <code>[usercode]</code> <code>[magic link]</code></div>
    <textarea name="g1_body" rows="12"><?php echo htmlspecialchars($defaultG1Body); ?></textarea>

    <label>Group 2 — Reminder Subject:</label>
    <input type="text" name="g2_subject" value="<?php echo htmlspecialchars($defaultG2Subject); ?>">

    <label>Group 2 — Body:</label>
    <div class="placeholder-hint">Available placeholders: <code>[Name]</code> <code>[email]</code> <code>[usercode]</code> <code>[magic link]</code></div>
    <textarea name="g2_body" rows="12"><?php echo htmlspecialchars($defaultG2Body); ?></textarea>
  </details>

  <?php if ($grandTotal > 0): ?>
  <div class="confirm-box">
    <p>
      <strong>Send <span class="selected-count" id="selectedCount"><?php echo $grandTotal; ?></span> invitation emails to selected recipients?</strong>
    </p>
    <input type="hidden" name="confirm" value="1">
    <button type="submit" class="btn btn-warning btn-lg" id="sendBtn">
      Send Selected Invitations
    </button>
  </div>
  <?php endif; ?>

  </form>

  <a href="/home" class="back-link">&larr; Back to Home</a>
</div>

<script>
function toggleAll(group, state) {
    var chk = document.querySelectorAll('.' + group + '-chk');
    for (var i = 0; i < chk.length; i++) { chk[i].checked = state; }
    document.getElementById(group + '_all').checked = state;
    updateCount();
}

function updateCount() {
    var g1 = document.querySelectorAll('.g1-chk:checked').length;
    var g2 = document.querySelectorAll('.g2-chk:checked').length;
    var el = document.getElementById('selectedCount');
    if (el) el.textContent = g1 + g2;
}

var chks = document.querySelectorAll('.g1-chk, .g2-chk');
for (var i = 0; i < chks.length; i++) { chks[i].addEventListener('change', updateCount); }

document.getElementById('inviteForm').addEventListener('submit', function(e) {
    var g1c = document.querySelectorAll('.g1-chk:checked').length;
    var g2c = document.querySelectorAll('.g2-chk:checked').length;
    if (g1c + g2c === 0) {
        if (!confirm('No recipients selected. Submit anyway?')) { e.preventDefault(); return; }
    }
    var btn = document.getElementById('sendBtn');
    btn.disabled = true;
    btn.textContent = 'Sending...';
});
</script>
</body>
</html>
