<?php session_start(); ?>
<?php require_once __DIR__ . '/helpers/permissions.php'; require_perm('personas.manage'); ?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width">
<head>
<link rel="stylesheet" type="text/css" href="styleform1.css">
<style>
.perm-grid { max-height: 500px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; margin-top: 10px; }
.perm-grid label { display: block; padding: 2px 0; font-weight: normal; }
.perm-grid input { margin-right: 6px; }
</style>
</head>
<body>
<?php
$errtext = "";
$id_f = "";
$name_f = "";
$desc_f = "";
$trantype = "Create";
$recid = -1;
$selectedPerms = [];

require_once __DIR__ . '/helpers/database.php';
$con = open_gliding_db();

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
    $recid = intval($_GET['id']);
    if ($recid >= 0) {
        $q = "SELECT * FROM personas WHERE id = " . $recid;
        $r = mysqli_query($con, $q);
        $row = mysqli_fetch_array($r);
        $id_f = $row['id'];
        $name_f = htmlspecialchars($row['name'], ENT_QUOTES);
        $desc_f = htmlspecialchars($row['description'] ?? '', ENT_QUOTES);
        $trantype = "Update";
        $pr = mysqli_query($con, "SELECT permission_id FROM persona_permissions WHERE persona_id = " . $recid);
        while ($prow = mysqli_fetch_array($pr)) {
            $selectedPerms[] = $prow['permission_id'];
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name_f = trim($_POST['name_i'] ?? '');
    $desc_f = trim($_POST['desc_i'] ?? '');
    $selectedPerms = $_POST['perms'] ?? [];
    if (empty($name_f)) {
        $errtext = "Persona name is required";
    } else {
        if (isset($_POST['del'])) {
            $q = "DELETE FROM personas WHERE id = " . intval($_POST['updateid']);
        } elseif ($_POST['tran'] == "Update") {
            $q = "UPDATE personas SET name='" . mysqli_real_escape_string($con, $name_f) . "', description='" . mysqli_real_escape_string($con, $desc_f) . "' WHERE id=" . intval($_POST['updateid']);
            if (mysqli_query($con, $q)) {
                $pid = intval($_POST['updateid']);
                mysqli_query($con, "DELETE FROM persona_permissions WHERE persona_id = $pid");
                foreach ($selectedPerms as $permId) {
                    $permId = intval($permId);
                    mysqli_query($con, "INSERT INTO persona_permissions (persona_id, permission_id) VALUES ($pid, $permId)");
                }
            } else {
                $errtext = "Database error: " . mysqli_error($con);
            }
        } else {
            $q = "INSERT INTO personas (name, description) VALUES ('" . mysqli_real_escape_string($con, $name_f) . "', '" . mysqli_real_escape_string($con, $desc_f) . "')";
            if (mysqli_query($con, $q)) {
                $pid = mysqli_insert_id($con);
                foreach ($selectedPerms as $permId) {
                    $permId = intval($permId);
                    mysqli_query($con, "INSERT INTO persona_permissions (persona_id, permission_id) VALUES ($pid, $permId)");
                }
                header('Location: Personas');
                exit;
            } else {
                $errtext = "Database error: " . mysqli_error($con);
            }
        }
        if (empty($errtext) && $_POST['tran'] == "Update") {
            header('Location: Personas');
            exit;
        }
    }
    $name_f = htmlspecialchars($name_f, ENT_QUOTES);
    $desc_f = htmlspecialchars($desc_f, ENT_QUOTES);
}
?>
<div id='divform'>
<form method="post" action="Persona">
<table>
<tr><td class='desc'>ID</td><td></td><td><?php echo $id_f; ?></td><td></td></tr>
<tr><td class='desc'>Persona Name</td><td></td>
<td><input type='text' name='name_i' size='50' value='<?php echo $name_f; ?>' maxlength='50' autofocus></td><td></td></tr>
<tr><td class='desc'>Description</td><td></td>
<td><input type='text' name='desc_i' size='80' value='<?php echo $desc_f; ?>' maxlength='255'></td><td></td></tr>
</table>

<h4>Permissions</h4>
<div class="perm-grid">
<?php
$allPerms = mysqli_query($con, "SELECT id, name, description FROM permissions ORDER BY name");
$currentSection = '';
while ($perm = mysqli_fetch_array($allPerms)) {
    $section = explode('.', $perm['name'])[0];
    if ($section !== $currentSection) {
        $currentSection = $section;
        echo "<h5 style='margin:8px 0 4px;border-bottom:1px solid #ddd;'>" . htmlspecialchars($section) . "</h5>";
    }
    $checked = in_array($perm['id'], $selectedPerms) ? " checked" : "";
    echo "<label><input type='checkbox' name='perms[]' value='" . $perm['id'] . "'" . $checked . "> " . htmlspecialchars($perm['name']) . " <small>(" . htmlspecialchars($perm['description'] ?? '') . ")</small></label>";
}
?>
</div>

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
