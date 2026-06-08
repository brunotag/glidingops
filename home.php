<?php session_start(); ?>
<?php $org = 0;
if (isset($_SESSION['org'])) $org = $_SESSION['org']; ?>
<?php
include './helpers/timehelpers.php';
require_once __DIR__ . '/helpers/logging.php';
require_once __DIR__ . '/helpers/permissions.php';
require_auth();

$con_params = require('./config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);
$GLOBALS['con'] = $con;
$dbOk = !mysqli_connect_errno();

$asOverride = 0;
if (!empty($_GET['as'])) {
    $userPerms = $_SESSION['permissions'] ?? [];
    if (in_array('god.view-as', $userPerms)) {
        $asOverride = $_GET['as'];
    }
}

$dateTime = new DateTime('now');
$dateStr = $dateTime->format('Y-m-d');
$localDateInt = intval($dateTime->format('Ymd'));
require_once __DIR__ . '/helpers.php';

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
    if (has_perm('admin.manage')) {
        $q6 = "SELECT COUNT(*) AS cnt FROM (SELECT firstname, surname FROM members WHERE org = $org GROUP BY firstname, surname HAVING COUNT(*) > 1) AS dups";
        $r6 = mysqli_query($con, $q6);
        if ($r6 && $row = mysqli_fetch_array($r6)) {
            $duplicateCount = intval($row['cnt']);
        }
    }
}

