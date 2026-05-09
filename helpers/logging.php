<?php
// Common utility functions

function isLocalEnvironment() {
    static $isLocal = null;
    if ($isLocal !== null) return $isLocal;

    $isLocal = (
        php_uname('n') === 'vagrant' ||
        strpos($_SERVER['HTTP_HOST'] ?? '', '192.168') !== false ||
        strpos($_SERVER['HTTP_HOST'] ?? '', 'glidingops.test') !== false ||
        (isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['DOCUMENT_ROOT'], 'vagrant') !== false)
    );

    return $isLocal;
}

function isLocal() {
    return isLocalEnvironment();
}

function getLogDir() {
    static $logDir = null;
    if ($logDir === null) {
        $logDir = dirname(__DIR__) . '/log';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
    }
    return $logDir;
}

function logMsg($message, $logName = 'app.log') {
    if (!isLocalEnvironment()) return;

    $logFile = getLogDir() . '/' . $logName;
    $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " [" . $uri . "] " . $message . "\n", FILE_APPEND);
}

function logError($message, $logName = 'app.log') {
    if (!isLocalEnvironment()) return;

    $logFile = getLogDir() . '/' . $logName;
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $trace[1] ?? $trace[0];
    $file = basename($caller['file'] ?? '');
    $line = $caller['line'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " [" . $uri . "] ERROR [$file:$line] " . $message . "\n", FILE_APPEND);
}

// Register fatal error handler (local dev only)
if (isLocalEnvironment()) {
    function logFatalErrorHandler() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $msg = $error['message'] . ' in ' . $error['file'] . ':' . $error['line'];
            $logFile = getLogDir() . '/error.log';
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [PHP FATAL] $msg\n", FILE_APPEND);
        }
    }
    register_shutdown_function('logFatalErrorHandler');
}