@extends('emails.layouts.default')

@section('content')
    <!-- Main Title -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding-bottom: 32px;">
                <h1 style="margin: 0; padding: 0; font-size: 28px; font-weight: 700; color: #111827; letter-spacing: -0.5px; line-height: 1.2; text-align: center;">
                    {{ trans('emails.Movement Created', [], $locale ?? 'en') }}
                </h1>
            </td>
        </tr>
    </table>

    <!-- Main Content -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding-bottom: 24px;">
                <p style="margin: 0; padding: 0; font-size: 16px; color: #374151; line-height: 1.6; text-align: center; max-width: 500px;">
                    {{ trans('emails.Hello', [], $locale ?? 'en') }},
                </p>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding-bottom: 32px;">
                <p style="margin: 0; padding: 0; font-size: 16px; color: #374151; line-height: 1.6; text-align: center; max-width: 500px;">
                    {{ trans('emails.A new movement has been created in the system. Details are below:', [], $locale ?? 'en') }}
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
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        {{ trans('emails.Movement Number', [], $locale ?? 'en') }}
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        {{ $movement->transaction_number ?? trans('emails.N/A', [], $locale ?? 'en') }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        {{ trans('emails.Type', [], $locale ?? 'en') }}
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        @if($movement->type === 'add')
                                                            {{ trans('emails.Income', [], $locale ?? 'en') }}
                                                        @else
                                                            {{ trans('emails.Expense', [], $locale ?? 'en') }}
                                                        @endif
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        {{ trans('emails.Amount', [], $locale ?? 'en') }}
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        {{ $movement->currency->symbol ?? '€' }}{{ number_format($movement->amount / 100, 2, ',', '.') }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @if($movement->category)
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        {{ trans('emails.Category', [], $locale ?? 'en') }}
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        {{ $movement->category->name }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endif
                                @if($movement->description)
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        {{ trans('emails.Description', [], $locale ?? 'en') }}
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        {{ $movement->description }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endif
                                @if($vessel)
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 6px;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        {{ trans('emails.Vessel', [], $locale ?? 'en') }}
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        {{ $vessel->name }}
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
                                                        {{ trans('emails.Created by', [], $locale ?? 'en') }}
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; padding: 0; font-size: 15px; color: #111827; line-height: 1.5;">
                                                        {{ $user->name ?? trans('emails.System', [], $locale ?? 'en') }}
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
                                {{ $actionText ?? trans('emails.View Movement', [], $locale ?? 'en') }}
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    @endif
@endsection

