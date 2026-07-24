<?php

if (!function_exists('gops_csrf_token')) {
function gops_csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}
}

if (!function_exists('gops_csrf_field')) {
function gops_csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . gops_csrf_token() . '">';
}
}

if (!function_exists('gops_verify_csrf')) {
function gops_verify_csrf(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }
    $token = $_POST['_csrf'] ?? '';
    if (empty($token)) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($token) || empty($_SESSION['_csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['_csrf_token'], $token);
}
}

if (!function_exists('gops_require_csrf')) {
function gops_require_csrf(): void
{
    if (!gops_verify_csrf()) {
        http_response_code(400);
        die("Invalid or missing CSRF token");
    }
}
}