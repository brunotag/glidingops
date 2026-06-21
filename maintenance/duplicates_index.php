<?php 
  include '../helpers/audit_helpers.php';
  session_start();
  require_once __DIR__ . '/../helpers/permissions.php';
  require_perm('members.dedup');
  $current_org = isset($_SESSION['org']) ? $_SESSION['org'] : 0;
  $org = $current_org;
?>
<?php include '../helpers/dev_mode_banner.php'; ?>
<?php $inc = "../orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
<?php $inc = "../orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>
<?php
  require_once __DIR__ . '/../helpers/database.php';
  $con = open_gliding_db();

  $q = "
        SELECT firstname, surname, org, COUNT(*) AS dup_count, organisations.name AS org_name
        FROM members
        JOIN organisations ON members.org = organisations.id
        WHERE members.org = {$current_org}
        GROUP BY firstname, surname, org
        HAVING COUNT(*) > 1
        ORDER BY surname, firstname";
  $result = mysqli_query($con, $q);
  $groups = [];
  while($row = $result->fetch_assoc()) {
      $groups[] = $row;
  }
  mysqli_free_result($result);

  // Handle merge POST
  $mergeResult = null;
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['merge'])) {
      $groupId = $_POST['group_id'] ?? '';
      $genuineId = intval($_POST['genuine_id_' . $groupId] ?? 0);
      $allIds = explode(',', $_POST['all_ids_' . $groupId] ?? '');

      if ($genuineId <= 0 || count($allIds) < 2) {
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
                  audit_log($con, "Merged member id $id into member id $genuineId");
              }
              mysqli_commit($con);
              $mergeResult = ['success' => true, 'message' => 'Members merged successfully.'];
          } catch (Exception $e) {
              mysqli_rollback($con);
              $mergeResult = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
          }
          // Refresh groups after merge
          $result = mysqli_query($con, $q);
          $groups = [];
          while($row = $result->fetch_assoc()) {
              $groups[] = $row;
          }
          mysqli_free_result($result);
      }
  }

  $columns = ['id', 'firstname', 'surname', 'displayname', 'org_name', 'class', 'status', 'email', 'phone_mobile', 'gnz_number'];
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
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
  <title>Duplicate Members</title>
  <style>
    <?php $inc = "../orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
    <?php $inc = "../orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
  </style>
  <style>
    body { margin:0; font-family:Arial,Helvetica,sans-serif; background:#fafafa; }
    .padding-container { padding:20px; max-width:1000px; margin:0 auto; }
    h2 { margin:0 0 5px 0; font-size:20px; color:#2d3748; }
    .dup-group { background:#fff; border-radius:4px; box-shadow:0 1px 4px rgba(0,0,0,0.08); margin-bottom:15px; overflow:hidden; }
    .dup-group .group-header { background:#063552; color:#f26120; padding:10px 14px; font-size:14px; font-weight:bold; }
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
    .group-id { font-size:11px; color:#888; font-weight:normal; margin-left:8px; }
  </style>
</head>
<body>
  <div class="padding-container">
    <h2>Duplicate Members</h2>
    <a href="/" class="back-link">&larr; Home</a>

<?php if ($mergeResult): ?>
    <div class="alert alert-<?php echo $mergeResult['success'] ? 'success' : 'danger'; ?>">
      <?php echo htmlspecialchars($mergeResult['message']); ?>
    </div>
<?php endif; ?>

<?php if (empty($groups)): ?>
    <p class="no-dups">No duplicate members found.</p>
<?php else: ?>
<?php foreach ($groups as $g):
        $fnameEsc = mysqli_real_escape_string($con, $g['firstname']);
        $snameEsc = mysqli_real_escape_string($con, $g['surname']);
        $members = [];
        $mr = mysqli_query($con, "SELECT m.*, organisations.name AS org_name FROM members m JOIN organisations ON m.org = organisations.id WHERE m.firstname = '$fnameEsc' AND m.surname = '$snameEsc' AND m.org = {$current_org} ORDER BY m.id");
        while ($mrow = mysqli_fetch_assoc($mr)) {
            $members[] = $mrow;
        }
        $groupId = md5($g['firstname'] . $g['surname'] . $g['org']);
        $allIds = array_map(function($m) { return $m['id']; }, $members);
?>
    <form method="post" action="./duplicates_index.php" class="dup-group">
      <div class="group-header">
        <?php echo htmlspecialchars($g['firstname'] . ' ' . $g['surname']); ?> 
        <span class="group-id"><?php echo count($members); ?> records</span>
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
<?php foreach ($members as $m): ?>
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
<?php mysqli_close($con); ?>
