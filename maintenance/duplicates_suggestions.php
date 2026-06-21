<?php 
  include '../helpers/audit_helpers.php';
  session_start();
  require_once __DIR__ . '/../helpers/permissions.php';
  require_perm('admin.manage');
  $current_org = isset($_SESSION['org']) ? $_SESSION['org'] : 0;
  $org = $current_org;
?>
<?php include '../helpers/dev_mode_banner.php'; ?>
<?php $inc = "../orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
<?php $inc = "../orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>
<?php
  function levenshteinUtf8($s1, $s2) {
      $s1 = strtolower(trim($s1));
      $s2 = strtolower(trim($s2));
      if ($s1 === $s2) return 0;
      $len1 = strlen($s1);
      $len2 = strlen($s2);
      if ($len1 === 0) return $len2;
      if ($len2 === 0) return $len1;
      return levenshtein($s1, $s2);
  }

  require_once __DIR__ . '/../helpers/database.php';
  $con = open_gliding_db();

  $classNames = [];
  $cres = mysqli_query($con, "SELECT id, class FROM membership_class");
  while ($crow = mysqli_fetch_assoc($cres)) {
      $classNames[$crow['id']] = $crow['class'];
  }
  $statusNames = [];
  $sres = mysqli_query($con, "SELECT id, status_name FROM membership_status");
  while ($srow = mysqli_fetch_assoc($sres)) {
      $statusNames[$srow['id']] = $srow['status_name'];
  }

  $allMembers = [];
  $mr = mysqli_query($con, "SELECT m.*, organisations.name AS org_name FROM members m JOIN organisations ON m.org = organisations.id WHERE m.org = $current_org ORDER BY m.surname, m.firstname");
  while ($mrow = mysqli_fetch_assoc($mr)) {
      $allMembers[] = $mrow;
  }

  // Heuristic 1: Same email (non-empty, non-null)
  $emailGroups = [];
  $emailMap = [];
  foreach ($allMembers as $m) {
      $e = strtolower(trim($m['email'] ?? ''));
      if ($e === '' || $e === '0') continue;
      $emailMap[$e][] = $m;
  }
  foreach ($emailMap as $email => $members) {
      if (count($members) > 1) {
          $emailGroups[] = ['reason' => "Same email address: $email", 'members' => $members];
      }
  }

  // Heuristic 2: Same phone (non-empty)
  $phoneGroups = [];
  $phoneMap = [];
  foreach ($allMembers as $m) {
      $p = preg_replace('/[^0-9]/', '', $m['phone_mobile'] ?? '');
      if ($p === '' || strlen($p) < 6) continue;
      $phoneMap[$p][] = $m;
  }
  foreach ($phoneMap as $phone => $members) {
      if (count($members) > 1) {
          $phoneGroups[] = ['reason' => "Same phone number: $phone", 'members' => $members];
      }
  }

  // Heuristic 3: Similar names (same surname, firstname Levenshtein <= 2, or vice versa)
  $similarGroups = [];
  $seenPairs = [];
  for ($i = 0; $i < count($allMembers); $i++) {
      for ($j = $i + 1; $j < count($allMembers); $j++) {
          $a = $allMembers[$i];
          $b = $allMembers[$j];
          if ($a['id'] == $b['id']) continue;
          $key = min($a['id'], $b['id']) . '-' . max($a['id'], $b['id']);
          if (isset($seenPairs[$key])) continue;
          $fnDist = levenshteinUtf8($a['firstname'], $b['firstname']);
          $snDist = levenshteinUtf8($a['surname'], $b['surname']);
          if (($fnDist <= 2 && $snDist === 0) || ($snDist <= 2 && $fnDist === 0) || ($fnDist <= 2 && $snDist <= 2)) {
              $seenPairs[$key] = true;
              $label = "Similar names";
              if ($fnDist === 0 && $snDist > 0) $label = "Similar surname (\"{$a['surname']}\" vs \"{$b['surname']}\")";
              elseif ($snDist === 0 && $fnDist > 0) $label = "Similar first name (\"{$a['firstname']}\" vs \"{$b['firstname']}\")";
              else $label = "Similar names (\"{$a['firstname']} {$a['surname']}\" vs \"{$b['firstname']} {$b['surname']}\")";
              $similarGroups[$label][] = $a['id'];
              $similarGroups[$label][] = $b['id'];
          }
      }
  }
  $similarGroupList = [];
  foreach ($similarGroups as $label => $ids) {
      $ids = array_unique($ids);
      $members = [];
      foreach ($ids as $id) {
          foreach ($allMembers as $m) {
              if ($m['id'] == $id) {
                  $members[] = $m;
                  break;
              }
          }
      }
      if (count($members) > 1) {
          $similarGroupList[] = ['reason' => $label, 'members' => $members];
      }
  }

  // Heuristic 4: Agency/organisation names
  $agencyKeywords = ['club', 'school', 'ltd', 'limited', 'trust', 'society', 'inc', 'organisation', 'organization', 'association', 'committee', 'management', 'agency', 'aero', 'aviation', 'flying school'];
  $agencyGroups = [];
  foreach ($allMembers as $m) {
      $name = strtolower($m['surname'] . ' ' . $m['firstname']);
      foreach ($agencyKeywords as $kw) {
          if (strpos($name, $kw) !== false) {
              $agencyGroups[] = ['reason' => "Possible agency/organisation: \"{$m['surname']}\" contains \"$kw\"", 'members' => [$m]];
              break;
          }
      }
  }

  // Heuristic 5: Members with no flights and no user account (orphans)
  $orphanIds = [];
  foreach ($allMembers as $m) {
      $fq = mysqli_query($con, "SELECT COUNT(*) AS cnt FROM flights WHERE (pic = {$m['id']} OR p2 = {$m['id']})");
      $fc = 0;
      if ($fq && $fr = mysqli_fetch_assoc($fq)) $fc = $fr['cnt'];
      $uq = mysqli_query($con, "SELECT COUNT(*) AS cnt FROM users WHERE member = {$m['id']}");
      $uc = 0;
      if ($uq && $ur = mysqli_fetch_assoc($uq)) $uc = $ur['cnt'];
      if ($fc === 0 && $uc === 0) {
          $orphanIds[] = $m['id'];
      }
  }
  $orphanMembers = [];
  foreach ($allMembers as $m) {
      if (in_array($m['id'], $orphanIds)) {
          $orphanMembers[] = $m;
      }
  }

  // Combine all suggestions
  $suggestions = [];
  foreach ($emailGroups as $g) $suggestions[] = $g;
  foreach ($phoneGroups as $g) $suggestions[] = $g;
  foreach ($similarGroupList as $g) $suggestions[] = $g;
  foreach ($agencyGroups as $g) $suggestions[] = $g;
  if (count($orphanMembers) > 0) {
      $suggestions[] = ['reason' => 'Orphan members (no flights, no user account)', 'members' => $orphanMembers];
  }

  // Handle merge POST
  $mergeResult = null;
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['merge'])) {
      $groupId = $_POST['group_id'] ?? '';
      $genuineId = intval($_POST['genuine_id_' . $groupId] ?? 0);
      $allIds = explode(',', $_POST['all_ids_' . $groupId] ?? '');

      if ($genuineId <= 0 || count($allIds) < 1) {
          $mergeResult = ['success' => false, 'message' => 'Select one member to keep.'];
      } elseif (!in_array($genuineId, $allIds)) {
          $mergeResult = ['success' => false, 'message' => 'Invalid selection.'];
      } else {
          try {
              mysqli_begin_transaction($con);
              foreach ($allIds as $id) {
                  $id = intval($id);
                  if ($id === $genuineId || $id <= 0) continue;

                  mysqli_query($con, "UPDATE flights SET billing_member2 = $genuineId WHERE billing_member2 = $id");
                  mysqli_query($con, "UPDATE flights SET billing_member1 = $genuineId WHERE billing_member1 = $id");
                  mysqli_query($con, "UPDATE flights SET pic = $genuineId WHERE pic = $id");
                  mysqli_query($con, "UPDATE flights SET p2 = $genuineId WHERE p2 = $id");
                  mysqli_query($con, "UPDATE flights SET towpilot = $genuineId WHERE towpilot = $id");

                  mysqli_query($con, "DELETE FROM role_member WHERE member_id = $id AND role_id IN (SELECT role_id FROM (SELECT role_id FROM role_member WHERE member_id = $genuineId) AS tmp)");
                  mysqli_query($con, "UPDATE role_member SET member_id = $genuineId WHERE member_id = $id");
                  mysqli_query($con, "UPDATE texts SET txt_member_id = $genuineId WHERE txt_member_id = $id");
                  mysqli_query($con, "UPDATE audit SET memberid = $genuineId WHERE memberid = $id");
                  mysqli_query($con, "UPDATE bookings SET member_id = $genuineId WHERE member_id = $id");
                  mysqli_query($con, "UPDATE group_member SET gm_member_id = $genuineId WHERE gm_member_id = $id");
                  mysqli_query($con, "UPDATE scheme_subs SET member = $genuineId WHERE member = $id");
                  mysqli_query($con, "UPDATE users SET member = $genuineId WHERE member = $id");
                  mysqli_query($con, "DELETE FROM members WHERE id = $id");
                  audit_log($con, "Merged member id $id into member id $genuineId (suggestion)");
              }
              mysqli_commit($con);
              $mergeResult = ['success' => true, 'message' => 'Members merged successfully.'];
          } catch (Exception $e) {
              @mysqli_rollback($con);
              $mergeResult = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
          }
      }
  }

  @mysqli_close($con);
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
  <title>Suggested Duplicates</title>
  <style>
    <?php $inc = "../orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
    <?php $inc = "../orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
  </style>
  <style>
    body { margin:0; font-family:Arial,Helvetica,sans-serif; background:#fafafa; }
    .padding-container { padding:20px; max-width:1000px; margin:0 auto; }
    h2 { margin:0 0 5px 0; font-size:20px; color:#2d3748; }
    .summary-line { font-size:13px; color:#666; margin-bottom:15px; }
    .dup-group { background:#fff; border-radius:4px; box-shadow:0 1px 4px rgba(0,0,0,0.08); margin-bottom:15px; overflow:hidden; }
    .dup-group .group-header { background:#063552; color:#f26120; padding:10px 14px; font-size:13px; font-weight:bold; }
    table { width:100%; border-collapse:collapse; }
    th { background:#f5f5f5; text-align:left; padding:8px 10px; font-size:12px; color:#555; border-bottom:1px solid #e0e0e0; white-space:nowrap; }
    td { padding:8px 10px; font-size:13px; color:#333; border-bottom:1px solid #f0f0f0; }
    tr:last-child td { border-bottom:none; }
    tr:nth-child(even) td { background:#f7fafc; }
    tr:hover td { background:#edf2f7; }
    .genuine-cell { text-align:center; width:60px; }
    .genuine-cell input { transform:scale(1.3); cursor:pointer; }
    .merge-bar { padding:10px 14px; background:#f8f9fa; border-top:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; }
    .merge-bar .count { font-size:12px; color:#666; }
    .alert { padding:10px 14px; border-radius:4px; margin-bottom:15px; font-size:13px; }
    .alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
    .alert-danger { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
    .no-dups { color:#888; font-size:14px; padding:20px; text-align:center; }
    .back-link { display:inline-block; margin-top:5px; font-size:13px; color:#063552; }
  </style>
</head>
<body>
  <div class="padding-container">
    <h2>Suggested Duplicates</h2>
    <p class="summary-line">Heuristic-based suggestions for possible duplicate or suspicious member records.</p>
    <a href="/" class="back-link">&larr; Home</a>

<?php if ($mergeResult): ?>
    <div class="alert alert-<?php echo $mergeResult['success'] ? 'success' : 'danger'; ?>">
      <?php echo htmlspecialchars($mergeResult['message']); ?>
    </div>
<?php endif; ?>

<?php if (empty($suggestions)): ?>
    <p class="no-dups">No suggestions found.</p>
<?php else: ?>
<?php foreach ($suggestions as $sg):
        $groupId = md5($sg['reason'] . implode(',', array_map(function($m) { return $m['id']; }, $sg['members'])));
        $allIds = array_map(function($m) { return $m['id']; }, $sg['members']);
?>
    <form method="post" action="./duplicates_suggestions.php" class="dup-group">
      <div class="group-header">
        <?php echo htmlspecialchars($sg['reason']); ?> 
        <span style="font-size:11px;color:#888;font-weight:normal;margin-left:8px;"><?php echo count($sg['members']); ?> records</span>
      </div>
      <table>
        <thead>
          <tr>
            <th class="genuine-cell">Keep</th>
            <th>ID</th>
            <th>First Name</th>
            <th>Surname</th>
            <th>Display Name</th>
            <th>Class</th>
            <th>Status</th>
            <th>Email</th>
            <th>Phone</th>
            <th>GNZ</th>
          </tr>
        </thead>
        <tbody>
<?php foreach ($sg['members'] as $m): ?>
          <tr>
            <td class="genuine-cell"><input type="radio" name="genuine_id_<?php echo $groupId; ?>" value="<?php echo $m['id']; ?>" required></td>
            <td><?php echo $m['id']; ?></td>
            <td><?php echo htmlspecialchars($m['firstname']); ?></td>
            <td><?php echo htmlspecialchars($m['surname']); ?></td>
            <td><?php echo htmlspecialchars($m['displayname']); ?></td>
            <td><?php echo htmlspecialchars($classNames[$m['class']] ?? $m['class']); ?></td>
            <td><?php echo htmlspecialchars($statusNames[$m['status']] ?? $m['status']); ?></td>
            <td><?php echo htmlspecialchars($m['email'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($m['phone_mobile'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($m['gnz_number'] ?? ''); ?></td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
      <div class="merge-bar">
        <span class="count">Select the record to keep, then merge.</span>
        <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
        <input type="hidden" name="all_ids_<?php echo $groupId; ?>" value="<?php echo implode(',', $allIds); ?>">
        <button type="submit" name="merge" value="1" class="btn btn-primary btn-sm">Merge</button>
      </div>
    </form>
<?php endforeach; ?>
<?php endif; ?>

  </div>
</body>
</html>
