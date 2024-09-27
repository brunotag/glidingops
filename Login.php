<!DOCTYPE HTML>
<html>

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>gops.wwgc.co.nz Home</title>
  <style>
    body {
      margin: 0px;
      font-family: Arial, Helvetica, sans-serif;
      background-color: #f0f0ff;
    }

    #main {
      background-color: #f0f0ff;
      padding:10px;
    }

    #heading {
      background-color: #063552;
    }

    #entry {
      background-color: #d1d1ff;
      padding: 10px;
      margin: 10px;
      float:left;
    }

    #links{
      background-color: #d1d1ff;
      padding: 10px;
      margin: 10px;
      float:left;
    }
    
    .container::before,.container::after{
      content: " ";
      display:table;
    }
    .container::after{
      clear:both
    }

    .box:last-child{
      float:none;
    }    

    @media screen and (max-width:768px){
      .box{
        float:none;
      }
    }

    table {
      border-collapse: collapse;
    }

    h1 {
      font-size: 16px;
    }

    a, a:visited, a:hover, a:active {
      color: blue;
    }

    #heading {
      width: 100%;
      height: 76px;
      overflow: hidden;
    }

    #heading-logo {
      float: left;
    }

    #heading-right {
      float: right;
    }
  </style>
</head>

<body>
  <link rel="stylesheet" href="./css/notify.css" />
  <script src="./js/notify.js"></script>
  <?php
  if (
    (isset($_GET['registered']))
    ||
    (isset($_GET['recovered']))
  ) {
  ?>
    <script>
      //TODO: replace hardcoded domain
      var options = {
        message: "<br/> Check your mail box! <br/> </br> You should have received an email from machinery.gops@wwgc.co.nz",
        color: "success",
        timeout: 10000,
      };
      notify(options);
    </script>
  <?php
  }
  ?>
  <div id='heading'>
    <div id='heading-logo'><img src='HomeLogo.jpg'></div>
    <div id='heading-right'><img src='minilogo.jpg'></div>
  </div>
  <div id='main' class="container">
    <div id='entry' class="box">
      <p>Please enter your login details</p>
      <form method='POST' action='checklogin.php'>
        <table>
          <tr>
            <td>Username:</td>
            <td><input type='text' name='user' size='30' title='Enter email address' autofocus></td>
            <td></td>
          </tr>
          <tr>
            <td>Password:</td>
            <td><input type='password' name='pcode' size='30'></td>
            <td></td>
          </tr>
          <tr>
            <td style="font-size:small"><a href='Forgotten.php'>Forgotten Password</a></td>
            <td style="text-align: right;"><input type="submit" name"Submit" value="Login"></td>
            <td></td>
          </tr>
        </table>
      </form>
      <p>Club members not registered yet, click <a href='RegisterMe'>here</a></p>
    </div>
    <div id='links' class="box">
        <p>Handy links</p>
        <table>
            <tr>
              <td>Bookings:</td>
              <td><a href='https://glidegreytown.nz/latest/#booking' target='_blank'>https://glidegreytown.nz/latest/#booking</a></td>
              <td></td>
            </tr>
            <tr>
              <td>Rosters:</td>
              <td><a href='https://docs.google.com/spreadsheets/d/1bXYn5oiQfIt6CEzK0Gc33L9HDUBd9A_HEQcDrOHxt1s/edit?usp=sharing'>Google Sheet</a></td>
              <td></td>
            </tr>
        </table>
    </div>
    <div id="twitter" class="box">
      <iframe style="width:100%;height:500px;border:0px;" src="/messages-list.php?org=1" title="Twitter feed"></iframe>
    </div>    
  </div>
</body>

</html>