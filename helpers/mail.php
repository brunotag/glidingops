<?php

require_once dirname(__DIR__) . '/lrv/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mail
{
    private static $config = null;

    private static function getConfig(): array
    {
        if (self::$config === null) {
            $path = dirname(__DIR__) . '/config/mail.php';
            self::$config = file_exists($path) ? require $path : [
                'host' => 'localhost',
                'port' => 1025,
                'encryption' => '',
                'username' => '',
                'password' => '',
                'from_address' => 'machinery.gops@wwgc.co.nz',
                'from_name' => 'WWGC Gliding Operations',
            ];
        }
        return self::$config;
    }

    private static function createMailer(): PHPMailer
    {
        $cfg = self::getConfig();
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $cfg['host'];
        $mail->Port = (int)$cfg['port'];
        $mail->SMTPAuth = !empty($cfg['username']);
        if (!empty($cfg['username'])) {
            $mail->Username = $cfg['username'];
            $mail->Password = $cfg['password'];
        }
        if (!empty($cfg['encryption'])) {
            $mail->SMTPSecure = $cfg['encryption'];
        }
        $mail->setFrom($cfg['from_address'], $cfg['from_name']);
        $mail->Sender = $cfg['from_address'];
        $mail->CharSet = 'UTF-8';
        return $mail;
    }

    private static function send(PHPMailer $mail): bool
    {
        try {
            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            return false;
        }
    }

    public static function SendMail($to, $subject, $message, $reply_to, $content_type)
    {
        $mail = self::createMailer();
        try {
            $mail->addAddress($to);
            $mail->addReplyTo($reply_to);
            $mail->Subject = $subject;
            $mail->isHTML(stripos($content_type, 'html') !== false);
            $mail->Body = $message;
            if ($mail->isHTML()) {
                $mail->AltBody = strip_tags($message);
            }
            return self::send($mail);
        } catch (PHPMailerException $e) {
            return false;
        }
    }

    public static function SendMailPlainText($to, $subject, $message)
    {
        return self::SendMail($to, $subject, $message, 'servicedelivery@wwgc.co.nz', 'text/plain');
    }

    public static function SendMailHtml($to, $subject, $message)
    {
        return self::SendMail($to, $subject, $message, 'servicedelivery@wwgc.co.nz', 'text/html');
    }

    public static function SendMailPlainTextReplyTo($to, $subject, $message, $reply_to)
    {
        return self::SendMail($to, $subject, $message, 'servicedelivery@wwgc.co.nz, ' . $reply_to, 'text/plain');
    }

    public static function SendMailHtmlReplyTo($to, $subject, $message, $reply_to)
    {
        return self::SendMail($to, $subject, $message, 'servicedelivery@wwgc.co.nz, ' . $reply_to, 'text/html');
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
                $sent = self::SendMail($to, $subject, $message, $reply_to, 'text/plain');

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
                $sent = self::SendMail($to, $subject, $message, $reply_to, 'text/plain');

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
