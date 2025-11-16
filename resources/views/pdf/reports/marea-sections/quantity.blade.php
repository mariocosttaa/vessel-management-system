{{--
    Marea Fishing Quantity Returns Section Template

    This template displays fishing quantity returns for a marea.
    Only shown if marea status is 'returned' or 'closed'.

    Required Variables:
    - $vessel: Vessel model instance
    - $marea: Marea model instance with quantityReturns relationship loaded
--}}
@if(in_array($marea->status, ['returned', 'closed']) && $marea->quantityReturns && $marea->quantityReturns->count() > 0)
    <div class="section-header">
        <h3 class="section-title">{{ trans('pdfs.Fishing Quantity Returns') }}</h3>
    </div>

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
@endif

