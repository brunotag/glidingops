<?php
// Handle Facebook signed_request callback (POST from Facebook)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signed_request'])) {
    $signed_request = $_POST['signed_request'];
    $parts = explode('.', $signed_request, 2);
    if (count($parts) === 2) {
        $data = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    } else {
        $data = [];
    }
    $fb_user_id = isset($data['user_id']) ? $data['user_id'] : 'unknown';
    $confirmation_code = md5($fb_user_id . time());

    // Log for manual processing
    error_log("Facebook data deletion request for user_id: $fb_user_id, code: $confirmation_code");

    header('Content-Type: application/json');
    echo json_encode([
        'url' => 'https://gops.wwgc.co.nz/data-deletion?confirmed=1&code=' . $confirmation_code,
        'confirmation_code' => $confirmation_code,
    ]);
    exit;
}

// Handle user-initiated GET request
$confirmed = isset($_GET['confirmed']);
$code = isset($_GET['code']) ? htmlspecialchars($_GET['code']) : '';
?>
<!DOCTYPE HTML>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>gops.wwgc.co.nz - Data Deletion</title>
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
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
    .content-card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      padding: 30px;
      margin-top: 20px;
      margin-bottom: 20px;
    }
    h2 {
      color: #063552;
      border-bottom: 2px solid #f26120;
      padding-bottom: 8px;
    }
    footer {
      text-align: center;
      padding: 16px;
      color: #888;
      font-size: 13px;
    }
    @media (max-width: 768px) {
      .content-card { padding: 16px; }
    }
  </style>
</head>
<body>

<div id="heading">
  <div id="heading-logo">
    <a href="/"><img src="minilogo.jpg" alt="Logo" style="height:76px;"></a>
  </div>
  <div id="heading-right" style="color:#f26120; padding:26px 20px 0 0; font-size:18px;">
    Gliding Operations
  </div>
</div>

<div class="container">
  <div class="row">
    <div class="col-md-6 col-md-offset-3">
      <div class="content-card">
        <h2>Data Deletion</h2>

        <?php if ($confirmed): ?>
          <div class="alert alert-success">
            <strong>Deletion request received.</strong> Your reference code is: <code><?php echo $code; ?></code>
            <br><br>
            An administrator will process your request. Flight records may be retained for aviation compliance purposes.
          </div>
          <p><a href="/" class="btn btn-default">Return to Home</a></p>
        <?php else: ?>
          <p>To request deletion of your account and personal data, please email the club operations manager with your name and the email address associated with your account.</p>
          <p>Alternatively, log in and use the <strong>Edit My Details</strong> page to update or remove your information.</p>
          <hr>
          <p class="text-muted"><small>This page also serves as the callback URL required by Facebook's Data Deletion Request API.</small></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<footer>
  Wellington Gliding Club &mdash; <a href="/">Home</a> &mdash; <a href="/privacy">Privacy Policy</a> &mdash; <a href="/data-deletion">Data Deletion</a>
</footer>

</body>
</html>
