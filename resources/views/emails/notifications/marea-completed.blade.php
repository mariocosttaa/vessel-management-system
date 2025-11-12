@extends('emails.layouts.default')

@section('content')
    <!-- Main Title -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding-bottom: 32px;">
                <h1 style="margin: 0; padding: 0; font-size: 28px; font-weight: 700; color: #111827; letter-spacing: -0.5px; line-height: 1.2; text-align: center;">
                    Embarcação Retornou da Marea
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
                    A embarcação retornou da marea. Abaixo estão os detalhes:
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
                                @if(isset($marea) && $marea->started_at)
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
                                @if(isset($returnedAt))
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        Data de Retorno
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        {{ \Carbon\Carbon::parse($returnedAt)->format('d/m/Y H:i') }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @elseif(isset($marea) && $marea->returned_at)
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        Data de Retorno
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        {{ \Carbon\Carbon::parse($marea->returned_at)->format('d/m/Y H:i') }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endif
                                @if(isset($marea) && $marea->started_at && ($marea->returned_at || $returnedAt))
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        Duração
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        @php
                                                            $started = \Carbon\Carbon::parse($marea->started_at);
                                                            $returned = \Carbon\Carbon::parse($marea->returned_at ?? $returnedAt);
                                                            $duration = $started->diffForHumans($returned, true);
                                                        @endphp
                                                        {{ $duration }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endif
                                @if(isset($marea) && $marea->quantity_returns && $marea->quantity_returns->count() > 0)
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        Quantidades Retornadas
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        {{ $marea->quantity_returns->count() }} tipo(s) de quantidade
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
                                                        Marcado Como Retornado Por
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
                    A embarcação retornou com sucesso da marea. Pode agora fechar a marea quando estiver pronto.
                </p>
            </td>
        </tr>
    </table>
@endsection

