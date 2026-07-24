<?php session_start(); ?>
<?php require_once __DIR__ . '/helpers/permissions.php'; require_perm('password.change'); ?>
<!DOCTYPE HTML>
<html>

<head>
</head>

<body>
  <?php
  $DEBUG = 0;
  $errtxt = "";

  $myusername = $_SESSION['who'];
  $myusername = stripslashes($myusername);
  require_once __DIR__ . '/helpers/database.php';
$con = open_gliding_db();
if (mysqli_connect_errno()) {
    echo "<p>Unable to connect to database</p>";
    exit();
  }
  $sql = "SELECT * FROM users WHERE usercode='$myusername'";
  if ($DEBUG > 0)
    echo "<p>" . $sql . "</p>";
  $r = mysqli_query($con, $sql);
  $row = mysqli_fetch_array($r);

  $canChangePassword = false;
  $userId = $row['id'];
  if (isset($_SESSION['force_pw_reset']) && $_SESSION['force_pw_reset'] == 1) {
    $canChangePassword = true;
  } elseif (isset($_SESSION['auth_via_magic_link']) && $_SESSION['auth_via_magic_link'] == 1) {
    $canChangePassword = true;
  } else {
    $mypasswordold = $_POST['pcodeold'];
    $mypasswordold = stripslashes($mypasswordold);
    if (!empty($row['password_hash'])) {
      $canChangePassword = password_verify($mypasswordold, $row['password_hash']);
    } elseif (!empty($row['password'])) {
      $canChangePassword = (md5($mypasswordold) == $row['password']);
      if ($canChangePassword) {
        $hash = password_hash($mypasswordold, PASSWORD_BCRYPT);
        $updateStmt = mysqli_prepare($con, "UPDATE users SET password_hash = ?, password = NULL WHERE id = ?");
        mysqli_stmt_bind_param($updateStmt, 'si', $hash, $userId);
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);
      }
    }
  }

  if ($canChangePassword) {
    $newpw1 = $_POST['pcodenew1'];
    $newpw2 = $_POST['pcodenew2'];
    $newpw1 = stripslashes($newpw1);
    $newpw2 = stripslashes($newpw2);
    if ($newpw1 != $newpw2) {
      $errtxt =  "New passords not identicle";
    } else {
      $hash = password_hash($newpw1, PASSWORD_BCRYPT);
      $updateStmt = mysqli_prepare($con, "UPDATE users SET password_hash = ?, password = NULL, force_pw_reset = 0 WHERE id = ?");
      mysqli_stmt_bind_param($updateStmt, 'si', $hash, $userId);
      mysqli_stmt_execute($updateStmt);
      mysqli_stmt_close($updateStmt);
      if (isset($_SESSION['force_pw_reset'])){
        unset($_SESSION['force_pw_reset']);
      }
      if (isset($_SESSION['auth_via_magic_link'])){
        unset($_SESSION['auth_via_magic_link']);
      }
      header('Location: home');
    }
  } else {
    $errtxt = "ERROR: Wrong Old Password Specified";
  }
  mysqli_close($con);
  echo "<p>" . $errtxt . "</p>";
  ?>


</body>

</html>