<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f7f3ff;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f7f3ff; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #efe7ff; box-shadow: 0 8px 24px rgba(125, 51, 240, 0.08);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #6927ca 0%, #7d33f0 45%, #8f4dff 100%); padding: 28px 40px; text-align: center; border-bottom: 4px solid #f44e1a;">
                            <span style="color: #ffffff; font-size: 24px; font-weight: 700; letter-spacing: -0.02em;">{{ config('app.name') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <h1 style="margin: 0 0 20px; color: #121827; font-size: 22px; font-weight: 700;">Welcome {{ $firstName }},</h1>
                            <p style="margin: 0 0 18px; color: #596f98; font-size: 16px; line-height: 1.65;">
                                Your email has been verified successfully and your account is now active.
                            </p>
                            <p style="margin: 0; color: #596f98; font-size: 16px; line-height: 1.65;">
                                You can now log in and start using the platform.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #121827; padding: 24px 40px; text-align: center; border-top: 3px solid #f44e1a;">
                            <p style="margin: 0; color: #9ca3af; font-size: 13px;">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
