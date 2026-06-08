<?php
$code = isset($_GET['code']) ? intval($_GET['code']) : 500;
$codes = [
    400 => ['message' => 'Bad Request', 'detail' => 'The request could not be understood by the server.'],
    403 => ['message' => 'Access Denied', 'detail' => 'You do not have permission to access this page.'],
    404 => ['message' => 'Page Not Found', 'detail' => 'The page you are looking for does not exist or has been moved.'],
    500 => ['message' => 'Server Error', 'detail' => 'Something went wrong. Please try again later.'],
    503 => ['message' => 'Service Unavailable', 'detail' => 'The site is temporarily down for maintenance. Please check back shortly.'],
];
if (!isset($codes[$code])) $code = 500;
$info = $codes[$code];
http_response_code($code);

$uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
$ref = $_SERVER['HTTP_REFERER'] ?? 'none';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
error_log("[ERROR:$code] URI=$uri Referer=$ref UA=$ua");
?>
<!DOCTYPE HTML>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $code; ?> - <?php echo $info['message']; ?> - GOPS</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: Arial, Helvetica, sans-serif;
  background-color: #063552;
  color: #333;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
.header {
  background-color: #063552;
  width: 100%;
  height: 76px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  flex-shrink: 0;
}
.brand-text {
  font-size: 60px;
  font-weight: 900;
  font-family: 'Arial Narrow', Arial, sans-serif;
  letter-spacing: -2px;
  line-height: 1;
}
.brand-text .wwgc { color: #f26120; }
.brand-text .gops { color: #fff; font-weight: 300; letter-spacing: 0; margin-left: 6px; }
@media (max-width: 480px) {
  .brand-text { font-size: 36px; letter-spacing: -1px; }
}
.main {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 30px 16px;
}
.card {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.2);
  padding: 40px;
  max-width: 500px;
  width: 100%;
  text-align: center;
}
.code {
  font-size: 80px;
  font-weight: 900;
  color: #f26120;
  line-height: 1;
  margin-bottom: 8px;
}
.message {
  font-size: 22px;
  font-weight: 700;
  color: #063552;
  margin-bottom: 12px;
}
.detail {
  font-size: 15px;
  color: #666;
  line-height: 1.5;
  margin-bottom: 28px;
}
.btn-home {
  display: inline-block;
  padding: 10px 28px;
  background-color: #063552;
  color: #f26120;
  font-weight: 700;
  font-size: 15px;
  text-decoration: none;
  border-radius: 6px;
  transition: background-color 0.15s;
}
.btn-home:hover { background-color: #0a4a70; }
</style>
</head>
<body>
<div class="header">
  <span class="brand-text"><span class="wwgc">WWGC</span><span class="gops">GOPS</span></span>
</div>
<div class="main">
  <div class="card">
    <div class="code"><?php echo $code; ?></div>
    <div class="message"><?php echo $info['message']; ?></div>
    <div class="detail"><?php echo $info['detail']; ?></div>
    <a href="/home" class="btn-home">Back to Home</a>
  </div>
</div>
</body>
</html>
