<?php

/*
 * Gmail setup:
 * 1. Enable 2-Step Verification on the Gmail account.
 * 2. Create a Gmail "App Password" and place its 16-character value in
 *    SMTP_PASSWORD. A normal Gmail password will not work with SMTP.
 *
 * In XAMPP, set these values in Apache's environment or replace the empty
 * fallback below while testing. Never commit a real app password to Git.
 */
return [
    'host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'username' => getenv('SMTP_USERNAME') ?: 'sadesign914@gmail.com',
    'password' => getenv('SMTP_PASSWORD') ?: 'hmodpqkiwhumcyng',
    'port' => (int) (getenv('SMTP_PORT') ?: 465),
    'encryption' => getenv('SMTP_ENCRYPTION') ?: 'ssl',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'SA Design',

    // Must be an address recipients can access, not an expired temporary tunnel.
    'base_url' => rtrim(getenv('APP_URL') ?: 'https://sadesign.site/', '/') . '/',
];
