<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ffffff;">
    <tr>
        <td align="center" style="padding: 40px 40px 30px 40px;">
            <!-- Separator Line -->
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="border-top: 1px solid #e5e7eb; padding-bottom: 30px;"></td>
                </tr>
            </table>

            <!-- Footer Content -->
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <!-- Logo -->
                <tr>
                    <td align="center" style="padding-bottom: 24px;">
                        <img src="{{ asset('bindamy-marea-logo-light.png') }}" alt="{{ config('app.name', 'Bindamy Mareas') }}" style="max-width: 180px; height: auto; display: block;" />
                    </td>
                </tr>

                <!-- Privacy Policy and Terms Links -->
                <tr>
                    <td align="center" style="padding-bottom: 24px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto;">
                            <tr>
                                <td align="center">
                                    <a href="{{ route('privacy-policy') }}" style="color: #6b7280; text-decoration: none; font-size: 14px; font-weight: 700; padding-right: 12px;">
                                        {{ trans('emails.Privacy Policy') }}
                                    </a>
                                    <span style="color: #d1d5db; font-size: 14px; padding: 0 8px;">|</span>
                                    <a href="{{ route('terms-of-service') }}" style="color: #6b7280; text-decoration: none; font-size: 14px; font-weight: 700; padding-left: 12px;">
                                        {{ trans('emails.Terms of Service') }}
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Message -->
                <tr>
                    <td align="center">
                        <p style="margin: 0; padding: 0; font-size: 12px; color: #6b7280; line-height: 1.6; max-width: 500px;">
                            @php
                                $emailLink = '<a href="mailto:geral@mareas.bindamy.site" style="color: #6b7280; text-decoration: none; font-weight: 700;">geral@mareas.bindamy.site</a>';
                            @endphp
                            {!! trans('emails.This is an automatic message from the :app system. If you do not wish to receive these messages, you can change your preferences in your profile settings or contact us at :email.', [
                                'app' => config('app.name', 'Bindamy Mareas'),
                                'email' => $emailLink
                            ]) !!}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

