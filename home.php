<?php session_start(); ?>
<?php $org = 0;
if (isset($_SESSION['org'])) $org = $_SESSION['org']; ?>
<?php
include './helpers/timehelpers.php';
if (isset($_SESSION['security'])) {
   if (!($_SESSION['security'] & 1)) {
      die("Secruity level too low for this page");
   }
} else {
    header('Location: /Login.php');
   die("Please logon");
}

$effectiveSecurity = $_SESSION['security'];
$asOverride = 0;
if (isset($_GET['as']) && ($effectiveSecurity & 128)) {
    $v = intval($_GET['as']);
    if ($v >= 0 && $v <= 255) {
        $effectiveSecurity = $v;
        $asOverride = $v;
    }
}
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width">
<meta name="viewport" content="initial-scale=1.0">

<head>
<base href="/">
   <style>
      <?php $inc = "./orgs/" . $org . "/heading2.css";
      include $inc; ?>
   </style>
   <style type="text/css">
      body {
         margin: 0px;
         font-family: Arial, Helvetica, sans-serif;
      }

      #container {
         margin: 5px;
         border: 0px;
      }

      #menu1 {
         margin: 5px;
         border: 0px;
         background-color: #e0e0e0;
         padding: 1px;
         border-radius: 5px;
      }

      #menu2 {
         margin: 5px;
         border: 0px;
         background-color: #e0e0e0;
      }

      #menu2 td {
         vertical-align: top;
      }

      a {
         text-decoration: none;
         border-left: 5px;
      }

      a:link {
         color: #000000;
      }

      a:visited {
         color: #000000;
      }

      a:hover {
         color: #0000FF;
      }

      p.u {
         font-size: 12px;
         margin-top: 0px;
         margin-left: 0px;
         margin-bottom: 0px;
      }

      p.u2 {
         font-size: 12px;
         margin-top: 0px;
         margin-left: 20px;
         margin-bottom: 0px;
      }

      p.p2 {
         font-size: 12px;
         margin: 0;
         font-weight: bold;
      }

      p.p3 {
         font-size: 12px;
         margin: 0;
         font-weight: bold;
      }

      p.p4 {
         font-size: 12px;
         margin-left: 10px;
         margin-top: 0px;
         margin-bottom: 0px;
      }

      h1 {
         font-size: 14px;
         color: #0000e0
      }

      h2.u {
         font-size: 14px;
      }

      h3.u {
         font-size: 12px;
         margin-left: 10px;
      }

      table {
         border-collapse: collapse;
      }

      table.tbl1 {
         width: 100%;
         table-layout: fixed;
      }

      .s1 {
         font-weight: bold;
         color: #000080
      }
   </style>
</head>

