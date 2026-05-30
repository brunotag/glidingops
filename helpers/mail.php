<?php

class Mail 
{
    private static function getCommonHeaders($reply_to) {
        return 
            'From: WWGC Gliding Operations <machinery.gops@wwgc.co.nz>' . "\r\n" .
            'Reply-To: ' . $reply_to . "\r\n" .
            'Return-PATH: gops.wwgc.co.nz@gmail.com' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
    }

    public static function SendMail($to, $subject, $message, $reply_to, $content_type)
    {
        //TODO: replace hardcoded domains
        $headers =  Mail::getCommonHeaders($reply_to) . "\r\n" .'Content-type: ' . $content_type;
        return mail($to, $subject, $message, $headers, '-r machinery.gops@wwgc.co.nz');
    }    

    public static function SendMailPlainText($to, $subject, $message)
    {
        return Mail::SendMail($to, $subject, $message, 'servicedelivery@wwgc.co.nz', 'text/plain');
    }
    
    public static function SendMailHtml($to, $subject, $message)
    {
        return Mail::SendMail($to, $subject, $message, 'servicedelivery@wwgc.co.nz', 'text/html; charset=iso-8859-1');
    }

    public static function SendMailPlainTextReplyTo($to, $subject, $message, $reply_to)
    {
        return Mail::SendMail($to, $subject, $message, 'servicedelivery@wwgc.co.nz, '. $reply_to, 'text/plain');
    }
    
    public static function SendMailHtmlReplyTo($to, $subject, $message, $reply_to)
    {
        return Mail::SendMail($to, $subject, $message,'servicedelivery@wwgc.co.nz, '. $reply_to, 'text/html; charset=iso-8859-1');
    }

    public static function SendMailToRecipients($recipients, $subject, $message, $reply_to)
    {
        $result = [
            'success' => 0,
            'failed' => [],
            'total' => count($recipients)
        ];

        foreach ($recipients as $recipient) {
            $email = is_array($recipient) ? $recipient['email'] : $recipient;
            $name = is_array($recipient) && isset($recipient['name']) ? $recipient['name'] : '';

            try {
                $to = !empty($name) ? "$name <$email>" : $email;
                $sent = Mail::SendMail($to, $subject, $message, $reply_to, 'text/plain');

                if ($sent) {
                    $result['success']++;
                } else {
                    $result['failed'][] = [
                        'email' => $email,
                        'reason' => 'Mail server rejected'
                    ];
                }
            } catch (Exception $e) {
                $result['failed'][] = [
                    'email' => $email,
                    'reason' => $e->getMessage()
                ];
            } catch (Error $e) {
                $result['failed'][] = [
                    'email' => $email,
                    'reason' => $e->getMessage()
                ];
            }
        }

        return $result;
    }

    public static function SendMailBatch($recipients, $subject, $message, $reply_to, $progress_callback = null)
    {
        $result = [
            'success' => 0,
            'failed' => [],
            'total' => count($recipients)
        ];

        $processed = 0;

        foreach ($recipients as $recipient) {
            $email = is_array($recipient) ? $recipient['email'] : $recipient;
            $name = is_array($recipient) && isset($recipient['name']) ? $recipient['name'] : '';

            $status = ['email' => $email, 'status' => 'pending'];

            try {
                $to = !empty($name) ? "$name <$email>" : $email;
                $sent = Mail::SendMail($to, $subject, $message, $reply_to, 'text/plain');

                if ($sent) {
                    $result['success']++;
                    $status['status'] = 'success';
                } else {
                    $status['status'] = 'failed';
                    $status['reason'] = 'Mail server rejected';
                    $result['failed'][] = ['email' => $email, 'reason' => 'Mail server rejected'];
                }
            } catch (Exception $e) {
                $status['status'] = 'failed';
                $status['reason'] = $e->getMessage();
                $result['failed'][] = ['email' => $email, 'reason' => $e->getMessage()];
            } catch (Error $e) {
                $status['status'] = 'failed';
                $status['reason'] = $e->getMessage();
                $result['failed'][] = ['email' => $email, 'reason' => $e->getMessage()];
            }

            $processed++;
            if ($progress_callback) {
                $progress_callback($status, $processed, $result['total']);
            }
        }

        return $result;
    }

}
