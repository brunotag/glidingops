<?php session_start(); ?>
<?php
$org=0;
if(isset($_SESSION['org'])) $org=$_SESSION['org'];
if(isset($_SESSION['security'])){
 if (!($_SESSION['security'] & 4)) {die("Secruity level too low for this page");}
}else{
 header('Location: Login.php');
 die("Please logon");
}
?>
<!DOCTYPE HTML>
<html>
<meta name="viewport" content="width=device-width">
<meta name="viewport" content="initial-scale=1.0">
<head>
<link rel="stylesheet" type="text/css" href="styletable1.css">
<script>
  function goBack() {window.history.back()}
  function deleteMessage(_id) {
    const userConfirmed = confirm("Are you sure you want to delete message " + _id + "?");
    if (!userConfirmed)  
      return;
    const xhr = new XMLHttpRequest();
    xhr.open("DELETE", "message-delete.php", true);
    xhr.setRequestHeader("Content-Type", "application/json"); 
    const payload = {
      id: _id, 
    };

    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4){
        if (xhr.status === 200) {
          location.reload(); 
        }else {
          // If there's an error, parse and display the error message
          try {
            const response = JSON.parse(xhr.responseText);
            alert("Error: " + response.error); // Display the error message
          } catch (e) {
            alert("An unexpected error occurred."); // Fallback for unexpected errors
          }
        }
      } 
    };
    xhr.send(JSON.stringify(payload));
  }
</script>
</head>
<body>
<?php
include './helpers/timehelpers.php';
function dtfmt($dt)
{
	if (substr($dt,0,4) != '0000')
		return substr($dt,8,2).'/'.substr($dt,5,2).'/'.substr($dt,0,4);
	else
		return '';
}
$DEBUG=0;
$diagtext="";
$pageid=18;
$pkcol=1;
$pagesortdata = $_SESSION['pagesortdata'];
$colsort = $pagesortdata[$pageid];
if ($_SERVER["REQUEST_METHOD"] == "GET")
{
 if(isset($_GET['col']))
 {
  if($_GET['col'] != "" && $_GET['col'] != null)
  {
   $colsort = $_GET['col'];
   $pagesortdata[$pageid] = $colsort;
   $_SESSION['pagesortdata'] = $pagesortdata;
  }
 }
}
if ($colsort == 0)
 	$colsort = $pkcol;
?>
<div id="div1">
<div id="div2">
  <table><tr>
  <?php
  if (true){echo '<th ';if ($colsort == 1) echo "class='colsel'";echo ">";echo "ID";echo "</th>";}
  if (true){echo '<th ';if ($colsort == 2) echo "class='colsel'";echo ">";echo "Message Text";echo "</th>";}
  if (true){echo '<th ';if ($colsort == 3) echo "class='colsel'";echo ">";echo "Sender";echo "</th>";}
  if (true){echo '<th ';if ($colsort == 4) echo "class='colsel'";echo ">";echo "Creation Time";echo "</th>";}
  if (true){echo '<th ';if ($colsort == 5) echo "class='colsel'";echo ">";echo "";echo "</th>";}
  ?>
  </tr>
  <?php
  $con_params = require('./config/database.php'); $con_params = $con_params['gliding']; 
  $con=mysqli_connect($con_params['hostname'],$con_params['username'],$con_params['password'],$con_params['dbname']);
  if (mysqli_connect_errno())
  {
  echo "<p>Unable to connect to database</p>";
  }

  $sql= <<<SQL
  SELECT mx.id, msg, m.displayname as sender, mx.create_time
  FROM messages mx INNER JOIN members m on mx.txt_sender_member_id = m.id
  WHERE is_broadcast = 1
  ORDER BY id DESC
  LIMIT 200
  SQL;
  $r = mysqli_query($con,$sql);
  $rownum = 0;
  while ($row = mysqli_fetch_array($r) )
  {
    $rownum = $rownum + 1;
    echo "<tr class='";if (($rownum % 2) == 0)echo "even";else echo "odd";  echo "'>";
    echo "<td>";echo $row[0];echo "</td>";
    echo "<td>";echo $row[1];echo "</td>";
    echo "<td>";echo $row[2];echo "</td>";
    echo "<td>";if ($row[3]!=0){$txt_timestamp_create_d=new DateTime($row[3]); echo timeLocalFormat($txt_timestamp_create_d,$_SESSION['timezone'],'d/m/Y H:i:s');}echo "</td>";
    echo "<td>";
    echo "<td>";
    echo "<button onclick='deleteMessage(";echo $row[0];echo ")'>Delete</button>";
    echo "</td>";
    echo "</tr>";
  }
  ?>
</table>
</div>
</div>
</body>
</html>
