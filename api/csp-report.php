<?php
require_once __DIR__ . '/../helpers/logging.php';

$raw = file_get_contents('php://input');
if (empty($raw)) {
    http_response_code(204);
    exit;
}

$report = json_decode($raw, true);
if (!$report) {
    http_response_code(204);
    exit;
}

$blocked = $report['csp-report']['blocked-uri'] ?? 'unknown';
$violated = $report['csp-report']['violated-directive'] ?? 'unknown';
$page = $report['csp-report']['document-uri'] ?? 'unknown';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

logMsg("$ip | $page | $violated | $blocked | " . json_encode($report), 'csp.log');

http_response_code(204);
