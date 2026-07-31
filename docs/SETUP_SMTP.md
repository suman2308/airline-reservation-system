# SMTP Setup Guide

AeroBook can send transactional emails (email verification, password reset) via SMTP using PHPMailer.

## Prerequisites

1. **PHPMailer library** (manual download required)
2. **SMTP credentials** from your email provider

## Step 1: Install PHPMailer

PHPMailer is NOT included in the repository due to licensing and size. You need to download it manually:

```bash
# Create the directory
mkdir -p lib/phpmailer

# Download PHPMailer (latest version)
curl -L https://github.com/PHPMailer/PHPMailer/releases/download/v6.9.3/PHPMailer-6.9.3.zip -o phpmailer.zip
unzip phpmailer.zip -d lib/phpmailer/
rm phpmailer.zip

# Move files out of version subdirectory
mv lib/phpmailer/PHPMailer-6.9.3/* lib/phpmailer/
rm -rf lib/phpmailer/PHPMailer-6.9.3
```

Required files:
- `lib/phpmailer/PHPMailer.php`
- `lib/phpmailer/SMTP.php`
- `lib/phpmailer/Exception.php`

## Step 2: Configure SMTP Settings

### Via `.env` file

```env
MAIL_MODE=smtp
MAIL_FROM=noreply@aerobook.in
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your-email@gmail.com
MAIL_PASS=your-app-password
MAIL_ENCRYPTION=tls
```

### Via `includes/config.php`

```php
define('MAIL_MODE', 'smtp');
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'your-email@gmail.com');
define('MAIL_PASS', 'your-app-password');
define('MAIL_ENCRYPTION', 'tls');
```

## Step 3: Using Gmail

For Gmail SMTP, you need an **App Password** (NOT your regular password):

1. Enable 2-Factor Authentication on your Google account
2. Go to: https://myaccount.google.com/apppasswords
3. Generate a new app password for "Mail"
4. Use this 16-character password as `MAIL_PASS`

## Step 4: Verify Email Sending

1. Register a new account
2. Check `logs/email.log` to see if the email was sent/logged
3. If `MAIL_MODE=smtp`, check the recipient's inbox (including spam folder)

## Fallback Mode

When `MAIL_MODE=log` (the default), emails are written to `logs/email.log` instead of being sent:

```
[2026-07-30 10:30:00] TO: user@example.com (John) | SUBJECT: Verify your AeroBook account
```

This is useful for:
- Development environments
- Testing without configuring SMTP
- Debugging email content

## Supported Email Templates

| Template | Trigger | Recipient |
|----------|---------|-----------|
| Email Verification | User registration | New user |
| Password Reset | Forgot password request | Registered user |

> **Note:** Booking confirmations and cancellations do not send emails. They are communicated via in-app notifications and shown on the booking confirmation page.

## Troubleshooting

| Problem | Solution |
|---------|----------|
| PHPMailer not found | Check `lib/phpmailer/PHPMailer.php` exists |
| SMTP connection failed | Verify host, port, and encryption settings |
| Authentication failed | Check username and app password |
| Email going to spam | Add SPF/DKIM records to your domain |
| Gmail blocking | Use an App Password, not your regular password |
