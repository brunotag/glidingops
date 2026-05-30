<?php
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isMobile = preg_match('/Mobile|Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i', $userAgent);

if ($isMobile) {
    require __DIR__ . '/MasterDisplayMobile.php';
} else {
    require __DIR__ . '/MasterDisplayDesktop.php';
}
