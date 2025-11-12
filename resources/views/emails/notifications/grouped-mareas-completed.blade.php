@extends('emails.layouts.default')

@section('content')
    <!-- Main Title -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding-bottom: 32px;">
                <h1 style="margin: 0; padding: 0; font-size: 28px; font-weight: 700; color: #111827; letter-spacing: -0.5px; line-height: 1.2; text-align: center;">
                    @if($count > 1)
                        {{ $count }} Embarcações Retornaram da Marea
                    @else
                        Embarcação Retornou da Marea
                    @endif
                </h1>
            </td>
        </tr>
    </table>

    <!-- Main Content -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding-bottom: 24px;">
                <p style="margin: 0; padding: 0; font-size: 16px; color: #374151; line-height: 1.6; text-align: center; max-width: 500px;">
                    Olá {{ $user->name }},
                </p>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding-bottom: 32px;">
                <p style="margin: 0; padding: 0; font-size: 16px; color: #374151; line-height: 1.6; text-align: center; max-width: 500px;">
                    @if($count > 1)
                        {{ $count }} mareas foram concluídas no sistema.
                    @else
                        Uma marea foi concluída no sistema.
                    @endif
                </p>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding-bottom: 24px;">
                <p style="margin: 0; padding: 0; font-size: 14px; color: #6b7280; line-height: 1.6; text-align: center; max-width: 500px;">
                    <strong style="color: #374151;">Embarcação:</strong> {{ $vessel->name ?? 'N/A' }}
                </p>
            </td>
        </tr>
    </table>

    <!-- Mareas List -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0 0 32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 500px;">
                    @foreach($notifications as $notification)
                    <tr>
                        <td style="padding-bottom: @if(!$loop->last) 16px; @else 0; @endif">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f9fafb; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            @if(isset($notification->subject_data['name']))
                                            <tr>
                                                <td style="padding-bottom: 12px;">
                                                    <p style="margin: 0; padding: 0; font-size: 14px; font-weight: 600; color: #111827; line-height: 1.4;">
                                                        {{ $notification->subject_data['name'] }}
                                                    </p>
                                                </td>
                                            </tr>
                                            @endif
                                            @if(isset($notification->subject_data['started_at']))
                                            <tr>
                                                <td style="padding-bottom: 8px;">
                                                    <p style="margin: 0; padding: 0; font-size: 13px; color: #6b7280; line-height: 1.4;">
                                                        <strong>Data de Partida:</strong>
                                                        {{ \Carbon\Carbon::parse($notification->subject_data['started_at'])->format('d/m/Y H:i') }}
                                                    </p>
                                                </td>
                                            </tr>
                                            @endif
                                            @if(isset($notification->subject_data['returned_at']))
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 13px; color: #6b7280; line-height: 1.4;">
                                                        <strong>Data de Retorno:</strong>
                                                        {{ \Carbon\Carbon::parse($notification->subject_data['returned_at'])->format('d/m/Y H:i') }}
                                                    </p>
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endforeach
                </table>
            </td>
            </tr>
    </table>

    <!-- Action Button -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0 0 32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="background-color: #111827; border-radius: 8px;">
                            <a href="{{ route('panel.mareas.index', ['vessel' => $vessel->id]) }}" style="display: inline-block; padding: 16px 40px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px; letter-spacing: -0.1px;">
                                Ver Mareas
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Note -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding-top: 8px;">
                <p style="margin: 0; padding: 0; font-size: 14px; color: #6b7280; line-height: 1.5; text-align: center; max-width: 500px;">
                    A(s) embarcação(ões) retornou(ram) com sucesso da marea. Pode agora fechar a(s) marea(s) quando estiver pronto.
                    @if($count > 1)
                        As últimas {{ $count }} mareas concluídas são mostradas acima.
                    @endif
                </p>
            </td>
        </tr>
    </table>
@endsection

