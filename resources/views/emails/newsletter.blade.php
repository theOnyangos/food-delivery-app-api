<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine ?? 'Newsletter' }} – {{ config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f5f5;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f5f5f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="padding: 24px 40px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                            <span style="color: #111827; font-size: 20px; font-weight: 600;">{{ config('app.name') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px; color: #333333; font-size: 16px; line-height: 1.6;">
                            {!! $bodyHtml !!}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px; text-align: center; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 12px;">
                            You received this email because you subscribed to the {{ config('app.name') }} newsletter.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
