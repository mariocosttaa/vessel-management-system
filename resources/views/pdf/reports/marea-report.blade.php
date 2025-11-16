{{--
    Marea Report Template

    This template is specifically designed for displaying marea (fishing trip) reports.
    It extends the base PDF layout and includes:
    - Marea information (number, name, description, status, dates)
    - Summary section (total income, total expenses, net result)
    - Distribution calculation results (if applicable)
    - Income movimentations table
    - Expense movimentations table
    - Crew members section
    - Fishing quantity returns section

    Required Variables:
    - $vessel: Vessel model instance
    - $marea: Marea model instance with all relationships loaded
    - $summary: Array with financial summary
    - $distribution: Array with distribution calculation results
    - $incomeTransactions: Collection of income movimentations
    - $expenseTransactions: Collection of expense movimentations
    - $title: Report title (default: 'Marea Report')
    - $subtitle: Report subtitle (optional)
    - $enableColors: Boolean to enable/disable colors (default: false)
--}}
@php
    // Set default value for enableColors if not provided (default to false - colors disabled)
    $enableColors = $enableColors ?? false;
@endphp

@extends('pdf.layouts.base')

@section('title', $title ?? 'Marea Report')

@section('content')
    @php
        use App\Actions\MoneyAction;
        use App\Models\VesselSetting;

        // Get currency from marea, vessel settings, or default to EUR
        $vesselSetting = VesselSetting::getForVessel($vessel->id);
        $defaultCurrency = $marea->getCurrency() ?? $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';
        $houseOfZeros = $marea->getHouseOfZeros();
    @endphp

    <div class="report-content">
        {{-- Marea Information Section (includes dates) --}}
        <div class="marea-info-section">
            <h3 class="section-title">{{ trans('pdfs.Marea Information') }}</h3>
            <table class="info-table">
                <tr>
                    <td class="info-label">{{ trans('pdfs.Marea Number') }}:</td>
                    <td class="info-value">{{ $marea->marea_number }}</td>
                    <td class="info-label">{{ trans('pdfs.Status') }}:</td>
                    <td class="info-value">{{ trans('pdfs.' . ucfirst(str_replace('_', ' ', $marea->status))) }}</td>
                </tr>
                @if($marea->name)
                <tr>
                    <td class="info-label">{{ trans('pdfs.Name') }}:</td>
                    <td class="info-value" colspan="3">{{ $marea->name }}</td>
                </tr>
                @endif
                @if($marea->description)
                <tr>
                    <td class="info-label">{{ trans('pdfs.Description') }}:</td>
                    <td class="info-value" colspan="3">{{ $marea->description }}</td>
                </tr>
                @endif
                <tr>
                    <td class="info-label">{{ trans('pdfs.Estimated Departure') }}:</td>
                    <td class="info-value">{{ $marea->estimated_departure_date ? $marea->estimated_departure_date->format('d/m/Y') : '-' }}</td>
                    <td class="info-label">{{ trans('pdfs.Actual Departure') }}:</td>
                    <td class="info-value">{{ $marea->actual_departure_date ? $marea->actual_departure_date->format('d/m/Y') : '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">{{ trans('pdfs.Estimated Return') }}:</td>
                    <td class="info-value">{{ $marea->estimated_return_date ? $marea->estimated_return_date->format('d/m/Y') : '-' }}</td>
                    <td class="info-label">{{ trans('pdfs.Actual Return') }}:</td>
                    <td class="info-value">{{ $marea->actual_return_date ? $marea->actual_return_date->format('d/m/Y') : '-' }}</td>
                </tr>
            </table>
        </div>

        {{-- Summary Section --}}
        @if(isset($summary))
            <div class="summary-section">
                <h3 class="section-title">{{ trans('pdfs.Summary') }}</h3>
                <table class="summary-table">
                    <tr>
                        <td class="summary-cell">
                            <div class="summary-label">{{ trans('pdfs.Total Income') }}</div>
                            <div class="summary-value">
                                {{ MoneyAction::format($summary['total_income'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                            </div>
                            <div class="summary-count">({{ $summary['income_count'] ?? 0 }} {{ trans('pdfs.movimentations') }})</div>
                        </td>
                        <td class="summary-cell">
                            <div class="summary-label">{{ trans('pdfs.Total Expenses') }}</div>
                            <div class="summary-value">
                                {{ MoneyAction::format($summary['total_expenses'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                            </div>
                            <div class="summary-count">({{ $summary['expense_count'] ?? 0 }} {{ trans('pdfs.movimentations') }})</div>
                        </td>
                        <td class="summary-cell">
                            <div class="summary-label">{{ trans('pdfs.Net Result') }}</div>
                            <div class="summary-value">
                                {{ MoneyAction::format($summary['net_result'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                            </div>
                        </td>
                        <td class="summary-cell">
                            <div class="summary-label">{{ trans('pdfs.Total Transactions') }}</div>
                            <div class="summary-value">
                                {{ $summary['total_count'] ?? 0 }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        {{-- Distribution Section (if calculation is enabled) --}}
        @if($marea->use_calculation && isset($distribution) && !empty($distribution['items']))
            <div class="distribution-section">
                <h3 class="section-title">{{ trans('pdfs.Distribution Calculation') }}</h3>
                <table class="distribution-table">
                    <thead>
                        <tr>
                            <th class="col-item-name">{{ trans('pdfs.Item') }}</th>
                            <th class="col-item-value">{{ trans('pdfs.Value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($distribution['items'] as $itemData)
                            <tr>
                                <td class="col-item-name">{{ $itemData['item']->name ?? '-' }}</td>
                                <td class="col-item-value">
                                    {{ $itemData['formatted_value'] ?? MoneyAction::format($itemData['value'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                                </td>
                            </tr>
                        @endforeach
                        @if(isset($distribution['final_result']))
                            <tr class="final-result-row">
                                <td class="col-item-name"><strong>{{ trans('pdfs.Final Result') }}</strong></td>
                                <td class="col-item-value">
                                    <strong>{{ $distribution['formatted_final_result'] ?? MoneyAction::format($distribution['final_result'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}</strong>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Income Movimentations Table --}}
        @if(isset($incomeTransactions) && $incomeTransactions->count() > 0)
            <div class="transactions-header">
                <h3 class="section-title">{{ trans('pdfs.Income Transactions') }}</h3>
            </div>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th class="col-date">{{ trans('pdfs.Date') }}</th>
                        <th class="col-description">{{ trans('pdfs.Description') }}</th>
                        <th class="col-category">{{ trans('pdfs.Category') }}</th>
                        <th class="col-amount">{{ trans('pdfs.Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($incomeTransactions as $transaction)
                        @php
                            $amountValue = $transaction->total_amount ?? $transaction->amount;
                            $transactionCurrency = $transaction->currency ?? $defaultCurrency;
                            $transactionHouseOfZeros = $transaction->house_of_zeros ?? $houseOfZeros;
                            $amount = MoneyAction::format($amountValue, $transactionHouseOfZeros, $transactionCurrency, true);
                            $amountClass = $enableColors ? 'amount-income' : 'amount-neutral';
                        @endphp
                        <tr>
                            <td class="col-date">{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                            <td class="col-description">{{ $transaction->description ?? '-' }}</td>
                            <td class="col-category">{{ $transaction->category->translated_name ?? '-' }}</td>
                            <td class="col-amount {{ $amountClass }}">
                                + {{ $amount }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Expense Movimentations Table --}}
        @if(isset($expenseTransactions) && $expenseTransactions->count() > 0)
            <div class="transactions-header">
                <h3 class="section-title">{{ trans('pdfs.Expense Transactions') }}</h3>
            </div>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th class="col-date">{{ trans('pdfs.Date') }}</th>
                        <th class="col-description">{{ trans('pdfs.Description') }}</th>
                        <th class="col-category">{{ trans('pdfs.Category') }}</th>
                        <th class="col-amount">{{ trans('pdfs.Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenseTransactions as $transaction)
                        @php
                            $amountValue = $transaction->total_amount ?? $transaction->amount;
                            $transactionCurrency = $transaction->currency ?? $defaultCurrency;
                            $transactionHouseOfZeros = $transaction->house_of_zeros ?? $houseOfZeros;
                            $amount = MoneyAction::format($amountValue, $transactionHouseOfZeros, $transactionCurrency, true);
                            $amountClass = $enableColors ? 'amount-expense' : 'amount-neutral';
                        @endphp
                        <tr>
                            <td class="col-date">{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                            <td class="col-description">{{ $transaction->description ?? '-' }}</td>
                            <td class="col-category">{{ $transaction->category->translated_name ?? '-' }}</td>
                            <td class="col-amount {{ $amountClass }}">
                                - {{ $amount }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Crew Members Section --}}
        @if($marea->crewMembers && $marea->crewMembers->count() > 0)
            <div class="crew-section">
                <h3 class="section-title">{{ trans('pdfs.Crew Members') }}</h3>
                <table class="crew-table">
                    <thead>
                        <tr>
                            <th class="col-crew-name">{{ trans('pdfs.Name') }}</th>
                            <th class="col-crew-email">{{ trans('pdfs.Email') }}</th>
                            <th class="col-crew-notes">{{ trans('pdfs.Notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($marea->crewMembers as $crewMember)
                            <tr>
                                <td class="col-crew-name">{{ $crewMember->name }}</td>
                                <td class="col-crew-email">{{ $crewMember->email }}</td>
                                <td class="col-crew-notes">{{ $crewMember->pivot->notes ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Fishing Quantity Returns Section --}}
        @if($marea->quantityReturns && $marea->quantityReturns->count() > 0)
            <div class="quantity-section">
                <h3 class="section-title">{{ trans('pdfs.Fishing Quantity Returns') }}</h3>
                <table class="quantity-table">
                    <thead>
                        <tr>
                            <th class="col-quantity-name">{{ trans('pdfs.Name') }}</th>
                            <th class="col-quantity-value">{{ trans('pdfs.Quantity') }}</th>
                            <th class="col-quantity-notes">{{ trans('pdfs.Notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($marea->quantityReturns as $quantityReturn)
                            <tr>
                                <td class="col-quantity-name">{{ $quantityReturn->name }}</td>
                                <td class="col-quantity-value">{{ number_format($quantityReturn->quantity, 2, ',', '.') }}</td>
                                <td class="col-quantity-notes">{{ $quantityReturn->notes ?? '-' }}</td>
                            </tr>
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
            margin-top: 5mm;
            padding: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            page-break-after: avoid;
            page-break-inside: avoid;
            background-color: #fff;
        }

        .report-content > * {
            margin-left: 0 !important;
            padding-left: 0 !important;
        }

        /* Section Titles */
        .section-title {
            color: #000;
            font-size: 14px;
            margin: 15px 0 10px 0 !important;
            padding: 0 !important;
            font-weight: bold;
            letter-spacing: 0.05em;
            line-height: 1.4;
            background-color: #fff;
            text-align: left;
        }

        /* Marea Information Section */
        .marea-info-section {
            margin-top: 2px;
            margin-bottom: 10px;
            background-color: #fff;
            padding: 0 !important;
            margin-left: 0 !important;
        }

        /* Dates Section */
        .dates-section {
            margin-top: 8px;
            margin-bottom: 10px;
            background-color: #fff;
            padding: 0 !important;
            margin-left: 0 !important;
        }

        /* Info Table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            background-color: #fff;
        }

        .info-table td {
            padding: 6px 8px;
            font-size: 10px;
            color: #000;
            border-bottom: 1px solid #ddd;
            background-color: #fff;
        }

        .info-label {
            font-weight: 600;
            width: 30%;
            text-align: left;
        }

        .info-value {
            width: 20%;
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

        .summary-count {
            font-size: 9px;
            color: #666;
            margin-top: 4px;
            font-weight: normal;
        }

        /* Distribution Section */
        .distribution-section {
            margin-top: 8px;
            margin-bottom: 14px;
            page-break-after: avoid;
            page-break-inside: avoid;
            background-color: #fff;
            padding: 0 !important;
            margin-left: 0 !important;
        }

        .distribution-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 5px;
            background-color: #fff;
        }

        .distribution-table th {
            padding: 10px 8px;
            font-weight: bold;
            font-size: 10px;
            color: #000;
            border-bottom: 1px solid #ddd;
            letter-spacing: 0.05em;
            background-color: #fff;
            text-align: left;
        }

        .distribution-table td {
            padding: 8px 6px;
            font-size: 10px;
            color: #000;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #ddd;
            background-color: #fff;
        }

        .col-item-name {
            width: 60%;
            text-align: left;
        }

        .col-item-value {
            width: 40%;
            text-align: right;
            font-weight: 600;
        }

        .final-result-row {
            background-color: #f5f5f5;
        }

        .final-result-row td {
            font-weight: 700;
            padding-top: 12px;
            padding-bottom: 12px;
        }

        /* Transactions Section */
        .transactions-header {
            margin-top: 5px;
            page-break-after: avoid;
            background-color: #fff;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 5px;
            page-break-inside: auto;
            background-color: #fff;
        }

        .transactions-table thead {
            display: table-header-group;
            page-break-after: avoid;
            background-color: #fff;
        }

        .transactions-table thead tr:first-child th {
            padding-top: 30px !important;
        }

        .transactions-table thead tr {
            background-color: #fff;
            page-break-after: avoid;
        }

        .transactions-table th {
            padding: 20px 8px 10px 8px;
            font-weight: bold;
            font-size: 10px;
            color: #000;
            border-bottom: 1px solid #ddd;
            letter-spacing: 0.05em;
            background-color: #fff;
        }

        .transactions-table tbody {
            background-color: #fff;
        }

        .transactions-table tbody tr {
            page-break-inside: auto;
            background-color: #fff;
        }

        .transactions-table td {
            padding: 8px 6px;
            font-size: 10px;
            color: #000;
            letter-spacing: 0.05em;
            word-spacing: 0.05em;
            border-bottom: 1px solid #ddd;
            background-color: #fff;
        }

        /* Column Widths for Transactions */
        .col-date {
            width: 15%;
            text-align: left;
        }

        .col-description {
            width: 35%;
            text-align: left;
            word-wrap: break-word;
        }

        .col-category {
            width: 25%;
            text-align: left;
            word-wrap: break-word;
        }

        .col-amount {
            width: 25%;
            text-align: right;
            font-size: 11px;
            font-weight: 700;
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
        }

        /* Amount colors */
        .amount-income {
            color: rgb(34, 197, 94) !important;
        }

        .amount-expense {
            color: rgb(239, 68, 68) !important;
        }

        .amount-neutral {
            color: rgb(0, 0, 0) !important;
        }

        /* Crew Section */
        .crew-section {
            margin-top: 15px;
            margin-bottom: 14px;
            page-break-after: avoid;
            page-break-inside: avoid;
            background-color: #fff;
            padding: 0 !important;
            margin-left: 0 !important;
        }

        .crew-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 5px;
            background-color: #fff;
        }

        .crew-table th {
            padding: 10px 8px;
            font-weight: bold;
            font-size: 10px;
            color: #000;
            border-bottom: 1px solid #ddd;
            letter-spacing: 0.05em;
            background-color: #fff;
            text-align: left;
        }

        .crew-table td {
            padding: 8px 6px;
            font-size: 10px;
            color: #000;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #ddd;
            background-color: #fff;
        }

        .col-crew-name {
            width: 30%;
        }

        .col-crew-email {
            width: 35%;
        }

        .col-crew-notes {
            width: 35%;
            word-wrap: break-word;
        }

        /* Quantity Section */
        .quantity-section {
            margin-top: 15px;
            margin-bottom: 14px;
            page-break-after: avoid;
            page-break-inside: avoid;
            background-color: #fff;
            padding: 0 !important;
            margin-left: 0 !important;
        }

        .quantity-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 5px;
            background-color: #fff;
        }

        .quantity-table th {
            padding: 10px 8px;
            font-weight: bold;
            font-size: 10px;
            color: #000;
            border-bottom: 1px solid #ddd;
            letter-spacing: 0.05em;
            background-color: #fff;
            text-align: left;
        }

        .quantity-table td {
            padding: 8px 6px;
            font-size: 10px;
            color: #000;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #ddd;
            background-color: #fff;
        }

        .col-quantity-name {
            width: 30%;
        }

        .col-quantity-value {
            width: 25%;
            text-align: right;
            font-weight: 600;
        }

        .col-quantity-notes {
            width: 45%;
            word-wrap: break-word;
        }
    </style>
@endpush

