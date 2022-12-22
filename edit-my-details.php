<?php session_start(); ?>
<?php

$org = 0;
if (isset($_SESSION['org'])) $org = $_SESSION['org'];

$reqid = $_SESSION['memberid'];

if (!isset($reqid)){
  header("Location: /home");
}

$DEBUG = 0;

$pageid = 111;
$errtext = "";
$sqltext = "";
$error = 0;
$id_err = "";
$org_err = "";
$create_time_err = "";
$effective_from_err = "";
$firstname_err = "";
$surname_err = "";
$displayname_err = "";
$date_of_birth_err = "";
$mem_addr1_err = "";
$mem_addr2_err = "";
$mem_addr3_err = "";
$mem_addr4_err = "";
$mem_city_err = "";
$mem_country_err = "";
$mem_postcode_err = "";
$emerg_addr1_err = "";
$emerg_addr2_err = "";
$emerg_addr3_err = "";
$emerg_addr4_err = "";
$emerg_city_err = "";
$emerg_country_err = "";
$emerg_postcode_err = "";
$gnz_number_err = "";
$qgp_number_err = "";
$phone_home_err = "";
$phone_mobile_err = "";
$phone_work_err = "";
$email_err = "";
$first_aider_err = "";
$localdate_lastemail_err = "";

