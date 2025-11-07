<?php

namespace App\Http\Requests;

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
        // Get vessel ID from route parameter
        $vesselId = $this->route('vessel');
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // Check if user can manage vessel users (administrator permission)
        return $user->canManageVesselUsers($vesselId);
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

