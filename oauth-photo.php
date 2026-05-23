<?php session_start(); ?>
<!DOCTYPE HTML>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>gops.wwgc.co.nz - Profile Photo</title>
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
    .photo-compare { text-align:center; margin-bottom:20px; }
    .photo-box { display:inline-block; margin:10px 20px; text-align:center; vertical-align:top; }
    .photo-box img { width:200px; height:200px; border-radius:8px; object-fit:cover; border:2px solid #ddd; }
    .photo-box .label { display:block; margin-top:8px; font-weight:600; color:#555; }
    .btn-group { text-align:center; margin-top:20px; }
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
          <h3>Profile Photo</h3>

          <?php
          $photoUrl = $_SESSION['social_photo_url'] ?? '';
          $provider = $_SESSION['social_photo_provider'] ?? '';
          $memberId = $_SESSION['memberid'] ?? '';
          $isLinked = isset($_GET['linked']);

          if (empty($photoUrl) || empty($memberId)) {
              echo '<div class="alert alert-warning">No photo pending. <a href="home">Go to home</a></div>';
              echo '</div></div></div></div></body></html>';
              exit;
          }

          $action = $_POST['action'] ?? '';
          if ($action === 'replace') {
              require_once __DIR__ . '/helpers/oauth-photo-helper.php';
              saveSocialPhoto($photoUrl, $memberId);
              unset($_SESSION['social_photo_url'], $_SESSION['social_photo_provider']);
              header('Location: home');
              exit;
          }

          if ($action === 'keep') {
              unset($_SESSION['social_photo_url'], $_SESSION['social_photo_provider']);
              header('Location: home');
              exit;
          }

          $localPhoto = 'img/members/' . intval($memberId) . '.jpg';
          $providerLabel = ucfirst($provider);
          ?>

          <p class="info-text" style="font-size:14px; color:#555; margin-bottom:20px;">
            <?php if ($isLinked): ?>
            Your account has been linked to <strong><?php echo $providerLabel; ?></strong>.
            We also found a profile photo &mdash; would you like to use it?
            <?php else: ?>
            We found a profile photo from your <strong><?php echo $providerLabel; ?></strong> account.
            Would you like to use it as your Gliding Ops profile photo?
            <?php endif; ?>
          </p>

          <div class="photo-compare">
            <div class="photo-box">
              <img src="<?php echo $localPhoto; ?>" alt="Current photo"
                   onerror="this.parentElement.style.display='none'">
              <span class="label">Current Photo</span>
            </div>
            <div class="photo-box">
              <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Social photo"
                   onerror="this.parentElement.style.display='none'">
              <span class="label"><?php echo $providerLabel; ?> Photo</span>
            </div>
          </div>

          <form method="POST" class="btn-group">
            <button type="submit" name="action" value="replace" class="btn btn-primary">Use Social Photo</button>
            <button type="submit" name="action" value="keep" class="btn btn-default">Keep Current</button>
          </form>

        </div>
      </div>
    </div>
  </div>
</body>
</html>
