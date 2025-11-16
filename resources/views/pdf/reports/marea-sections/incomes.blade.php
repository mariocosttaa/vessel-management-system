{{--
    Marea Incomes Section Template

    This template displays income movimentations for a marea.

    Required Variables:
    - $vessel: Vessel model instance
    - $marea: Marea model instance
    - $incomes: Collection of income movimentations
    - $defaultCurrency: Currency code
    - $houseOfZeros: Decimal places
    - $enableColors: Boolean for colors
--}}
@php
    use App\Actions\MoneyAction;

    // Ensure enableColors is a boolean
    $enableColors = isset($enableColors) ? (bool) $enableColors : false;

    // Calculate summary for incomes
    $totalIncomes = $incomes->sum('total_amount');
    $incomeCount = $incomes->count();
@endphp

@if($incomes->count() > 0)
    <div class="section-header">
        <h3 class="section-title">{{ trans('pdfs.Incomes') }}</h3>
    </div>

    {{-- Summary --}}
    <div class="summary-section">
        <table class="summary-table">
            <tr>
                <td class="summary-cell">
                    <div class="summary-label">{{ trans('pdfs.Total Income') }}</div>
                    <div class="summary-value">
                        {{ MoneyAction::format($totalIncomes, $houseOfZeros, $defaultCurrency, true) }}
                    </div>
                    <div class="summary-count">({{ $incomeCount }} {{ trans('pdfs.movimentations') }})</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Incomes Table --}}
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
            @foreach($incomes as $income)
                @php
                    $amountValue = $income->total_amount ?? $income->amount;
                    $incomeCurrency = $income->currency ?? $defaultCurrency;
                    $incomeHouseOfZeros = $income->house_of_zeros ?? $houseOfZeros;
                    $amount = MoneyAction::format($amountValue, $incomeHouseOfZeros, $incomeCurrency, true);
                    $amountClass = $enableColors ? 'amount-income' : 'amount-neutral';
                @endphp
                <tr>
                    <td class="col-date">{{ $income->transaction_date->format('d/m/Y') }}</td>
                    <td class="col-description">{{ $income->description ?? '-' }}</td>
                    <td class="col-category">{{ $income->category->translated_name ?? '-' }}</td>
                    <td class="col-amount {{ $amountClass }}">
                        + {{ $amount }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

