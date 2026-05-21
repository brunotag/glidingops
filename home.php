<?php session_start(); ?>
<?php $org = 0;
if (isset($_SESSION['org'])) $org = $_SESSION['org']; ?>
<?php
include './helpers/timehelpers.php';
require_once __DIR__ . '/helpers/logging.php';
if (isset($_SESSION['security'])) {
   if (!($_SESSION['security'] & 1)) {
      die("Secruity level too low for this page");
   }
} else {
    header('Location: /Login.php');
   die("Please logon");
}

$effectiveSecurity = $_SESSION['security'];
$asOverride = 0;
if (isset($_GET['as']) && ($effectiveSecurity & 128)) {
    $v = intval($_GET['as']);
    if ($v >= 0 && $v <= 255) {
        $effectiveSecurity = $v;
        $asOverride = $v;
    }
}

$con_params = require('./config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);
$dbOk = !mysqli_connect_errno();

$dateTime = new DateTime('now');
$dateStr = $dateTime->format('Y-m-d');
$localDateInt = intval($dateTime->format('Ymd'));
require_once __DIR__ . '/helpers.php';

// Rostered duties
$rosterDuties = [];
if ($dbOk && isset($_SESSION['memberid'])) {
    $q = "SELECT localdate, a.name FROM duty LEFT JOIN dutytypes a ON a.id = duty.type WHERE duty.org = $org AND member = {$_SESSION['memberid']} AND localdate >= '$dateStr' ORDER BY localdate ASC";
    $r = mysqli_query($con, $q);
    if ($r) {
        while ($row = mysqli_fetch_array($r)) {
            $rosterDuties[] = ['date' => $row[0], 'type' => $row[1]];
        }
    }
}

// Data widgets
$recentFlights = [];
$todayFlightCount = 0;
$recentMessages = [];
$strTZ = '';

if ($dbOk) {
    $strTZ = orgTimezone($con, $org);

    // Today's flights
    $todayFlights = [];
    $flightTypeGlider = getGlidingFlightType($con);
    $q2 = "SELECT f.localdate, f.glider, f.pic, f.p2, f.start, f.land,
                  m1.displayname as pic_name, m2.displayname as p2_name
           FROM flights f
           LEFT JOIN members m1 ON m1.id = f.pic
           LEFT JOIN members m2 ON m2.id = f.p2
           WHERE f.localdate = $localDateInt AND f.org = $org AND f.deleted = 0
             AND f.type = " . intval($flightTypeGlider) . " AND f.start > 0
           ORDER BY f.seq ASC LIMIT 20";
    $r2 = mysqli_query($con, $q2);
    if ($r2) {
        while ($row = mysqli_fetch_array($r2)) {
            $todayFlights[] = $row;
        }
    }

    // Recent flights for this member
    if (isset($_SESSION['memberid']) && intval($_SESSION['memberid']) > 0) {
        $mid = intval($_SESSION['memberid']);
        $q3 = "SELECT f.localdate, f.glider, f.start, f.land, f.pic, f.p2,
                      m1.displayname as pic_name, m2.displayname as p2_name
               FROM flights f
               LEFT JOIN members m1 ON m1.id = f.pic
               LEFT JOIN members m2 ON m2.id = f.p2
               WHERE (f.pic = $mid OR f.p2 = $mid) AND f.deleted = 0 AND f.org = $org
               ORDER BY f.localdate DESC, f.start DESC LIMIT 5";
        $r3 = mysqli_query($con, $q3);
        if ($r3) {
            while ($row = mysqli_fetch_array($r3)) {
                $recentFlights[] = $row;
            }
        }
    }

    // Recent announcements
    $q4 = "SELECT create_time, msg FROM messages WHERE org = $org ORDER BY create_time DESC LIMIT 3";
    $r4 = mysqli_query($con, $q4);
    if ($r4) {
        while ($row = mysqli_fetch_array($r4)) {
            $recentMessages[] = $row;
        }
    }

    // Broadcast messages for inline widget
    $broadcastMessages = [];
    $q5 = "SELECT id, create_time, msg FROM messages WHERE is_broadcast = 1 AND org = $org ORDER BY create_time DESC LIMIT 4";
    $r5 = mysqli_query($con, $q5);
    if ($r5) {
        while ($row = mysqli_fetch_array($r5)) {
            $broadcastMessages[] = $row;
        }
    }

    // Duplicate members count
    $duplicateCount = 0;
    if ($effectiveSecurity & 64) {
        $q6 = "SELECT COUNT(*) AS cnt FROM (SELECT firstname, surname FROM members WHERE org = $org GROUP BY firstname, surname HAVING COUNT(*) > 1) AS dups";
        $r6 = mysqli_query($con, $q6);
        if ($r6 && $row = mysqli_fetch_array($r6)) {
            $duplicateCount = intval($row['cnt']);
        }
    }
}
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
  <title>Home - Gliding Ops</title>
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
  <style>
    <?php $inc = "./orgs/" . $org . "/heading2.css"; include $inc; ?>
  </style>
  <style>
    body { margin: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f0f0ff; }
    #container { margin: 5px; border: 0px; }

    .widget-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(275px, 1fr));
      gap: 20px;
      grid-auto-flow: dense;
      align-items: start;
    }
    .widget-grid > .wide { grid-column: span 2; }
    @media (max-width: 580px) {
      .widget-grid > .wide { grid-column: span 1; }
    }

    .dashboard-card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.08);
      overflow: hidden;
    }
    .dashboard-card .card-header {
      background: #063552;
      color: #f26120;
      font-weight: bold;
      font-size: 16px;
      padding: 12px 16px;
    }
    .dashboard-card .card-body {
      padding: 14px 16px;
      font-size: 15px;
    }
    .dashboard-card .card-body a {
      display: block;
      padding: 7px 0;
      color: #333;
      text-decoration: none;
      border-bottom: 1px solid #f0f0f0;
    }
    .dashboard-card .card-body a:last-child { border-bottom: none; }
    .dashboard-card .card-body a:hover { color: #063552; font-weight: bold; }

    .nav-card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.08);
      overflow: hidden;
    }
    .nav-card:hover {
      box-shadow: 0 3px 12px rgba(0,0,0,0.12);
      transform: translateY(-1px);
    }
    .nav-card .card-header {
      background: #063552;
      color: #f26120;
      font-weight: bold;
      font-size: 16px;
      padding: 12px 16px;
    }
    .nav-card .card-body {
      padding: 12px 16px;
      font-size: 15px;
    }
    .nav-card .card-body a {
      display: block;
      padding: 6px 0;
      color: #333;
      text-decoration: none;
    }
    .nav-card .card-body a:hover { color: #063552; font-weight: bold; }

    .roster-box {
      background: #d9edf7;
      border: 1px solid #bce8f1;
      border-radius: 6px;
      padding: 10px 14px;
      margin-bottom: 20px;
      font-size: 13px;
      color: #31708f;
    }

    a { text-decoration: none; }
    a:link { color: #333; }
    a:visited { color: #333; }
    a:hover { color: #063552; }

    .msg-item { background:#fff; margin:8px; padding:12px 14px; border-radius:3px; border:1px solid #e6ecf0; color:#203A5E; position:relative; }
    .msg-item:not(.seen) { border-left:4px solid #063552; }
    .msg-item .msg-meta { font-size:13px; color:#657786; margin-bottom:6px; }
    .msg-item .msg-meta .new-badge { display:inline; background:#063552; color:#f26120; font-size:10px; font-weight:bold; padding:2px 7px; border-radius:3px; margin-left:8px; text-transform:uppercase; vertical-align:middle; }
    .msg-item.seen .msg-meta .new-badge { display:none; }
    .msg-item .msg-text { font-size:14px; color:#203A5E; line-height:1.4; }
    .msg-empty { color:#888; font-size:13px; padding:14px 16px; }
    .dup-badge { display:inline-block; background:#063552; color:#f26120; font-size:10px; font-weight:bold; padding:1px 7px; border-radius:8px; margin-left:5px; vertical-align:middle; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/helpers/dev_mode_banner.php'; ?>

  <?php if ($asOverride): ?>
    <div style="background:#fff3cd;color:#856404;text-align:center;padding:6px;font-size:13px;font-weight:bold;">
      Viewing as security level <?php echo $asOverride; ?>
      &mdash; <a href="home" style="color:#856404;text-decoration:underline;">Clear override</a>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['auth_via_magic_link']) && $_SESSION['auth_via_magic_link'] == 1): ?>
    <div style="background:#d9edf7;color:#31708f;text-align:center;padding:8px;font-size:13px;border-bottom:1px solid #bce8f1;">
      Logged in via email link. Set a password for future logins &mdash; <a href="/PasswordChange" style="color:#245269;text-decoration:underline;">Change password</a>
    </div>
  <?php endif; ?>

  <?php $inc = "./orgs/" . $org . "/heading2.txt"; include $inc; ?>
  <?php $inc = "./orgs/" . $org . "/menu1.txt"; include $inc; ?>

  <div id="container">
    <div class="container-fluid" style="padding:10px;">

      <?php if (!empty($rosterDuties)): ?>
        <div class="roster-box">
          <strong>Your next rostered duties:</strong>
          <?php foreach ($rosterDuties as $d): ?>
            <?php $dd = strval($d['date']); $dt = strlen($dd) >= 8 ? substr($dd, 6, 2) . '/' . substr($dd, 4, 2) . '/' . substr($dd, 0, 4) : $dd; ?>
            &nbsp; <?php echo $dt; ?> - <?php echo htmlspecialchars($d['type']); ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="widget-grid">

        <div class="dashboard-card wide">
          <div class="card-header">Latest Updates</div>
          <div class="card-body" style="padding:0;height:340px;overflow-y:auto;background:#F1F1EF;">
            <?php if (empty($broadcastMessages)): ?>
              <p class="msg-empty">No recent broadcasts.</p>
            <?php else: ?>
              <?php foreach ($broadcastMessages as $bm): ?>
                <?php
                  $bmDate = new DateTime($bm['create_time']);
                  if ($strTZ) $bmDate->setTimezone(new DateTimeZone($strTZ));
                  $bmDateStr = $bmDate->format('d M Y - H:i');
                ?>
                <div class="msg-item" data-msg-id="<?php echo intval($bm['id']); ?>">
                  <div class="msg-meta">
                    <?php echo htmlspecialchars($bmDateStr); ?>
                    <span class="new-badge">NEW</span>
                  </div>
                  <div class="msg-text"><?php echo htmlspecialchars($bm['msg']); ?></div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="dashboard-card wide">
          <div class="card-header">My Gliding</div>
          <div class="card-body">
            <p style="margin:0 0 6px 0;font-size:13px;color:#888;font-weight:bold;"><?php echo htmlspecialchars($_SESSION['dispname'] ?? 'Your'); ?>'s last 5 flights:</p>
            <?php if (empty($recentFlights)): ?>
              <p style="color:#888;font-size:13px;margin:4px 0;">No recent flights found.</p>
            <?php else: ?>
              <?php foreach ($recentFlights as $f): ?>
                <?php
                  $dur = '';
                  if ($f['start'] && $f['land']) {
                    $totalMins = floor(($f['land'] - $f['start']) / 60000);
                    $hours = floor($totalMins / 60);
                    $mins = $totalMins % 60;
                    $dur = ' (' . $hours . 'h ' . $mins . 'min)';
                  }
                  $ld = strval($f['localdate']);
                  $dispDate = strlen($ld) >= 8 ? substr($ld, 6, 2) . '/' . substr($ld, 4, 2) . '/' . substr($ld, 0, 4) : $ld;

                  $otherPilot = '';
                  if (intval($f['pic']) === $mid && !empty($f['p2_name'])) {
                    $otherPilot = ', with ' . $f['p2_name'];
                  } elseif (intval($f['p2']) === $mid && !empty($f['pic_name'])) {
                    $otherPilot = ', with ' . $f['pic_name'];
                  }
                ?>
                <span style="display:block;padding:7px 0;color:#333;border-bottom:1px solid #f0f0f0;"><?php echo $dispDate; ?> &mdash; <?php echo htmlspecialchars($f['glider']); ?><?php echo $dur; ?><?php echo htmlspecialchars($otherPilot); ?></span>
              <?php endforeach; ?>
              <a href="/MyFlights" style="color:#063552;font-weight:bold;border-top:1px solid #e0e0e0;margin-top:4px;padding-top:6px;">View all your flights &rarr;</a>
            <?php endif; ?>
            <a href="/EditMyDetails" style="color:#063552;font-weight:bold;border-top:1px solid #e0e0e0;margin-top:4px;padding-top:6px;">Edit Your Details &rarr;</a>
          </div>
        </div>

        <div class="dashboard-card wide">
          <div class="card-header">Flying / Tracking</div>
          <div class="card-body">
            <?php if (empty($todayFlights)): ?>
              <p style="color:#888;font-size:13px;margin:4px 0;">No flights logged today.</p>
            <?php else: ?>
              <?php foreach ($todayFlights as $f): ?>
                <?php
                  $dur = '';
                  if ($f['start'] && $f['land']) {
                    $totalMins = floor(($f['land'] - $f['start']) / 60000);
                    $hours = floor($totalMins / 60);
                    $mins = $totalMins % 60;
                    $dur = ' (' . $hours . 'h ' . $mins . 'min)';
                  } else {
                    $dur = ' (flying)';
                  }
                  $pilotLine = htmlspecialchars($f['glider']) . ' &mdash; ' . htmlspecialchars($f['pic_name'] ?? '?');
                  if (!empty($f['p2_name'])) {
                    $pilotLine .= ' with ' . htmlspecialchars($f['p2_name']);
                  }
                ?>
                <span style="display:block;padding:5px 0;color:#333;border-bottom:1px solid #f0f0f0;"><?php echo $pilotLine; ?><?php echo $dur; ?></span>
              <?php endforeach; ?>
            <?php endif; ?>
            <?php if ($org == 1): ?>
              <a href="/wgc-new" target="_blank" style="color:#063552;font-weight:bold;border-top:1px solid #e0e0e0;margin-top:4px;padding-top:6px;">Real Time Map &rarr;</a>
              <div style="margin-top:6px;display:flex;align-items:center;">
                <input type="date" id="map-date" style="font-size:13px;padding:2px 4px;border:1px solid #ccc;border-radius:3px;">
                <a href="#" id="see-past-flights" style="color:#063552;font-weight:bold;margin-left:6px;white-space:nowrap;">See Past Flights &rarr;</a>
              </div>
            <?php elseif ($org == 2): ?>
              <a href="/ssb" target="_blank" style="color:#063552;font-weight:bold;border-top:1px solid #e0e0e0;margin-top:4px;padding-top:6px;">Real Time Map &rarr;</a>
            <?php elseif ($org == 3): ?>
              <a href="/cgc" target="_blank" style="color:#063552;font-weight:bold;border-top:1px solid #e0e0e0;margin-top:4px;padding-top:6px;">Real Time Map &rarr;</a>
            <?php elseif ($org == 4): ?>
              <a href="/agc" target="_blank" style="color:#063552;font-weight:bold;border-top:1px solid #e0e0e0;margin-top:4px;padding-top:6px;">Real Time Map &rarr;</a>
            <?php endif; ?>
          </div>
        </div>

        <!-- 4. Daily Ops -->
        <?php if ($effectiveSecurity >= 4): ?>
<div class="nav-card">
            <div class="card-header">Daily Ops</div>
            <div class="card-body">
              <a href="/StartDay?org=<?php echo $org; ?>">New Daily Timesheet</a>
              <a href="/EditDailySheet?org=<?php echo $org; ?>">Edit Daily Timesheet</a>
              <a href="/DailyLogSheet?org=<?php echo $org; ?>">View Daily Timesheet</a>
            </div>
          </div>
        <?php endif; ?>

        <!-- 5. Rosters & Bookings -->
        <?php if ($effectiveSecurity >= 1): ?>
          <div class="nav-card">
            <div class="card-header">Rosters &amp; Bookings</div>
            <div class="card-body">
              <a href="https://glidegreytown.nz/latest/#booking" target="_blank">Bookings (Google)</a>
              <a href="/Bookings">Bookings (new)</a>
              <a href="https://docs.google.com/spreadsheets/d/1bXYn5oiQfIt6CEzK0Gc33L9HDUBd9A_HEQcDrOHxt1s/edit?usp=sharing" target="_blank">Rosters (Google Sheet)</a>
            </div>
          </div>
        <?php endif; ?>

        <!-- 6. Messaging -->
        <?php if ($effectiveSecurity >= 5): ?>
          <div class="nav-card">
            <div class="card-header">Messaging</div>
            <div class="card-body">
              <a href="/MessagingPage">Broadcast a Message</a>
              <a href="/MessagesTree">See Past Messages</a>
            </div>
          </div>
        <?php endif; ?>

        <!-- 7. Members & Users -->
        <?php if ($effectiveSecurity >= 1): ?>
          <div class="nav-card">
            <div class="card-header">Members &amp; Users</div>
            <div class="card-body">
              <a href="/AllMembers">View Members</a>
              <?php if ($effectiveSecurity & 64): ?>
                <a href="/UsersList">View Users</a>
                <a href="/Users">Create User</a>
                <a href="/maintenance/duplicates_index.php">Manage Duplicate Memberships<?php if ($duplicateCount > 0): ?> <span class="dup-badge"><?php echo $duplicateCount; ?></span><?php endif; ?></a>
              <?php endif; ?>
              <?php if ($effectiveSecurity & 24): ?>
                <a href="/app/reports/membersRolesStatsReport">Members Roles Report</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- 8. Reports -->
        <?php if ($effectiveSecurity >= 1): ?>
          <div class="nav-card">
            <div class="card-header">Reports</div>
            <div class="card-body">
              <?php if ($effectiveSecurity & 8): ?>
                <a href="/TreasurerReportNew3">Treasurer Report</a>
                <a href="/TreasurerReportNew4">Treasurer Report (New)</a>
              <?php endif; ?>
              <?php if ($_SESSION['security'] & 1): ?>
                <a href="/app/allFlightsReport">All Flights Report</a>
<?php $isMobile = preg_match('/Mobile|Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i', $_SERVER['HTTP_USER_AGENT'] ?? ''); ?>
                <span style="display:block; white-space:nowrap;"><a href="<?php echo $isMobile ? '/AllFlightsMobile' : '/AllFlightsReportNew'; ?>" style="display:inline;">All Flights Report (New)</a><?php if (isLocal()): ?> <a href="/AllFlightsMobile" style="display:inline;color:#d00;">[dev] mobile</a><?php endif; ?></span>
<?php endif; ?>
              <?php if ($effectiveSecurity & 32): ?>
                <a href="/Engineer">Engineer Report</a>
                <a href="/last-flights-list?col=1&descsort=1">Currency Report</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- 10. Diagnostics & Recovery -->
        <?php if ($effectiveSecurity & 64): ?>
          <div class="nav-card">
            <div class="card-header">Diagnostics &amp; Recovery</div>
            <div class="card-body">
              <a href="/maintenance/testemail.php">Test Email</a>
              <a href="/SentMessages">All Messages</a>
              <a href="/Analytics">Analytics Dashboard</a>
              <?php if ($_SESSION['security'] & 128): ?>
                <a href="/ViewAs">View Homepage As...</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- 11. Super Admin -->
        <?php if ($effectiveSecurity & 128): ?>
          <div class="nav-card">
            <div class="card-header">Super Admin</div>
            <div class="card-body">
              <a href="/InviteUsers">Invite Users to Gliding Ops</a>
              <a href="/Organisations">Organisations</a>
              <a href="/maintenance/duplicates_suggestions.php">Suggested Duplicates</a>
            </div>
          </div>
        <?php endif; ?>

        <!-- 12. Data Maintenance -->
        <?php if ($effectiveSecurity & 120): ?>
          <div class="nav-card">
            <div class="card-header">Data Maintenance</div>
            <div class="card-body">
              <?php if ($effectiveSecurity & 104): ?>
                <a href="/AllAircraft">Aircraft</a>
              <?php endif; ?>
              <?php if ($effectiveSecurity & 64): ?>
                <a href="/AircraftTypes">Aircraft Types</a>
                <a href="/DutyTypes">Duty Types</a>
                <a href="/flights-list.php">Flights Raw</a>
                <a href="/membership_class-list.php">Membership Classes</a>
                <a href="/membership_status-list.php">Membership Statuses</a>
                <a href="/Roles">Roles</a>
                <a href="/AssignRoles">Role Assignment</a>
                <a href="/spots-list.php">Spots</a>
                <a href="/manage-secret-code.php">Manage Secret Code</a>
              <?php endif; ?>
              <?php if ($effectiveSecurity & 72): ?>
                <a href="/IncentiveSchemes">Incentive Schemes</a>
                <a href="/OtherCharges">Other Charges</a>
                <a href="/SubsToSchemes">Subs to Incentives</a>
                <a href="/TowCharges">Tow Charging</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

      </div>

      <!-- Git hash -->
      <p style="text-align:right;font-size:10px;color:#888;">
        <?php
        $gitHash = trim(@exec('git -C ' . escapeshellarg(__DIR__) . ' rev-parse --short HEAD'));
        if ($gitHash) echo $gitHash;
        ?>
      </p>

    </div>
  </div>

  <script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>
  <script>
  (function() {
    var maxSeen = parseInt(localStorage.getItem('gops_broadcast_seen_max') || '0', 10);
    var currentMax = 0;
    document.querySelectorAll('.msg-item[data-msg-id]').forEach(function(el) {
      var id = parseInt(el.getAttribute('data-msg-id'), 10);
      if (id > currentMax) currentMax = id;
      if (id <= maxSeen) el.classList.add('seen');
    });
    if (currentMax > maxSeen) {
      localStorage.setItem('gops_broadcast_seen_max', currentMax.toString());
    }
  })();
  </script>

  <style>
    <?php $inc = "./orgs/" . $org . "/heading2.css"; include $inc; ?>
    <?php $inc = "./orgs/" . $org . "/menu1.css"; include $inc; ?>
  </style>
</body>
</html>
<?php if ($dbOk) mysqli_close($con); ?>


