<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
</head>
<body style="margin:0;padding:0;background:#f6f6f6;font-family:Arial,sans-serif;">
<div style="max-width:560px;margin:40px auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.08);">
    <div style="background:#111;padding:20px;border-bottom:4px solid #facc15;color:#facc15;font-weight:700;font-size:22px;text-align:center;">{{ config('app.name') }}</div>
    <div style="padding:28px;">
        <h2 style="margin:0 0 14px;color:#111;">{{ $success ? 'Email verified' : 'Verification failed' }}</h2>
        <p style="margin:0 0 22px;color:#333;line-height:1.6;">{{ $message }}</p>
        <a href="{{ $loginUrl }}" style="display:inline-block;background:#facc15;color:#111;font-weight:700;text-decoration:none;padding:12px 20px;border-radius:8px;">Go to login</a>
    </div>
</div>
</body>
</html>
