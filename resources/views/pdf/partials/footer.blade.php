@php
    $systemName = config('app.name', 'Vessel Management System');
@endphp

<div style="padding: 0; margin: 0; width: 100%;">
    <table style="width: 100%; border: none; margin: 0; padding: 0; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; vertical-align: top; border: none; font-size: 8px; color: #000; padding: 0; margin: 0;">
                <p style="margin: 0; padding: 0; letter-spacing: 0.02em; line-height: 1.2;">
                    <strong>{{ $systemName }}</strong><br>
                    © {{ date('Y') }} All rights reserved
                </p>
            </td>
            <td style="width: 50%; vertical-align: top; text-align: right; border: none; font-size: 8px; color: #000; padding: 0; margin: 0;">
                <p style="margin: 0; padding: 0; letter-spacing: 0.02em; line-height: 1.2;">
                    <span class="page-number">Page 1</span><br>
                    Generated on {{ now()->format('d/m/Y H:i') }}
                </p>
            </td>
        </tr>
    </table>
</div>

