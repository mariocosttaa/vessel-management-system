{{--
    Maintenance Report Template

    This template is specifically designed for displaying maintenance reports.
    It extends the base PDF layout and includes:
    - Maintenance information (number, name, description, status, dates)
    - Summary section (total expenses)
    - Detailed transactions table

    Required Variables:
    - $vessel: Vessel model instance
    - $maintenance: Maintenance model instance
    - $transactions: Collection of transactions
    - $summary: Array with 'total_expenses', 'expense_count'
    - $title: Report title (default: 'Maintenance Report')
    - $subtitle: Report subtitle (optional)
    - $enableColors: Boolean to enable/disable colors (default: false)
--}}
@php
    // Set default value for enableColors if not provided (default to false - colors disabled)
    $enableColors = isset($enableColors) ? (bool) $enableColors : false;
@endphp

@extends('pdf.layouts.base')

@section('title', $title ?? 'Maintenance Report')

@section('content')
    @php
        use App\Actions\MoneyAction;
        use App\Models\VesselSetting;

        // Get currency from maintenance, vessel settings, or default to EUR
        $vesselSetting = VesselSetting::getForVessel($vessel->id);
        $defaultCurrency = $maintenance->getCurrency() ?? $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';
        $houseOfZeros = $maintenance->getHouseOfZeros();
    @endphp

    <div class="report-content">
        {{-- Maintenance Information --}}
        <div class="maintenance-info-section">
            <h3 class="section-title">{{ trans('pdfs.Maintenance Information') }}</h3>
            <table class="info-table">
                <tr>
                    <td class="info-label">{{ trans('pdfs.Maintenance Number') }}:</td>
                    <td class="info-value">{{ $maintenance->maintenance_number }}</td>
                </tr>
                @if($maintenance->name)
                    <tr>
                        <td class="info-label">{{ trans('pdfs.Name') }}:</td>
                        <td class="info-value">{{ $maintenance->name }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="info-label">{{ trans('pdfs.Status') }}:</td>
                    <td class="info-value">{{ trans('pdfs.' . ucfirst($maintenance->status)) }}</td>
                </tr>
                @if($maintenance->start_date)
                    <tr>
                        <td class="info-label">{{ trans('pdfs.Start Date') }}:</td>
                        <td class="info-value">{{ $maintenance->start_date->format('d/m/Y') }}</td>
                    </tr>
                @endif
                @if($maintenance->end_date)
                    <tr>
                        <td class="info-label">{{ trans('pdfs.End Date') }}:</td>
                        <td class="info-value">{{ $maintenance->end_date->format('d/m/Y') }}</td>
                    </tr>
                @endif
                @if($maintenance->closed_at)
                    <tr>
                        <td class="info-label">{{ trans('pdfs.Closed At') }}:</td>
                        <td class="info-value">{{ $maintenance->closed_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @endif
                @if($maintenance->description)
                    <tr>
                        <td class="info-label">{{ trans('pdfs.Description') }}:</td>
                        <td class="info-value">{{ $maintenance->description }}</td>
                    </tr>
                @endif
            </table>
        </div>

        {{-- Summary Section --}}
        @if(isset($summary))
            <div class="summary-section">
                <h3 class="section-title">{{ trans('pdfs.Summary') }}</h3>
                <table class="summary-table">
                    <tr>
                        <td class="summary-cell">
                            <div class="summary-label">{{ trans('pdfs.Total Expenses') }}</div>
                            <div class="summary-value">
                                {{ MoneyAction::format($summary['total_expenses'] ?? 0, $houseOfZeros, $defaultCurrency, true) }}
                            </div>
                            <div class="summary-count">({{ $summary['expense_count'] ?? 0 }} {{ $summary['expense_count'] == 1 ? trans('pdfs.Expense') : trans('pdfs.Expenses') }})</div>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        {{-- Transactions Table --}}
        @if(isset($transactions) && $transactions->count() > 0)
            <div class="transactions-header">
                <h3 class="section-title">{{ trans('pdfs.Expenses') }}</h3>
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
                    @foreach($transactions as $transaction)
                        @php
                            $amountValue = $transaction->total_amount ?? $transaction->amount;
                            $transactionCurrency = $transaction->currency ?? $defaultCurrency;
                            $transactionHouseOfZeros = $transaction->house_of_zeros ?? $houseOfZeros;
                            $amount = MoneyAction::format($amountValue, $transactionHouseOfZeros, $transactionCurrency, true);

                            // Determine color based on transaction type
                            $amountClass = 'amount-neutral';
                            if ($transaction->type === 'expense') {
                                $amountClass = $enableColors ? 'amount-expense' : 'amount-neutral';
                            }
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
        @else
            <div class="empty-state">
                <p>{{ trans('pdfs.No transactions found for this maintenance.') }}</p>
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
            font-size: 14px;
            font-weight: bold;
            color: #000;
            margin: 15px 0 10px 0;
            padding: 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Maintenance Information Section */
        .maintenance-info-section {
            margin-bottom: 15px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            background-color: #fff;
        }

        .info-table td {
            padding: 6px 8px;
            font-size: 10px;
            color: #000;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        .info-label {
            width: 30%;
            font-weight: bold;
            text-align: left;
        }

        .info-value {
            width: 70%;
            text-align: left;
            word-wrap: break-word;
        }

        /* Summary Section */
        .summary-section {
            margin: 15px 0;
            page-break-inside: avoid;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            background-color: #fff;
        }

        .summary-cell {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }

        .summary-label {
            font-size: 9px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #000;
            margin: 5px 0;
        }

        .summary-count {
            font-size: 9px;
            color: #666;
            margin-top: 5px;
        }

        /* Transactions Header */
        .transactions-header {
            margin: 15px 0 10px 0;
        }

        /* Transactions Table */
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            background-color: #fff;
            page-break-inside: auto;
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

        /* Column Widths */
        .col-date {
            width: 15%;
            text-align: left;
        }

        .col-description {
            width: 40%;
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
            color: rgb(34, 197, 94) !important; /* Green for income (#22c55e) */
        }

        .amount-expense {
            color: rgb(239, 68, 68) !important; /* Red for expense (#ef4444) */
        }

        .amount-neutral {
            color: rgb(0, 0, 0) !important; /* Black for neutral */
        }

        /* Empty State */
        .empty-state {
            margin: 20px 0;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 11px;
        }
    </style>
@endpush