function InputChecker($data)
{
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $trantype = "Update";
  $con_params = require('./config/database.php');
  $con_params = $con_params['gliding'];
  $con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);
  if (mysqli_connect_errno()) {
    $errtext = "Failed to connect to Database: " . mysqli_connect_error();
  } else {
    $q = "SELECT * FROM members WHERE id = " . $reqid;
    $r = mysqli_query($con, $q);
    $row = mysqli_fetch_array($r);
    if ($_SESSION['org'] > 0 && $row['org'] != $_SESSION['org'])
      die("Member not found!");
    $id_f = $row['id'];
    $firstname_f = htmlspecialchars($row['firstname'], ENT_QUOTES);
    $surname_f = htmlspecialchars($row['surname'], ENT_QUOTES);
    $displayname_f = htmlspecialchars($row['displayname'], ENT_QUOTES);
    $date_of_birth_f = $row['date_of_birth'];
    $mem_addr1_f = htmlspecialchars($row['mem_addr1'], ENT_QUOTES);
    $mem_addr2_f = htmlspecialchars($row['mem_addr2'], ENT_QUOTES);
    $mem_addr3_f = htmlspecialchars($row['mem_addr3'], ENT_QUOTES);
    $mem_addr4_f = htmlspecialchars($row['mem_addr4'], ENT_QUOTES);
    $mem_city_f = htmlspecialchars($row['mem_city'], ENT_QUOTES);
    $mem_country_f = htmlspecialchars($row['mem_country'], ENT_QUOTES);
    $mem_postcode_f = htmlspecialchars($row['mem_postcode'], ENT_QUOTES);
    $emerg_addr1_f = htmlspecialchars($row['emerg_addr1'], ENT_QUOTES);
    $emerg_addr2_f = htmlspecialchars($row['emerg_addr2'], ENT_QUOTES);
    $emerg_addr3_f = htmlspecialchars($row['emerg_addr3'], ENT_QUOTES);
    $emerg_addr4_f = htmlspecialchars($row['emerg_addr4'], ENT_QUOTES);
    $emerg_city_f = htmlspecialchars($row['emerg_city'], ENT_QUOTES);
    $emerg_country_f = htmlspecialchars($row['emerg_country'], ENT_QUOTES);
    $emerg_postcode_f = htmlspecialchars($row['emerg_postcode'], ENT_QUOTES);
    $gnz_number_f = $row['gnz_number'];
    $qgp_number_f = $row['qgp_number'];
    $phone_home_f = htmlspecialchars($row['phone_home'], ENT_QUOTES);
    $phone_mobile_f = htmlspecialchars($row['phone_mobile'], ENT_QUOTES);
    $phone_work_f = htmlspecialchars($row['phone_work'], ENT_QUOTES);
    $email_f = htmlspecialchars($row['email'], ENT_QUOTES);
    $first_aider_f = $row['first_aider'];

    mysqli_close($con);
  }
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $firstname_f = InputChecker($_POST["firstname_i"]);
  if (empty($firstname_f)) {
    $firstname_err = "FIRSTNAME is required";
    $error = 1;
  }
  $surname_f = InputChecker($_POST["surname_i"]);
  if (empty($surname_f)) {
    $surname_err = "SURNAME is required";
    $error = 1;
  }
  $displayname_f = InputChecker($_POST["displayname_i"]);
  if (empty($displayname_f)) {
    $displayname_err = "DISPLAY NAME is required";
    $error = 1;
  }
  $date_of_birth_f = InputChecker($_POST["date_of_birth_i"]);
  $mem_addr1_f = InputChecker($_POST["mem_addr1_i"]);
  $mem_addr2_f = InputChecker($_POST["mem_addr2_i"]);
  $mem_addr3_f = InputChecker($_POST["mem_addr3_i"]);
  $mem_addr4_f = InputChecker($_POST["mem_addr4_i"]);
  $mem_city_f = InputChecker($_POST["mem_city_i"]);
  $mem_country_f = InputChecker($_POST["mem_country_i"]);
  $mem_postcode_f = InputChecker($_POST["mem_postcode_i"]);
  $emerg_addr1_f = InputChecker($_POST["emerg_addr1_i"]);
  $emerg_addr2_f = InputChecker($_POST["emerg_addr2_i"]);
  $emerg_addr3_f = InputChecker($_POST["emerg_addr3_i"]);
  $emerg_addr4_f = InputChecker($_POST["emerg_addr4_i"]);
  $emerg_city_f = InputChecker($_POST["emerg_city_i"]);
  $emerg_country_f = InputChecker($_POST["emerg_country_i"]);
  $emerg_postcode_f = InputChecker($_POST["emerg_postcode_i"]);
  $gnz_number_f = InputChecker($_POST["gnz_number_i"]);
  if (!empty($gnz_number_f)) {
    if (!is_numeric($gnz_number_f)) {
      $gnz_number_err = "GNZ NUMBER is not numeric";
      $error = 1;
    }
  } else {
    $gnz_number_f = 0;
  }
  $qgp_number_f = InputChecker($_POST["qgp_number_i"]);
  if (!empty($qgp_number_f)) {
    if (!is_numeric($qgp_number_f)) {
      $qgp_number_err = "QGP NUMBER is not numeric";
      $error = 1;
    }
  } else {
    $qgp_number_f = 0;
  }
  $phone_home_f = InputChecker($_POST["phone_home_i"]);
  $phone_mobile_f = InputChecker($_POST["phone_mobile_i"]);
  $phone_work_f = InputChecker($_POST["phone_work_i"]);
  $email_f = InputChecker($_POST["email_i"]);
  if (is_array($_POST['first_aider_i']) && in_array("1", $_POST['first_aider_i']))
    $first_aider_f = 1;
  else
    $first_aider_f = 0;
  if ($error != 1) {
    $con_params = require('./config/database.php');
    $con_params = $con_params['gliding'];
    $con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);
    if (mysqli_connect_errno()) {
      $errtext = "Failed to connect to Database: " . mysqli_connect_error();
    } else {
      if (isset($_POST["tran"])) {
        if ($_POST["tran"] == "Update") {
          $Q = "UPDATE members SET ";
          $Q .= "firstname=";
          $Q .= "'" . mysqli_real_escape_string($con, $firstname_f)  . "'";
          $Q .= ",surname=";
          $Q .= "'" . mysqli_real_escape_string($con, $surname_f)  . "'";
          $Q .= ",displayname=";
          $Q .= "'" . mysqli_real_escape_string($con, $displayname_f)  . "'";
          $Q .= ",date_of_birth=";
          $Q .= "'" . $date_of_birth_f . "'";
          $Q .= ",mem_addr1=";
          $Q .= "'" . mysqli_real_escape_string($con, $mem_addr1_f)  . "'";
          $Q .= ",mem_addr2=";
          $Q .= "'" . mysqli_real_escape_string($con, $mem_addr2_f)  . "'";
          $Q .= ",mem_addr3=";
          $Q .= "'" . mysqli_real_escape_string($con, $mem_addr3_f)  . "'";
          $Q .= ",mem_addr4=";
          $Q .= "'" . mysqli_real_escape_string($con, $mem_addr4_f)  . "'";
          $Q .= ",mem_city=";
          $Q .= "'" . mysqli_real_escape_string($con, $mem_city_f)  . "'";
          $Q .= ",mem_country=";
          $Q .= "'" . mysqli_real_escape_string($con, $mem_country_f)  . "'";
          $Q .= ",mem_postcode=";
          $Q .= "'" . mysqli_real_escape_string($con, $mem_postcode_f)  . "'";
          $Q .= ",emerg_addr1=";
          $Q .= "'" . mysqli_real_escape_string($con, $emerg_addr1_f)  . "'";
          $Q .= ",emerg_addr2=";
          $Q .= "'" . mysqli_real_escape_string($con, $emerg_addr2_f)  . "'";
          $Q .= ",emerg_addr3=";
          $Q .= "'" . mysqli_real_escape_string($con, $emerg_addr3_f)  . "'";
          $Q .= ",emerg_addr4=";
          $Q .= "'" . mysqli_real_escape_string($con, $emerg_addr4_f)  . "'";
          $Q .= ",emerg_city=";
          $Q .= "'" . mysqli_real_escape_string($con, $emerg_city_f)  . "'";
          $Q .= ",emerg_country=";
          $Q .= "'" . mysqli_real_escape_string($con, $emerg_country_f)  . "'";
          $Q .= ",emerg_postcode=";
          $Q .= "'" . mysqli_real_escape_string($con, $emerg_postcode_f)  . "'";
          $Q .= ",gnz_number=";
          $Q .= "'" . $gnz_number_f . "'";
          $Q .= ",qgp_number=";
          $Q .= "'" . $qgp_number_f . "'";
          $Q .= ",phone_home=";
          $Q .= "'" . mysqli_real_escape_string($con, $phone_home_f)  . "'";
          $Q .= ",phone_mobile=";
          $Q .= "'" . mysqli_real_escape_string($con, $phone_mobile_f)  . "'";
          $Q .= ",phone_work=";
          $Q .= "'" . mysqli_real_escape_string($con, $phone_work_f)  . "'";
          $Q .= ",email=";
          $Q .= "'" . mysqli_real_escape_string($con, $email_f)  . "'";
          $Q .= ",first_aider=";
          $Q .= "'" . $first_aider_f . "'";
          $Q .= " WHERE ";
          $Q .= "id ";
          $Q .= "= ";
          $Q .= $_POST['id'];
        }
      }
      $sqltext = $Q;
      if (!mysqli_query($con, $Q)) {
        $errtext = "Database entry: " . mysqli_error($con) . "<br>" . $Q;
      } else {
        $_SESSION["message"] = "Your details have been successfully updated.";
        header("Location: edit-my-details.php");
        exit();
      }
      mysqli_close($con);
    }
  }
  $firstname_f = htmlspecialchars($firstname_f, ENT_QUOTES);
  $surname_f = htmlspecialchars($surname_f, ENT_QUOTES);
  $displayname_f = htmlspecialchars($displayname_f, ENT_QUOTES);
  $mem_addr1_f = htmlspecialchars($mem_addr1_f, ENT_QUOTES);
  $mem_addr2_f = htmlspecialchars($mem_addr2_f, ENT_QUOTES);
  $mem_addr3_f = htmlspecialchars($mem_addr3_f, ENT_QUOTES);
  $mem_addr4_f = htmlspecialchars($mem_addr4_f, ENT_QUOTES);
  $mem_city_f = htmlspecialchars($mem_city_f, ENT_QUOTES);
  $mem_country_f = htmlspecialchars($mem_country_f, ENT_QUOTES);
  $mem_postcode_f = htmlspecialchars($mem_postcode_f, ENT_QUOTES);
  $emerg_addr1_f = htmlspecialchars($emerg_addr1_f, ENT_QUOTES);
  $emerg_addr2_f = htmlspecialchars($emerg_addr2_f, ENT_QUOTES);
  $emerg_addr3_f = htmlspecialchars($emerg_addr3_f, ENT_QUOTES);
  $emerg_addr4_f = htmlspecialchars($emerg_addr4_f, ENT_QUOTES);
  $emerg_city_f = htmlspecialchars($emerg_city_f, ENT_QUOTES);
  $emerg_country_f = htmlspecialchars($emerg_country_f, ENT_QUOTES);
  $emerg_postcode_f = htmlspecialchars($emerg_postcode_f, ENT_QUOTES);
  $phone_home_f = htmlspecialchars($phone_home_f, ENT_QUOTES);
  $phone_mobile_f = htmlspecialchars($phone_mobile_f, ENT_QUOTES);
  $phone_work_f = htmlspecialchars($phone_work_f, ENT_QUOTES);
  $email_f = htmlspecialchars($email_f, ENT_QUOTES);
}