<body>
   <?php include __DIR__ . '/helpers/dev_mode_banner.php' ?>
   <?php if ($asOverride): ?>
      <div style="background:#fff3cd;color:#856404;text-align:center;padding:6px;font-size:13px;font-weight:bold;">
         Viewing as security level <?php echo $asOverride; ?>
         &mdash; <a href="home" style="color:#856404;text-decoration:underline;">Clear override</a>
      </div>
   <?php endif; ?>
   <?php $inc = "./orgs/" . $org . "/heading2.txt";
   include $inc; ?>
    <div id='container'>
              <?php
             $con_params = require('./config/database.php');
             $con_params = $con_params['gliding'];
             $con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);
             if (!mysqli_connect_errno()) {
                $rownum = 0;
                $dateTime = new DateTime('now');
                $dateStr = $dateTime->format('Y-m-d');
                $strTZ = orgTimezone($con, $org);
                if (isset($_SESSION['memberid'])) {
                  $q = "SELECT localdate, a.name from duty LEFT JOIN dutytypes a ON a.id = duty.type where duty.org = " . $org . " and member = " . $_SESSION['memberid'] . " and localdate >= '" . $dateStr . "' order by localdate asc";
                  $r = mysqli_query($con, $q);
                  while ($row = mysqli_fetch_array($r)) {
                     if ($rownum == 0) {
                        echo "<p class='p3'>YOUR NEXT ROSTERED DUTIES:</p>";
                     }
                     $rownum = $rownum + 1;
                     $dtstr = $row[0];
                     echo "<p class='p4'>" . substr($dtstr, 8, 2) . "/" . substr($dtstr, 5, 2) . "/" . substr($dtstr, 0, 4) . " " . $row[1] . "</p>";
                  }
               }
            }
             ?>
       <div id="menu1">
         <div id="menu2">
            <table class='tbl1'>
               <?php
               echo "<tr>";
               $col = 0;
               $totcol = 6;
               if (intval($_SESSION['memberid']) > 0) {
                  echo "<td><h2 class='u'>MY GOPs</h2>";
                  echo "<p class='u'><a href='MyFlights'>My Flights</a></p>";
                  echo "<p class='u'><a href='EditMyDetails'>My Details</a></p>";
                  echo "</td>";
                  $col = ($col + 1) % $totcol;
                  if ($col == 0) echo "</tr><tr>";
               }
               echo "<td><h2 class='u'><a href='FlyingNow?org=" . $org . "'>FLYING NOW</a></h2>";
                if ($org == 1) {
                   echo "<p class='u'><a href='/wgc'>Real Time map</a></p>";
                   echo "<p class='u'><a href='/wgc-new'>Real Time map (new)</a></p>";
                }
                if ($org == 2)
                   echo "<p class='u'><a href='/ssb'>Real Time map</a></p>";
                if ($org == 3)
                   echo "<p class='u'><a href='/cgc'>Real Time map</a></p>";
                if ($org == 4)
                   echo "<p class='u'><a href='/agc'>Real Time map</a></p>";
               echo "</td>";
               $col = ($col + 1) % $totcol;
               if ($col == 0) echo "</tr><tr>";
               //TODO: BOOKING - HARDCODED LINK
               echo "<td><h2 class='u'><a href='https://glidegreytown.nz/latest/#booking' target='_blank'>BOOKINGS</a></h2>";
               echo "</td>";
               $col = ($col + 1) % $totcol;
               if ($col == 0) echo "</tr><tr>";

               echo "<td><h2 class='u'><a href='https://docs.google.com/spreadsheets/d/1bXYn5oiQfIt6CEzK0Gc33L9HDUBd9A_HEQcDrOHxt1s/edit?usp=sharing'>ROSTERS</a></h2></td>";
               $col = ($col + 1) % $totcol;
               if ($col == 0) echo "</tr><tr>";

               echo "<td><h2 class='u'><a href='AllMembers'>MEMBERS</a></h2></td>";
               $col = ($col + 1) % $totcol;
               if ($col == 0) echo "</tr><tr>";

               echo "<td><h2 class='u'></h2></td>";
               $col = ($col + 1) % $totcol;
               if ($col == 0) echo "</tr><tr>";

                if (($effectiveSecurity & 1)) {
                    echo "<td><h2 class='u'>MESSAGING</h2>";
                   echo "<p class='u'><a href='MessagingPage'>Broadcast A Message</a></p>";
                   if (($effectiveSecurity & 4)) {
                       echo "<p class='u'><a href='/MessagesTree'>See Past Messages</a></p>";
                   }
                   echo "</td>";
                  $col = ($col + 1) % $totcol;
                  if ($col == 0) echo "</tr><tr>";
               }

               if ($effectiveSecurity >= 1) {
                   echo "<td><h2 class='u'>DAILY OPS</h2>";
                   if ($effectiveSecurity >= 4)
                      echo "<p class='u'><a href='StartDay.php?org=" . $org . "'>New Daily Timesheet</a></p>";
                   if ($effectiveSecurity >= 4)
                      echo "<p class='u'><a href='/EditDailySheet?org=" . $org . "'>Edit Daily Timesheet</a></p>";
                   echo "<p class='u'><a href='DailyLogSheet.php?org=" . $org . "'>View Daily Timesheet</a></p>";

                   echo "</td>";
                  $col = ($col + 1) % $totcol;
                  if ($col == 0) echo "</tr><tr>";
               }

               if ($effectiveSecurity >= 1) {
                  echo "<td><h2 class='u'>REPORTS</h2>";
if (($effectiveSecurity & 8))
                     echo "<p class='u'><a href='Treasurer.php'>Treasurer Report</a></p>";
                   if (($effectiveSecurity & 8))
                      echo "<p class='u'><a href='TreasurerReportNew'>Treasurer Report - Option 1 (Grouped by Member)</a></p>";
                   if (($effectiveSecurity & 8))
                      echo "<p class='u'><a href='TreasurerReportNew2'>Treasurer Report - Option 2 (Table with Headers)</a></p>";
                   if (($effectiveSecurity & 8))
                      echo "<p class='u'><a href='TreasurerReportNew3'>Treasurer Report - Option 3 (Flat)</a></p>";
                   if (($_SESSION['security'] & 1))
                      echo "<p class='u'><a href='/app/allFlightsReport'>All Flights Report</a></p>";
                   if (($_SESSION['security'] & 1))
                     echo "<p class='u'><a href='/AllFlightsReportNew'>All Flights Report (New)</a></p>";
                  if (($effectiveSecurity & 24))
                     echo "<p class='u'><a href='/app/reports/membersRolesStatsReport'>Members roles Report</a></p>";
                  if (($effectiveSecurity & 32))
                     echo "<p class='u'><a href='Engineer.php'>Engineer Report</a></p>";
                  if (($effectiveSecurity & 32))
                     echo "<p class='u'><a href='last-flights-list.php?col=1&descsort=1'>Currency Report</a></p>";
                  echo "</td>";
                  $col = ($col + 1) % $totcol;
                  if ($col == 0) echo "</tr><tr>";
               }

               if (($effectiveSecurity & 120)) {
                  echo "<td><h2 class='u'>DATA MAINTENANCE</h2>";
                  if (($effectiveSecurity & 104))
                     echo "<p class='u'><a href='AllAircraft'>Aircraft</a></p>";
                  if (($effectiveSecurity & 64))
                     echo "<p class='u'><a href='AircraftTypes'>Aircraft Types</a></p>";
                  if (($effectiveSecurity & 128))
                     echo "<p class='u'><a href='BillingOptions'>Charging Options</a></p>";
                  if (($effectiveSecurity & 64))
                     echo "<p class='u'><a href='DutyTypes'>Duty Types</a></p>";
                  if (($effectiveSecurity & 64))
                     echo "<p class='u'><a href='flights-list.php'>Flights Raw</a></p>";
                  if (($effectiveSecurity & 72))
                     echo "<p class='u'><a href='IncentiveSchemes'>Incentive Schemes</a></p>";
                  if (($effectiveSecurity & 64))
                     echo "<p class='u'><a href='membership_class-list.php'>Membership Classes</a></p>";
                  if (($effectiveSecurity & 64))
                     echo "<p class='u'><a href='membership_status-list.php'>Membership Statuses</a></p>";
                  if (($effectiveSecurity & 72))
                     echo "<p class='u'><a href='OtherCharges'>Other Charges</a></p>";
                  if (($effectiveSecurity & 64))
                     echo "<p class='u'><a href='Roles'>Roles</a></p>";
                  if (($effectiveSecurity & 64))
                     echo "<p class='u'><a href='AssignRoles'>Role Assigment</a></p>";
                  if (($effectiveSecurity & 64))
                     echo "<p class='u'><a href='spots-list.php'>Spots</a></p>";
                  if (($effectiveSecurity & 72))
                     echo "<p class='u'><a href='SubsToSchemes'>Subs to Incentives</a></p>";
                  if (($effectiveSecurity & 72))
                     echo "<p class='u'><a href='TowCharges'>Tow Charging</a></p>";
                  if (($effectiveSecurity & 64))
                     echo "<p class='u'><a href='maintenance/duplicates_index.php'>Manage duplicate memberships</a></p>";
                   if (($effectiveSecurity & 64))
                      echo "<p class='u'><a href='manage-secret-code.php'>Manage secret code</a></p>";

                   echo "</td>";
                  $col = ($col + 1) % $totcol;
                  if ($col == 0) echo "</tr><tr>";
               }

