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
<?php
require_once __DIR__ . '/helpers/database.php';
$con = open_gliding_db();

$personaId = intval($_GET['id'] ?? 0);
$personaRow = mysqli_fetch_array(mysqli_query($con, "SELECT id, name, description FROM personas WHERE id = $personaId"));
if (!$personaRow) {
    echo "<p>Persona not found. <a href='Personas'>Back to list</a></p>";
    mysqli_close($con);
    exit;
}
$personaName = htmlspecialchars($personaRow['name']);
$personaDesc = htmlspecialchars($personaRow['description'] ?? '');

// handle remove
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_user_id'])) {
    $removeUserId = intval($_POST['remove_user_id']);
    mysqli_query($con, "DELETE FROM user_personas WHERE persona_id = $personaId AND user_id = $removeUserId");
    echo "<div class='alert alert-success'>User removed from persona.</div>";
}

echo "<h3>Users with persona: $personaName</h3>";
if ($personaDesc) echo "<p>$personaDesc</p>";
echo "<p><a href='Personas'>&larr; Back to personas</a></p>";

$q = "SELECT u.id, u.name, u.usercode, u.org, m.displayname AS member_name
      FROM users u
      INNER JOIN user_personas up ON up.user_id = u.id
      LEFT JOIN members m ON m.id = u.member
      WHERE up.persona_id = $personaId
      ORDER BY u.name";
$r = mysqli_query($con, $q);

echo "<div class='table-responsive'><table class='table table-bordered table-striped' style='width:100%;'>";
echo "<tr><th>ID</th><th>Name</th><th>Usercode</th><th>Member</th><th>Org</th><th></th></tr>";

$count = 0;
while ($row = mysqli_fetch_array($r)) {
    $count++;
    $uid = $row['id'];
    $orgName = '';
    $orgR = mysqli_query($con, "SELECT name FROM organisations WHERE id = " . intval($row['org']));
    if ($orgRow = mysqli_fetch_array($orgR)) $orgName = htmlspecialchars($orgRow['name']);
    echo "<tr>";
    echo "<td>" . $uid . "</td>";
    echo "<td>" . htmlspecialchars($row['name'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($row['usercode'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($row['member_name'] ?? '') . "</td>";
    echo "<td>" . $orgName . "</td>";
    echo "<td>";
    echo "<form method='post' style='margin:0' onsubmit=\"return confirm('Remove this user from $personaName?');\">";
    echo "<input type='hidden' name='remove_user_id' value='$uid'>";
    echo "<input type='submit' value='Remove' class='btn btn-danger btn-xs'>";
    echo "</form>";
    echo "</td>";
    echo "</tr>";
}
echo "</table></div>";
echo "<p><em>$count user(s) with this persona.</em></p>";
mysqli_close($con);
?>
</div></div>
</body>
</html>
