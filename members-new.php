<?php session_start(); ?>
<?php
require_once __DIR__ . '/load_model.php';
require_once __DIR__ . '/helpers/logging.php';

logMsg("START - " . ($_GET['id'] ?? 'no id'));

$org = 0;
if (isset($_SESSION['org'])) $org = $_SESSION['org'];
require_once __DIR__ . '/helpers/permissions.php'; require_auth();

$requestedId = isset($_GET['id']) ? intval($_GET['id']) : null;
$currentMemberId = isset($_SESSION['memberid']) ? intval($_SESSION['memberid']) : 0;
$isCreateNew = $requestedId === null && strpos($_SERVER['REQUEST_URI'], 'MemberNew') !== false;

if (!$isCreateNew && $requestedId !== null && $requestedId !== $currentMemberId) {
    require_once __DIR__ . '/helpers/permissions.php'; require_perm('member.edit');
}

if ($isCreateNew) {
    $memberId = null;
} elseif ($requestedId !== null) {
    $memberId = $requestedId;
} else {
    $memberId = $currentMemberId;
}
$isEdit = $memberId !== null && $memberId > 0;
$isMyDetails = !$isCreateNew && $requestedId === null && $memberId === $currentMemberId;

// Load data using direct queries (like other pages)
require_once __DIR__ . '/helpers/database.php';
$con = open_gliding_db();

// Get membership classes
$allClasses = [];
$q = "SELECT * FROM membership_class ORDER BY class";
$r = mysqli_query($con, $q);
while ($row = mysqli_fetch_assoc($r)) {
    $allClasses[] = $row;
}

// Get membership statuses  
$allStatuses = [];
$q = "SELECT * FROM membership_status ORDER BY status_name";
$r = mysqli_query($con, $q);
while ($row = mysqli_fetch_assoc($r)) {
    $allStatuses[] = $row;
}

// Get roles
$allRoles = [];
$q = "SELECT * FROM roles ORDER BY name";
$r = mysqli_query($con, $q);
while ($row = mysqli_fetch_assoc($r)) {
    $allRoles[] = $row;
}

// Default values
$defaultClassId = null;
$defaultStatusId = null;
foreach ($allClasses as $c) {
    if ($c['class'] === 'Flying') { $defaultClassId = $c['id']; break; }
}
foreach ($allStatuses as $s) {
    if ($s['status_name'] === 'Active') { $defaultStatusId = $s['id']; break; }
}

// Load member data if editing
$member = null;
$memberRoles = [];
if ($isEdit) {
    $q = "SELECT * FROM members WHERE id = " . $memberId;
    $r = mysqli_query($con, $q);
    if ($row = mysqli_fetch_assoc($r)) {
        if ($org > 0 && $row['org'] != $org) {
            die("Record not found");
        }
        $member = $row;
        
        // Get roles
        $q = "SELECT role_id FROM role_member WHERE member_id = " . $memberId;
        $r = mysqli_query($con, $q);
        while ($row = mysqli_fetch_assoc($r)) {
            $memberRoles[] = $row['role_id'];
        }
    }
}

mysqli_close($con);
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width">
<head>
    <title><?php echo $isEdit ? 'Edit Member' : 'New Member'; ?></title>
    <?php include 'jsLibraies.php'; ?>
    <style>
        body { padding: 0; margin: 0; }
        .no-padding-container { width: 100%; }
        .padding-container { padding: 15px; }
        h2 { margin-bottom: 15px; margin-right: 20px; }
        .title-row { display: flex; align-items: center; margin-bottom: 10px; }
        .form-group label { font-weight: bold; }
        .checkbox { margin-left: 0; }
        .help-block { font-size: 12px; color: #666; }
        .panel { margin-bottom: 15px; }
        #message-area .alert { margin-bottom: 15px; }
    </style>
    <?php $inc = "./orgs/" . $org . "/heading2.css"; if (file_exists($inc)) { echo '<style>'; include $inc; echo '</style>'; } ?>
    <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) { echo '<style>'; include $inc; echo '</style>'; } ?>
</head>
<body>
<div class="no-padding-container">
    <?php $inc = "./orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
    <?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>
