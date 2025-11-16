{{--
    Financial Report Template

    This template displays financial reports for a specific month and year.
    It includes:
    - Summary section (total income, total expenses, net balance, transaction count)
    - Category breakdown (income and expenses by category)
    - Daily breakdown table
    - Marea information (fishing trips) for the month

    Required Variables:
    - $vessel: Vessel model instance
    - $year: Year (e.g., 2025)
    - $month: Month (1-12)
    - $monthLabel: Month name (e.g., "November")
    - $defaultCurrency: Currency code
    - $summary: Array with financial summary
    - $categoryBreakdown: Collection of category breakdown data
    - $dailyBreakdown: Collection of daily breakdown data
    - $mareas: Collection of marea data for the month
    - $title: Report title (default: 'Financial Report')
    - $subtitle: Report subtitle
--}}

@extends('pdf.layouts.base')

@section('title', $title ?? 'Financial Report')

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
                            <div class="summary-label">{{ trans('pdfs.Total Income') }}</div>
                            <div class="summary-value {{ $enableColors ? 'amount-income' : '' }}">
                                {{ MoneyAction::format($summary['total_income'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                            </div>
                            @if(isset($summary['income_change']))
                                <div class="summary-change">
                                    {{ $summary['income_change'] > 0 ? '+' : '' }}{{ number_format($summary['income_change'], 1) }}%
                                </div>
                            @endif
                        </td>
                        <td class="summary-cell">
                            <div class="summary-label">{{ trans('pdfs.Total Expenses') }}</div>
                            <div class="summary-value {{ $enableColors ? 'amount-expense' : '' }}">
                                {{ MoneyAction::format($summary['total_expenses'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                            </div>
                            @if(isset($summary['expenses_change']))
                                <div class="summary-change">
                                    {{ $summary['expenses_change'] > 0 ? '+' : '' }}{{ number_format($summary['expenses_change'], 1) }}%
                                </div>
                            @endif
                        </td>
                        <td class="summary-cell">
                            <div class="summary-label">{{ trans('pdfs.Net Balance') }}</div>
                            <div class="summary-value {{ $enableColors ? (($summary['net_balance'] ?? 0) >= 0 ? 'amount-income' : 'amount-expense') : '' }}">
                                {{ MoneyAction::format($summary['net_balance'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                            </div>
                            @if(isset($summary['net_change']))
                                <div class="summary-change">
                                    {{ $summary['net_change'] > 0 ? '+' : '' }}{{ number_format($summary['net_change'], 1) }}%
                                </div>
                            @endif
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

        {{-- Category Breakdown Section --}}
        @if(isset($categoryBreakdown) && $categoryBreakdown->count() > 0)
            <div class="category-section">
                <h3 class="section-title">{{ trans('pdfs.Category Breakdown') }}</h3>

                {{-- Expenses by Category --}}
                @php
                    $expenseCategories = $categoryBreakdown->filter(function($cat) {
                        return ($cat['expenses'] ?? 0) > 0;
                    });
                @endphp
                @if($expenseCategories->count() > 0)
                    <h4 class="subsection-title">{{ trans('pdfs.Expenses by Category') }}</h4>
                    <table class="category-table">
                        <thead>
                            <tr>
                                <th class="col-category-name">{{ trans('pdfs.Category') }}</th>
                                <th class="col-category-amount">{{ trans('pdfs.Amount') }}</th>
                                <th class="col-category-percentage">{{ trans('pdfs.Percentage') }}</th>
                                <th class="col-category-count">{{ trans('pdfs.Count') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenseCategories as $cat)
                                @php
                                    $percentage = $summary['total_expenses'] > 0
                                        ? (($cat['expenses'] / $summary['total_expenses']) * 100)
                                        : 0;
                                @endphp
                                <tr>
                                    <td class="col-category-name">{{ $cat['category_name'] ?? trans('pdfs.Uncategorized') }}</td>
                                    <td class="col-category-amount {{ $enableColors ? 'amount-expense' : '' }}">
                                        {{ MoneyAction::format($cat['expenses'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                    </td>
                                    <td class="col-category-percentage">{{ number_format($percentage, 1) }}%</td>
                                    <td class="col-category-count">{{ $cat['count'] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                {{-- Income by Category --}}
                @php
                    $incomeCategories = $categoryBreakdown->filter(function($cat) {
                        return ($cat['income'] ?? 0) > 0;
                    });
                @endphp
                @if($incomeCategories->count() > 0)
                    <h4 class="subsection-title">{{ trans('pdfs.Income by Category') }}</h4>
                    <table class="category-table">
                        <thead>
                            <tr>
                                <th class="col-category-name">{{ trans('pdfs.Category') }}</th>
                                <th class="col-category-amount">{{ trans('pdfs.Amount') }}</th>
                                <th class="col-category-percentage">{{ trans('pdfs.Percentage') }}</th>
                                <th class="col-category-count">{{ trans('pdfs.Count') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($incomeCategories as $cat)
                                @php
                                    $percentage = $summary['total_income'] > 0
                                        ? (($cat['income'] / $summary['total_income']) * 100)
                                        : 0;
                                @endphp
                                <tr>
                                    <td class="col-category-name">{{ $cat['category_name'] ?? trans('pdfs.Uncategorized') }}</td>
                                    <td class="col-category-amount {{ $enableColors ? 'amount-income' : '' }}">
                                        {{ MoneyAction::format($cat['income'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                    </td>
                                    <td class="col-category-percentage">{{ number_format($percentage, 1) }}%</td>
                                    <td class="col-category-count">{{ $cat['count'] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endif

        {{-- Daily Breakdown Section --}}
        @if(isset($dailyBreakdown) && $dailyBreakdown->count() > 0)
            <div class="daily-section">
                <h3 class="section-title">{{ trans('pdfs.Daily Breakdown') }}</h3>
                <table class="daily-table">
                    <thead>
                        <tr>
                            <th class="col-date">{{ trans('pdfs.Date') }}</th>
                            <th class="col-daily-income">{{ trans('pdfs.Income') }}</th>
                            <th class="col-daily-expenses">{{ trans('pdfs.Expenses') }}</th>
                            <th class="col-daily-net">{{ trans('pdfs.Net') }}</th>
                            <th class="col-daily-count">{{ trans('pdfs.Transactions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dailyBreakdown as $day)
                            <tr>
                                <td class="col-date">{{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}</td>
                                <td class="col-daily-income {{ $enableColors ? 'amount-income' : '' }}">
                                    {{ MoneyAction::format($day['income'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-daily-expenses {{ $enableColors ? 'amount-expense' : '' }}">
                                    {{ MoneyAction::format($day['expenses'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-daily-net {{ $enableColors ? (($day['net'] ?? 0) >= 0 ? 'amount-income' : 'amount-expense') : '' }}">
                                    {{ MoneyAction::format($day['net'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-daily-count">{{ $day['count'] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Marea Information Section --}}
        @if(isset($mareas) && $mareas->count() > 0)
            <div class="marea-section">
                <h3 class="section-title">{{ trans('pdfs.Mareas (Fishing Trips)') }}</h3>
                <table class="marea-table">
                    <thead>
                        <tr>
                            <th class="col-marea-number">{{ trans('pdfs.Marea Number') }}</th>
                            <th class="col-marea-name">{{ trans('pdfs.Name') }}</th>
                            <th class="col-marea-status">{{ trans('pdfs.Status') }}</th>
                            <th class="col-marea-income">{{ trans('pdfs.Income') }}</th>
                            <th class="col-marea-expenses">{{ trans('pdfs.Expenses') }}</th>
                            <th class="col-marea-net">{{ trans('pdfs.Net') }}</th>
                            <th class="col-marea-count">{{ trans('pdfs.Transactions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mareas as $marea)
                            <tr>
                                <td class="col-marea-number">{{ $marea['marea_number'] ?? '-' }}</td>
                                <td class="col-marea-name">{{ $marea['name'] ?? '-' }}</td>
                                <td class="col-marea-status">{{ trans('pdfs.' . ucfirst(str_replace('_', ' ', $marea['status'] ?? ''))) }}</td>
                                <td class="col-marea-income {{ $enableColors ? 'amount-income' : '' }}">
                                    {{ MoneyAction::format($marea['total_income'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-marea-expenses {{ $enableColors ? 'amount-expense' : '' }}">
                                    {{ MoneyAction::format($marea['total_expenses'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-marea-net {{ $enableColors ? (($marea['net_result'] ?? 0) >= 0 ? 'amount-income' : 'amount-expense') : '' }}">
                                    {{ MoneyAction::format($marea['net_result'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                                <td class="col-marea-count">{{ $marea['transaction_count'] ?? 0 }}</td>
                            </tr>
                            @if(isset($marea['quantity_returns']) && count($marea['quantity_returns']) > 0)
                                <tr class="marea-quantity-row">
                                    <td colspan="7" class="col-quantity-returns">
                                        <strong>{{ trans('pdfs.Quantity Returns') }}:</strong>
                                        @foreach($marea['quantity_returns'] as $qr)
                                            {{ $qr['name'] }}: {{ number_format($qr['quantity'], 2, ',', '.') }}
                                            @if(!$loop->last), @endif
                                        @endforeach
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
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
            width: 40%;
        }

        .col-category-amount {
            width: 25%;
            text-align: right;
        }

        .col-category-percentage {
            width: 15%;
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

        .col-daily-income {
            width: 20%;
            text-align: right;
        }

        .col-daily-expenses {
            width: 20%;
            text-align: right;
        }

        .col-daily-net {
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
            width: 12%;
        }

        .col-marea-name {
            width: 18%;
        }

        .col-marea-status {
            width: 12%;
        }

        .col-marea-income {
            width: 15%;
            text-align: right;
        }

        .col-marea-expenses {
            width: 15%;
            text-align: right;
        }

        .col-marea-net {
            width: 15%;
            text-align: right;
        }

        .col-marea-count {
            width: 13%;
            text-align: center;
        }

        .marea-quantity-row {
            background-color: #f9f9f9;
        }

        .col-quantity-returns {
            font-size: 8px;
            padding: 6px 8px;
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

