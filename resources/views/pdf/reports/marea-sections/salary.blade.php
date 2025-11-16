{{--
    Marea Salary Section Template

    This template displays salary payment movimentations for a marea.
    Salary payments are identified by having crew_member_id not null.

    Required Variables:
    - $vessel: Vessel model instance
    - $marea: Marea model instance
    - $salaryTransactions: Collection of salary payment movimentations
    - $defaultCurrency: Currency code
    - $houseOfZeros: Decimal places
    - $enableColors: Boolean for colors
--}}
@php
    use App\Actions\MoneyAction;

    // Ensure enableColors is a boolean
    $enableColors = isset($enableColors) ? (bool) $enableColors : false;

    // Calculate summary for salary payments
    $totalSalary = $salaryTransactions->sum('total_amount');
    $salaryCount = $salaryTransactions->count();
@endphp

@if($salaryTransactions->count() > 0)
    <div class="section-header">
        <h3 class="section-title">{{ trans('pdfs.Salary Payments') }}</h3>
    </div>

    {{-- Summary --}}
    <div class="summary-section">
        <table class="summary-table">
            <tr>
                <td class="summary-cell">
                    <div class="summary-label">{{ trans('pdfs.Total Salary Payments') }}</div>
                    <div class="summary-value">
                        {{ MoneyAction::format($totalSalary, $houseOfZeros, $defaultCurrency, true, $enableColors, 'negative') }}
                    </div>
                    <div class="summary-count">({{ $salaryCount }} {{ trans('pdfs.movimentations') }})</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Salary Payments Table --}}
    <table class="transactions-table">
        <thead>
            <tr>
                <th class="col-date">{{ trans('pdfs.Date') }}</th>
                <th class="col-description">{{ trans('pdfs.Description') }}</th>
                <th class="col-category">{{ trans('pdfs.Crew Member') }}</th>
                <th class="col-amount">{{ trans('pdfs.Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($salaryTransactions as $salary)
                @php
                    $amountValue = $salary->total_amount ?? $salary->amount;
                    $salaryCurrency = $salary->currency ?? $defaultCurrency;
                    $salaryHouseOfZeros = $salary->house_of_zeros ?? $houseOfZeros;
                    $amount = MoneyAction::format($amountValue, $salaryHouseOfZeros, $salaryCurrency, true);
                    $amountClass = $enableColors ? 'amount-expense' : 'amount-neutral';
                @endphp
                <tr>
                    <td class="col-date">{{ $salary->transaction_date->format('d/m/Y') }}</td>
                    <td class="col-description">{{ $salary->description ?? '-' }}</td>
                    <td class="col-category">{{ $salary->crewMember->name ?? '-' }}</td>
                    <td class="col-amount {{ $amountClass }}">
                        - {{ $amount }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