</div>

<div class="padding-container">
    <div class="title-row">
        <h2><?php 
            if ($isMyDetails) {
                echo 'Edit Your Details';
            } else {
                echo $isEdit ? 'Edit Member' : 'New Member';
            }
        ?></h2>
        <?php if (!$isMyDetails): ?>
        <a href="/AllMembers" class="btn btn-default btn-sm">Back to Members List</a>
        <?php endif; ?>
    </div>

    <div id="message-area"></div>

    <form id="member-form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" id="member-id" value="<?php echo $memberId; ?>">

        <div class="row">
        <div class="col-md-9">

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Member Details</strong></div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="firstname">First Name *</label>
                            <input type="text" class="form-control" name="firstname" id="firstname" value="<?php echo $member ? htmlspecialchars($member['firstname'] ?? '') : ''; ?>" required maxlength="40">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="surname">Surname *</label>
                            <input type="text" class="form-control" name="surname" id="surname" value="<?php echo $member ? htmlspecialchars($member['surname'] ?? '') : ''; ?>" required maxlength="40">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="displayname">Display Name *</label>
                            <input type="text" class="form-control" name="displayname" id="displayname" value="<?php echo $member ? htmlspecialchars($member['displayname'] ?? '') : ''; ?>" required maxlength="80">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" id="date_of_birth" value="<?php echo $member ? $member['date_of_birth'] ?? '' : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="class">Class *</label>
                            <select class="form-control" name="class" id="class" required>
                                <option value="">Select Class</option>
                                <?php foreach ($allClasses as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo ($member && $member['class'] == $class['id']) || (!$member && $defaultClassId == $class['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['class']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select class="form-control" name="status" id="status" required>
                                <option value="">Select Status</option>
                                <?php foreach ($allStatuses as $status): ?>
                                    <option value="<?php echo $status['id']; ?>" <?php echo ($member && $member['status'] == $status['id']) || (!$member && $defaultStatusId == $status['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($status['status_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="gnz_number">GNZ Number</label>
                            <input type="text" class="form-control" name="gnz_number" id="gnz_number" value="<?php echo $member ? htmlspecialchars($member['gnz_number'] ?? '') : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="phone_mobile">Mobile Phone</label>
                            <input type="text" class="form-control" name="phone_mobile" id="phone_mobile" value="<?php echo $member ? htmlspecialchars($member['phone_mobile'] ?? '') : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" name="email" id="email" value="<?php echo $member ? htmlspecialchars($member['email'] ?? '') : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="medical_expire">Medical Expiry</label>
                            <input type="date" class="form-control" name="medical_expire" id="medical_expire" value="<?php echo $member ? $member['medical_expire'] ?? '' : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="bfr_expire">BFR Expiry</label>
                            <input type="date" class="form-control" name="bfr_expire" id="bfr_expire" value="<?php echo $member ? $member['bfr_expire'] ?? '' : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="gone_solo" id="gone_solo" value="1" <?php echo $member && isset($member['gone_solo']) && $member['gone_solo'] ? 'checked' : ''; ?>>
                                Gone Solo
                            </label>
                            <label style="margin-left: 20px;">
                                <input type="checkbox" name="official_observer" id="official_observer" value="1" <?php echo $member && isset($member['official_observer']) && $member['official_observer'] ? 'checked' : ''; ?>>
                                Official Observer
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </div><!-- col-md-9 -->
        <div class="col-md-3">

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Photo</strong></div>
            <div class="panel-body" style="text-align:center;">
                <?php if ($isEdit): ?>
                <div style="margin:0 auto 10px auto;max-width:160px;max-height:160px;border:1px solid #ddd;border-radius:4px;overflow:hidden;">
                    <img id="current-photo" src="/img/members/<?php echo $memberId; ?>.jpg" onerror="this.src='/img/noprofile.png'" style="width:100%;height:auto;">
                </div>
                <?php endif; ?>
                <label class="btn btn-primary btn-sm" style="cursor:pointer;">
                    Choose File <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="var f=this.files[0];this.nextElementSibling.textContent=f?f.name:'No file chosen'">
                    <span style="font-weight:normal;margin-left:4px;"></span>
                </label>
                <p class="help-block" style="font-size:11px;">JPEG, PNG or WebP. Max 2MB.</p>
            </div>
        </div>

        </div><!-- col-md-3 -->
        </div><!-- row -->

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Address</strong></div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mem_addr1">Address Line 1</label>
                            <input type="text" class="form-control" name="mem_addr1" id="mem_addr1" value="<?php echo $member ? htmlspecialchars($member['mem_addr1'] ?? '') : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mem_addr2">Address Line 2</label>
                            <input type="text" class="form-control" name="mem_addr2" id="mem_addr2" value="<?php echo $member ? htmlspecialchars($member['mem_addr2'] ?? '') : ''; ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mem_addr3">Suburb</label>
                            <input type="text" class="form-control" name="mem_addr3" id="mem_addr3" value="<?php echo $member ? htmlspecialchars($member['mem_addr3'] ?? '') : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mem_city">City</label>
                            <input type="text" class="form-control" name="mem_city" id="mem_city" value="<?php echo $member ? htmlspecialchars($member['mem_city'] ?? '') : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mem_postcode">Postcode</label>
                            <input type="text" class="form-control" name="mem_postcode" id="mem_postcode" value="<?php echo $member ? htmlspecialchars($member['mem_postcode'] ?? '') : ''; ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mem_country">Country</label>
                            <input type="text" class="form-control" name="mem_country" id="mem_country" value="<?php echo $member ? htmlspecialchars($member['mem_country'] ?? '') : ''; ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="panel panel-default">
            <div class="panel-heading"><strong>Emergency Contact</strong></div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="emerg_addr1">Name</label>
                            <input type="text" class="form-control" name="emerg_addr1" id="emerg_addr1" value="<?php echo $member ? htmlspecialchars($member['emerg_addr1'] ?? '') : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="emerg_addr2">Phone</label>
                            <input type="text" class="form-control" name="emerg_addr2" id="emerg_addr2" value="<?php echo $member ? htmlspecialchars($member['emerg_addr2'] ?? '') : ''; ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="emerg_addr3">Relationship</label>
                            <input type="text" class="form-control" name="emerg_addr3" id="emerg_addr3" value="<?php echo $member ? htmlspecialchars($member['emerg_addr3'] ?? '') : ''; ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$isMyDetails): ?>
        <div class="panel panel-default">
            <div class="panel-heading"><strong>Roles</strong></div>
            <div class="panel-body">
                <?php if (count($allRoles) === 0): ?>
                    <p>No roles available</p>
                <?php else: ?>
                    <?php foreach ($allRoles as $role): ?>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="roles[]" value="<?php echo $role['id']; ?>" <?php echo in_array($role['id'], $memberRoles) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($role['name']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div style="margin-top: 15px; margin-bottom: 30px;">
            <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Update Member' : 'Create Member'; ?></button>
            <a href="/AllMembers" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    var displayNameModified = <?php echo $isEdit ? 'true' : 'false'; ?>;

    // Auto-suggest displayname
    function updateDisplayName() {
        var firstname = $('#firstname').val();
        var surname = $('#surname').val();
        if (firstname && surname && !displayNameModified) {
            $('#displayname').val(firstname.trim() + ' ' + surname.trim());
        }
    }

    $('#displayname').on('input', function() {
        displayNameModified = true;
    });

    $('#firstname, #surname').on('input', updateDisplayName);

    // Form submission
    $('#member-form').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            url: '/api/member-form.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $('#message-area').html('<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>' + data.message + '</div>');
                    if (data.photo_url) {
                        var img = $('#current-photo');
                        if (img.length) {
                            img.attr('src', data.photo_url + '?t=' + Date.now());
                            img.closest('div').show();
                        }
                    }
                } else {
                    $('#message-area').html('<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>' + (data.message || 'Error saving member') + '</div>');
                }
                $('html, body').scrollTop(0);
            },
            error: function(xhr, status, error) {
                $('#message-area').html('<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>Failed to save member</div>');
                $('html, body').scrollTop(0);
            }
        });
    });
});
</script>

</body>
</html>