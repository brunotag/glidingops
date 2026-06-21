<?php session_start(); ?>
<?php require_once __DIR__ . '/helpers/permissions.php'; require_perm('personas.manage'); ?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="styletable1.css">
</head>
<body>
<div id="div1"><div id="div2">
<div class="table-responsive"><table class="table table-bordered table-striped" style="width:100%;">
<tr><th>ID</th><th>Persona Name</th><th>Description</th><th>Permissions</th><th>Users</th></tr>
<?php
require_once __DIR__ . '/helpers/database.php';
$con = open_gliding_db();
$r = mysqli_query($con, "SELECT id, name, description FROM personas ORDER BY name");
while ($row = mysqli_fetch_array($r)) {
    $pid = $row['id'];
    $permR = mysqli_query($con, "SELECT COUNT(*) FROM persona_permissions WHERE persona_id = $pid");
    $permCount = mysqli_fetch_array($permR)[0];
    $userR = mysqli_query($con, "SELECT COUNT(*) FROM user_personas WHERE persona_id = $pid");
    $userCount = mysqli_fetch_array($userR)[0];
    echo "<tr>";
    echo "<td><a href='Persona?id=" . $pid . "'>" . $pid . "</a></td>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['description'] ?? '') . "</td>";
    echo "<td>" . $permCount . " permissions</td>";
    echo "<td><a href='PersonaUsers?id=" . $pid . "'>" . $userCount . " users</a></td>";
    echo "</tr>";
}
mysqli_close($con);
?>
</table></div></div></div>
<form action='Persona' method='GET'><input type='submit' value='Create New'></form>
</body>
</html>
