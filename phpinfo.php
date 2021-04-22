<?php
  require_once "./includes/moduleEnvironment.php";
  $env = $devt_environment->getkey('APP_ENV');  
  if($env == 'development') { 
	phpinfo(INFO_ALL);
  }
?>
