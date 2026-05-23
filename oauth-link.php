<?php session_start(); ?>
<!DOCTYPE HTML>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>gops.wwgc.co.nz - Link Account</title>
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>
  <style>
    body { margin:0; font-family:Arial,Helvetica,sans-serif; background-color:#f0f0ff; }
    #heading { background-color:#063552; width:100%; height:76px; overflow:hidden; }
    #heading-logo { float:left; }
    #heading-right { float:right; }
    .card {
      background:#fff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);
      padding:30px; margin-top:20px; margin-bottom:20px;
    }
    .card h3 { margin-top:0; margin-bottom:20px; color:#063552; }
    .alert { margin-bottom:15px; }
    .provider-badge {
      display:inline-block; padding:4px 12px; border-radius:4px;
      font-size:13px; font-weight:bold; color:#fff; margin-bottom:15px;
    }
    .provider-badge.google { background:#4285F4; }
    .provider-badge.facebook { background:#1877F2; }
    .info-text { font-size:14px; color:#555; margin-bottom:20px; }
  </style>
</head>
<body>
  <div id='heading'>
    <div id='heading-logo'><img src='HomeLogo.jpg'></div>
    <div id='heading-right'><img src='minilogo.jpg'></div>
  </div>

  <div class="container">
    <div class="row">
      <div class="col-md-6 col-md-offset-3">
        <div class="card">
          <h3>Link Your Account</h3>

          <?php
          $email = $_SESSION['oauth_pending_email'] ?? '';
          $provider = $_SESSION['oauth_pending_provider'] ?? '';
          $provider_id = $_SESSION['oauth_pending_provider_id'] ?? '';

          if (empty($email) || empty($provider)) {
              echo '<div class="alert alert-warning">No pending account link request. <a href="Login.php">Back to login</a></div>';
              echo '</div></div></div></div></body></html>';
              exit;
          }

          $link_error = $_GET['error'] ?? '';
          $link_success = $_GET['success'] ?? '';
          if ($link_error === 'wrong_password') {
              echo '<div class="alert alert-danger">Incorrect password. Please try again.</div>';
          } elseif ($link_error === 'no_user') {
              echo '<div class="alert alert-danger">No account found with that username. Please check your email address or register first.</div>';
          } elseif ($link_error === 'db_error') {
              echo '<div class="alert alert-danger">A database error occurred. Please try again.</div>';
          } elseif ($link_success === 'linked') {
              echo '<div class="alert alert-success">Account linked successfully! Redirecting...</div>';
              echo '<script>setTimeout(function(){ window.location.href="home"; }, 2000);</script>';
              echo '</div></div></div></div></body></html>';
              exit;
          }
          ?>

          <p class="info-text">
            You signed in with <strong class="provider-badge <?php echo $provider; ?>"><?php echo ucfirst($provider); ?></strong>
            using the email <strong><?php echo htmlspecialchars($email); ?></strong>.
          </p>

          <p class="info-text">
            No existing account was found with the email <strong><?php echo htmlspecialchars($email); ?></strong>. You can link it to your existing account below.
          </p>

          <div class="panel panel-default">
            <div class="panel-heading"><strong>Option 1:</strong> Link to an existing account</div>
            <div class="panel-body">
              <p>If you already have a Gliding Ops account with a different email, enter your credentials below to link this social login.</p>
              <form method="POST" action="oauth-link-action.php">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="provider" value="<?php echo htmlspecialchars($provider); ?>">
                <input type="hidden" name="provider_id" value="<?php echo htmlspecialchars($provider_id); ?>">
                <div class="form-group">
                  <label for="existing_user">Your existing username (email)</label>
                  <input type="text" class="form-control" name="existing_user" id="existing_user" placeholder="Enter your existing username" required>
                </div>
                <div class="form-group">
                  <label for="existing_password">Your existing password</label>
                  <input type="password" class="form-control" name="existing_password" id="existing_password" placeholder="Enter your existing password" required>
                </div>
                <button type="submit" class="btn btn-primary">Link Accounts</button>
              </form>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="panel-heading"><strong>Option 2:</strong> Register a new account</div>
            <div class="panel-body">
              <p>If you are a new member, go back to the login page and use the <strong>Use Email Link or Register</strong> tab to create your account. Then you can link this social login from your profile settings.</p>
              <a href="Login.php" class="btn btn-default">Back to Login</a>
              <button type="button" class="btn btn-link" id="skip-link">I'll do this later</button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <script>
  $('#skip-link').click(function() {
    window.location.href = 'Login.php';
  });
  </script>
</body>
</html>
