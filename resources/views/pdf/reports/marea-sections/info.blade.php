{{--
    Marea Information Section Template

    This template displays basic marea information including dates (always included).

    Required Variables:
    - $vessel: Vessel model instance
    - $marea: Marea model instance
--}}
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

