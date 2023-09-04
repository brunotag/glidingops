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
include "./helpers/mail.php";
$con_params = require('./config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);
if (mysqli_connect_errno()) {
    error_log("SendTxt ERROR: Unable to cpnnect to database");
    echo "<p>Unable to connect to database</p>";
    exit();
} else {

    $sql = <<<SQL
    SELECT texts.txt_id,texts.txt_to, messages.msg, COALESCE(members.email,"") as email_to, COALESCE(users.usercode,"") as email_to_2, COALESCE(members2.email, "") as email_from
    FROM texts 
        INNER JOIN messages ON messages.id = texts.txt_msg_id
        INNER JOIN members ON texts.txt_member_id = members.id
        INNER JOIN users on users.member = members.id
        INNER JOIN members members2 ON messages.txt_sender_member_id = members2.id
    WHERE txt_status = 0;
SQL;
    $r = mysqli_query($con, $sql);
    $messages_and_email_addresses = [];
    $ids_to_update = [];
    while ($row = mysqli_fetch_array($r)) {

        if ($row["msg"] && strlen($row["msg"]) > 0) {
            $email_to = "";
            $email_from = "";
            if (filter_var($row["email_to"], FILTER_VALIDATE_EMAIL)) {
                $email_to = $row["email_to"];
            } else if (filter_var($row["email_to_2"], FILTER_VALIDATE_EMAIL)) {
                $email_to = $row["email_to_2"];
            }
            if (filter_var($row["email_from"], FILTER_VALIDATE_EMAIL)) {
                $email_from = $row["email_from"];
            }
            //PREP SEND EMAIL
            if (strlen($email_to) > 0) {
                if (!$messages_and_email_addresses[$row["msg"]]) {
                    $messages_and_email_addresses[$row["msg"]] = [
                        "email_from" => $email_from,
                        "email_to" => []
                    ];
                }
                array_push($messages_and_email_addresses[$row["msg"]]["email_to"], $email_to);
            }
            array_push($ids_to_update, $row['txt_id']);
        } else {
            //Mark as error
            $Q = "UPDATE texts SET txt_status=2 WHERE txt_id = " . $row['txt_id'];
            $r2 = mysqli_query($con, $Q);
        }
    }

    //TODO: localisation?
    $date = new DateTime("now", new DateTimeZone('Pacific/Auckland'));
    foreach ($messages_and_email_addresses as $msg => $email_addresses) {
        Mail::SendMailPlainTextReplyTo(implode(', ', $email_addresses["email_to"]), "WWGC Msg | " . $date->format('D d M h:i A'), $msg, $email_addresses["email_from"]);
    }
    
    $Q = "UPDATE texts SET txt_status=3 WHERE txt_id IN (" . implode(', ',$ids_to_update) .")";
    $r2 = mysqli_query($con, $Q);

    mysqli_close($con);
}
header('Location: MessagingPage');
