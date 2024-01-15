
<?php
include './helpers/session_helpers.php';
session_start();

$org = isset($_GET['org']) ? $_GET['org'] : (isset($_SESSION['org']) ? $org=$_SESSION['org'] : 0);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>Greytown Soaring Centre Webcams</title>
    <script>
        var numimages = 5;
        var order = [4, 2, 3, 5, 1];
        function start() {
            var divi = document.getElementById('images');
            for (var i = 0; i < numimages; i++) {
                var d = document.createElement('DIV');
                d.className = 'd1';
                var im = document.createElement('IMG');
                im.src = "orgs/<?php echo $org?>/camera/camera" + order[i] + ".jpg";
                d.appendChild(im);
                divi.appendChild(d);
            }
            setInterval(function () {location.reload()}, 60000);
        }
    </script>
    <style>
    body {font-family: Arial, Helvetica, sans-serif;font-size: 9pt;margin: 0;background-color: #000000;}
    div.d1 {text-align: center;padding: 8px;}
    @media screen and (max-width: 450px)
    {
        #container {width: 100%; margin: auto;}
        h1 {color: blue;text-align: center;font-size: 12pt}
    }
    @media screen and (max-width: 1200px) and (min-width: 451px)
    {
        #container {width: 100%; margin: auto;}
        h1 {color: blue;text-align: center;font-size: 12pt}
    }
    @media screen and (min-width: 1301px)
    {
        #container {width: 1300px; margin: auto;}
        h1 {color: blue;text-align: center;}
    } 
    </style>
</head>
<body onload='start()'>
    <div id = 'container'>
        <div id='heading'>
            <h1>LATEST WEB CAMS</h1>
        </div>
        <div id='images'>      
        </div>
    </div>
</body>
</html>