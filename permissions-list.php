<?php session_start(); ?>
<?php require_once __DIR__ . '/helpers/permissions.php'; require_perm('permissions.manage'); ?>
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
<tr><th>ID</th><th>Permission Name</th><th>Description</th></tr>
<?php
require_once __DIR__ . '/helpers/database.php';
$con = open_gliding_db();
$r = mysqli_query($con, "SELECT id, name, description FROM permissions ORDER BY name");
while ($row = mysqli_fetch_array($r)) {
    echo "<tr>";
    echo "<td><a href='Permission?id=" . $row['id'] . "'>" . $row['id'] . "</a></td>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['description'] ?? '') . "</td>";
    echo "</tr>";
}
mysqli_close($con);
?>
</table></div></div></div>
<form action='Permission' method='GET'><input type='submit' value='Create New'></form>
</body>
</html>
