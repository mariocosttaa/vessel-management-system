{{--
    Marea Expenses Section Template

    This template displays expense movimentations for a marea.
    Can include or exclude salary expenses based on $includeSalary parameter.

    Required Variables:
    - $vessel: Vessel model instance
    - $marea: Marea model instance
    - $expenses: Collection of expense movimentations
    - $includeSalary: Boolean to include/exclude salary expenses (default: true)
    - $defaultCurrency: Currency code
    - $houseOfZeros: Decimal places
    - $enableColors: Boolean for colors
--}}
@php
    use App\Actions\MoneyAction;

    // Ensure enableColors is a boolean
    $enableColors = isset($enableColors) ? (bool) $enableColors : false;

    // Expenses are already filtered in the parent template
    // Just calculate summary for expenses
    $totalExpenses = $expenses->sum('total_amount');
    $expenseCount = $expenses->count();

    // Determine section title based on whether salary is included
    // If includeSalary is true, it means we're showing expenses WITH salary
    // If includeSalary is false, it means we're showing expenses WITHOUT salary
    $sectionTitle = $includeSalary ? trans('pdfs.Expenses with Salary') : trans('pdfs.Expenses');
@endphp

@if($expenses->count() > 0)
    <div class="section-header">
        <h3 class="section-title">{{ $sectionTitle }}</h3>
    </div>

    {{-- Summary --}}
    <div class="summary-section">
        <table class="summary-table">
            <tr>
                <td class="summary-cell">
                    <div class="summary-label">{{ trans('pdfs.Total Expenses') }}</div>
                    <div class="summary-value">
                        {{ MoneyAction::format($totalExpenses, $houseOfZeros, $defaultCurrency, true) }}
                    </div>
                    <div class="summary-count">({{ $expenseCount }} {{ trans('pdfs.movimentations') }})</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Expenses Table --}}
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
            @foreach($expenses as $expense)
                @php
                    $amountValue = $expense->total_amount ?? $expense->amount;
                    $expenseCurrency = $expense->currency ?? $defaultCurrency;
                    $expenseHouseOfZeros = $expense->house_of_zeros ?? $houseOfZeros;
                    $amount = MoneyAction::format($amountValue, $expenseHouseOfZeros, $expenseCurrency, true);
                    $amountClass = $enableColors ? 'amount-expense' : 'amount-neutral';
                @endphp
                <tr>
                    <td class="col-date">{{ $expense->transaction_date->format('d/m/Y') }}</td>
                    <td class="col-description">{{ $expense->description ?? '-' }}</td>
                    <td class="col-category">{{ $expense->category->translated_name ?? '-' }}</td>
                    <td class="col-amount {{ $amountClass }}">
                        - {{ $amount }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

