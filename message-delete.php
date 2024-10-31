<?php session_start(); ?>
<?php
$org=0;
if(isset($_SESSION['org'])) $org=$_SESSION['org'];
if(isset($_SESSION['security'])){
 if (!($_SESSION['security'] & 4)){die("Secruity level too low for this page");}
}else{
 header('Location: Login.php');
 die("Please logon");
}
?>
<?php
if ($_SERVER["REQUEST_METHOD"] == "GET")
{
  header("HTTP/1.1 418 I'm a teapot");
}
else if ($_SERVER["REQUEST_METHOD"] == "DELETE")
{
  $payload = json_decode(file_get_contents("php://input"), true);
  if ($payload === null) {
    http_response_code(400); 
    echo json_encode(["error" => "Invalid JSON payload"]);
    exit;
  }

  $con_params = require('./config/database.php'); $con_params = $con_params['gliding']; 
  $con=mysqli_connect($con_params['hostname'],$con_params['username'],$con_params['password'],$con_params['dbname']);
   if (mysqli_connect_errno())
   {
    http_response_code(500); 
    echo json_encode(["error" => "Failed to connect to Database"]);
   }
   else
   {
     $Q = "";
     $id = $payload['id'];
     $Q="DELETE FROM texts WHERE txt_msg_id = " . $id ;
     if(!mysqli_query($con,$Q) )
     {
      http_response_code(500); 
      echo json_encode(["error" => "Error trying to delete. " . mysqli_error($con)]);
     }
     $Q="DELETE FROM messages WHERE id = " . $id ;
     if(!mysqli_query($con,$Q) )
     {
      http_response_code(500); 
      echo json_encode(["error" => "Error trying to delete. " . mysqli_error($con)]);
     }
     mysqli_close($con);     
   }
}
?>
