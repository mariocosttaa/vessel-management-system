{{--
    Marea Partial Report Template

    This template displays selected sections of a marea report.
    It extends the base PDF layout and includes only the selected sections.

    Required Variables:
    - $vessel: Vessel model instance
    - $marea: Marea model instance with all relationships loaded
    - $sections: Array with section flags (expenses, expensesWithSalary, incomes, crew, quantity)
    - $title: Report title (default: 'Marea Report')
    - $subtitle: Report subtitle (optional)
    - $enableColors: Boolean to enable/disable colors (default: false)
--}}
@php
    // Set default value for enableColors if not provided
    // Make sure to cast to boolean explicitly
    $enableColors = isset($enableColors) ? (bool) $enableColors : false;

    // Get currency from marea, vessel settings, or default to EUR
    use App\Actions\MoneyAction;
    use App\Models\VesselSetting;

    $vesselSetting = VesselSetting::getForVessel($vessel->id);
    $defaultCurrency = $marea->getCurrency() ?? $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';
    $houseOfZeros = $marea->getHouseOfZeros();

    // Get expenses and incomes
    $allExpenses = $marea->transactions->where('type', 'expense');
    $incomes = $marea->transactions->where('type', 'income');

    // Get salary transactions (transactions with crew_member_id not null)
    $salaryTransactions = $marea->transactions->where('type', 'expense')
        ->filter(function ($transaction) {
            return $transaction->crew_member_id !== null;
        });

    // Determine if expenses should include salary
    $includeSalary = $sections['expensesWithSalary'] ?? false;

    // If expensesWithSalary is selected, use all expenses
    // If expenses (without salary) is selected, filter out salary expenses
    if ($sections['expensesWithSalary'] ?? false) {
        $expenses = $allExpenses;
    } elseif ($sections['expenses'] ?? false) {
        // Filter out salary expenses
        $salaryCategories = \App\Models\MovimentationCategory::forVessel($vessel->id)
            ->where('type', 'expense')
            ->whereIn('name', ['Salários', 'Salaries', 'Crew Salaries', 'Wages'])
            ->pluck('id');

        $expenses = $allExpenses->reject(function ($expense) use ($salaryCategories) {
            return $expense->category_id && $salaryCategories->contains($expense->category_id);
        });
    } else {
        $expenses = collect();
    }
@endphp

@extends('pdf.layouts.base')

@section('title', $title ?? 'Marea Report')

@section('content')
    <div class="report-content">
        {{-- Always include marea information --}}
        @include('pdf.reports.marea-sections.info', ['vessel' => $vessel, 'marea' => $marea])

        {{-- Expenses Section --}}
        @if(($sections['expenses'] ?? false) || ($sections['expensesWithSalary'] ?? false))
            @include('pdf.reports.marea-sections.expenses', [
                'vessel' => $vessel,
                'marea' => $marea,
                'expenses' => $expenses,
                'includeSalary' => ($sections['expensesWithSalary'] ?? false),
                'defaultCurrency' => $defaultCurrency,
                'houseOfZeros' => $houseOfZeros,
                'enableColors' => $enableColors
            ])
        @endif

        {{-- Incomes Section --}}
        @if($sections['incomes'] ?? false)
            @include('pdf.reports.marea-sections.incomes', [
                'vessel' => $vessel,
                'marea' => $marea,
                'incomes' => $incomes,
                'defaultCurrency' => $defaultCurrency,
                'houseOfZeros' => $houseOfZeros,
                'enableColors' => $enableColors
            ])
        @endif

        {{-- Crew Members Section --}}
        @if($sections['crew'] ?? false)
            @include('pdf.reports.marea-sections.crew', [
                'vessel' => $vessel,
                'marea' => $marea
            ])
        @endif

        {{-- Fishing Quantity Section --}}
        @if($sections['quantity'] ?? false)
            @include('pdf.reports.marea-sections.quantity', [
                'vessel' => $vessel,
                'marea' => $marea
            ])
        @endif

        {{-- Salary Payments Section --}}
        @if($sections['salary'] ?? false)
            @include('pdf.reports.marea-sections.salary', [
                'vessel' => $vessel,
                'marea' => $marea,
                'salaryTransactions' => $salaryTransactions,
                'defaultCurrency' => $defaultCurrency,
                'houseOfZeros' => $houseOfZeros,
                'enableColors' => $enableColors
            ])
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

        .section-header {
            margin-top: 15px;
            margin-bottom: 10px;
            background-color: #fff;
            padding: 0 !important;
            margin-left: 0 !important;
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
            width: 100%;
            padding: 0;
            vertical-align: top;
            border: none;
            background-color: #fff;
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

        /* Transactions Section */
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

