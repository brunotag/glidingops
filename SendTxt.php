<?php
session_start();
function var_error_log($object = null, $text = '')
{
    ob_start();
    var_dump($object);
    $contents = ob_get_contents();
    ob_end_clean();
    error_log("{$text} {$contents}");
}

//Start
//Gte t=environemnt variables
include "helpers.php";
$con_params = require('./config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);
if (mysqli_connect_errno()) {
    error_log("SendTxt ERROR: Unable to cpnnect to database");
    echo "<p>Unable to connect to database</p>";
    exit();
} else {

    $sql = <<<SQL
    SELECT texts.txt_id,texts.txt_to, messages.msg, members.email as email_to, users.usercode as email_to_2
    FROM texts 
        INNER JOIN messages ON messages.id = texts.txt_msg_id
        INNER JOIN members ON texts.txt_member_id = members.id
        INNER JOIN users on users.member = members.id
    WHERE txt_status = 0;
SQL;
    $r = mysqli_query($con, $sql);
    while ($row = mysqli_fetch_array($r)) {

        if ($row["msg"] && strlen($row["msg"]) > 0) {
            $email_to = "";
            if (filter_var($row["email_to"], FILTER_VALIDATE_EMAIL)) {
                $email_to = $row["email_to"];
            } else if (filter_var($row["email_to_2"], FILTER_VALIDATE_EMAIL)) {
                $email_to = $row["email_to_2"];
            }
            //SEND EMAIL
            if (strlen($email_to) > 0) {
                SendMail($email_to, "WWGC GOPS Message", $row["msg"]);
            }
            //SEND Text
            if ($row['txt_to']) {
                $strTo = trim($row['txt_to']);
                $strTo = trim($strTo, "+");
                $strTo = str_replace(" ", "", $strTo);

                $smskey = getenv("SMS_KEY");
                $gateway_host = getenv("SMS_HOST");
                if (strlen($strTo) > 0
                    && ($smskey && strlen($smskey) > 0)
                    && ($gateway_host && strlen($gateway_host) > 0)
                ) {//TODO: emails don't log as error nor as successful...
                    $strTo = urlencode($strTo);
                    $postparam = array();
                    $postparam['smskey'] = $smskey;
                    $postparam['phone'] = $strTo;
                    $postparam['msg'] = $row[2];
                    $postparam['county_code'] = "64";
                    $callback = '';
                    if (empty($_SERVER['HTTPS']))
                        $callback = "http://";
                    else
                        $callback = "https://";

                    $callback .= $_SERVER['HTTP_HOST'];
                    $callback .= "/TextStatus.php";
                    $postparam['callback_url'] = $callback;

                    $str = json_encode($postparam);
                    $url = $gateway_host;

                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
                    $result = curl_exec($ch);
                    if (!$result) {
                        error_log("Curl error in SendTxt.php " . curl_error($ch));
                    }

                    $result = json_decode($result, true);


                    $smsid = 0;
                    $status = "ERROR";
                    if (isset($result['meta'])) {
                        if ($result['meta']['status'] = "OK") {
                            $Q = "UPDATE texts SET txt_status=1 txt_timestamp_sent = now() WHERE txt_id = " . $row['txt_id'];
                            $r2 = mysqli_query($con, $Q);

                            $data = $result['data'];
                            $smsid = intval($data['textid']);
                            //if (isset($data['status']) && $data['status'])
                            //    $status = "SENT";
                            $Q = "UPDATE texts SET txt_unique=" . $smsid . " WHERE txt_id = " . $row['txt_id'];
                            $r2 = mysqli_query($con, $Q);
                        }
                    }
                } else {
                    //Mark as error
                    $Q = "UPDATE texts SET txt_status=2 WHERE txt_id = " . $row['txt_id'];
                    $r2 = mysqli_query($con, $Q);
                }
            }
        } else {
            //Mark as error
            $Q = "UPDATE texts SET txt_status=2 WHERE txt_id = " . $row['txt_id'];
            $r2 = mysqli_query($con, $Q);
        }
    }

    mysqli_close($con);
}
header('Location: MessagingPage');