?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width">
<meta name="viewport" content="initial-scale=1.0">

<head>
  <style>
    <?php $inc = "./orgs/" . $org . "/heading2.css";
    include $inc; ?>
  </style>
  <style>
    <?php $inc = "./orgs/" . $org . "/menu1.css";
    include $inc; ?>
  </style>
  <link rel="stylesheet" type="text/css" href="styleform1.css">
</head>

<body>
  <?php include __DIR__ . '/helpers/dev_mode_banner.php' ?>
  <?php $inc = "./orgs/" . $org . "/heading2.txt";
  include $inc; ?>
  <?php $inc = "./orgs/" . $org . "/menu1.txt";
  include $inc; ?>
  <script>
    function goBack() {
      window.location = 'home'
    }
  </script>
  <link rel="stylesheet" href="./css/notify.css" />
  <script src="./js/notify.js"></script>
  <?php
  if (isset($_SESSION['message'])) {
  ?>
    <script>
      var options = {
        message: '<?php echo $_SESSION['message'] ?>',
        color: "success",
        timeout: 10000,
      };
      notify(options);
    </script>
  <?php
    $_SESSION['message'] = null;
  } ?>
  <div id='divform'>
    <form method="post" action="<?php echo htmlspecialchars('./edit-my-details.php'); ?>">
      <table>
        <?php if (true) {
          echo "<tr><td class='desc'>ID</td><td></td>";
          echo "<td>";
          echo $id_f;
          echo "</td>";
          echo "<td>";
          echo $id_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>FIRSTNAME</td><td>*</td>";
          echo "<td>";
          echo "<input ";
          if (strlen($firstname_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='firstname_i' ";
          echo "size='30' ";
          echo "Value='";
          echo $firstname_f;
          echo "' ";
          echo "maxlength='40'";
          echo "><span class='field-error-msg'>{$firstname_err}</span>";
          echo "</td>";
          echo "<td>";
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>SURNAME</td><td>*</td>";
          echo "<td>";
          echo "<input ";
          if (strlen($surname_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='surname_i' ";
          echo "size='30' ";
          echo "Value='";
          echo $surname_f;
          echo "' ";
          echo "maxlength='40'";
          echo "><span class='field-error-msg'>{$surname_err}</span>";
          echo "</td>";
          echo "<td></td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>DISPLAY NAME</td><td>*</td>";
          echo "<td>";
          echo "<input ";
          if (strlen($displayname_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='displayname_i' ";
          echo "size='40' ";
          echo "Value='";
          echo $displayname_f;
          echo "' ";
          echo "maxlength='80'";
          echo "><span class='field-error-msg'>{$displayname_err}</span>";
          echo "</td>";
          echo "<td></td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>DATE OF BIRTH</td><td></td>";
          echo "<td><input type='date' name='date_of_birth_i' Value='" . substr($date_of_birth_f, 0, 10) . "'></td>";
          echo "<td>";
          echo $date_of_birth_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>POSTAL ADDRESS</td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($mem_addr1_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='mem_addr1_i' ";
          echo "Value='";
          echo $mem_addr1_f;
          echo "' ";
          echo "maxlength='45'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $mem_addr1_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'></td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($mem_addr2_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='mem_addr2_i' ";
          echo "Value='";
          echo $mem_addr2_f;
          echo "' ";
          echo "maxlength='45'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $mem_addr2_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'></td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($mem_addr3_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='mem_addr3_i' ";
          echo "Value='";
          echo $mem_addr3_f;
          echo "' ";
          echo "maxlength='45'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $mem_addr3_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'></td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($mem_addr4_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='mem_addr4_i' ";
          echo "Value='";
          echo $mem_addr4_f;
          echo "' ";
          echo "maxlength='45'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $mem_addr4_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>CITY</td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($mem_city_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='mem_city_i' ";
          echo "Value='";
          echo $mem_city_f;
          echo "' ";
          echo "maxlength='45'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $mem_city_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>COUNTRY</td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($mem_country_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='mem_country_i' ";
          echo "Value='";
          echo $mem_country_f;
          echo "' ";
          echo "maxlength='45'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $mem_country_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>POSTCODE</td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($mem_postcode_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='mem_postcode_i' ";
          echo "Value='";
          echo $mem_postcode_f;
          echo "' ";
          echo "maxlength='45'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $mem_postcode_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>EMERGENCY CONTACT INFO</td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($emerg_addr1_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='emerg_addr1_i' ";
          echo "Value='";
          echo $emerg_addr1_f;
          echo "' ";
          echo "maxlength='45'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $emerg_addr1_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'></td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($emerg_addr2_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='emerg_addr2_i' ";
          echo "Value='";
          echo $emerg_addr2_f;
          echo "' ";
          echo "maxlength='45'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $emerg_addr2_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'></td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($emerg_addr3_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='emerg_addr3_i' ";
          echo "Value='";
          echo $emerg_addr3_f;
          echo "' ";
          echo "maxlength='45'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $emerg_addr3_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'></td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($emerg_addr4_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='emerg_addr4_i' ";
          echo "Value='";
          echo $emerg_addr4_f;
          echo "' ";
          echo "maxlength='45'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $emerg_addr4_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>CITY</td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($emerg_city_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='emerg_city_i' ";
          echo "Value='";
          echo $emerg_city_f;
          echo "' ";
          echo "maxlength='45'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $emerg_city_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>COUNTRY</td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($emerg_country_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='emerg_country_i' ";
          echo "Value='";
          echo $emerg_country_f;
          echo "' ";
          echo "maxlength='45'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $emerg_country_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>POSTCODE</td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($emerg_postcode_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='emerg_postcode_i' ";
          echo "Value='";
          echo $emerg_postcode_f;
          echo "' ";
          echo "maxlength='45'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $emerg_postcode_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>GNZ NUMBER</td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($gnz_number_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='gnz_number_i' ";
          echo "size='10' ";
          echo "Value='";
          echo $gnz_number_f;
          echo "' ";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $gnz_number_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>QGP NUMBER</td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($qgp_number_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='qgp_number_i' ";
          echo "size='10' ";
          echo "Value='";
          echo $qgp_number_f;
          echo "' ";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $qgp_number_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>HOME PHONE</td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($phone_home_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='phone_home_i' ";
          echo "size='20' ";
          echo "Value='";
          echo $phone_home_f;
          echo "' ";
          echo "maxlength='30'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $phone_home_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>MOBILE PHONE</td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($phone_mobile_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='phone_mobile_i' ";
          echo "size='20' ";
          echo "Value='";
          echo $phone_mobile_f;
          echo "' ";
          echo "maxlength='30'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $phone_mobile_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>WORK PHONE</td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($phone_work_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='phone_work_i' ";
          echo "size='20' ";
          echo "Value='";
          echo $phone_work_f;
          echo "' ";
          echo "maxlength='30'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $phone_work_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>EMAIL</td><td></td>";
          echo "<td>";
          echo "<input ";
          if (strlen($email_err) > 0) echo "class='err' ";
          echo "type='text' ";
          echo "name='email_i' ";
          echo "size='50' ";
          echo "Value='";
          echo $email_f;
          echo "' ";
          echo "maxlength='50'";
          echo ">";
          echo "</td>";
          echo "<td>";
          echo $email_err;
          echo "</td></tr>";
        }
        ?>
        <?php if (true) {
          echo "<tr><td class='desc'>FIRST AIDER</td><td></td>";
          echo "<td><input type='checkbox' name='first_aider_i[]' Value='1' ";
          if ($first_aider_f == 1) echo "checked";
          echo "></td>";
          echo "<td>";
          echo $first_aider_err;
          echo "</td></tr>";
        }
        ?>
      </table>
      <table>
        <tr>
          <td>
            <input type="submit" name='tran' value='Update'>
          </td>
          <td></td>
          <td></td>
        </tr>
      </table>
      <input type="hidden" name='id' value='<?php echo $reqid; ?>'>
    </form>
  </div>
  <div>
    <p><?php echo $errtext; ?></p>
    <?php if ($DEBUG > 0) echo "<p>" . $sqltext . "</p>"; ?>
  </div>
</body>

</html>