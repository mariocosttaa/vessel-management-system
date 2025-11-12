<?php

namespace App\Http\Requests;

use App\Actions\General\EasyHashAction;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for deleting a crew member.
 *
 * @property int $vessel
 * @property int $crewMember
 */
class DeleteCrewMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get vessel ID from route parameter (may be hashed)
        $vesselIdParam = $this->route('vessel');
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // Decode vessel ID if it's hashed
        $vesselId = is_numeric($vesselIdParam)
            ? (int) $vesselIdParam
            : EasyHashAction::decode($vesselIdParam, 'vessel-id');

        if (!$vesselId || !is_numeric($vesselId)) {
            return false;
        }

        // Check if user can manage vessel users (administrator permission)
        return $user->canManageVesselUsers((int) $vesselId);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }
}

