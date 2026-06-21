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
  if (isset($_SESSION['force_pw_reset']) && $_SESSION['force_pw_reset'] == 1) {
    $canChangePassword = true;
  } elseif (isset($_SESSION['auth_via_magic_link']) && $_SESSION['auth_via_magic_link'] == 1) {
    $canChangePassword = true;
  } else {
    $mypasswordold = $_POST['pcodeold'];
    $mypasswordold = stripslashes($mypasswordold);
    $mypasswordold = md5($mypasswordold);
    if ($row['password'] == $mypasswordold) {
      $canChangePassword = true;
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
      $sql = "UPDATE users SET password = '" . md5($newpw1) . "' , force_pw_reset = 0 where usercode='$myusername'";
      $r = mysqli_query($con, $sql);
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