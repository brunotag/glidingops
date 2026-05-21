<?php
require_once __DIR__ . '/../helpers/logging.php';
$msg = $_POST['msg'] ?? $_GET['msg'] ?? 'empty';
logMsg("JS-DEBUG: " . $msg);
header('Content-Type: text/plain');
echo "ok";
