<?php
session_start();

require_once __DIR__ . '/helpers/logging.php';
require_once __DIR__ . '/helpers/email-templates.php';

if (!isLocal()) {
    die("Only available in dev environment");
}

require_once __DIR__ . '/helpers/permissions.php'; require_auth();

$org = isset($_SESSION['org']) ? intval($_SESSION['org']) : 0;
if ($org === 0) $org = 1;

$con_params = require('./config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);
$dbOk = !mysqli_connect_errno();

require_once __DIR__ . '/helpers.php';

$rawDate = $_POST['date'] ?? date('Y-m-d');
$selectedDate = str_replace('-', '', $rawDate);
if (strlen($selectedDate) !== 8) $selectedDate = date('Ymd');
$selectedMemberId = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
$previewHtml = '';
$previewError = '';
$previewData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedMemberId > 0 && $dbOk) {
    $dateStr2 = substr($selectedDate, 6, 2) . '/' . substr($selectedDate, 4, 2) . '/' . substr($selectedDate, 0, 4);
    $currentYm = substr($selectedDate, 0, 6);
    $orgname = getOrganisationName($con, $org);
    $isInstructor = IsMemberInstructor($con, $selectedMemberId);

    $previewData = getMemberRecapData($con, $org, $selectedMemberId, $selectedDate, $currentYm, $isInstructor);

    if (count($previewData['flights']) > 0) {
        $previewHtml = buildRecapEmail($orgname, $previewData['display_name'], $previewData['flights'], $dateStr2, $previewData['stats']);
    } else {
        $previewError = 'No finalised flights found for this member on ' . $dateStr2;
    }
}

