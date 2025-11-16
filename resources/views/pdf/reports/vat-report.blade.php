{{--
    VAT Report Template

    This template displays VAT reports for a specific month and year.
    It includes:
    - Summary section (total VAT, base amount, total with VAT, transaction count)
    - VAT Profile breakdown
    - Category breakdown
    - Daily breakdown table
    - Marea breakdown
    - Transactions list

    Required Variables:
    - $vessel: Vessel model instance
    - $year: Year (e.g., 2025)
    - $month: Month (1-12)
    - $monthLabel: Month name (e.g., "November")
    - $defaultCurrency: Currency code
    - $summary: Array with VAT summary
    - $vatProfileBreakdown: Collection of VAT profile breakdown data
    - $categoryBreakdown: Collection of category breakdown data
    - $dailyBreakdown: Collection of daily breakdown data
    - $mareaBreakdown: Collection of marea breakdown data
    - $transactions: Collection of transaction data
    - $title: Report title (default: 'VAT Report')
    - $subtitle: Report subtitle
--}}

@extends('pdf.layouts.base')

@section('title', $title ?? 'VAT Report')

@section('content')
    @php
        use App\Actions\MoneyAction;
        use App\Models\VesselSetting;

        // Set default value for enableColors if not provided (default to false - colors disabled)
        $enableColors = $enableColors ?? false;

        // Get currency and house of zeros
        $vesselSetting = VesselSetting::getForVessel($vessel->id);
        $houseOfZeros = $vesselSetting->house_of_zeros ?? 2;
    @endphp

    <div class="report-content">
        {{-- Summary Section --}}
        @if(isset($summary))
            <div class="summary-section">
                <h3 class="section-title">{{ trans('pdfs.Summary') }}</h3>
                <table class="summary-table">
                    <tr>
                        <td class="summary-cell">
                            <div class="summary-label">{{ trans('pdfs.Total VAT') }}</div>
                            <div class="summary-value {{ $enableColors ? 'amount-income' : '' }}">
                                {{ MoneyAction::format($summary['total_vat'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                            </div>
                            @if(isset($summary['vat_change']))
                                <div class="summary-change">
                                    {{ $summary['vat_change'] > 0 ? '+' : '' }}{{ number_format($summary['vat_change'], 1) }}%
                                </div>
                            @endif
                        </td>
                        <td class="summary-cell">
                            <div class="summary-label">{{ trans('pdfs.Base Amount') }}</div>
                            <div class="summary-value">
                                {{ MoneyAction::format($summary['total_base_amount'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                            </div>
                            @if(isset($summary['base_change']))
                                <div class="summary-change">
                                    {{ $summary['base_change'] > 0 ? '+' : '' }}{{ number_format($summary['base_change'], 1) }}%
                                </div>
                            @endif
                        </td>
                        <td class="summary-cell">
                            <div class="summary-label">{{ trans('pdfs.Total with VAT') }}</div>
                            <div class="summary-value {{ $enableColors ? 'amount-income' : '' }}">
                                {{ MoneyAction::format($summary['total_amount_with_vat'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                            </div>
                        </td>
                        <td class="summary-cell">
                            <div class="summary-label">{{ trans('pdfs.Transactions') }}</div>
                            <div class="summary-value">
                                {{ $summary['transaction_count'] ?? 0 }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        {{-- VAT Profile Breakdown Section --}}
        @if(isset($vatProfileBreakdown) && $vatProfileBreakdown->count() > 0)
            <div class="vat-profile-section">
                <h3 class="section-title">{{ trans('pdfs.VAT by Profile') }}</h3>
                <table class="vat-profile-table">
                    <thead>
                        <tr>
                            <th class="col-profile-name">{{ trans('pdfs.VAT Profile') }}</th>
                            <th class="col-profile-percentage">{{ trans('pdfs.Percentage') }}</th>
                            <th class="col-profile-base">{{ trans('pdfs.Base Amount') }}</th>
                            <th class="col-profile-vat">{{ trans('pdfs.VAT') }}</th>
                            <th class="col-profile-total">{{ trans('pdfs.Total') }}</th>
                            <th class="col-profile-count">{{ trans('pdfs.Count') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vatProfileBreakdown as $profile)
                            <tr>
                                <td class="col-profile-name">
                                    {{ $profile['vat_profile_name'] ?? trans('pdfs.Unknown') }}
                                    @if(isset($profile['vat_profile_code']) && $profile['vat_profile_code'])
                                        <br><span class="text-xs text-muted-foreground">({{ $profile['vat_profile_code'] }})</span>
                                    @endif
                                    @if(isset($profile['country']) && $profile['country'])
                                        <br><span class="text-xs text-muted-foreground">{{ $profile['country']['name'] ?? '' }}</span>
                                    @endif
                                </td>
                                <td class="col-profile-percentage">{{ number_format($profile['vat_profile_percentage'] ?? 0, 2) }}%</td>
                                <td class="col-profile-base">
                                    {{ MoneyAction::format($profile['total_base_amount'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-profile-vat {{ $enableColors ? 'amount-income' : '' }}">
                                    {{ MoneyAction::format($profile['total_vat_amount'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-profile-total {{ $enableColors ? 'amount-income' : '' }}">
                                    {{ MoneyAction::format($profile['total_amount_with_vat'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-profile-count">{{ $profile['transaction_count'] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Category Breakdown Section --}}
        @if(isset($categoryBreakdown) && $categoryBreakdown->count() > 0)
            <div class="category-section">
                <h3 class="section-title">{{ trans('pdfs.VAT by Category') }}</h3>
                <table class="category-table">
                    <thead>
                        <tr>
                            <th class="col-category-name">{{ trans('pdfs.Category') }}</th>
                            <th class="col-category-base">{{ trans('pdfs.Base Amount') }}</th>
                            <th class="col-category-vat">{{ trans('pdfs.VAT') }}</th>
                            <th class="col-category-total">{{ trans('pdfs.Total') }}</th>
                            <th class="col-category-percentage">{{ trans('pdfs.Percentage') }}</th>
                            <th class="col-category-count">{{ trans('pdfs.Count') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categoryBreakdown as $cat)
                            @php
                                $percentage = $summary['total_vat'] > 0
                                    ? (($cat['total_vat_amount'] / $summary['total_vat']) * 100)
                                    : 0;
                            @endphp
                            <tr>
                                <td class="col-category-name">{{ $cat['category_name'] ?? trans('pdfs.Uncategorized') }}</td>
                                <td class="col-category-base">
                                    {{ MoneyAction::format($cat['total_base_amount'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-category-vat {{ $enableColors ? 'amount-income' : '' }}">
                                    {{ MoneyAction::format($cat['total_vat_amount'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-category-total {{ $enableColors ? 'amount-income' : '' }}">
                                    {{ MoneyAction::format($cat['total_amount_with_vat'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-category-percentage">{{ number_format($percentage, 1) }}%</td>
                                <td class="col-category-count">{{ $cat['transaction_count'] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Daily Breakdown Section --}}
        @if(isset($dailyBreakdown) && $dailyBreakdown->count() > 0)
            <div class="daily-section">
                <h3 class="section-title">{{ trans('pdfs.Daily VAT Breakdown') }}</h3>
                <table class="daily-table">
                    <thead>
                        <tr>
                            <th class="col-date">{{ trans('pdfs.Date') }}</th>
                            <th class="col-daily-base">{{ trans('pdfs.Base Amount') }}</th>
                            <th class="col-daily-vat">{{ trans('pdfs.VAT') }}</th>
                            <th class="col-daily-total">{{ trans('pdfs.Total') }}</th>
                            <th class="col-daily-count">{{ trans('pdfs.Transactions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dailyBreakdown as $day)
                            <tr>
                                <td class="col-date">{{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}</td>
                                <td class="col-daily-base">
                                    {{ MoneyAction::format($day['base_amount'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-daily-vat {{ $enableColors ? 'amount-income' : '' }}">
                                    {{ MoneyAction::format($day['vat_amount'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-daily-total {{ $enableColors ? 'amount-income' : '' }}">
                                    {{ MoneyAction::format($day['total_amount'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-daily-count">{{ $day['count'] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Marea Breakdown Section --}}
        @if(isset($mareaBreakdown) && $mareaBreakdown->count() > 0)
            <div class="marea-section">
                <h3 class="section-title">{{ trans('pdfs.VAT by Marea') }}</h3>
                <table class="marea-table">
                    <thead>
                        <tr>
                            <th class="col-marea-number">{{ trans('pdfs.Marea Number') }}</th>
                            <th class="col-marea-name">{{ trans('pdfs.Name') }}</th>
                            <th class="col-marea-base">{{ trans('pdfs.Base Amount') }}</th>
                            <th class="col-marea-vat">{{ trans('pdfs.VAT') }}</th>
                            <th class="col-marea-total">{{ trans('pdfs.Total') }}</th>
                            <th class="col-marea-count">{{ trans('pdfs.Transactions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mareaBreakdown as $marea)
                            <tr>
                                <td class="col-marea-number">{{ $marea['marea_number'] ?? '-' }}</td>
                                <td class="col-marea-name">{{ $marea['marea_name'] ?? '-' }}</td>
                                <td class="col-marea-base">
                                    {{ MoneyAction::format($marea['total_base_amount'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-marea-vat {{ $enableColors ? 'amount-income' : '' }}">
                                    {{ MoneyAction::format($marea['total_vat_amount'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-marea-total {{ $enableColors ? 'amount-income' : '' }}">
                                    {{ MoneyAction::format($marea['total_amount_with_vat'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-marea-count">{{ $marea['transaction_count'] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Transactions List Section --}}
        @if(isset($transactions) && $transactions->count() > 0)
            <div class="transactions-section">
                <h3 class="section-title">{{ trans('pdfs.All Transactions with VAT') }}</h3>
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th class="col-trans-date">{{ trans('pdfs.Date') }}</th>
                            <th class="col-trans-number">{{ trans('pdfs.Transaction') }}</th>
                            <th class="col-trans-description">{{ trans('pdfs.Description') }}</th>
                            <th class="col-trans-category">{{ trans('pdfs.Category') }}</th>
                            <th class="col-trans-profile">{{ trans('pdfs.VAT Profile') }}</th>
                            <th class="col-trans-base">{{ trans('pdfs.Base Amount') }}</th>
                            <th class="col-trans-vat">{{ trans('pdfs.VAT') }}</th>
                            <th class="col-trans-total">{{ trans('pdfs.Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            <tr>
                                <td class="col-trans-date">{{ $transaction['transaction_date'] ? \Carbon\Carbon::parse($transaction['transaction_date'])->format('d/m/Y') : '-' }}</td>
                                <td class="col-trans-number">{{ $transaction['transaction_number'] ?? '-' }}</td>
                                <td class="col-trans-description">{{ $transaction['description'] ?? '-' }}</td>
                                <td class="col-trans-category">{{ $transaction['category']['name'] ?? trans('pdfs.Uncategorized') }}</td>
                                <td class="col-trans-profile">
                                    @if(isset($transaction['vat_profile']) && $transaction['vat_profile'])
                                        {{ $transaction['vat_profile']['name'] ?? '-' }}
                                        @if(isset($transaction['vat_profile']['percentage']))
                                            <br><span class="text-xs">({{ number_format($transaction['vat_profile']['percentage'], 2) }}%)</span>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="col-trans-base">
                                    {{ MoneyAction::format($transaction['base_amount'] ?? 0, $houseOfZeros, $transaction['currency'] ?? $defaultCurrency, true) }}
                                </td>
                                <td class="col-trans-vat {{ $enableColors ? 'amount-income' : '' }}">
                                    {{ MoneyAction::format($transaction['vat_amount'] ?? 0, $houseOfZeros, $transaction['currency'] ?? $defaultCurrency, true) }}
                                </td>
                                <td class="col-trans-total {{ $enableColors ? 'amount-income' : '' }}">
                                    {{ MoneyAction::format($transaction['total_amount'] ?? 0, $houseOfZeros, $transaction['currency'] ?? $defaultCurrency, true) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="col-trans-total-label"><strong>{{ trans('pdfs.Total') }}</strong></td>
                            <td class="col-trans-base">
                                <strong>{{ MoneyAction::format($summary['total_base_amount'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}</strong>
                            </td>
                            <td class="col-trans-vat {{ $enableColors ? 'amount-income' : '' }}">
                                <strong>{{ MoneyAction::format($summary['total_vat'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}</strong>
                            </td>
                            <td class="col-trans-total {{ $enableColors ? 'amount-income' : '' }}">
                                <strong>{{ MoneyAction::format($summary['total_amount_with_vat'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}</strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <style>
        /* Report Content Container */
        .report-content {
            margin-top: 10mm;
            padding: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            background-color: #fff;
        }

        .report-content > * {
            margin-left: 0 !important;
            padding-left: 0 !important;
        }

        /* First section has extra top margin */
        .report-content > *:first-child {
            margin-top: 0 !important;
        }

        .summary-section {
            margin-top: 0 !important;
            margin-bottom: 25px !important;
        }

        /* Section Titles */
        .section-title {
            color: #000;
            font-size: 14px;
            font-weight: bold;
            margin: 20px 0 15px 0 !important;
            padding: 0 !important;
            page-break-after: avoid;
        }

        .subsection-title {
            color: #000;
            font-size: 12px;
            font-weight: bold;
            margin: 15px 0 12px 0 !important;
            padding: 0 !important;
            page-break-after: avoid;
        }

        /* Summary Table */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0 25px 0;
            background-color: #fff;
        }

        .summary-cell {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
            background-color: #fff;
            vertical-align: top;
        }

        .summary-label {
            font-size: 9px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #000;
            margin-bottom: 3px;
        }

        .summary-change {
            font-size: 8px;
            color: #666;
        }

        /* VAT Profile Table */
        .vat-profile-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0 25px 0;
            background-color: #fff;
        }

        .vat-profile-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 9px;
            color: #000;
        }

        .vat-profile-table td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 9px;
            color: #000;
            background-color: #fff;
        }

        .col-profile-name {
            width: 25%;
        }

        .col-profile-percentage {
            width: 10%;
            text-align: right;
        }

        .col-profile-base {
            width: 18%;
            text-align: right;
        }

        .col-profile-vat {
            width: 18%;
            text-align: right;
        }

        .col-profile-total {
            width: 18%;
            text-align: right;
        }

        .col-profile-count {
            width: 11%;
            text-align: center;
        }

        /* Category Table */
        .category-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0 25px 0;
            background-color: #fff;
        }

        .category-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 9px;
            color: #000;
        }

        .category-table td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 9px;
            color: #000;
            background-color: #fff;
        }

        .col-category-name {
            width: 25%;
        }

        .col-category-base {
            width: 15%;
            text-align: right;
        }

        .col-category-vat {
            width: 15%;
            text-align: right;
        }

        .col-category-total {
            width: 15%;
            text-align: right;
        }

        .col-category-percentage {
            width: 10%;
            text-align: right;
        }

        .col-category-count {
            width: 20%;
            text-align: center;
        }

        /* Daily Table */
        .daily-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0 25px 0;
            background-color: #fff;
        }

        .daily-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 9px;
            color: #000;
        }

        .daily-table td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 9px;
            color: #000;
            background-color: #fff;
        }

        .col-date {
            width: 20%;
        }

        .col-daily-base {
            width: 20%;
            text-align: right;
        }

        .col-daily-vat {
            width: 20%;
            text-align: right;
        }

        .col-daily-total {
            width: 20%;
            text-align: right;
        }

        .col-daily-count {
            width: 20%;
            text-align: center;
        }

        /* Marea Table */
        .marea-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0 25px 0;
            background-color: #fff;
        }

        .marea-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 9px;
            color: #000;
        }

        .marea-table td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 9px;
            color: #000;
            background-color: #fff;
        }

        .col-marea-number {
            width: 15%;
        }

        .col-marea-name {
            width: 20%;
        }

        .col-marea-base {
            width: 18%;
            text-align: right;
        }

        .col-marea-vat {
            width: 18%;
            text-align: right;
        }

        .col-marea-total {
            width: 18%;
            text-align: right;
        }

        .col-marea-count {
            width: 11%;
            text-align: center;
        }

        /* Transactions Table */
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0 25px 0;
            background-color: #fff;
            font-size: 8px;
        }

        .transactions-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            padding: 6px 4px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 8px;
            color: #000;
        }

        .transactions-table td {
            padding: 6px 4px;
            border: 1px solid #ddd;
            font-size: 8px;
            color: #000;
            background-color: #fff;
        }

        .transactions-table tfoot {
            background-color: #f9f9f9;
        }

        .transactions-table tfoot td {
            font-weight: bold;
        }

        .col-trans-date {
            width: 8%;
        }

        .col-trans-number {
            width: 10%;
        }

        .col-trans-description {
            width: 18%;
        }

        .col-trans-category {
            width: 12%;
        }

        .col-trans-profile {
            width: 12%;
        }

        .col-trans-base {
            width: 12%;
            text-align: right;
        }

        .col-trans-vat {
            width: 12%;
            text-align: right;
        }

        .col-trans-total {
            width: 12%;
            text-align: right;
        }

        .col-trans-total-label {
            text-align: right;
            padding-right: 8px;
        }

        /* Amount colors */
        .amount-income {
            color: rgb(34, 197, 94) !important;
            font-weight: 700;
        }

        .amount-expense {
            color: rgb(239, 68, 68) !important;
            font-weight: 700;
        }

        .amount-neutral {
            color: rgb(0, 0, 0) !important;
            font-weight: 700;
        }
    </style>
@endpush

