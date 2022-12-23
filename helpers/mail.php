<?php

class Mail 
{
    private static function getCommonHeaders() {
        return 
            'From: Gliding Operations <gops.wwgc.co.nz@gmail.com>' . "\r\n" .
            'Reply-To: wgcoperations@gmail.com' . "\r\n" .
            'Return-PATH: gops.wwgc.co.nz@gmail.com' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
    }
    
    public static function SendMailPlainText($to, $subject, $message)
    {
        //TODO: replace hardcoded domains
        $headers = Mail::getCommonHeaders() . "\r\n" ."Content-Type: text/plain;";
        return mail($to, $subject, $message, $headers, '-r gops.wwgc.co.nz@gmail.com');
    }
    
    public static function SendMailHtml($to, $subject, $message)
    {
        //TODO: replace hardcoded domains
        $headers =  Mail::getCommonHeaders() . "\r\n" .'Content-type: text/html; charset=iso-8859-1';
        return mail($to, $subject, $message, $headers, '-r gops.wwgc.co.nz@gmail.com');
    }
}
