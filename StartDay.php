<?php
include './helpers/timehelpers.php';
include 'helpers.php';
include './helpers/session_helpers.php';
session_start();
require_security_level(4);

$org = 0;
$errtext = '';
$defaultLocation = '';
$con_params = require('./config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET['org'])) {
        $org = intval($_GET['org']);
        if ($org < 1) {
            die("Error: You must supply an organisation number");
        }
    }
    if (isset($_GET['location'])) {
        $defaultLocation = $_GET['location'];
    } else {
        $q = "SELECT default_location FROM organisations WHERE id = " . $org;
        $r = mysqli_query($con, $q);
        if ($r && $r->num_rows > 0) {
            $row = mysqli_fetch_array($r);
            $defaultLocation = $row[0];
        } else {
            die("Invalid organisation number");
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $org = intval($_POST['org']);
    if ($org <= 0)
        die("No organisation specified");
    $loc = trim($_POST['location']);
    if (strlen($loc) > 0) {
        header("Location: dailysheet.php?org=" . $org . "&location=" . urlencode($loc));
        exit();
    } else {
        $errtext = "You must enter a location";
    }
}

$dateTime = new DateTime("now", new DateTimeZone(orgTimezone($con, $org)));
$dateStr = $dateTime->format('d-M-Y');
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>New Daily Timesheet</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style><?php $inc = "./orgs/" . $org . "/heading2.css";
    include $inc; ?></style>
    <style><?php $inc = "./orgs/" . $org . "/menu1.css";
    include $inc; ?></style>
    <script>function goBack() {window.history.back()}</script>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f0f0ff;
        }
        #container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        #entry {
            background: #fff;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .page-title {
            font-size: 18px;
            font-weight: bold;
            color: #063552;
            margin: 0 0 4px 0;
        }
        .date-label {
            font-size: 14px;
            color: #555;
            margin: 0 0 20px 0;
        }
        .field-label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: #333;
            margin-bottom: 6px;
        }
        .location-input {
            width: 100%;
            font-size: 18px;
            padding: 10px 12px;
            border: 2px solid #ccc;
            border-radius: 6px;
            transition: border-color 0.2s;
        }
        .location-input:focus {
            border-color: #063552;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 0, 128, 0.1);
        }
        .submit-btn {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            color: #f26120;
            background: #063552;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 16px;
            transition: background 0.2s;
        }
        .submit-btn:hover {
            background: #052040;
        }
        .error-text {
            color: #c00;
            font-size: 13px;
            margin-top: 8px;
        }
        .back-link {
            display: inline-block;
            margin-top: 16px;
            font-size: 13px;
            color: #0000c0;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/helpers/dev_mode_banner.php' ?>
<?php $inc = "./orgs/" . $org . "/heading2.txt";
include $inc; ?>
<?php $inc = "./orgs/" . $org . "/menu1.txt";
include $inc; ?>
<div id="container">
    <div id="entry">
        <p class="page-title">New Daily Timesheet</p>
        <p class="date-label"><?php echo $dateStr; ?></p>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <label class="field-label" for="location">Enter Location:</label>
            <input id="location" class="location-input" type="text" value="<?php echo htmlspecialchars($defaultLocation); ?>" name="location" placeholder="e.g. Greytown, Masterton, etc." autofocus>
            <?php if ($errtext): ?>
                <p class="error-text"><?php echo $errtext; ?></p>
            <?php endif; ?>
            <input type="submit" class="submit-btn" value="Open Timesheet">
            <input type="hidden" name="org" value="<?php echo $org; ?>">
        </form>
        <a class="back-link" href="home">&larr; Back to Home</a>
    </div>
</div>
</body>
</html>
