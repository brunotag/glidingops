<!DOCTYPE html>
<html>
<body>
<style>


body {
  background: #F1F1EF;
  font-family: 'Asap', sans-serif;
  font-family: 'Roboto', sans-serif;

}
.imgdiv{
  float:left;
  width:25%;
  max-width:60px;
}
.paragraph{
  display: block;
  margin-left: 2px;
  margin-right: 2px;
  width:100%;
}
.paragraph p{
  margin-top:33px;
}

.avator {
  border-radius:100px;
  max-width:45px;
  margin-right: 15px;
}

.tweet-wrap {
  max-width:530px;
  background: #FFFFFF;
  margin: 0 auto;
  margin-top: 15px;
  border-radius:3px;
  padding: 15px;
  border-bottom: 1px solid #e6ecf0;
  border-top: 1px solid #e6ecf0;
  color: #203A5E;
}

.tweet-header {
  display: block;
  width:100%;
  min-height:40px;
  font-size:18px;
}
.tweet-header-info {
  float:left;
  width:75%;
  font-weight:bold;
}
.tweet-header-info span {
  color:#657786;
  font-weight:normal;
}
.tweet-header-info p {
  font-weight:normal;
  margin-top: 10px;
  
}
.tweet-img-wrap {
  padding-left: 60px;
}

@media screen and (max-width:430px){
  body {
    padding-left: 20px;
    padding-right: 20px;
  }
  .tweet-header img {
    margin-bottom: 20px;
  }
  .tweet-header-info p {
    margin-bottom: 30px;
  }
  .tweet-header {
    font-size:14px;
  }  
  .paragraph p{
    font-size: 14px;
  }
}

::-webkit-scrollbar {
  width: 10px;
}
::-webkit-scrollbar-track {
  background:  #F1F1EF;
}
::-webkit-scrollbar-thumb {
  background: #203A5E;
}
::-webkit-scrollbar-thumb:hover {
  background: #E9560D;
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
    echo "    <div class=\"imgdiv\">";
    echo "      <img src=\""."./orgs/" . $org . "/twitter_icon.jpg"."\" alt=\"\" class=\"avator\">";
    echo "    </div>";
    echo "    <div class=\"tweet-header-info\">";
    echo "       <div class=\"titlediv\">";
    echo file_get_contents("./orgs/" . $org . "/twitter_name.txt");
    echo "       </div>";
    echo "       <div class=\"datetimediv\">";
    echo "          <span>".$date."</span>";
    echo "       </div>";                 
    echo "    </div>";
    echo "  </div>";
    echo "    <div class=\"paragraph\">";    
    echo "       <p> ".$row[1]." </p>";
    echo "    </div>";     
    echo "</div>  ";
}
$conn = null;
?>

</body>
</html>