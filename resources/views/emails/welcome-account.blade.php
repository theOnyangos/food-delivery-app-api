<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to ASL</title>
</head>
<body style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f6f6f6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f6f6f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #111111 0%, #2b2b2b 60%, #111111 100%); padding: 32px 40px; text-align: center; border-bottom: 4px solid #facc15;">
                            <span style="color: #facc15; font-size: 24px; font-weight: 700;">{{ config('app.name') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <h1 style="margin: 0 0 24px; color: #111111; font-size: 22px; font-weight: 700;">Welcome {{ $firstName }},</h1>
                            <p style="margin: 0 0 20px; color: #333333; font-size: 16px; line-height: 1.6;">
                                Your email has been verified successfully and your account is now active.
                            </p>
                            <p style="margin: 0; color: #333333; font-size: 16px; line-height: 1.6;">
                                You can now log in and start using the platform.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #111111; padding: 24px 40px; text-align: center; border-top: 3px solid #facc15;">
                            <p style="margin: 0; color: rgba(255,255,255,0.9); font-size: 13px;">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
