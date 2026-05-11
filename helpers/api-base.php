<?php
ob_start();

require_once __DIR__ . '/../helpers/logging.php';

function apiMaybeResumeSession() {
    if (isset($_SERVER['HTTP_X_SESSION_ID']) && strlen($_SERVER['HTTP_X_SESSION_ID']) > 10) {
        session_id($_SERVER['HTTP_X_SESSION_ID']);
    }
    session_start();

    if (isset($_SERVER['HTTP_X_MEMBER_ID']) && isset($_SERVER['HTTP_X_ORG'])) {
        $_SESSION['memberid'] = intval($_SERVER['HTTP_X_MEMBER_ID']);
        $_SESSION['org'] = intval($_SERVER['HTTP_X_ORG']);
    }
}

function apiRequireAuth() {
    if (!isset($_SESSION['memberid'])) {
        apiExitWithError('Not logged in');
    }
}

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

function apiExitWithError($message, $con = null) {
    if ($con && $con instanceof mysqli) mysqli_close($con);
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    ob_end_flush();
    exit;
}
