<!DOCTYPE HTML>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>gops.wwgc.co.nz - Login</title>
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <link rel="stylesheet" href="./css/notify.css" />
  <script src="./js/notify.js"></script>
  <style>
    body {
      margin: 0;
      font-family: Arial, Helvetica, sans-serif;
      background-color: #f0f0ff;
    }
    #heading {
      background-color: #063552;
      width: 100%;
      height: 76px;
      overflow: hidden;
    }
    #heading-logo { float: left; }
    #heading-right { float: right; }
    .login-card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      padding: 30px;
      margin-top: 20px;
      margin-bottom: 20px;
    }
    .login-card h3 {
      margin-top: 0;
      margin-bottom: 20px;
      color: #063552;
    }
    .alert-container { margin-bottom: 15px; }
    .alert-container .alert { margin-bottom: 10px; }
    .sidebar-card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      padding: 20px;
      margin-top: 20px;
      margin-bottom: 20px;
    }
    .sidebar-card h4 {
      margin-top: 0;
      color: #063552;
    }
    .nav-tabs {
      border-bottom: none;
    }
    .nav-tabs > li {
      margin-bottom: 0;
    }
    .nav-tabs > li > a {
      margin-right: 0;
      border: 1px solid transparent;
      border-bottom: none;
      border-radius: 6px 6px 0 0;
      color: #063552;
      background: #e8e8f0;
    }
    .nav-tabs > li.active > a,
    .nav-tabs > li.active > a:hover,
    .nav-tabs > li.active > a:focus {
      border: 1px solid #ddd;
      border-bottom: 1px solid #f8f8fa;
      background: #f8f8fa;
      color: #063552;
      font-weight: bold;
    }
    .nav-tabs > li > a:hover {
      background: #dcdce8;
      border-color: transparent;
    }
    .tab-content {
      border: 1px solid #ddd;
      border-top: none;
      border-radius: 0 4px 4px 4px;
      padding: 20px;
      margin-top: -1px;
      background: #f8f8fa;
    }
    .form-group label {
      font-weight: normal;
      color: #555;
    }
    .twitter-frame {
      border: 0;
      width: 100%;
      height: 500px;
    }
    #magic-link-form .alert,
    #password-form .alert {
      display: none;
    }
    .invalid-feedback {
      display: none;
      color: #a94442;
      font-size: 12px;
      margin-top: 5px;
    }
    .has-error .invalid-feedback { display: block; }

    .social-login-section {
      margin-top: 25px;
      padding-top: 20px;
      border-top: 1px solid #e0e0e0;
    }
    .social-divider {
      text-align: center;
      font-size: 13px;
      color: #999;
      margin-bottom: 15px;
      position: relative;
    }
    .social-divider span {
      background: #f8f8fa;
      padding: 0 12px;
      position: relative;
      z-index: 1;
    }
    .social-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      justify-content: center;
    }
    .btn-oauth {
      display: inline-flex;
      align-items: center;
      padding: 8px 18px;
      border-radius: 4px;
      font-size: 14px;
      font-family: Arial, Helvetica, sans-serif;
      text-decoration: none;
      border: 1px solid #ddd;
      transition: opacity 0.15s;
    }
    .btn-oauth:hover { opacity: 0.88; text-decoration: none; }
    .btn-google { background: #fff; color: #444; }
    .btn-google:hover { background: #f5f5f5; color: #444; }
    .btn-facebook { background: #1877F2; color: #fff; border-color: #1877F2; }
    .btn-facebook:hover { background: #166fe5; color: #fff; }
    .oauth-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 22px;
      height: 22px;
      margin-right: 8px;
      font-weight: bold;
      font-size: 16px;
      border-radius: 50%;
    }

  </style>
</head>
<body>
  <div id='heading'>
    <div id='heading-logo'><img src='HomeLogo.jpg'></div>
    <div id='heading-right'><img src='minilogo.jpg'></div>
  </div>

  <div class="container">
    <?php
    $errorMsg = '';
    $errorType = isset($_GET['error']) ? $_GET['error'] : '';
    if ($errorType === 'invalid_link') $errorMsg = 'Invalid login link. Please request a new one.';
    elseif ($errorType === 'link_used') $errorMsg = 'This link has already been used. Please request a new one.';
    elseif ($errorType === 'link_expired') $errorMsg = 'This link has expired. Please request a new one.';
    elseif ($errorType === 'server_error') $errorMsg = 'A server error occurred. Please try again.';
    elseif ($errorType === 'oauth_state_mismatch') $errorMsg = 'Security check failed. Please try signing in again.';
    elseif ($errorType === 'oauth_token_exchange') $errorMsg = 'Could not complete sign-in with the provider. Please try again.';
    elseif ($errorType === 'oauth_email_not_found') $errorMsg = 'No account found with the email from your social login. Use the Email Link tab below to register, or try a different sign-in method.';
    elseif ($errorType === 'oauth_provider_error') $errorMsg = 'The social sign-in provider returned an error. Please try again.';
    elseif ($errorType === 'oauth_not_configured') $errorMsg = 'This sign-in method is not yet configured. Please use another method.';
    $showMagicLinkTab = $errorType !== '';
    ?>
    <div class="row">
      <div class="col-md-7">
        <div class="login-card">
          <h3>Sign in to Gliding Ops</h3>

          <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
          <?php endif; ?>

          <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="<?php echo $showMagicLinkTab ? '' : 'active'; ?>">
              <a href="#password-tab" role="tab" data-toggle="tab">Use Password</a>
            </li>
            <li role="presentation" class="<?php echo $showMagicLinkTab ? 'active' : ''; ?>">
              <a href="#magiclink-tab" role="tab" data-toggle="tab">Use Email Link or Register</a>
            </li>
          </ul>

          <div class="tab-content">
            <!-- Password Tab -->
            <div role="tabpanel" class="tab-pane <?php echo $showMagicLinkTab ? '' : 'active'; ?>" id="password-tab">
              <form method='POST' action='checklogin.php'>
                <div class="form-group">
                  <label for="user">Username</label>
                  <input type='text' class="form-control" name='user' id="user" placeholder="Enter username" autofocus>
                </div>
                <div class="form-group">
                  <label for="pcode">Password</label>
                  <input type='password' class="form-control" name='pcode' id="pcode" placeholder="Password">
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
                <a href="#" id="switch-to-magiclink" style="margin-left:15px;font-size:small;">Forgot password? Use email link instead</a>
              </form>
            </div>

            <!-- Magic Link Tab -->
            <div role="tabpanel" class="tab-pane <?php echo $showMagicLinkTab ? 'active' : ''; ?>" id="magiclink-tab">
              <p style="font-size:13px;color:#666;margin-bottom:15px;">
                If you are a new member , enter the email address you used when joining the club: an account will be created and you will be prompted to set a password on first login. 
                <br/><br/>
                If you already have an account, enter your email address and a login link will be sent.
              </p>
              <form id="magic-link-form">
                <div class="form-group">
                  <label for="magic-email">Email address</label>
                  <input type='text' class="form-control" name='email' id="magic-email" placeholder="Enter your email address">
                  <div class="invalid-feedback">Please enter your email address</div>
                </div>
                <button type="submit" class="btn btn-primary" id="send-link-btn">Send Login Link</button>
                <div id="magic-link-spinner" style="display:none;margin-left:10px;display:none;">
                  <span class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span> Sending...
                </div>
              </form>
              <div id="magic-link-alert" class="alert alert-success" style="display:none;margin-top:15px;">
                If an account exists for this email, a login link has been sent. Check your inbox (and spam folder).
              </div>
              <div id="magic-link-error" class="alert alert-danger" style="display:none;margin-top:15px;">
                Something went wrong. Please try again.
              </div>
            </div>
          </div>

          <!-- Social Login Buttons -->
          <div class="social-login-section">
            <p class="social-divider"><span>or sign in with</span></p>
            <div class="social-buttons">
              <a href="oauth-login?provider=google" class="btn btn-oauth btn-google">
                <span class="oauth-icon">G</span> Sign in with Google
              </a>
              <a href="oauth-login?provider=facebook" class="btn btn-oauth btn-facebook">
                <span class="oauth-icon">f</span> Sign in with Facebook
              </a>

            </div>
          </div>

        </div>
      </div>

      <div class="col-md-5">
        <div class="sidebar-card">
          <h4>Handy Links</h4>
          <table class="table table-condensed">
            <tr><td>Bookings:</td><td><a href='https://glidegreytown.nz/latest/#booking' target='_blank'>glidegreytown.nz</a></td></tr>
            <tr><td>Rosters:</td><td><a href='https://docs.google.com/spreadsheets/d/1bXYn5oiQfIt6CEzK0Gc33L9HDUBd9A_HEQcDrOHxt1s/edit?usp=sharing' target='_blank'>Google Sheet</a></td></tr>
          </table>
        </div>

        <div class="sidebar-card">
          <h4>
            Latest Updates
            <small><a href="#" id="toggle-twitter" style="font-size:12px;">hide</a></small>
          </h4>
          <iframe id="twitter-frame" class="twitter-frame" src="/messages-list.php?org=1" title="Updates feed"></iframe>
        </div>
      </div>
    </div>
  </div>

  <style>
    @keyframes glyphicon-spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(359deg); }
    }
    .glyphicon-refresh-animate {
      animation: glyphicon-spin 1s infinite linear;
    }
  </style>

  <script>
  $(document).ready(function() {
    // Handle errors from verify redirect
    <?php if ($showMagicLinkTab): ?>
    $('#magic-email').focus();
    <?php else: ?>
    $('#user').focus();
    <?php endif; ?>

    // "Forgot password" link switches to magic link tab
    $('#switch-to-magiclink').click(function(e) {
      e.preventDefault();
      $('.nav-tabs a[href="#magiclink-tab"]').tab('show');
      $('#magic-email').val($('#user').val()).focus();
    });

    // Tab switch: focus email field
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
      var target = $(e.target).attr('href');
      if (target === '#password-tab') {
        $('#user').focus();
      } else if (target === '#magiclink-tab') {
        $('#magic-email').focus();
      }
    });

    // Magic link form submission
    $('#magic-link-form').submit(function(e) {
      e.preventDefault();

      var email = $('#magic-email').val().trim();
      if (!email || email.length < 2) {
        $('#magic-email').closest('.form-group').addClass('has-error');
        return;
      }
      $('#magic-email').closest('.form-group').removeClass('has-error');

      $('#send-link-btn').prop('disabled', true);
      $('#magic-link-spinner').show();
      $('#magic-link-alert').hide();
      $('#magic-link-error').hide();

      $.ajax({
        url: '/api/magic-link-request',
        method: 'POST',
        data: { email: email },
        dataType: 'json'
      })
      .done(function(data) {
        $('#magic-link-alert').show();
      })
      .fail(function() {
        $('#magic-link-error').show();
      })
      .always(function() {
        $('#send-link-btn').prop('disabled', false);
        $('#magic-link-spinner').hide();
      });
    });

    // Hide on keypress resets error state
    $('#magic-email').on('input', function() {
      $(this).closest('.form-group').removeClass('has-error');
    });

    // Toggle twitter feed
    $('#toggle-twitter').click(function(e) {
      e.preventDefault();
      var frame = $('#twitter-frame');
      var link = $(this);
      if (frame.is(':visible')) {
        frame.hide();
        link.text('show');
      } else {
        frame.show();
        link.text('hide');
      }
    });
  });
  </script>

  <?php
  // Backwards compat: show notification for registered/recovered flows
  if (isset($_GET['registered']) || isset($_GET['recovered'])) {
  ?>
    <script>
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
</body>
</html>
