{{--
    Marea Crew Members Section Template

    This template displays crew members for a marea.

    Required Variables:
    - $vessel: Vessel model instance
    - $marea: Marea model instance with crewMembers relationship loaded
--}}
@if($marea->crewMembers && $marea->crewMembers->count() > 0)
    <div class="section-header">
        <h3 class="section-title">{{ trans('pdfs.Crew Members') }}</h3>
    </div>

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
@endif