// Favourites
$editFavs = isset($_GET['edit_favs']) && $_GET['edit_favs'] == 1 && has_perm('god.view-as');
$editMemberId = isset($_GET['edit_member_id']) ? intval($_GET['edit_member_id']) : 0;
$favMemberId = $editFavs && $editMemberId ? $editMemberId : (isset($_SESSION['memberid']) ? intval($_SESSION['memberid']) : 0);
$favourites = [];
$favHrefs = [];
if ($dbOk && $favMemberId > 0) {
    $favQ = "SELECT href, label FROM user_favourites WHERE member_id = $favMemberId ORDER BY created_at ASC";
    $favR = mysqli_query($con, $favQ);
    if ($favR) {
        while ($row = mysqli_fetch_assoc($favR)) {
            $favourites[] = $row;
            $favHrefs[] = $row['href'];
        }
    }
}
$favHrefsJson = json_encode($favHrefs);
$favMemberIdJson = json_encode($favMemberId);
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
    .nav-link-row {
      display: flex;
      align-items: center;
      padding: 6px 0;
      border-bottom: 1px solid #f0f0f0;
    }
    .nav-link-row:last-child { border-bottom: none; }
    .nav-link-row a {
      display: inline;
      color: #333;
      text-decoration: none;
      line-height: 1.4;
    }
    .nav-card .nav-link-row a:hover { color: #063552; font-weight: bold; }
    .nav-card .nav-link-row a:focus { outline: none; }
    .nav-link-row .fav-star {
      margin-left: auto;
      cursor: pointer;
      font-size: 28px;
      color: #ccc;
      user-select: none;
      line-height: 1;
      padding-left: 12px;
    }
    .nav-link-row .fav-star.active { color: #f26120; }
    .nav-link-row .fav-star:hover { color: #f29720; }
    .card-body span .fav-star { font-size: 24px; cursor: pointer; color: #ccc; user-select: none; margin-left: 6px; }
    .card-body span .fav-star.active { color: #f26120; }
    .nav-card .card-body > a { display: block; padding: 6px 0; color: #333; text-decoration: none; border-bottom: 1px solid #f0f0f0; }
    .nav-card .card-body > a:last-child { border-bottom: none; }
    .nav-card .card-body > a:hover { color: #063552; font-weight: bold; }



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
      &mdash; <a href="/home/" style="color:#856404;text-decoration:underline;">Clear override</a>
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

      <div class="widget-grid">

        <?php if (!empty($favourites)): ?>
          <div class="nav-card no-stars">
            <div class="card-header">Favourites</div>
            <div class="card-body">
              <?php foreach ($favourites as $fav): ?>
                <a href="<?php echo htmlspecialchars($fav['href']); ?>"><?php echo htmlspecialchars($fav['label']); ?></a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

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
              <a href="/wgc" target="_blank" style="color:#063552;font-weight:bold;border-top:1px solid #e0e0e0;margin-top:4px;padding-top:6px;">Real Time Map &rarr;</a>
              <div style="margin-top:6px;display:flex;align-items:center;">
                <input type="date" id="map-date" style="font-size:13px;padding:2px 4px;border:1px solid #ccc;border-radius:3px;">
                <a href="#" id="see-past-flights" style="color:#063552;font-weight:bold;margin-left:6px;white-space:nowrap;">See Past Flights &rarr;</a>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- 4. Daily Ops -->
        <?php if (has_any_perm('daily-sheet.start-day', 'daily-sheet.edit', 'daily-sheet.access', 'self-launch.access')): ?>
<div class="nav-card">
            <div class="card-header">Daily Ops</div>
            <div class="card-body">
              <?php if (has_perm('daily-sheet.start-day')): ?><a href="/StartDay?org=<?php echo $org; ?>">New Daily Timesheet</a><?php endif; ?>
              <?php if (has_perm('daily-sheet.edit')): ?><a href="/EditDailySheet?org=<?php echo $org; ?>">Edit Daily Timesheet</a><?php endif; ?>
              <?php if (has_perm('daily-sheet.access')): ?><a href="/DailyLogSheet?org=<?php echo $org; ?>">View Daily Timesheet</a><?php endif; ?>
              <?php if (has_perm('self-launch.access')): ?><a href="/SelfLaunchEntry">Self-Launch Flight</a><?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- 5. Rosters & Bookings -->
        <?php if (has_perm('bookings.view')): ?>
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
        <?php if (has_any_perm('messages.send', 'messages.view')): ?>
          <div class="nav-card">
            <div class="card-header">Messaging</div>
            <div class="card-body">
              <?php if (has_perm('messages.send')): ?><a href="/MessagingPage">Broadcast a Message</a><?php endif; ?>
              <?php if (has_perm('messages.view')): ?><a href="/MessagesTree">See Past Messages</a><?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- 7. Members & Users -->
        <?php if (has_any_perm('members.list', 'users.manage', 'admin.manage', 'members.edit')): ?>
          <div class="nav-card">
            <div class="card-header">Members &amp; Users</div>
            <div class="card-body">
              <?php if (has_perm('members.list')): ?><a href="/AllMembers">View Members</a><?php endif; ?>
              <?php if (has_perm('users.manage')): ?><a href="/UsersList">View Users</a><?php endif; ?>
              <?php if (has_perm('users.manage')): ?><a href="/Users">Create User</a><?php endif; ?>
              <?php if (has_perm('members.edit')): ?><a href="/maintenance/duplicates_index.php">Manage Duplicate Memberships<?php if ($duplicateCount > 0): ?> <span class="dup-badge"><?php echo $duplicateCount; ?></span><?php endif; ?></a><?php endif; ?>
              <?php if (has_perm('admin.manage')): ?><a href="/app/reports/membersRolesStatsReport">Members Roles Report</a><?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- 8. Reports -->
        <?php if (has_any_perm('treasurer-report.view', 'flights.list', 'engineer.view')): ?>
          <div class="nav-card">
            <div class="card-header">Reports</div>
            <div class="card-body">
              <?php if (has_perm('treasurer-report.view')): ?><a href="/BillingReport">Billing Report</a><?php endif; ?>
              <?php if (has_perm('treasurer-report.view')): ?><a href="/TreasurerReportNew3">Treasurer Report</a><?php endif; ?>
              <?php if (has_perm('treasurer-report.view')): ?><a href="/TreasurerReportNew4">Treasurer Report (New)</a><?php endif; ?>
              <?php if (has_perm('flights.list')): ?><a href="/app/allFlightsReport">All Flights Report</a><?php endif; ?>
              <?php if (has_perm('flights.list')): ?><a href="/AllFlightsReportNew">All Flights Report (New)</a><?php endif; ?>
<?php if (isLocal() && has_perm('flights.list')): ?><a href="/AllFlightsMobile" style="color:#d00;">[dev] All Flights Report (New) [mobile]</a><?php endif; ?>
              <?php if (has_perm('engineer.view')): ?><a href="/Engineer">Engineer Report</a><?php endif; ?>
              <?php if (has_perm('engineer.view')): ?><a href="/last-flights-list?col=1&descsort=1">Currency Report</a><?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- 9. Analytics -->
        <?php if (has_any_perm('analytics.season-trends', 'analytics.dashboard')): ?>
          <div class="nav-card">
            <div class="card-header">Analytics</div>
            <div class="card-body">
              <?php if (has_perm('analytics.season-trends')): ?><a href="/SeasonTrends">Trends Across Seasons</a><?php endif; ?>
              <?php if (has_perm('analytics.dashboard')): ?><a href="/Analytics">Compare Two Seasons / YTD</a><?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- 10. Diagnostics & Recovery -->
        <?php if (has_any_perm('admin.manage', 'messages.view', 'god.view-as')): ?>
          <div class="nav-card">
            <div class="card-header">Diagnostics &amp; Recovery</div>
            <div class="card-body">
              <?php if (has_perm('admin.manage')): ?><a href="/maintenance/testemail.php">Test Email</a><?php endif; ?>
              <?php if (has_perm('messages.view')): ?><a href="/SentMessages">All Messages</a><?php endif; ?>
              <?php if (has_perm('god.view-as')): ?><a href="/ViewAs">View Homepage As...</a><?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Dev Mode -->
        <?php if (isLocal()): ?>
          <div class="nav-card" style="border: 2px solid #d9534f;">
            <div class="card-header" style="background: #d9534f; color: #fff; font-size: 13px;">DEV MODE</div>
            <div class="card-body">
              <a href="/DevEmailPreview" style="color:#d9534f;">Preview Recap Email</a>
            </div>
          </div>
        <?php endif; ?>

        <!-- 11. Super Admin -->
        <?php if (has_any_perm('users.invite', 'organisations.manage', 'admin.manage')): ?>
          <div class="nav-card">
            <div class="card-header">Super Admin</div>
            <div class="card-body">
              <?php if (has_perm('users.invite')): ?><a href="/InviteUsers">Invite Users to Gliding Ops</a><?php endif; ?>
              <?php if (has_perm('organisations.manage')): ?><a href="/Organisations">Organisations</a><?php endif; ?>
              <?php if (has_perm('admin.manage')): ?><a href="/maintenance/duplicates_suggestions.php">Suggested Duplicates</a><?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- 12. Data Maintenance -->
        <?php if (has_any_perm('aircraft.manage', 'aircraft-types.manage', 'flight-types.manage', 'launch-types.manage', 'billing-options.manage', 'roles.manage', 'membership-classes.manage', 'membership-statuses.manage', 'spots.manage', 'admin.manage', 'charges.manage', 'tow-charges.manage', 'incentive-schemes.manage', 'scheme-subs.manage', 'personas.manage', 'permissions.manage')): ?>
          <div class="nav-card">
            <div class="card-header">Data Maintenance</div>
            <div class="card-body">
              <?php if (has_perm('aircraft.manage')): ?><a href="/AllAircraft">Aircraft</a><?php endif; ?>
              <?php if (has_perm('aircraft-types.manage')): ?><a href="/AircraftTypes">Aircraft Types</a><?php endif; ?>
              <?php if (has_perm('flights.manage')): ?><a href="/flights-list.php">Flights Raw</a><?php endif; ?>
              <?php if (has_perm('membership-classes.manage')): ?><a href="/membership_class-list.php">Membership Classes</a><?php endif; ?>
              <?php if (has_perm('membership-statuses.manage')): ?><a href="/membership_status-list.php">Membership Statuses</a><?php endif; ?>
              <?php if (has_perm('roles.manage')): ?><a href="/Roles">Roles</a><?php endif; ?>
              <?php if (has_perm('spots.manage')): ?><a href="/spots-list.php">Spots</a><?php endif; ?>
              <?php if (has_perm('admin.manage')): ?><a href="/manage-secret-code.php">Manage Secret Code</a><?php endif; ?>
              <?php if (has_perm('personas.manage')): ?><a href="/Personas">Personas</a><?php endif; ?>
              <?php if (has_perm('permissions.manage')): ?><a href="/Permissions">Permissions</a><?php endif; ?>
              <?php if (has_perm('incentive-schemes.manage')): ?><a href="/IncentiveSchemes">Incentive Schemes</a><?php endif; ?>
              <?php if (has_perm('charges.manage')): ?><a href="/OtherCharges">Other Charges</a><?php endif; ?>
              <?php if (has_perm('scheme-subs.manage')): ?><a href="/SubsToSchemes">Subs to Incentives</a><?php endif; ?>
              <?php if (has_perm('tow-charges.manage')): ?><a href="/TowCharges">Tow Charging</a><?php endif; ?>
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

  <script>
  var userFavHrefs = <?php echo $favHrefsJson; ?>;
  var editFavs = <?php echo $editFavs ? 'true' : 'false'; ?>;
  var favMemberId = <?php echo $favMemberIdJson; ?>;

  $(function() {
    // Restructure direct child links into rows (text clickable, star separate)
    $('.nav-card:not(.no-stars) > .card-body > a').each(function() {
      var $a = $(this);
      var href = $a.attr('href');
      if (!href) return;
      var label = $.trim($a.clone().children().remove().end().text());
      var isFav = userFavHrefs.indexOf(href) !== -1;
      var $star = $('<span class="fav-star' + (isFav ? ' active' : '') + '" data-href="' + href.replace(/"/g, '&quot;') + '" data-label="' + label.replace(/"/g, '&quot;') + '">' + (isFav ? '&#9733;' : '&#9734;') + '</span>');
      $star.insertAfter($a);
      var $row = $('<div class="nav-link-row"></div>');
      $a.before($row);
      $row.append($a, $star);
    });
    // Links inside wrappers (e.g. "All Flights Report (New)") — star inside link
    $('.nav-card:not(.no-stars) .card-body span a').each(function() {
      var $a = $(this);
      if ($a.find('.fav-star').length) return;
      var href = $a.attr('href');
      if (!href) return;
      var label = $.trim($a.text());
      var isFav = userFavHrefs.indexOf(href) !== -1;
      $a.append('<span class="fav-star' + (isFav ? ' active' : '') + '" data-href="' + href.replace(/"/g, '&quot;') + '" data-label="' + label.replace(/"/g, '&quot;') + '">' + (isFav ? '&#9733;' : '&#9734;') + '</span>');
    });

    $(document).on('click', '.fav-star', function(e) {
      e.preventDefault();
      e.stopPropagation();
      var $star = $(this);
      var data = {
        href: $star.data('href'),
        label: $star.data('label')
      };
      if (editFavs && favMemberId) {
        data.member_id = favMemberId;
      }
      $.ajax({
        url: '/api/favourites',
        method: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        dataType: 'json',
        success: function(resp) {
          $star.toggleClass('active').html(resp.favourited ? '&#9733;' : '&#9734;');
          var href = $star.data('href');
          var label = $star.data('label');
          if (resp.favourited) {
            userFavHrefs.push(href);
            var $favCard = $('.widget-grid > .no-stars');
            if ($favCard.length === 0) {
              $favCard = $('<div class="nav-card no-stars"><div class="card-header">Favourites</div><div class="card-body"></div></div>');
              $('.widget-grid').prepend($favCard);
            }
            $favCard.find('.card-body').append('<a href="' + href.replace(/"/g, '&quot;') + '">' + label.replace(/"/g, '&quot;') + '</a>');
          } else {
            userFavHrefs = userFavHrefs.filter(function(h) { return h !== href; });
            var $favCard = $('.widget-grid > .no-stars');
            if ($favCard.length) {
              $favCard.find('.card-body a[href="' + href.replace(/"/g, '&quot;') + '"]').remove();
              if ($favCard.find('.card-body a').length === 0) {
                $favCard.remove();
              }
            }
          }
        }
      });
    });
  });
  </script>

  <style>
    <?php $inc = "./orgs/" . $org . "/heading2.css"; include $inc; ?>
    <?php $inc = "./orgs/" . $org . "/menu1.css"; include $inc; ?>
  </style>
</body>
</html>
<?php if ($dbOk) mysqli_close($con); ?>


