<?php

function saveSocialPhoto($photoUrl, $memberId) {
    if (empty($photoUrl) || empty($memberId)) return false;

    $imgData = @file_get_contents($photoUrl, false, stream_context_create([
        'http' => ['timeout' => 10],
    ]));
    if (!$imgData) return false;

    $srcImage = @imagecreatefromstring($imgData);
    if (!$srcImage) return false;

    $w = imagesx($srcImage);
    $h = imagesy($srcImage);
    $maxDim = 400;
    if ($w > $maxDim || $h > $maxDim) {
        $ratio = $w / $h;
        if ($w > $h) {
            $newW = $maxDim;
            $newH = intval($maxDim / $ratio);
        } else {
            $newH = $maxDim;
            $newW = intval($maxDim * $ratio);
        }
        $dstImage = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($srcImage);
        $srcImage = $dstImage;
    }

    $photoDir = dirname(__DIR__) . '/img/members';
    if (!is_dir($photoDir)) {
        @mkdir($photoDir, 0777, true);
    }

    $destPath = $photoDir . '/' . intval($memberId) . '.jpg';
    $result = imagejpeg($srcImage, $destPath, 80);
    imagedestroy($srcImage);
    return $result;
}

function getSocialPhotoUrl($provider, $userData) {
    if ($provider === 'google') {
        return $userData['picture'] ?? '';
    }
    if ($provider === 'facebook') {
        return $userData['picture']['data']['url'] ?? '';
    }
    return '';
}