if (($effectiveSecurity & 64)) {
                   echo "<td><h2 class='u'>USERS</h2>";
                   echo "<p class='u'><a href='/Users'>Create User</a></p>";
                   echo "<p class='u'><a href='/UsersList'>View users</a></p>";

                   echo "</td>";
                   $col = ($col + 1) % $totcol;
                   if ($col == 0) echo "</tr><tr>";
                }

                if (($effectiveSecurity & 64)) {
                   echo "<td><h2 class='u'>DIAGNOSTICS & RECOVERY</h2>";
                   echo "<p class='u'><a href='Recovery.php'>Get Local Browser Cache</a></p>";
                    echo "<p class='u'><a href='maintenance/testemail.php'>Test Email</a></p>";
                    echo "<p class='u'><a href='/SentMessages'>All Messages</a></p>";
                    if (($_SESSION['security'] & 128))
                       echo "<p class='u'><a href='/ViewAs'>View Homepage As...</a></p>";

                   echo "</td>";
                  $col = ($col + 1) % $totcol;
                  if ($col == 0) echo "</tr><tr>";
               }

               if (($effectiveSecurity & 128)) {
                  echo "<td><h2 class='u'>SUPER ADMIN</h2>";
                  echo "<p class='u'><a href='Organisations'>Organisations</a></p>";
                  echo "</td>";
                  $col = ($col + 1) % $totcol;
                  if ($col == 0) echo "</tr><tr>";
               }

               echo "</tr>";
               ?>
</table>
            <p style="text-align:right;font-size:10px;color:#888;">
               <?php
               $gitHash = trim(@exec('git -C ' . escapeshellarg(__DIR__) . ' rev-parse --short HEAD'));
               if ($gitHash) echo $gitHash;
               ?>
            </p>
             <?php
             if ($org == 1) {
                ?>
                <iframe style="width: 100%;height: 300px;border:0px;" src="/messages-list.php?org=1" title="Twitter feed"></iframe>
             <?php
                }
             ?>
         </div>
      </div>
   </div>
</body>

</html>
