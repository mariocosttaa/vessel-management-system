{{--
    Movement Report Template

    This template is specifically designed for displaying movement reports.
    It extends the base PDF layout and includes:
    - Period information with start/end dates
    - Summary section (only on first page)
    - Detailed movements table with pagination support

    Required Variables:
    - $vessel: Vessel model instance
    - $transactions: Collection of movements
    - $summary: Array with 'total_income', 'total_expenses', 'net_balance', 'total_count'
    - $period: String describing the period (optional)
    - $startDate: Start date for period (optional)
    - $endDate: End date for period (optional)
    - $title: Report title (default: 'Movement Report')
    - $subtitle: Report subtitle (optional)
    - $enableColors: Boolean to enable/disable colors (default: true)
--}}
@php
    // Set default value for enableColors if not provided (default to false - colors disabled)
    $enableColors = $enableColors ?? false;
@endphp

@extends('pdf.layouts.base')

@section('title', $title ?? 'Movement Report')

@section('content')
    @php
        use App\Actions\MoneyAction;
        use App\Models\VesselSetting;

        // Get default currency from vessel_settings first, then vessel currency_code, then EUR
        $vesselSetting = VesselSetting::getForVessel($vessel->id);
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

        // If movements exist, try to determine currency from movements
        // Use the most common currency among movements, or first movement's currency
        if (isset($transactions) && $transactions->count() > 0) {
            $currencies = $transactions->pluck('currency')->filter()->unique();
            if ($currencies->count() === 1) {
                // All transactions use the same currency, use that
                $defaultCurrency = $currencies->first();
            } elseif ($currencies->count() > 1) {
                // Multiple currencies - use the most common one
                $currencyCounts = $transactions->pluck('currency')->filter()->countBy();
                $defaultCurrency = $currencyCounts->sortDesc()->keys()->first() ?? $defaultCurrency;
            }
        }
    @endphp

    <div class="report-content">
        {{-- Period Information --}}
        @if(isset($period))
            <div class="period-section">
                <p class="period-text">
                    @if(isset($startDate) && isset($endDate))
                        <strong>{{ trans('pdfs.Period:') }}</strong> {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                    @elseif(isset($transactions) && $transactions->count() > 0)
                        @php
                            $firstDate = $transactions->min('transaction_date');
                            $lastDate = $transactions->max('transaction_date');
                        @endphp
                        <strong>{{ trans('pdfs.Period:') }}</strong> {{ \Carbon\Carbon::parse($firstDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($lastDate)->format('d/m/Y') }}
                    @else
                        <strong>{{ trans('pdfs.Period:') }}</strong> {{ $period }}
                    @endif
                </p>
            </div>
        @endif

        {{-- Summary Section - Horizontal Layout (Only on first page) --}}
        @if(isset($summary))
            <div class="summary-section">
                <h3 class="section-title">{{ trans('pdfs.Summary') }}</h3>
                <table class="summary-table">
                    <tr>
                        <td class="summary-cell">
                            <div class="summary-label">{{ trans('pdfs.Total Income') }}</div>
                            <div class="summary-value">
                                {{ MoneyAction::format($summary['total_income'] ?? 0, 2, $defaultCurrency, true) }}
                            </div>
                        </td>
                        <td class="summary-cell">
                            <div class="summary-label">{{ trans('pdfs.Total Expenses') }}</div>
                            <div class="summary-value">
                                {{ MoneyAction::format($summary['total_expenses'] ?? 0, 2, $defaultCurrency, true) }}
                            </div>
                        </td>
                        <td class="summary-cell">
                            <div class="summary-label">{{ trans('pdfs.Net Balance') }}</div>
                            <div class="summary-value">
                                {{ MoneyAction::format($summary['net_balance'] ?? 0, 2, $defaultCurrency, true) }}
                            </div>
                        </td>
                        <td class="summary-cell">
                            <div class="summary-label">{{ trans('pdfs.Total Movements') }}</div>
                            <div class="summary-value">
                                {{ $summary['total_count'] ?? 0 }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        {{-- Movements Table --}}
        @if(isset($transactions) && $transactions->count() > 0)
            <div class="movements-header">
                <h3 class="section-title">{{ trans('pdfs.Movements') }}</h3>
            </div>
            <table class="movements-table">
                <thead>
                    <tr>
                        <th class="col-date">{{ trans('pdfs.Date') }}</th>
                        <th class="col-description">{{ trans('pdfs.Description') }}</th>
                        <th class="col-category">{{ trans('pdfs.Category') }}</th>
                        <th class="col-amount">{{ trans('pdfs.Amount') }}</th>
                        <th class="col-type">{{ trans('pdfs.Type') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $movement)
                        @php
                            $amountValue = $movement->total_amount ?? $movement->amount;
                            $houseOfZeros = $movement->house_of_zeros ?? 2;
                            // Use movement's own currency, fallback to default currency
                            $movementCurrency = $movement->currency ?? $defaultCurrency;
                            $amount = MoneyAction::format($amountValue, $houseOfZeros, $movementCurrency, true);

                            // Use the enableColors variable set at the top of the template

                            // Determine sign and color based on movement type
                            $sign = '';
                            $amountClass = 'amount-neutral';
                            if ($movement->type === 'income') {
                                $sign = '+';
                                $amountClass = $enableColors ? 'amount-income' : 'amount-neutral';
                            } elseif ($movement->type === 'expense') {
                                $sign = '-';
                                $amountClass = $enableColors ? 'amount-expense' : 'amount-neutral';
                            }

                            // Format amount with sign and space
                            $formattedAmount = $sign ? $sign . ' ' . $amount : $amount;
                        @endphp
                        <tr>
                            <td class="col-date">{{ $movement->transaction_date->format('d/m/Y') }}</td>
                            <td class="col-description">{{ $movement->description ?? '-' }}</td>
                            <td class="col-category">{{ $movement->category->translated_name ?? '-' }}</td>
                            <td class="col-amount {{ $amountClass }}">
                                {{ $formattedAmount }}
                            </td>
                            <td class="col-type">{{ trans('pdfs.' . ucfirst($movement->type)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">
                <p>{{ trans('pdfs.No movements found for the selected period.') }}</p>
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <style>
        /* Report Content Container */
        .report-content {
            margin-top: 5mm; /* Gap from header on first page */
            padding: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            page-break-after: avoid;
            page-break-inside: avoid;
            background-color: #fff;
        }

        /* Force all child elements to have no left margin/padding */
        .report-content > * {
            margin-left: 0 !important;
            padding-left: 0 !important;
        }

        /* Period Section */
        .period-section {
            margin-top: 2px; /* Reduced gap to be closer to subtitle */
            margin-bottom: 10px;
            background-color: #fff;
            padding: 0 !important;
            margin-left: 0 !important;
        }

        .period-text {
            margin: 0 !important;
            padding: 0 !important;
            font-size: 10px;
            color: #000;
            font-weight: 600;
            letter-spacing: 0.05em;
            line-height: 1.6;
            background-color: #fff;
            text-align: left;
        }

        /* Summary Section */
        .summary-section {
            margin-top: 8px;
            margin-bottom: 14px;
            page-break-after: avoid;
            page-break-inside: avoid;
            background-color: #fff;
            padding: 0 !important;
            margin-left: 0 !important;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            table-layout: fixed;
            background-color: #fff;
        }

        .summary-cell {
            width: 25%;
            padding: 0 15px 0 0;
            vertical-align: top;
            border: none;
            background-color: #fff;
        }

        .summary-cell:last-child {
            padding: 0 0 0 15px;
        }

        .summary-label {
            font-size: 10px;
            color: #000;
            margin-bottom: 8px;
            letter-spacing: 0.05em;
            line-height: 1.4;
            background-color: #fff;
            text-align: left;
            font-weight: 600;
        }

        .summary-value {
            font-size: 12px;
            color: #000;
            font-weight: 700;
            letter-spacing: 0.05em;
            line-height: 1.4;
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            background-color: #fff;
        }

        /* Movements Section */
        .movements-header {
            margin-top: 5px;
            page-break-after: avoid;
            background-color: #fff;
        }

        .section-title {
            color: #000;
            font-size: 14px;
            margin: 0 0 10px 0 !important;
            padding: 0 !important;
            font-weight: bold;
            letter-spacing: 0.05em;
            line-height: 1.4;
            background-color: #fff;
            text-align: left;
        }

        .movements-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 5px;
            page-break-inside: auto;
            background-color: #fff;
        }

        .movements-table thead {
            display: table-header-group;
            page-break-after: avoid;
            background-color: #fff;
        }

        /* Extra spacing for table header when it appears on a new page */
        .movements-table thead tr:first-child th {
            padding-top: 30px !important; /* More padding on new pages to prevent header overlap */
        }

        .movements-table thead tr {
            background-color: #fff;
            page-break-after: avoid;
        }

        .movements-table th {
            padding: 20px 8px 10px 8px; /* Increased top padding to prevent header overlap */
            font-weight: bold;
            font-size: 10px;
            color: #000;
            border-bottom: 1px solid #ddd;
            letter-spacing: 0.05em;
            background-color: #fff;
        }

        .movements-table tbody {
            background-color: #fff;
        }

        .movements-table tbody tr {
            page-break-inside: auto;
            background-color: #fff;
        }

        .movements-table td {
            padding: 8px 6px;
            font-size: 10px;
            color: #000;
            letter-spacing: 0.05em;
            word-spacing: 0.05em;
            border-bottom: 1px solid #ddd;
            background-color: #fff;
        }

        /* Column Widths */
        .col-date {
            width: 12%;
            text-align: left;
        }

        .col-description {
            width: 28%;
            text-align: left;
            word-wrap: break-word;
        }

        .col-category {
            width: 25%;
            text-align: left;
            word-wrap: break-word;
        }

        .col-amount {
            width: 20%;
            text-align: right;
            font-size: 11px;
            font-weight: 700;
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
        }

        /* Amount colors - Use RGB for better DomPDF support */
        .amount-income {
            color: rgb(34, 197, 94) !important; /* Green for income - more vibrant (#22c55e) */
        }

        .amount-expense {
            color: rgb(239, 68, 68) !important; /* Red for expense (#ef4444) */
        }

        .amount-neutral {
            color: rgb(0, 0, 0) !important; /* Black for transfers/neutral */
        }

        .col-type {
            width: 15%;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        /* Empty State */
        .empty-state {
            padding: 40px;
            text-align: center;
            color: #000;
        }

        .empty-state p {
            font-size: 14px;
        }
    </style>
@endpush

