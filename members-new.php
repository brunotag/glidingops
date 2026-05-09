<?php session_start(); ?>
<?php
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

// For demo/demo: show empty form initially, JS will load data
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width">
<head>
    <title><?php echo $isEdit ? 'Edit Member' : 'New Member'; ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
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
                            <input type="text" class="form-control" name="firstname" id="firstname" required maxlength="40">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="surname">Surname *</label>
                            <input type="text" class="form-control" name="surname" id="surname" required maxlength="40">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="displayname">Display Name *</label>
                            <input type="text" class="form-control" name="displayname" id="displayname" required maxlength="80">
                            <span class="help-block">Auto-suggested from first + surname</span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" id="date_of_birth">
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
                                <option value="">Loading...</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select class="form-control" name="status" id="status" required>
                                <option value="">Loading...</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="gnz_number">GNZ Number</label>
                            <input type="text" class="form-control" name="gnz_number" id="gnz_number">
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
                            <input type="text" class="form-control" name="phone_mobile" id="phone_mobile">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" name="email" id="email">
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
                            <input type="date" class="form-control" name="medical_expire" id="medical_expire">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="bfr_expire">BFR Expiry</label>
                            <input type="date" class="form-control" name="bfr_expire" id="bfr_expire">
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
                        <input type="checkbox" name="gone_solo" id="gone_solo" value="1">
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
                        <input type="checkbox" name="official_observer" id="official_observer" value="1">
                        Official Observer
                    </label>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Roles</strong></div>
            <div class="panel-body" id="roles-container">
                <p>Loading roles...</p>
            </div>
        </div>

        <div style="margin-top: 15px; margin-bottom: 30px;">
            <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Update Member' : 'Create Member'; ?></button>
            <a href="/AllMembers" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    var isEdit = <?php echo $isEdit ? 'true' : 'false'; ?>;
    var memberId = <?php echo $memberId ? $memberId : 'null'; ?>;
    var displayNameModified = false;

    // Load form data
    $.ajax({
        url: '/api/member-form.php' + (memberId ? '?id=' + memberId : ''),
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.error) {
                $('#message-area').html('<div class="error-msg">' + data.message + '</div>');
                return;
            }

            // Populate class dropdown
            var classSelect = $('#class');
            classSelect.empty();
            classSelect.append('<option value="">Select Class</option>');
            data.classes.forEach(function(c) {
                classSelect.append('<option value="' + c.id + '">' + c.class + '</option>');
            });

            // Set default class (Flying)
            var flyingClass = data.classes.find(function(c) { return c.class === 'Flying'; });
            if (flyingClass) {
                classSelect.val(flyingClass.id);
            }

            // Populate status dropdown
            var statusSelect = $('#status');
            statusSelect.empty();
            statusSelect.append('<option value="">Select Status</option>');
            data.statuses.forEach(function(s) {
                statusSelect.append('<option value="' + s.id + '">' + s.status + '</option>');
            });

            // Set default status (Active)
            var activeStatus = data.statuses.find(function(s) { return s.status === 'Active'; });
            if (activeStatus) {
                statusSelect.val(activeStatus.id);
            }

            // Populate roles
            var rolesContainer = $('#roles-container');
            rolesContainer.empty();
            if (data.roles.length === 0) {
                rolesContainer.append('<p>No roles available</p>');
            } else {
                data.roles.forEach(function(role) {
                    var checked = data.member && data.member.roles && data.member.roles.indexOf(role.id) !== -1 ? 'checked' : '';
                    rolesContainer.append(
                        '<div class="checkbox">' +
                        '<label>' +
                        '<input type="checkbox" name="roles[]" value="' + role.id + '" ' + checked + '>' +
                        role.name +
                        '</label>' +
                        '</div>'
                    );
                });
            }

            // Populate member data if editing
            if (data.member) {
                $('#firstname').val(data.member.firstname);
                $('#surname').val(data.member.surname);
                $('#displayname').val(data.member.displayname);
                $('#date_of_birth').val(data.member.date_of_birth || '');
                $('#class').val(data.member.class);
                $('#status').val(data.member.status);
                $('#gnz_number').val(data.member.gnz_number || '');
                $('#phone_mobile').val(data.member.phone_mobile || '');
                $('#email').val(data.member.email || '');
                $('#medical_expire').val(data.member.medical_expire || '');
                $('#bfr_expire').val(data.member.bfr_expire || '');
                $('#gone_solo').prop('checked', data.member.gone_solo == 1);
                $('#official_observer').prop('checked', data.member.official_observer == 1);
                displayNameModified = true;
            }
        },
        error: function() {
            $('#message-area').html('<div class="error-msg">Failed to load form data</div>');
        }
    });

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