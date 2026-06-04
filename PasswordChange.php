<?php session_start(); ?>
<?php
$org = 0;
if (isset($_SESSION['org'])) $org = $_SESSION['org'];

require_once __DIR__ . '/helpers/permissions.php'; require_perm('password.change');
?>
<!DOCTYPE HTML>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Change Password - Gliding Ops</title>
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <style>
    body { margin: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f0f0ff; }
    #heading { background-color: #063552; width: 100%; height: 76px; overflow: hidden; }
    #heading-logo { float: left; }
    #heading-right { float: right; }
    .pw-card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      padding: 30px;
      margin-top: 20px;
      margin-bottom: 20px;
    }
    .pw-card h2 {
      margin-top: 0;
      margin-bottom: 20px;
      color: #063552;
    }
    .form-group label { font-weight: normal; color: #555; }
  </style>
  <style>
  <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
  <?php $inc = "./orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
  </style>
</head>
<body>
  <div id='heading'>
    <div id='heading-logo'><img src='HomeLogo.jpg'></div>
    <div id='heading-right'><img src='minilogo.jpg'></div>
  </div>

<?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>

  <div class="container">
    <div class="row">
      <div class="col-md-6 col-md-offset-3">
        <div class="pw-card">
          <h2>Change Password</h2>

<?php if (isset($_SESSION['auth_via_magic_link']) && $_SESSION['auth_via_magic_link'] == 1): ?>
          <div class="alert alert-info">
            <strong>Email login link used.</strong> Set a password below to enable password login in future.
          </div>
<?php endif; ?>

          <form method='POST' action='changepw.php'>
            <div class="form-group">
              <label>Username</label>
              <input type='text' class="form-control" value="<?php echo htmlspecialchars($_SESSION['who']); ?>" disabled>
            </div>

<?php
    $skipOldPw = (isset($_SESSION['force_pw_reset']) && $_SESSION['force_pw_reset'] == 1)
              || (isset($_SESSION['auth_via_magic_link']) && $_SESSION['auth_via_magic_link'] == 1);
    if (!$skipOldPw):
?>
            <div class="form-group">
              <label for="pcodeold">Current password</label>
              <input type='password' class="form-control" name='pcodeold' id="pcodeold" autofocus>
            </div>
<?php endif; ?>

            <div class="form-group">
              <label for="pcodenew1">New password</label>
              <input type='password' class="form-control" name='pcodenew1' id="pcodenew1" autofocus>
            </div>
            <div class="form-group">
              <label for="pcodenew2">Confirm new password</label>
              <input type='password' class="form-control" name='pcodenew2' id="pcodenew2">
            </div>
            <button type="submit" class="btn btn-primary">Change Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
