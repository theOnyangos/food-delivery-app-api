<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your ASL account</title>
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
                            <h1 style="margin: 0 0 24px; color: #111111; font-size: 22px; font-weight: 700;">Hi {{ $firstName }},</h1>
                            <p style="margin: 0 0 24px; color: #333333; font-size: 16px; line-height: 1.6;">
                                Thanks for signing up. Please verify your email address by clicking the button below. This link can only be used once and will expire in 60 minutes.
                            </p>
                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 32px 0;">
                                <tr>
                                    <td style="border-radius: 8px; background-color: #facc15;">
                                        <a href="{{ $verificationUrl }}" target="_blank" rel="noopener" style="display: inline-block; padding: 14px 32px; color: #111111; font-size: 16px; font-weight: 700; text-decoration: none;">
                                            Verify my account
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 0; color: #666666; font-size: 14px; line-height: 1.6;">If you didn't create an account, you can safely ignore this email.</p>
                            <p style="margin: 24px 0 0; color: #666666; font-size: 12px;">
                                Or copy and paste this link into your browser:<br>
                                <a href="{{ $verificationUrl }}" style="color: #111111; word-break: break-all;">{{ $verificationUrl }}</a>
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
