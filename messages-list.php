<!DOCTYPE html>
<html>
<body>
<style>
body {
  background: #e6ecf0;
  font-family: 'Asap', sans-serif;
  font-family: 'Roboto', sans-serif;

}
img {
  max-width:100%;
}
.avator {
  border-radius:100px;
  width:48px;
  margin-right: 15px;
}


.tweet-wrap {
  max-width:530px;
  background: #fff;
  margin: 0 auto;
  margin-top: 30px;
  border-radius:3px;
  padding: 25px;
  border-bottom: 1px solid #e6ecf0;
  border-top: 1px solid #e6ecf0;
}

.tweet-header {
  display: flex;
  align-items:flex-start;
  font-size:18px;
}
.tweet-header-info {
  font-weight:bold;
}
.tweet-header-info span {
  color:#657786;
  font-weight:normal;
  margin-left: 5px;
}
.tweet-header-info p {
  font-weight:normal;
  margin-top: 5px;
  
}
.tweet-img-wrap {
  padding-left: 60px;
}

.tweet-info-counts {
  display: flex;
  margin-left: 60px;
  margin-top: 10px;
}
.tweet-info-counts div {
  display: flex;
  margin-right: 20px;
}
.tweet-info-counts div svg {
  color:#657786;
  margin-right: 10px;
}
@media screen and (max-width:430px){
  body {
    padding-left: 20px;
    padding-right: 20px;
  }
  .tweet-header {
    flex-direction:column;
  }
  .tweet-header img {
    margin-bottom: 20px;
  }
  .tweet-header-info p {
    margin-bottom: 30px;
  }
  .tweet-img-wrap {
    padding-left: 0;
  }
  .tweet-info-counts {
  display: flex;
  margin-left: 0;
  }
  .tweet-info-counts div {
    margin-right: 10px;
  }
}
</style>

<link href="https://fonts.googleapis.com/css?family=Asap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">


<?php

if(isset($_SESSION['org'])) 
    $org=$_SESSION['org'];
else if (isset($_GET['org']))
    $org=$_GET['org'];


$con_params = require('./config/database.php');
$con_params = $con_params['gliding'];
if (mysqli_connect_errno()) {
    echo "<p>Unable to connect to database</p>";
}
$conn = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);
$query = "SELECT create_time, msg FROM gliding.messages WHERE is_broadcast AND org = ".$org." ORDER BY create_time desc LIMIT 20";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_array($result))
{
    $date = date_format(date_create($row[0]),"d M Y - H:ia");
        
    echo "<div class=\"tweet-wrap\">";
    echo "  <div class=\"tweet-header\">";
    echo "    <img src=\""."./orgs/" . $org . "/twitter_icon.jpg"."\" alt=\"\" class=\"avator\">";
    echo "    <div class=\"tweet-header-info\">";
    echo file_get_contents("./orgs/" . $org . "/twitter_name.txt");
    echo "<span> ".$date."</span>";
    echo "      <p> ".$row[1]." </p>";
          
    echo "    </div>    ";
    echo "</div>";
    echo "</div>  ";
}
$conn = null;
?>

</body>
</html>