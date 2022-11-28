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
      float: right;
      padding: 10px;
    }

    #picy {
      float: right;
    }

    #picy2 {
      margin: 20px;
    }

    #main2 {
      margin: 10px;
      max-width: 800px;
    }

    #main3 {
      margin: 10px;
      max-width: 800px;
    }

    #footer {
      float: bottom;
      text-align: center;
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
        message: "<br/> Check your mail box! <br/> </br> You should have received an email from operations@gops.wwgc.co.nz",
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
  <div id='main'>
    <div id='entry'>
      <p>Please enter your login details</p>
      <form method='POST' action='checklogin.php'>
        <table>
          <tr>
            <td>Username:</td>
            <td><input type='text' name='user' size='20' title='Enter email address' autofocus></td>
            <td></td>
          </tr>
          <tr>
            <td>Password:</td>
            <td><input type='password' name='pcode' size='20'></td>
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
    <div id='main2'>
      <p>Welcome to glidingops.com, the place where we help manage your Gliding club's operations. Here you will find a set of web based tools that helps the operations of your club, giving you more time to do what you really want to do, fly.</p>
      <p>The glidingops.com project started with the <a href='http://www.soar.co.nz/'>Wellington Gliding Club, New Zealand</a> in 2014 when a better tool for communicating with varying groups of multiple members was required. From there, it has now grown to a rich operations and management tool for the club.</p>
      <p>The club Treasurer can now get a spreadsheet with all the members' billing information with just a few clicks whilst engineers and the CFI can get immediate access to flying hours and logs for both gliders and pilots.</p>
      <p>A large screen TV in the club house gives live coverage of the days activities, creating a central spot for both members and visitors to congregate and enjoy the activities.</p>
    </div>
    <div id="picy">
      <img src="HomePhoto.jpg"></img>
    </div>
    <div id='main3'>
      <h1>Features</h1>
      <ul>
        <li>Management of membership database</li>
        <li>Flexible communications to selected groups of members</li>
        <li>Enables electronic flight recording (with data entry possible on mobile devices)</li>
        <li>CFI, CTP, Treasurer and Engineering Reports</li>
        <li>Automated billing</li>
        <li>Individual flight summaries emailed to pilots at the end of each flying day</li>
        <li>Integrated SPOT and other tracking</li>
        <li>Enables display of flight tracking</li>
        <li>Full member portal incluidng booking system under devlopment</li>
      </ul>
      <p>If your club is interested in options to use the system, please email us at <a href='mailto:wgcoperations@gmail.com?subject=Interest%20in%20gliding%20ops'>wgcoperations@gmail.com</a></p>
    </div>
  </div>
  <div id='foot'>
    Copyright &#169; glidingops.com 2014
  </div>
</body>

</html>