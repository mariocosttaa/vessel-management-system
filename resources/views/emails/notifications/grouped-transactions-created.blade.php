@extends('emails.layouts.default')

@section('content')
    <!-- Main Title -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding-bottom: 32px;">
                <h1 style="margin: 0; padding: 0; font-size: 28px; font-weight: 700; color: #111827; letter-spacing: -0.5px; line-height: 1.2; text-align: center;">
                    @if($count > 1)
                        {{ $count }} {{ trans('emails.Transactions Created', [], $locale ?? 'en') }}
                    @else
                        {{ trans('emails.Transaction Created', [], $locale ?? 'en') }}
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
                    {{ trans('emails.Hello :name', ['name' => $user->name], $locale ?? 'en') }},
                </p>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding-bottom: 32px;">
                <p style="margin: 0; padding: 0; font-size: 16px; color: #374151; line-height: 1.6; text-align: center; max-width: 500px;">
                    {{ trans('emails.New transactions have been created for vessel :vessel', [
                        'vessel' => $vessel->name ?? 'N/A'
                    ], $locale ?? 'en') }}
                </p>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding-bottom: 24px;">
                <p style="margin: 0; padding: 0; font-size: 14px; color: #6b7280; line-height: 1.6; text-align: center; max-width: 500px;">
                    <strong style="color: #374151;">{{ trans('emails.Vessel', [], $locale ?? 'en') }}:</strong> {{ $vessel->name ?? 'N/A' }}
                </p>
            </td>
        </tr>
    </table>

    <!-- Transactions List -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0 0 32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 500px;">
                    @foreach($notifications as $index => $notification)
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
                                                        <strong>{{ trans('emails.Type', [], $locale ?? 'en') }}:</strong>
                                                        @if(($notification->subject_data['type'] ?? '') === 'add')
                                                            {{ trans('emails.Income', [], $locale ?? 'en') }}
                                                        @else
                                                            {{ trans('emails.Expense', [], $locale ?? 'en') }}
                                                        @endif
                                                    </p>
                                                </td>
                                            </tr>
                                            @if(isset($notification->subject_data['amount']))
                                            <tr>
                                                <td style="padding-bottom: 8px;">
                                                    <p style="margin: 0; padding: 0; font-size: 13px; color: #6b7280; line-height: 1.4;">
                                                        <strong>{{ trans('emails.Amount', [], $locale ?? 'en') }}:</strong>
                                                        {{ $notification->subject_data['currency_symbol'] ?? '€' }}{{ number_format($notification->subject_data['amount'] / 100, 2, ',', '.') }}
                                                    </p>
                                                </td>
                                            </tr>
                                            @endif
                                            @if(isset($notification->subject_data['description']))
                                            <tr>
                                                <td style="padding-bottom: 8px;">
                                                    <p style="margin: 0; padding: 0; font-size: 13px; color: #6b7280; line-height: 1.4;">
                                                        <strong>{{ trans('emails.Description', [], $locale ?? 'en') }}:</strong> {{ Str::limit($notification->subject_data['description'], 50) }}
                                                    </p>
                                                </td>
                                            </tr>
                                            @endif
                                            @if($notification->actionByUser)
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 13px; color: #6b7280; line-height: 1.4;">
                                                        <strong>{{ trans('emails.Created by', [], $locale ?? 'en') }}:</strong> {{ $notification->actionByUser->name }}
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
                            <a href="{{ route('panel.movimentations.index', ['vessel' => $vessel->id]) }}" style="display: inline-block; padding: 16px 40px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px; letter-spacing: -0.1px;">
                                {{ trans('emails.View Transactions', [], $locale ?? 'en') }}
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
                    @if($count > 1)
                        {{ trans('emails.These notifications have been grouped to avoid spam. The last :count transactions created are shown above.', ['count' => $count], $locale ?? 'en') }}
                    @endif
                </p>
            </td>
        </tr>
    </table>
@endsection