if ($dbOk) mysqli_close($con);
?>
<!DOCTYPE HTML>
<html>
<head>
  <title>Dev Email Preview</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f0f0ff; font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 20px; }
    h1 { color: #063552; font-size: 20px; margin: 0 0 4px 0; }
    .dev-badge { display: inline-block; background: #d9534f; color: #fff; font-size: 11px; font-weight: bold; padding: 2px 10px; border-radius: 3px; text-transform: uppercase; vertical-align: middle; margin-left: 8px; }
    .form-panel { background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); padding: 20px; margin: 16px 0 20px 0; max-width: 500px; }
    .form-panel label { display: block; font-weight: bold; color: #333; margin-bottom: 4px; font-size: 13px; }
    .form-panel select, .form-panel input[type=date] { width: 100%; padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; margin-bottom: 12px; box-sizing: border-box; }
    .form-panel button { background: #063552; color: #f26120; border: none; padding: 8px 24px; border-radius: 4px; font-size: 14px; font-weight: bold; cursor: pointer; }
    .form-panel button:hover { opacity: 0.9; }
    .form-panel button:disabled { opacity: 0.4; cursor: default; }
    .preview-frame { border: 1px solid #ccc; border-radius: 6px; background: #fff; max-width: 680px; margin-top: 16px; }
    .preview-frame iframe { width: 100%; border: none; }
    .error-msg { color: #d9534f; font-weight: bold; margin: 12px 0; }
    .back-link { color: #063552; font-size: 13px; margin-bottom: 16px; display: block; }
    .email-data { background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; padding: 14px; margin-top: 12px; font-size: 12px; color: #555; }
    .email-data strong { color: #333; }
    .spinner { display: none; margin-left: 8px; color: #888; font-size: 13px; }
    .member-count { font-size: 12px; color: #888; margin: -8px 0 12px 0; }
  </style>
</head>
<body>
  <a href="/home" class="back-link">&larr; Back to Home</a>
  <h1>Email Preview <span class="dev-badge">Dev Only</span></h1>
  <p style="color:#888;font-size:13px;margin:4px 0 8px 0;">Select a date to find members who flew, then pick a member to preview their recap email.</p>

  <div class="form-panel">
    <form id="preview-form" method="post">
      <label>Date</label>
      <input type="date" id="flight-date" name="date"
             value="<?php echo substr($selectedDate, 0, 4) . '-' . substr($selectedDate, 4, 2) . '-' . substr($selectedDate, 6, 2); ?>">

      <label>Member who flew on this date</label>
      <select name="member_id" id="member-select" disabled>
        <option value="">-- Select a date first --</option>
      </select>
      <div class="spinner" id="loading-members">Loading members...</div>
      <div class="member-count" id="member-count"></div>

      <button type="submit" id="preview-btn" disabled>Preview Email</button>
    </form>
  </div>

  <?php if ($previewError): ?>
    <div class="error-msg"><?php echo htmlspecialchars($previewError); ?></div>
  <?php endif; ?>

  <?php if ($previewHtml): ?>
    <div class="email-data">
      <strong>To:</strong> <?php echo htmlspecialchars($previewData['email'] ?: '(no email)'); ?><br>
      <strong>Subject:</strong> Your WWGC flying recap - <?php echo htmlspecialchars($dateStr2); ?><br>
      <strong>Flights:</strong> <?php echo count($previewData['flights']); ?>
      <?php if ($previewData['stats']['total_flights_today'] > 0): ?>
        &middot; <strong>Duration:</strong> <?php echo $previewData['stats']['total_duration_today_min']; ?> min
      <?php endif; ?>
    </div>

    <div class="preview-frame">
      <iframe srcdoc="<?php echo htmlspecialchars($previewHtml, ENT_QUOTES); ?>" height="800" sandbox="allow-same-origin"></iframe>
    </div>
  <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedMemberId > 0): ?>
    <div class="email-data">No flights found for that member on that date.</div>
  <?php endif; ?>

  <script>
  (function() {
    var dateInput = document.getElementById('flight-date');
    var memberSelect = document.getElementById('member-select');
    var previewBtn = document.getElementById('preview-btn');
    var loading = document.getElementById('loading-members');
    var countLabel = document.getElementById('member-count');
    var form = document.getElementById('preview-form');

    function loadMembers(dateVal, selectedId) {
      selectedId = selectedId || 0;
      if (!dateVal) {
        memberSelect.innerHTML = '<option value="">-- Select a date first --</option>';
        memberSelect.disabled = true;
        previewBtn.disabled = true;
        countLabel.textContent = '';
        return;
      }

      memberSelect.innerHTML = '<option value="">Loading...</option>';
      memberSelect.disabled = true;
      previewBtn.disabled = true;
      loading.style.display = 'inline';

      var ymd = dateVal.replace(/-/g, '');

      var xhr = new XMLHttpRequest();
      xhr.open('GET', '/api/date-members?date=' + ymd, true);
      xhr.withCredentials = true;

      xhr.onload = function() {
        loading.style.display = 'none';
        if (xhr.status === 200) {
          try {
            var members = JSON.parse(xhr.responseText);
            memberSelect.innerHTML = '<option value="">-- Select member --</option>';
            if (members.length === 0) {
              memberSelect.innerHTML = '<option value="">No flights found on this date</option>';
              memberSelect.disabled = true;
              previewBtn.disabled = true;
              countLabel.textContent = '';
              return;
            }
            var foundSelected = false;
            for (var i = 0; i < members.length; i++) {
              var opt = document.createElement('option');
              opt.value = members[i].id;
              opt.textContent = members[i].name + ' (' + members[i].displayname + ')';
              if (selectedId && members[i].id == selectedId) {
                opt.selected = true;
                foundSelected = true;
              }
              memberSelect.appendChild(opt);
            }
            memberSelect.disabled = false;
            previewBtn.disabled = !foundSelected && !selectedId;
            countLabel.textContent = members.length + ' member' + (members.length !== 1 ? 's' : '') + ' flew on this date';
          } catch(e) {
            memberSelect.innerHTML = '<option value="">Error loading members</option>';
            memberSelect.disabled = true;
            previewBtn.disabled = true;
          }
        } else {
          memberSelect.innerHTML = '<option value="">Error loading members</option>';
          memberSelect.disabled = true;
          previewBtn.disabled = true;
        }
      };

      xhr.onerror = function() {
        loading.style.display = 'none';
        memberSelect.innerHTML = '<option value="">Network error</option>';
        memberSelect.disabled = true;
        previewBtn.disabled = true;
      };

      xhr.send();
    }

    <?php $selectedMemberIdJs = $selectedMemberId > 0 ? $selectedMemberId : 0; ?>

    // Always load members for the current date
    var initialDate = dateInput.value;
    if (initialDate) {
      loadMembers(initialDate, <?php echo $selectedMemberIdJs; ?>);
    }

    dateInput.addEventListener('change', function() {
      loadMembers(this.value);
    });

    memberSelect.addEventListener('change', function() {
      previewBtn.disabled = !this.value;
    });
  })();
  </script>
</body>
</html>
