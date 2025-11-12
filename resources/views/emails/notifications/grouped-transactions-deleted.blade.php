@extends('emails.layouts.default')

@section('content')
    <!-- Main Title -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding-bottom: 32px;">
                <h1 style="margin: 0; padding: 0; font-size: 28px; font-weight: 700; color: #111827; letter-spacing: -0.5px; line-height: 1.2; text-align: center;">
                    @if($count > 1)
                        {{ $count }} Transações Removidas
                    @else
                        Transação Removida
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
                        {{ $count }} transações foram removidas do sistema.
                    @else
                        Uma transação foi removida do sistema.
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

    <!-- Transactions List -->
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
                                            <tr>
                                                <td style="padding-bottom: 12px;">
                                                    <p style="margin: 0; padding: 0; font-size: 14px; font-weight: 600; color: #111827; line-height: 1.4;">
                                                        {{ $notification->subject_data['transaction_number'] ?? 'N/A' }}
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-bottom: 8px;">
                                                    <p style="margin: 0; padding: 0; font-size: 13px; color: #6b7280; line-height: 1.4;">
                                                        <strong>Tipo:</strong>
                                                        @if(($notification->subject_data['type'] ?? '') === 'add')
                                                            Receita
                                                        @else
                                                            Despesa
                                                        @endif
                                                    </p>
                                                </td>
                                            </tr>
                                            @if(isset($notification->subject_data['amount']))
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 13px; color: #6b7280; line-height: 1.4;">
                                                        <strong>Valor:</strong>
                                                        {{ $notification->subject_data['currency_symbol'] ?? '€' }}{{ number_format($notification->subject_data['amount'] / 100, 2, ',', '.') }}
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

    <!-- Note -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding-top: 8px;">
                <p style="margin: 0; padding: 0; font-size: 14px; color: #6b7280; line-height: 1.5; text-align: center; max-width: 500px;">
                    <strong style="color: #374151;">Nota:</strong> Estas transações foram permanentemente removidas do sistema e não podem ser recuperadas.
                    @if($count > 1)
                        As últimas {{ $count }} transações removidas são mostradas acima.
                    @endif
                </p>
            </td>
        </tr>
    </table>
@endsection

