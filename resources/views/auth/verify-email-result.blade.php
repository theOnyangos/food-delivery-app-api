<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email verification — {{ config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f7f3ff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f7f3ff; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellspacing="0" cellpadding="0" style="max-width: 560px; width: 100%; background-color: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #efe7ff; box-shadow: 0 8px 24px rgba(125, 51, 240, 0.08);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #6927ca 0%, #7d33f0 45%, #8f4dff 100%); padding: 24px 28px; text-align: center; border-bottom: 4px solid #f44e1a;">
                            <span style="color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: -0.02em;">{{ config('app.name') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px 28px;">
                            <h2 style="margin: 0 0 14px; color: #121827; font-size: 20px; font-weight: 700;">{{ $success ? 'Email verified' : 'Verification failed' }}</h2>
                            <p style="margin: 0 0 24px; color: #596f98; font-size: 16px; line-height: 1.65;">{{ $message }}</p>
                            <table role="presentation" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="border-radius: 10px; background-color: #f44e1a;">
                                        <a href="{{ $loginUrl }}" style="display: inline-block; color: #ffffff; font-weight: 700; text-decoration: none; padding: 12px 24px; font-size: 15px;">Go to login</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #121827; padding: 20px 28px; text-align: center; border-top: 3px solid #f44e1a;">
                            <p style="margin: 0; color: #9ca3af; font-size: 12px;">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
