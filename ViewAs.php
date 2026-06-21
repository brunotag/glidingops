<?php
session_start();
$org = 0;
if (isset($_SESSION['org'])) $org = $_SESSION['org'];

require_once __DIR__ . '/helpers/permissions.php'; require_perm('god.view-as');

require_once __DIR__ . '/helpers/database.php';
$con = open_gliding_db();

$users = [];
if (!mysqli_connect_errno()) {
    $r = mysqli_query($con, "SELECT u.id, u.name, u.member FROM users u ORDER BY u.name ASC");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $users[] = $row;
        }
    }
}

$personas = [];
$pr = mysqli_query($con, "SELECT name, description FROM personas ORDER BY name");
if ($pr) {
    while ($prow = mysqli_fetch_assoc($pr)) {
        $personas[] = $prow;
    }
}
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
            if (!opt || !opt.value) return;
            var persona = opt.getAttribute('data-persona') || 'member';
            var memberId = opt.getAttribute('data-member');
            var url = '/home/?as=' + persona;
            if (memberId && memberId !== '0') url += '&edit_favs=1&edit_member_id=' + memberId;
            window.open(url, '_blank');
        }
        function openHomeAsPersona() {
            var sel = document.getElementById('persona');
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
                <?php foreach ($users as $u): 
                    $upr = mysqli_query($con, "SELECT p.name FROM user_personas up JOIN personas p ON p.id = up.persona_id WHERE up.user_id = " . intval($u['id']));
                    $personaNames = [];
                    while ($uprow = mysqli_fetch_assoc($upr)) { $personaNames[] = $uprow['name']; }
                    $firstPersona = $personaNames[0] ?? '';
                ?>
                    <option value="<?php echo $u['id']; ?>" data-persona="<?php echo $firstPersona; ?>" data-member="<?php echo intval($u['member']); ?>">
                        <?php echo htmlspecialchars($u['name']); ?> (<?php echo implode(', ', $personaNames) ?: 'auth-only'; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p style="margin:10px 0 0;font-size:13px;color:#888;">You can click the stars to edit this user's favourites.</p>
            <button type="submit" class="open-btn">Open Homepage as This User</button>
        </form>

        <div class="or-divider">&mdash; or &mdash;</div>

        <form onsubmit="event.preventDefault(); openHomeAsPersona();">
            <label class="field-label" for="persona">Persona:</label>
            <select id="persona">
                <?php foreach ($personas as $p): ?>
                    <option value="<?php echo $p['name']; ?>"><?php echo htmlspecialchars($p['name']); ?> &mdash; <?php echo htmlspecialchars($p['description'] ?? ''); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="open-btn">Open Homepage as This Persona</button>
        </form>
        <a class="back-link" href="home">&larr; Back to Home</a>
    </div>
</div>
</body>
</html>
