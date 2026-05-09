<?php session_start(); ?>
<?php
require_once __DIR__ . '/load_model.php';

$org = 0;
if (isset($_SESSION['org'])) $org = $_SESSION['org'];
if (isset($_SESSION['security'])) {
    if (!($_SESSION['security'] & 6)) {
        die("Security level too low for this page");
    }
} else {
    header('Location: Login.php');
    die("Please logon");
}

$memberId = isset($_GET['id']) ? intval($_GET['id']) : null;
$isEdit = $memberId !== null;

// Load data server-side (like members-list-v2b.php)
$organisation = $org > 0 ? App\Models\Organisation::find($org) : null;
if ($organisation) {
    $allClasses = $organisation->membershipClasses()->orderBy('class')->get();
} else {
    $allClasses = collect([]);
}

$allStatuses = App\Models\MembershipStatus::orderBy('status')->get();

$allRoles = App\Models\Role::where('org', $org)->orWhere('org', 0)->orderBy('name')->get();

// Default values
$flyingClass = $allClasses->firstWhere('class', 'Flying');
$defaultClassId = $flyingClass ? $flyingClass->id : null;
$activeStatus = $allStatuses->firstWhere('status', 'Active');
$defaultStatusId = $activeStatus ? $activeStatus->id : null;

// Load member data if editing
$member = null;
$memberRoles = [];
if ($isEdit) {
    $member = \App\Models\Member::find($memberId);
    if ($member && $org > 0 && $member->org != $org) {
        die("Record not found");
    }
    if ($member) {
        $memberRoles = $member->roles->pluck('id')->toArray();
    }
}
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
        .error-msg { color: #a94442; font-size: 12px; }
        .success-msg { color: #3c763d; font-size: 14px; margin-bottom: 15px; }
    </style>
    <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
</head>
<body>
<div class="no-padding-container">
    <?php $inc = "./orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
    <?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>
</div>

<div class="padding-container">
    <div class="title-row">
        <h2><?php echo $isEdit ? 'Edit Member' : 'New Member'; ?></h2>
        <a href="/AllMembers" class="btn btn-default btn-sm">Back to Members List</a>
        <a href="/Member<?php echo $memberId ? '?id=' . $memberId : ''; ?>" class="btn btn-default btn-sm">Old Version</a>
    </div>

    <div id="message-area"></div>

    <form id="member-form" method="post">
        <input type="hidden" name="id" id="member-id" value="<?php echo $memberId; ?>">

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Basic Information</strong></div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="firstname">First Name *</label>
                            <input type="text" class="form-control" name="firstname" id="firstname" value="<?php echo $member ? htmlspecialchars($member->firstname ?? '') : ''; ?>" required maxlength="40">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="surname">Surname *</label>
                            <input type="text" class="form-control" name="surname" id="surname" value="<?php echo $member ? htmlspecialchars($member->surname ?? '') : ''; ?>" required maxlength="40">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="displayname">Display Name *</label>
                            <input type="text" class="form-control" name="displayname" id="displayname" value="<?php echo $member ? htmlspecialchars($member->displayname ?? '') : ''; ?>" required maxlength="80">
                            <span class="help-block">Auto-suggested from first + surname</span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" id="date_of_birth" value="<?php echo $member ? $member->date_of_birth ?? '' : ''; ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Membership</strong></div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="class">Class *</label>
                            <select class="form-control" name="class" id="class" required>
                                <option value="">Select Class</option>
                                <?php foreach ($allClasses as $class): ?>
                                    <option value="<?php echo $class->id; ?>" <?php echo ($member && $member->class == $class->id) || (!$member && $defaultClassId == $class->id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class->class); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select class="form-control" name="status" id="status" required>
                                <option value="">Select Status</option>
                                <?php foreach ($allStatuses as $status): ?>
                                    <option value="<?php echo $status->id; ?>" <?php echo ($member && $member->status == $status->id) || (!$member && $defaultStatusId == $status->id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($status->status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="gnz_number">GNZ Number</label>
                            <input type="text" class="form-control" name="gnz_number" id="gnz_number" value="<?php echo $member ? htmlspecialchars($member->gnz_number ?? '') : ''; ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Contact</strong></div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="phone_mobile">Mobile Phone</label>
                            <input type="text" class="form-control" name="phone_mobile" id="phone_mobile" value="<?php echo $member ? htmlspecialchars($member->phone_mobile ?? '') : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" name="email" id="email" value="<?php echo $member ? htmlspecialchars($member->email ?? '') : ''; ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Certifications</strong></div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="medical_expire">Medical Expiry</label>
                            <input type="date" class="form-control" name="medical_expire" id="medical_expire" value="<?php echo $member ? $member->medical_expire ?? '' : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="bfr_expire">BFR Expiry</label>
                            <input type="date" class="form-control" name="bfr_expire" id="bfr_expire" value="<?php echo $member ? $member->bfr_expire ?? '' : ''; ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Attributes</strong></div>
            <div class="panel-body">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="gone_solo" id="gone_solo" value="1" <?php echo $member && $member->gone_solo ? 'checked' : ''; ?>>
                        Gone Solo
                    </label>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="enable_email" id="enable_email" value="1" checked disabled>
                        Enable Email (always enabled)
                    </label>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="official_observer" id="official_observer" value="1" <?php echo $member && $member->official_observer ? 'checked' : ''; ?>>
                        Official Observer
                    </label>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Roles</strong></div>
            <div class="panel-body">
                <?php if ($allRoles->count() === 0): ?>
                    <p>No roles available</p>
                <?php else: ?>
                    <?php foreach ($allRoles as $role): ?>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="roles[]" value="<?php echo $role->id; ?>" <?php echo in_array($role->id, $memberRoles) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($role->name); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

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

        var formData = $(this).serialize();

        $.ajax({
            url: '/api/member-form.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $('#message-area').html('<div class="success-msg">' + data.message + '</div>');
                    setTimeout(function() {
                        window.location.href = '/AllMembers';
                    }, 1500);
                } else {
                    $('#message-area').html('<div class="error-msg">' + (data.message || 'Error saving member') + '</div>');
                }
            },
            error: function() {
                $('#message-area').html('<div class="error-msg">Failed to save member</div>');
            }
        });
    });
});
</script>

</body>
</html>