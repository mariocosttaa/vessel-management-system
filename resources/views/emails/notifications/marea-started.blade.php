@extends('emails.layouts.default')

@section('content')
    <!-- Main Title -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding-bottom: 32px;">
                <h1 style="margin: 0; padding: 0; font-size: 28px; font-weight: 700; color: #111827; letter-spacing: -0.5px; line-height: 1.2; text-align: center;">
                    Embarcação em Marea
                </h1>
            </td>
        </tr>
    </table>

    <!-- Main Content -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding-bottom: 24px;">
                <p style="margin: 0; padding: 0; font-size: 16px; color: #374151; line-height: 1.6; text-align: center; max-width: 500px;">
                    Olá,
                </p>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding-bottom: 32px;">
                <p style="margin: 0; padding: 0; font-size: 16px; color: #374151; line-height: 1.6; text-align: center; max-width: 500px;">
                    A embarcação partiu para marea. Abaixo estão os detalhes:
                </p>
            </td>
        </tr>
    </table>

    <!-- Details Section -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0 0 32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 500px; background-color: #f9fafb; border-radius: 8px;">
                    <tr>
                        <td style="padding: 24px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                @if(isset($marea) && $marea->name)
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        Nome da Marea
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        {{ $marea->name }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endif
                                @if(isset($vessel))
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        Embarcação
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        {{ $vessel->name ?? $vessel }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endif
                                @if(isset($startedAt))
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        Data de Partida
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        {{ \Carbon\Carbon::parse($startedAt)->format('d/m/Y H:i') }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @elseif(isset($marea) && $marea->started_at)
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        Data de Partida
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        {{ \Carbon\Carbon::parse($marea->started_at)->format('d/m/Y H:i') }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endif
                                @if(isset($marea) && $marea->expected_return_date)
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        Data Prevista de Retorno
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        {{ \Carbon\Carbon::parse($marea->expected_return_date)->format('d/m/Y') }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endif
                                @if(isset($marea) && $marea->crew && $marea->crew->count() > 0)
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        Tripulação
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        {{ $marea->crew->count() }} membro(s)
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        Iniciado Por
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        {{ $user->name ?? $user ?? 'Sistema' }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Action Button -->
    @if(isset($actionUrl))
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0 0 32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="background-color: #111827; border-radius: 8px;">
                            <a href="{{ $actionUrl }}" style="display: inline-block; padding: 16px 40px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px; letter-spacing: -0.1px;">
                                {{ $actionText ?? 'Ver Marea' }}
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    @endif

    <!-- Note -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding-top: 8px;">
                <p style="margin: 0; padding: 0; font-size: 14px; color: #6b7280; line-height: 1.5; text-align: center; max-width: 500px;">
                    A embarcação está agora em marea. Receberá uma notificação quando retornar.
                </p>
            </td>
        </tr>
    </table>
@endsection

