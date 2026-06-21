<?php

function checkSecretCode($org, $key) {
    $secretCode = md5($key);

    require_once __DIR__ . '/database.php';
    $con = open_gliding_db();
    if (mysqli_connect_errno())
    {
        return false;
    }

    $sql="SELECT secret_code FROM organisations WHERE id='$org'";
    $r = mysqli_query($con,$sql);
    $row = mysqli_fetch_array($r);
    return $row[0] == $secretCode;
}

function initiateServiceUserSession($org){
    $_SESSION['userid']= -1;
    $_SESSION['who']= "service-user";
    $_SESSION['memberid']= -1;
    $_SESSION['org']= $org;
    require_once __DIR__ . '/permissions.php';
    $_SESSION['permissions'] = ['daily-sheet.access', 'daily-sheet.start-day'];
    $q="SELECT timezone from organisations where id = " . $org;
    require_once __DIR__ . '/database.php';
    $con = open_gliding_db();
    $r2 = mysqli_query($con,$q);
    $row2 = mysqli_fetch_array($r2);
    $_SESSION['timezone'] = $row2[0];
}

function generateRandomString($length = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

?>