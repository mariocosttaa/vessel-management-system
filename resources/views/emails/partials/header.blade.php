@php
    $vessel = $vessel ?? null;
    $vesselLogoUrl = $vessel && $vessel->logo ? $vessel->logo_url : null;
@endphp

<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ffffff;">
    <tr>
        <td style="padding: 30px 40px 20px 40px;">
            <!-- Logo and Company Name -->
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td align="left" valign="middle" width="{{ $vesselLogoUrl ? '50%' : '100%' }}">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td align="left" valign="middle">
                                    <img src="{{ asset('bindamy-marea-logo-light.png') }}" alt="{{ config('app.name', 'Bindamy Mareas') }}" style="max-width: 200px; height: auto; display: block;" />
                                </td>
                            </tr>
                        </table>
                    </td>
                    @if($vesselLogoUrl)
                        <td align="right" valign="middle" width="50%">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="right">
                                <tr>
                                    <td align="right" valign="middle">
                                        <img src="{{ $vesselLogoUrl }}" alt="{{ $vessel->name ?? 'Vessel Logo' }}" style="max-width: 150px; height: auto; display: block;" />
                                    </td>
                                </tr>
                            </table>
                        </td>
                    @endif
                </tr>
            </table>
        </td>
    </tr>
</table>

