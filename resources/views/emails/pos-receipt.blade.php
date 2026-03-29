<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASL Order {{ $sale->receipt_number }}</title>
</head>
<body style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f7f3ff;">
@php
    $totals = $sale->totals ?? [];
    $lines = is_array($sale->lines) ? $sale->lines : [];
    $soldBy = $sale->soldByUser;
    $soldByLabel = $soldBy === null
        ? null
        : (trim(($soldBy->first_name ?? '').' '.($soldBy->last_name ?? '')) ?: $soldBy->email);
    $orderTypeLabel = ucfirst(str_replace('-', ' ', (string) $sale->order_type));
@endphp
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
                            <p style="margin: 0 0 8px; color: #7d33f0; font-size: 12px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;">ASL Order</p>
                            <h1 style="margin: 0 0 12px; color: #121827; font-size: 22px; font-weight: 700;">Order confirmation</h1>
                            <p style="margin: 0 0 24px; color: #596f98; font-size: 15px; line-height: 1.65;">
                                Thank you for your order. Below is a summary of what was purchased and the amount charged.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 24px; border: 1px solid #efe7ff; border-radius: 12px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 14px 18px; background-color: #f9f6ff;">
                                        <p style="margin: 0; color: #596f98; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em;">Reference</p>
                                        <p style="margin: 6px 0 0; color: #121827; font-size: 18px; font-weight: 700; font-family: ui-monospace, monospace;">{{ $sale->receipt_number }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 18px; border-top: 1px solid #efe7ff;">
                                        <p style="margin: 0; color: #596f98; font-size: 14px;">
                                            <strong style="color: #121827;">Order type:</strong> {{ $orderTypeLabel }}
                                        </p>
                                        @if($soldByLabel)
                                        <p style="margin: 8px 0 0; color: #596f98; font-size: 14px;">
                                            <strong style="color: #121827;">Served by:</strong> {{ $soldByLabel }}
                                        </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 12px; color: #121827; font-size: 15px; font-weight: 700;">Items ordered</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse; font-size: 14px;">
                                <thead>
                                    <tr>
                                        <th align="left" style="padding: 10px 12px; border-bottom: 2px solid #efe7ff; color: #596f98; font-weight: 600;">Item</th>
                                        <th align="center" style="padding: 10px 12px; border-bottom: 2px solid #efe7ff; color: #596f98; font-weight: 600; width: 56px;">Qty</th>
                                        <th align="right" style="padding: 10px 12px; border-bottom: 2px solid #efe7ff; color: #596f98; font-weight: 600; width: 96px;">Line total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($lines as $line)
                                    <tr>
                                        <td style="padding: 12px; border-bottom: 1px solid #f0f0f0; color: #121827;">{{ $line['meal_title'] ?? '—' }}</td>
                                        <td style="padding: 12px; border-bottom: 1px solid #f0f0f0; color: #596f98; text-align: center;">{{ (int) ($line['quantity'] ?? 0) }}</td>
                                        <td style="padding: 12px; border-bottom: 1px solid #f0f0f0; color: #121827; text-align: right; font-variant-numeric: tabular-nums;">${{ number_format((float) ($line['line_effective_total'] ?? 0), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" style="padding: 16px; color: #596f98;">No line items recorded.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top: 20px; font-size: 14px; color: #596f98;">
                                <tr>
                                    <td style="padding: 6px 0;">List subtotal</td>
                                    <td align="right" style="padding: 6px 0; color: #121827; font-variant-numeric: tabular-nums;">${{ number_format((float) ($totals['list_subtotal'] ?? 0), 2) }}</td>
                                </tr>
                                @if (($totals['menu_discount'] ?? 0) > 0)
                                <tr>
                                    <td style="padding: 6px 0;">Menu discount</td>
                                    <td align="right" style="padding: 6px 0; color: #059669; font-variant-numeric: tabular-nums;">−${{ number_format((float) $totals['menu_discount'], 2) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding: 6px 0;">Subtotal (after menu)</td>
                                    <td align="right" style="padding: 6px 0; color: #121827; font-variant-numeric: tabular-nums;">${{ number_format((float) ($totals['effective_subtotal'] ?? 0), 2) }}</td>
                                </tr>
                                @if (($totals['volume_discount'] ?? 0) > 0)
                                <tr>
                                    <td style="padding: 6px 0;">Volume discount</td>
                                    <td align="right" style="padding: 6px 0; color: #059669; font-variant-numeric: tabular-nums;">−${{ number_format((float) $totals['volume_discount'], 2) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding: 6px 0;">Taxable amount</td>
                                    <td align="right" style="padding: 6px 0; color: #121827; font-variant-numeric: tabular-nums;">${{ number_format((float) ($totals['taxable_subtotal'] ?? 0), 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0;">Tax</td>
                                    <td align="right" style="padding: 6px 0; color: #121827; font-variant-numeric: tabular-nums;">${{ number_format((float) ($totals['tax'] ?? 0), 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 0 6px; border-top: 2px solid #efe7ff; font-size: 16px; font-weight: 700; color: #121827;">Total paid</td>
                                    <td align="right" style="padding: 14px 0 6px; border-top: 2px solid #efe7ff; font-size: 16px; font-weight: 700; color: #121827; font-variant-numeric: tabular-nums;">${{ number_format((float) ($totals['total'] ?? 0), 2) }}</td>
                                </tr>
                            </table>

                            <p style="margin: 28px 0 0; color: #596f98; font-size: 14px; line-height: 1.65;">
                                If you have questions about this order, reply to this email or contact {{ config('app.name') }} support.
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
