<?php session_start(); ?>
<?php
require_once __DIR__ . '/helpers/permissions.php';
require_perm('messages.view');
$org = isset($_SESSION['org']) ? intval($_SESSION['org']) : 0;

require_once __DIR__ . '/helpers/timehelpers.php';

$con_params = require(__DIR__ . '/config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);
if (mysqli_connect_errno()) {
    die("Database connection failed");
}

$q = "SELECT m.id, m.msg, m.create_time, m.is_broadcast,
          t.txt_id, t.txt_status, t.txt_timestamp_create AS sent_time,
          mb.displayname, mb.email
   FROM messages m
   LEFT JOIN texts t ON t.txt_msg_id = m.id
   LEFT JOIN members mb ON mb.id = t.txt_member_id
   WHERE m.org = " . intval($org) . "
   ORDER BY m.create_time DESC, t.txt_id ASC";

$r = mysqli_query($con, $q);

$messages = [];
while ($row = mysqli_fetch_array($r)) {
    $mid = $row['id'];
    if (!isset($messages[$mid])) {
        $messages[$mid] = [
            'id' => $mid,
            'msg' => $row['msg'],
            'create_time' => $row['create_time'],
            'is_broadcast' => intval($row['is_broadcast']),
            'sendings' => []
        ];
    }
    if ($row['txt_id']) {
        $messages[$mid]['sendings'][] = [
            'displayname' => $row['displayname'],
            'email' => $row['email'],
            'status' => intval($row['txt_status']),
            'sent_time' => $row['sent_time']
        ];
    }
}

mysqli_close($con);

function timeFmt($dt) {
    if (!$dt || substr($dt, 0, 4) == '0000') return '';
    try {
        $d = new DateTime($dt);
        return $d->format('d/m/Y H:i');
    } catch (Exception $e) {
        return '';
    }
}

$statusLabels = [0 => 'Pending', 1 => 'Sent', 2 => 'Error', 3 => 'Sent via Email'];

function msgOverallStatus($sendings) {
    if (count($sendings) === 0) return 'amber';
    foreach ($sendings as $s) {
        $st = $s['status'];
        if ($st === 0 || $st === 2) return 'amber';
    }
    return 'green';
}

function sendStatusClass($status) {
    if ($status === 1 || $status === 3) return 'green';
    if ($status === 0) return 'amber';
    return 'red';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sent Messages</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<style>
body { background: #fff; font-size: 15px; }
.padding-container { padding: 15px; }
.title-row { display: flex; align-items: center; margin-bottom: 10px; }
.title-row h1 { margin: 0 15px 0 0; font-size:22px; font-weight:600; color:#222; }
.msg { margin-bottom: 3px; border-radius: 4px; overflow: hidden; border: 1px solid #e0e0e0; }
.msg.green { background: linear-gradient(to right, rgba(92,184,92,0.35), rgba(92,184,92,0) 80%); }
.msg.amber { background: linear-gradient(to right, rgba(240,173,78,0.35), rgba(240,173,78,0) 80%); }
.msg-header {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px; cursor: pointer; user-select: none;
    transition: background 0.15s;
}
.msg-header:hover { background: rgba(0,0,0,0.03); }
.msg-header .arrow { font-size: 11px; color: #999; transition: transform 0.2s; width: 12px; flex-shrink: 0; }
.msg-header .arrow.open { transform: rotate(90deg); }
.msg-text { flex: 1; font-weight: 600; color: #333; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.msg-time { font-size: 13px; color: #999; flex-shrink: 0; white-space: nowrap; }
.msg-badge {
    font-size: 11px; color: #666; background: #f0f0f0;
    padding: 2px 10px; border-radius: 10px; flex-shrink: 0; font-weight: 600;
}
.msg-badge.broadcast { background: #d9edf7; color: #31708f; }
.msg-children { display: none; border-top: 1px solid #e0e0e0; }
.msg-children.open { display: block; }
.sending {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 16px 10px 44px;
    border-bottom: 1px solid #f0f0f0; font-size: 14px;
}
.sending:last-child { border-bottom: none; }
.sending.green { background: linear-gradient(to right, rgba(92,184,92,0.2), rgba(92,184,92,0) 70%); }
.sending.amber { background: linear-gradient(to right, rgba(240,173,78,0.2), rgba(240,173,78,0) 70%); }
.sending.red { background: linear-gradient(to right, rgba(217,83,79,0.2), rgba(217,83,79,0) 70%); }
.s-name { width: 150px; font-weight: 500; color: #333; flex-shrink: 0; }
.s-email { flex: 1; color: #888; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.s-status {
    font-size: 11px; font-weight: 700; padding: 2px 10px; border-radius: 3px;
    flex-shrink: 0; text-align: center; min-width: 100px;
}
.s-status.green { background: #dff0d8; color: #3c763d; }
.s-status.amber { background: #fcf8e3; color: #8a6d3b; }
.s-status.red { background: #f2dede; color: #a94442; }
.empty { color: #999; padding: 30px; text-align: center; }
</style>
<style>
<?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
<?php $inc = "./orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
</style>
</head>
<body>
<div class="no-padding-container">
<?php $inc = "./orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
<?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>
</div>

<div class="padding-container">
<div class="title-row">
    <h1 id="page-title" style="margin:0">Sent Messages</h1>
    <a href="/SentMessages" class="btn btn-default btn-sm">Table View</a>
</div>

<?php if (count($messages) === 0): ?>
<div class="empty">No messages found.</div>
<?php else: ?>
<?php foreach ($messages as $m):
    $overall = msgOverallStatus($m['sendings']);
    $count = count($m['sendings']);
    $hasColor = $count > 0;
?>
<div class="msg<?php echo $hasColor ? ' ' . $overall : ''; ?>">
    <div class="msg-header" onclick="toggle(this)">
        <span class="arrow">&#9654;</span>
        <span class="msg-text"><?php echo htmlspecialchars($m['msg'] ?: '(empty)'); ?></span>
        <?php if ($m['is_broadcast']): ?>
        <span class="msg-badge broadcast">Twitter</span>
        <?php endif; ?>
        <span class="msg-time"><?php echo timeFmt($m['create_time']); ?></span>
        <span class="msg-badge">sent to <?php echo $count; ?> person<?php echo $count !== 1 ? 's' : ''; ?></span>
    </div>
    <div class="msg-children">
        <?php if ($count === 0): ?>
        <div class="sending"><span class="s-name" style="color:#999">No one was sent this message</span></div>
        <?php else: ?>
        <?php foreach ($m['sendings'] as $s):
            $sc = sendStatusClass($s['status']);
            $label = $statusLabels[$s['status']] ?? 'Unknown';
        ?>
        <div class="sending <?php echo $sc; ?>">
            <span class="s-name"><?php echo htmlspecialchars($s['displayname'] ?: '?'); ?></span>
            <span class="s-email"><?php echo htmlspecialchars($s['email'] ?: ''); ?></span>
            <span class="s-status <?php echo $sc; ?>"><?php echo $label; ?></span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script>
function toggle(el) {
    el.querySelector('.arrow').classList.toggle('open');
    el.nextElementSibling.classList.toggle('open');
}
</script>
</body>
</html>
