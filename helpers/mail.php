<?php

class Mail 
{
    private static function getCommonHeaders($reply_to) {
        return 
            'From: Gliding Operations <gops.wwgc.co.nz@gmail.com>' . "\r\n" .
            'Reply-To: ' . $reply_to . "\r\n" .
            'Return-PATH: gops.wwgc.co.nz@gmail.com' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
    }

    private static function SendMail($to, $subject, $message, $reply_to, $content_type)
    {
        //TODO: replace hardcoded domains
        $headers =  Mail::getCommonHeaders($reply_to) . "\r\n" .'Content-type: ' . $content_type;
        return mail($to, $subject, $message, $headers, '-r gops.wwgc.co.nz@gmail.com');
    }    

    public static function SendMailPlainText($to, $subject, $message)
    {
        return Mail::SendMail($to, $subject, $message, 'wgcoperations@gmail.com', 'text/plain');
    }
    
    public static function SendMailHtml($to, $subject, $message)
    {
        return Mail::SendMail($to, $subject, $message, 'wgcoperations@gmail.com', 'text/html; charset=iso-8859-1');
    }

    public static function SendMailPlainTextReplyTo($to, $subject, $message, $reply_to)
    {
        return Mail::SendMail($to, $subject, $message, 'wgcoperations@gmail.com, '. $reply_to, 'text/plain');
    }
    
    public static function SendMailHtmlReplyTo($to, $subject, $message, $reply_to)
    {
        return Mail::SendMail($to, $subject, $message,'wgcoperations@gmail.com, '. $reply_to, 'text/html; charset=iso-8859-1');
    }
    

}
