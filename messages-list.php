<?php
$org = isset($_GET['org']) ? intval($_GET['org']) : 0;
if ($org === 0) { echo "Missing org parameter"; exit; } ?>
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
  flex-shrink:0;
  width:45px;
  height:45px;
}
.paragraph{
  clear:both;
  margin:8px 2px 0 2px;
}

.avator {
  border-radius:100px;
  width:45px;
  height:45px;
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
  position:relative;
}
.tweet-wrap:not(.seen) { border-left:4px solid #063552; background:#f4f8fc; }
.new-badge { display:inline; background:#063552; color:#f26120; font-size:9px; font-weight:bold; padding:1px 6px; border-radius:3px; margin-left:6px; text-transform:uppercase; vertical-align:middle; }
.tweet-wrap.seen .new-badge { display:none; }

.tweet-header {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  width:100%;
  min-height:40px;
  font-size:18px;
}
.tweet-header-info {
  flex:1;
  min-width:0;
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
require_once __DIR__ . '/helpers/database.php';
$conn = open_gliding_db();
$query = "SELECT id, create_time, msg FROM gliding.messages WHERE is_broadcast AND org = ".$org." ORDER BY create_time desc LIMIT 10";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_array($result))
{
    $dateobj = date_create($row[1]);
    $dateobj->setTimezone(new DateTimeZone('Pacific/Auckland'));
    $date = date_format($dateobj,"d M Y - H:ia");
        
    echo "<div class=\"tweet-wrap\" data-msg-id=\"" . intval($row[0]) . "\">";
    echo "  <div class=\"tweet-header\">";
    echo "    <div class=\"imgdiv\">";
    echo "      <img src=\""."./orgs/" . $org . "/twitter_icon.jpg"."\" alt=\"\" class=\"avator\">";
    echo "    </div>";
    echo "    <div class=\"tweet-header-info\">";
    echo "       <div class=\"titlediv\">";
    echo file_get_contents("./orgs/" . $org . "/twitter_name.txt");
    echo "       </div>";
    echo "       <div class=\"datetimediv\">";
    echo "          <span>".$date."</span><b class=\"new-badge\">NEW</b>";
    echo "       </div>";                 
    echo "    </div>";
    echo "  </div>";
    echo "    <div class=\"paragraph\">";    
    echo "       <p> ".$row[2]." </p>";
    echo "    </div>";     
    echo "</div>  ";
}
$conn = null;
?>

<script>
(function() {
  var maxSeen = parseInt(localStorage.getItem('gops_broadcast_seen_max') || '0', 10);
  var currentMax = 0;
  document.querySelectorAll('.tweet-wrap[data-msg-id]').forEach(function(el) {
    var id = parseInt(el.getAttribute('data-msg-id'), 10);
    if (id > currentMax) currentMax = id;
    if (id <= maxSeen) el.classList.add('seen');
  });
  if (currentMax > maxSeen) {
    localStorage.setItem('gops_broadcast_seen_max', currentMax.toString());
  }
})();
</script>
</body>
</html>