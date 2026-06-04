<?php session_start(); ?>
<?php require_once __DIR__ . '/helpers/permissions.php'; require_perm('permissions.manage'); ?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width">
<head>
<link rel="stylesheet" type="text/css" href="styleform1.css">
</head>
<body>
<?php
$errtext = "";
$id_f = "";
$name_f = "";
$desc_f = "";
$trantype = "Create";
$recid = -1;

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
    $recid = $_GET['id'];
    if ($recid >= 0) {
        $con_params = require('./config/database.php'); $con_params = $con_params['gliding'];
        $con = mysqli_connect($con_params['hostname'],$con_params['username'],$con_params['password'],$con_params['dbname']);
        $q = "SELECT * FROM permissions WHERE id = " . intval($recid);
        $r = mysqli_query($con, $q);
        $row = mysqli_fetch_array($r);
        $id_f = $row['id'];
        $name_f = htmlspecialchars($row['name'], ENT_QUOTES);
        $desc_f = htmlspecialchars($row['description'] ?? '', ENT_QUOTES);
        $trantype = "Update";
        mysqli_close($con);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name_f = trim($_POST['name_i'] ?? '');
    $desc_f = trim($_POST['desc_i'] ?? '');
    if (empty($name_f)) {
        $errtext = "Permission name is required";
    } else {
        $con_params = require('./config/database.php'); $con_params = $con_params['gliding'];
        $con = mysqli_connect($con_params['hostname'],$con_params['username'],$con_params['password'],$con_params['dbname']);
        if (isset($_POST['del'])) {
            $q = "DELETE FROM permissions WHERE id = " . intval($_POST['updateid']);
        } elseif ($_POST['tran'] == "Update") {
            $q = "UPDATE permissions SET name='" . mysqli_real_escape_string($con, $name_f) . "', description='" . mysqli_real_escape_string($con, $desc_f) . "' WHERE id=" . intval($_POST['updateid']);
        } else {
            $q = "INSERT INTO permissions (name, description) VALUES ('" . mysqli_real_escape_string($con, $name_f) . "', '" . mysqli_real_escape_string($con, $desc_f) . "')";
        }
        if (!mysqli_query($con, $q)) {
            $errtext = "Database error: " . mysqli_error($con);
        } else {
            header('Location: Permissions');
            exit;
        }
        mysqli_close($con);
    }
    $name_f = htmlspecialchars($name_f, ENT_QUOTES);
    $desc_f = htmlspecialchars($desc_f, ENT_QUOTES);
}
?>
<div id='divform'>
<form method="post" action="Permission">
<table>
<tr><td class='desc'>ID</td><td></td><td><?php echo $id_f; ?></td><td></td></tr>
<tr><td class='desc'>Permission Name</td><td></td>
<td><input type='text' name='name_i' size='50' value='<?php echo $name_f; ?>' maxlength='50' autofocus></td><td></td></tr>
<tr><td class='desc'>Description</td><td></td>
<td><input type='text' name='desc_i' size='80' value='<?php echo $desc_f; ?>' maxlength='255'></td><td></td></tr>
</table>
<table>
<tr><td><input type='submit' name='tran' value='<?php echo $trantype; ?>'></td>
<td><?php if ($trantype == "Update") echo "<input type='submit' name='del' value='Delete'>"; ?></td></tr>
</table>
<input type='hidden' name='updateid' value='<?php echo $recid; ?>'>
</form>
</div>
<div><p><?php echo $errtext; ?></p></div>
</body>
</html>
