<?php

declare(strict_types=1);

/** @var string $resetUrl */
$url = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset your password</title>
</head>
<body style="margin:0;background:#f5f7fa;color:#20242c;font-family:Arial,sans-serif">
    <div style="max-width:560px;margin:0 auto;padding:32px 20px">
        <div style="background:#fff;border:1px solid #e2e6eb;border-radius:10px;padding:28px">
            <h1 style="margin:0 0 14px;font-size:22px;line-height:1.3">Reset your password</h1>
            <p style="margin:0 0 22px;line-height:1.6;color:#565e6b">
                We received a request to reset your password. Use the button below to choose a new one.
            </p>
            <p style="margin:0 0 22px">
                <a href="<?= $url ?>" style="display:inline-block;padding:11px 17px;border-radius:6px;background:#2764df;color:#fff;text-decoration:none;font-weight:600">Reset password</a>
            </p>
            <p style="margin:0;font-size:13px;line-height:1.5;color:#747d8c">
                If you did not request a password reset, you can ignore this email.
            </p>
        </div>
    </div>
</body>
</html>
