<?php
ob_start();

require_once __DIR__ . '/../helpers/logging.php';

function apiErrorHandler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;

    $msg = "$errstr in $errfile:$errline";

    if (isLocal()) {
        $logFile = getLogDir() . '/error.log';
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " [PHP ERROR] $msg\n", FILE_APPEND);
    }

    error_log("PHP ERROR: $msg");
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $errstr]);
    exit;
}
set_error_handler('apiErrorHandler');

function apiExit($con = null) {
    if ($con && $con instanceof mysqli) mysqli_close($con);
    ob_end_flush();
    exit;
}
