<?php
/**
 * AeroBook – Email Integration Layer
 *
 * Sends transactional emails via PHPMailer (SMTP) or falls back to logging.
 * All email templates are defined here as reusable HTML strings.
 *
 * Usage:
 *   require_once __DIR__ . '/Mailer.php';
 *   $mailer = new AeroMailer();
 *   $mailer->sendVerification('user@example.com', 'John', 'https://...');
 *
 * This system supports only:
 *   1. Email Verification
 *   2. Forgot Password / Password Reset
 *
 * Dependencies:
 *   - PHPMailer (manual install: download to lib/phpmailer/)
 *     https://github.com/PHPMailer/PHPMailer/releases
 *   - If PHPMailer not present, emails are logged to /logs/email.log
 */

class AeroMailer {
    private $mode;
    private $fromEmail;
    private $fromName;
    private $host;
    private $port;
    private $user;
    private $pass;
    private $encryption;
    private $phpmailerLoaded = false;

    public function __construct() {
        $this->mode = defined('MAIL_MODE') ? MAIL_MODE : 'log';
        $this->fromEmail = defined('MAIL_FROM') ? MAIL_FROM : 'noreply@aerobook.in';
        $this->fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'AeroBook';
        $this->host = defined('MAIL_HOST') ? MAIL_HOST : '';
        $this->port = defined('MAIL_PORT') ? MAIL_PORT : 587;
        $this->user = defined('MAIL_USER') ? MAIL_USER : '';
        $this->pass = defined('MAIL_PASS') ? MAIL_PASS : '';
        $this->encryption = defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : 'tls';

        // Check if PHPMailer is available (manual install in lib/phpmailer/)
        $phpmailerPath = __DIR__ . '/../lib/phpmailer/PHPMailer.php';
        $this->phpmailerLoaded = file_exists($phpmailerPath);
    }

    /**
     * Send an email using PHPMailer or log it.
     */
    public function send($to, $toName, $subject, $htmlBody, $textBody = '') {
        if ($this->mode === 'smtp' && $this->phpmailerLoaded && !empty($this->host)) {
            return $this->sendSMTP($to, $toName, $subject, $htmlBody, $textBody);
        }
        return $this->sendLog($to, $toName, $subject, $htmlBody);
    }

    /**
     * Send via PHPMailer SMTP.
     */
    private function sendSMTP($to, $toName, $subject, $htmlBody, $textBody) {
        try {
            require_once __DIR__ . '/../lib/phpmailer/PHPMailer.php';
            require_once __DIR__ . '/../lib/phpmailer/SMTP.php';
            require_once __DIR__ . '/../lib/phpmailer/Exception.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $this->host;
            $mail->SMTPAuth = !empty($this->user);
            $mail->Username = $this->user;
            $mail->Password = $this->pass;
            $mail->SMTPSecure = $this->encryption;
            $mail->Port = $this->port;
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to, $toName);
            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody ?: strip_tags($htmlBody);
            $mail->send();
            $this->log("Email sent to {$to}: {$subject}");
            return true;
        } catch (Exception $e) {
            $this->log("Email FAILED to {$to}: {$subject} - " . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Log the email instead of sending (development mode).
     */
    private function sendLog($to, $toName, $subject, $htmlBody) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $logFile = $logDir . '/email.log';
        $entry = "[" . date('Y-m-d H:i:s') . "] TO: {$to} ({$toName}) | SUBJECT: {$subject}\n";
        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        $this->log("Email logged to {$to}: {$subject}");
        return true;
    }

    private function log($msg) {
        if (function_exists('logInfo')) {
            logInfo($msg);
        }
    }

    /**
     * Build the standard HTML wrapper for all emails.
     */
    private function wrapTemplate($title, $content) {
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<style>
body{margin:0;padding:0;background:#F4F6F9;font-family:'Inter','Helvetica',sans-serif;color:#202A36}
.email-wrapper{max-width:600px;margin:0 auto;padding:20px}
.email-header{background:#202A36;padding:30px;text-align:center;border-radius:12px 12px 0 0}
.email-header h1{color:#fff;margin:0;font-size:22px}
.email-header .tagline{color:#A7B4C2;font-size:13px;margin-top:4px}
.email-body{background:#fff;padding:30px;border-radius:0 0 12px 12px;line-height:1.7}
.email-body h2{font-size:20px;margin:0 0 16px;color:#202A36}
.email-body p{margin:0 0 12px;color:#4B5563}
.email-body .btn{display:inline-block;padding:12px 28px;background:#202A36;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;margin:12px 0}
.email-footer{text-align:center;padding:20px;color:#6B7280;font-size:12px}
.email-footer a{color:#202A36;text-decoration:none}
</style></head><body>
<div class="email-wrapper">
<div class="email-header"><h1>AeroBook</h1><div class="tagline">Smart · Fast · Easy</div></div>
<div class="email-body"><h2>{$title}</h2>{$content}</div>
<div class="email-footer">
<p>AeroBook – Airline Reservation System</p>
<p><a href="mailto:support@aerobook.in">support@aerobook.in</a></p>
</div></div></body></html>
HTML;
    }

    /**
     * Send email verification.
     */
    public function sendVerification($to, $name, $verifyUrl) {
        $content = "<p>Hi {$name},</p><p>Welcome to AeroBook! Please verify your email address to activate your account.</p>";
        $content .= "<p style='text-align:center'><a href='{$verifyUrl}' class='btn'>Verify Email Address</a></p>";
        $content .= "<p style='font-size:13px;color:#6B7280;text-align:center'>Or copy this link:<br>{$verifyUrl}</p>";
        $content .= "<p>This link expires in 24 hours.</p>";
        return $this->send($to, $name, 'Verify your AeroBook account', $this->wrapTemplate('Verify Your Email', $content));
    }

    /**
     * Send password reset.
     */
    public function sendPasswordReset($to, $name, $resetUrl) {
        $content = "<p>Hi {$name},</p><p>We received a request to reset your password. Click the button below to set a new one.</p>";
        $content .= "<p style='text-align:center'><a href='{$resetUrl}' class='btn'>Reset Password</a></p>";
        $content .= "<p style='font-size:13px;color:#6B7280;text-align:center'>Or copy this link:<br>{$resetUrl}</p>";
        $content .= "<p>This link expires in 1 hour. If you didn't request this, please ignore this email.</p>";
        return $this->send($to, $name, 'Reset your AeroBook password', $this->wrapTemplate('Password Reset', $content));
    }
}
