<?php

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Transactional email (account verification, welcome, password reset), sent through any SMTP
 * server configured in .env (MAIL_*). Replaces the old Amazon SES SDK integration; Amazon SES
 * still works through its SMTP interface.
 */
class Mailer {

    public function send($action, $info, $lang = false) {

        $message = $this->build($action, $info, $lang);
        if ($message === null) {
            return FALSE;
        }

        $host = Config::get('MAIL_HOST');
        if (!$host) {
            error_log("Mailer: MAIL_HOST is not configured, '$action' email to {$info['to']} was not sent.");
            return FALSE;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = (int) Config::get('MAIL_PORT', 587);
            $encryption = strtolower((string) Config::get('MAIL_ENCRYPTION', 'tls'));
            if ($encryption === 'ssl' || $encryption === 'smtps') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls' || $encryption === 'starttls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }
            if (Config::get('MAIL_USERNAME')) {
                $mail->SMTPAuth = true;
                $mail->Username = Config::get('MAIL_USERNAME');
                $mail->Password = Config::get('MAIL_PASSWORD', '');
            }
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom(Config::get('MAIL_FROM_ADDRESS', 'noreply@localhost'), Config::get('MAIL_FROM_NAME', 'Hacker Experience'));
            $mail->addAddress($info['to']);
            $mail->Subject = $message['subject'];
            $mail->isHTML(true);
            $mail->Body = $message['html'];
            $mail->AltBody = $message['text'];
            $mail->send();
            return TRUE;
        } catch (MailerException $e) {
            error_log('Mailer: ' . $e->getMessage());
            return FALSE;
        }

    }

    private function build($action, $info, $lang) {

        $pt = in_array($lang, ['pt', 'br', 'pt_BR'], true);

        switch ($action) {
            case 'verify':
                $subject = 'Hacker Experience email confirmation';
                $template = $pt ? 'verify_br' : 'verify';
                $vars = ['USER' => $info['user'], 'KEY' => $info['key']];
                break;
            case 'welcome':
                $subject = 'Welcome to Hacker Experience!';
                $template = $pt ? 'welcome_br' : 'welcome';
                $vars = ['USER' => $info['user']];
                break;
            case 'request_reset':
                $subject = 'Reset account password';
                $template = 'reset';
                $vars = ['USER' => $info['user'], 'CODE' => $info['code']];
                break;
            default:
                return null;
        }

        $appUrl = rtrim((string) Config::get('APP_URL', 'http://localhost:8080'), '/');
        $vars['APP_URL'] = $appUrl;
        $vars['FORUM_URL'] = Config::get('FORUM_URL') ?: $appUrl;

        $html = file_get_contents(BASE_PATH . '/mail_templates/' . $template . '.html');
        foreach ($vars as $name => $value) {
            $html = str_replace('%' . $name . '%', htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'), $html);
        }

        $text = $html;
        if ($action === 'verify') {
            $text = str_replace('</form>', '<br/>' . _('Proceed to ') . $appUrl . '/welcome?code=' . $info['key'], $text);
        }

        return [
            'subject' => _($subject),
            'html' => $html,
            'text' => html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'),
        ];

    }

}
