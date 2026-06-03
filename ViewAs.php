<?php
session_start();
$org = 0;
if (isset($_SESSION['org'])) $org = $_SESSION['org'];

if (isset($_SESSION['security'])) {
    if (!($_SESSION['security'] & 128)) {
        die("Security level too low for this page");
    }
} else {
    header('Location: /Login.php');
    die("Please logon");
}

$con_params = require('./config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);

$users = [];
if (!mysqli_connect_errno()) {
    $r = mysqli_query($con, "SELECT u.id, u.name, u.securitylevel, u.member FROM users u ORDER BY u.name ASC");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $users[] = $row;
        }
    }
}

$levels = [
    1   => 'Member',
    2   => 'Booking Admin',
    3   => 'Member + Booking Admin',
    4   => 'Daily Ops',
    5   => 'Member + Daily Ops',
    6   => 'Booking Admin + Daily Ops',
    7   => 'Member + Booking Admin + Daily Ops',
    8   => 'CFO/Treasurer',
    9   => 'Member + CFO',
    12  => 'Daily Ops + CFO',
    16  => 'CFI',
    32  => 'Engineer',
    48  => 'CFI + Engineer',
    56  => 'CFO + CFI + Engineer',
    64  => 'Admin',
    72  => 'Admin + CFO',
    120 => 'Admin + Engineer + CFI + CFO',
    128 => 'God / Super Admin',
    255 => 'Full Access (All)',
];
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>View Homepage As...</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style><?php $inc = "./orgs/" . $org . "/heading2.css"; include $inc; ?></style>
    <style><?php $inc = "./orgs/" . $org . "/menu1.css"; include $inc; ?></style>
    <script>function goBack() {window.history.back()}</script>
    <style>
        * {box-sizing: border-box;}
        body {margin: 0;font-family: Arial, Helvetica, sans-serif;background: #f0f0ff;}
        #container {max-width: 600px;margin: 0 auto;padding: 20px;}
        #entry {background: #fff;border-radius: 8px;padding: 24px;box-shadow: 0 2px 8px rgba(0,0,0,0.1);}
        .page-title {font-size: 18px;font-weight: bold;color: #063552;margin: 0 0 4px 0;}
        .page-subtitle {font-size: 14px;color: #555;margin: 0 0 20px 0;}
        .field-label {display: block;font-size: 13px;font-weight: bold;color: #333;margin-bottom: 6px;}
        .or-divider {text-align: center;font-size: 13px;color: #999;margin: 16px 0;font-weight: bold;}
        select {width: 100%;font-size: 16px;padding: 10px 12px;border: 2px solid #ccc;border-radius: 6px;background: #fff;transition: border-color 0.2s;}
        select:focus {border-color: #063552;outline: none;box-shadow: 0 0 0 3px rgba(6,53,82,0.1);}
        .open-btn {width: 100%;padding: 12px;font-size: 16px;font-weight: bold;color: #f26120;background: #063552;border: none;border-radius: 6px;cursor: pointer;margin-top: 16px;transition: background 0.2s;}
        .open-btn:hover {background: #052040;}
        .back-link {display: inline-block;margin-top: 16px;font-size: 13px;color: #0000c0;text-decoration: none;}
        .back-link:hover {text-decoration: underline;}
    </style>
    <script>
        function openHomeAsUser() {
            var sel = document.getElementById('user');
            var opt = sel.options[sel.selectedIndex];
            var val = opt.getAttribute('data-level');
            var memberId = opt.getAttribute('data-member');
            if (val) {
                var url = '/home?as=' + val;
                if (memberId && memberId !== '0') url += '&edit_favs=1&edit_member_id=' + memberId;
                window.open(url, '_blank');
            }
        }
        function openHomeAsLevel() {
            var sel = document.getElementById('level');
            var val = sel.options[sel.selectedIndex].value;
            var url = '/home?as=' + val;
            window.open(url, '_blank');
        }
    </script>
</head>
<body>
<?php include __DIR__ . '/helpers/dev_mode_banner.php' ?>
<?php $inc = "./orgs/" . $org . "/heading2.txt"; include $inc; ?>
<?php $inc = "./orgs/" . $org . "/menu1.txt"; include $inc; ?>
<div id="container">
    <div id="entry">
        <p class="page-title">View Homepage As...</p>
        <p class="page-subtitle">Pick a user to match their role, or choose a security level directly. Opens in a new tab.</p>

        <form onsubmit="event.preventDefault(); openHomeAsUser();">
            <label class="field-label" for="user">Pick a User:</label>
            <select id="user">
                <option value="">-- Select a user --</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?php echo $u['id']; ?>" data-level="<?php echo $u['securitylevel']; ?>" data-member="<?php echo intval($u['member']); ?>">
                        <?php echo htmlspecialchars($u['name']); ?> (level <?php echo $u['securitylevel']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p style="margin:10px 0 0;font-size:13px;color:#888;">You can click the stars to edit this user's favourites.</p>
            <button type="submit" class="open-btn">Open Homepage as This User</button>
        </form>

        <div class="or-divider">&mdash; or &mdash;</div>

        <form onsubmit="event.preventDefault(); openHomeAsLevel();">
            <label class="field-label" for="level">Security Level:</label>
            <select id="level">
                <?php foreach ($levels as $val => $label): ?>
                    <option value="<?php echo $val; ?>"><?php echo $label; ?> (<?php echo $val; ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="open-btn">Open Homepage as This Role</button>
        </form>
        <a class="back-link" href="home">&larr; Back to Home</a>
    </div>
</div>
</body>
</html>
