<?php require './config/session.php'; $remember = !empty($_POST['remember']); session_set_cookie_params($remember ? SESSION_LIFETIME_REMEMBERED : SESSION_LIFETIME_NOT_REMEMBERED, "/"); ini_set('session.gc_maxlifetime', $remember ? SESSION_LIFETIME_REMEMBERED : 1440); session_start(); ?>
<!DOCTYPE HTML>
<html>
<head>
</head>
<body>
<?php
$DEBUG=0;
$pagesortdata = array();
for ($i = 0; $i < 65;$i++)
 $pagesortdata[$i] = 0;
$myusername=$_POST['user'];
$mypassword=$_POST['pcode'];
$myusername = stripslashes($myusername);
$mypassword = stripslashes($mypassword);
$mypassword = md5($mypassword);
require_once __DIR__ . '/helpers/database.php';
$con = open_gliding_db();
if (mysqli_connect_errno())
{
 echo "<p>Unable to connect to database</p>";
 exit();
}
$sql="SELECT * FROM users WHERE usercode='$myusername'";
$r = mysqli_query($con,$sql);
$row = mysqli_fetch_array($r);
if ($row['password'] == $mypassword)
{
  $doForceCheck=1;
  $_SESSION['userid']=$row['id'];
  $_SESSION['who']=$myusername;
  $_SESSION['memberid']=$row['member'];
  $_SESSION['org']=$row['org'];
  if ($_SESSION['org'] === NULL)
    $_SESSION['org'] = 0;
  require_once __DIR__ . '/helpers/permissions.php';
  $_SESSION['permissions'] = load_user_permissions($con, $row['id']);
  $_SESSION['pagesortdata']=$pagesortdata ;
  $_SESSION['dispname']=$row['name'];
  if ($_SESSION['org'] != 0)
  {
    $q="SELECT timezone from organisations where id = " . $_SESSION['org'];
     $r2 = mysqli_query($con,$q);
     $row2 = mysqli_fetch_array($r2);
     $_SESSION['timezone'] = $row2[0];
  }
  $desc='Login';
  if (!isset($_SESSION['memberid']) || $_SESSION['memberid'] === NULL)
   $q="INSERT INTO audit (userid,description) VALUES (" . $row['id'] .",'" . $desc . "')";
  else
    $q="INSERT INTO audit (userid,memberid,description) VALUES (" . $row['id'] ."," . $row['member'] .",'" . $desc . "')";
  $r = mysqli_query($con,$q);
  session_regenerate_id(true);
  if($doForceCheck>0)
  {
    if ($row['force_pw_reset'] > 0)
    {
      $_SESSION['force_pw_reset'] = 1;
      header('Location: PasswordChange');
    }
    else
      header('Location: home');
  }
  else
    header('Location: home');
}
else
{
  header('Location: Login.php?error=wrong_password');
}
mysqli_close($con);
?>
</body>
</html>
