<?php session_start(); ?>
<?php
require_once __DIR__ . '/load_model.php';

$org = 0;
if (isset($_GET['org'])) {
    $org = intval($_GET['org']);
} elseif (isset($_SESSION['org'])) {
    $org = $_SESSION['org'];
}

if (isset($_SESSION['security'])) {
    if (!($_SESSION['security'] & 64)) {
        die("Security level too low for this page");
    }
} else {
    header('Location: Login.php');
    die("Please logon");
}

$requestedId = isset($_GET['id']) ? intval($_GET['id']) : null;
$isEdit = $requestedId !== null;

$organisation = App\Models\Organisation::find($org);

$con_params = require('./config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);

// Get organisations for dropdown
$organisations = [];
$q = "SELECT * FROM organisations ORDER BY name";
$r = mysqli_query($con, $q);
while ($row = mysqli_fetch_assoc($r)) {
    $organisations[] = $row;
}

// Get members for dropdown
$members = [];
$q = "SELECT id, displayname FROM members";
if ($org > 0) {
    $q .= " WHERE org = " . intval($org);
}
$q .= " ORDER BY displayname";
$r = mysqli_query($con, $q);
while ($row = mysqli_fetch_assoc($r)) {
    $members[] = $row;
}

$user = null;
if ($isEdit) {
    $q = "SELECT * FROM users WHERE id = " . intval($requestedId);
    $r = mysqli_query($con, $q);
    if ($r) {
        $user = mysqli_fetch_assoc($r);
    }
}

mysqli_close($con);
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width">
<head>
    <title><?php echo $isEdit ? 'Edit User' : 'Create User'; ?></title>
    <?php include 'jsLibraies.php'; ?>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style>
        body { padding: 0; }
        .padding-container { padding: 15px; }
        .form-group { margin-bottom: 15px; }
        .required { color: red; }
        .error-msg { color: red; margin-bottom: 10px; }
        .success-msg { color: green; margin-bottom: 10px; }
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
<div class="title-row" style="display: flex; align-items: center; margin-bottom: 10px;">
    <h2><?php echo $isEdit ? 'Edit User' : 'Create User'; ?></h2>
    <a href="/users.php<?php echo $requestedId ? '?id=' . $requestedId : ''; ?>" class="btn btn-default btn-sm" style="margin-left: 10px;">Old Version</a>
</div>

<div id="message-area"></div>

<div id="user-form-container">
    <form id="user-form" class="form-horizontal">
        <input type="hidden" name="id" value="<?php echo $requestedId ?? ''; ?>">

        <div class="form-group">
            <label class="control-label col-sm-2">Name <span class="required">*</span></label>
            <div class="col-sm-4">
                <input type="text" class="form-control" name="name" id="name" value="<?php echo $user ? htmlspecialchars($user['name']) : ''; ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-sm-2">Usercode <span class="required">*</span></label>
            <div class="col-sm-4">
                <input type="text" class="form-control" name="usercode" id="usercode" value="<?php echo $user ? htmlspecialchars($user['usercode']) : ''; ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-sm-2">Password <?php if (!$isEdit) echo '<span class="required">*</span>'; ?></label>
            <div class="col-sm-4">
                <input type="text" class="form-control" name="password" id="password" <?php echo $isEdit ? '' : 'required'; ?>>
                <?php if ($isEdit): ?>
                <span class="help-block">Leave blank to keep existing password</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($_SESSION['security'] & 128): ?>
        <div class="form-group">
            <label class="control-label col-sm-2">Organisation</label>
            <div class="col-sm-4">
                <select class="form-control" name="org" id="org">
                    <option value="0"></option>
                    <?php foreach ($organisations as $orgRow): ?>
                        <option value="<?php echo $orgRow['id']; ?>" <?php echo ($user && $user['org'] == $orgRow['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($orgRow['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label class="control-label col-sm-2">Expires <span class="required">*</span></label>
            <div class="col-sm-4">
                <input type="date" class="form-control" name="expire" id="expire" value="<?php echo $user ? substr($user['expire'], 0, 10) : ''; ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-sm-2">Security Level <span class="required">*</span></label>
            <div class="col-sm-4">
                <input type="text" class="form-control" name="securitylevel" id="securitylevel" value="<?php echo $user ? htmlspecialchars($user['securitylevel']) : ''; ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-sm-2">Member</label>
            <div class="col-sm-4">
                <select class="form-control" name="member" id="member">
                    <option value="0"></option>
                    <?php foreach ($members as $memberRow): ?>
                        <option value="<?php echo $memberRow['id']; ?>" <?php echo ($user && $user['member'] == $memberRow['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($memberRow['displayname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-4">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="force_pw_reset" id="force_pw_reset" value="1" <?php echo ($user && $user['force_pw_reset'] == 1) ? 'checked' : ''; ?>>
                        Force Password Reset
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-4">
                <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Update' : 'Create'; ?></button>
                <a href="/UsersList" class="btn btn-default">Cancel</a>
            </div>
        </div>
    </form>
</div>
</div>

<script>
$(document).ready(function() {
    $('#user-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();

        $.ajax({
            url: '/api/user-form',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(data) {
                console.log('AJAX success:', data);
                if (data.success) {
                    $('#message-area').html('<div class="success-msg">' + data.message + '</div>');
                    $('html, body').scrollTop(0);
                    if (!<?php echo $isEdit ? 'true' : 'false'; ?>) {
                        window.location.href = '/UsersList';
                    }
                } else {
                    console.log('Showing error:', data.message);
                    $('#message-area').html('<div class="error-msg">' + (data.message || 'Error saving user') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error:', status, error);
                $('#message-area').html('<div class="error-msg">Failed to save user</div>');
            }
        });
    });
});
</script>

</body>
</html>
