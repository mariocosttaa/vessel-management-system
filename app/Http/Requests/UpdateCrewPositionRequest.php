<?php

namespace App\Http\Requests;

use App\Models\CrewPosition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateCrewPositionRequest validates updating an existing crew position.
 *
 * Input fields:
 * @property string $name
 * @property string|null $description
 * @property int|null $vessel_role_access_id
 *
 * Route parameters:
 * @property int $vessel (accessed via $this->route('vessel') for authorization)
 * @property CrewPosition $crewPosition (accessed via $this->route('crewPosition'))
 *
 * @method mixed route(string $key = null)
 * @method \Illuminate\Contracts\Auth\Authenticatable|null user()
 * @method bool boolean(string $key)
 * @method array all()
 * @method void merge(array $data)
 * @method array input(string $key = null, mixed $default = null)
 */
class UpdateCrewPositionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get vessel ID from route parameter for authorization check only
        $vessel = $this->route('vessel');
        $crewPositionId = $this->route('crewPosition');
        /** @var \App\Models\User|null $user */
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // Extract vessel ID (handle both model instance and ID)
        $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;

        // Resolve crew position manually (handle both model instance and ID)
        $crewPosition = is_object($crewPositionId) ? $crewPositionId : CrewPosition::findOrFail($crewPositionId);

        // Check if user can manage crew (for crew roles management)
        // This allows administrators and supervisors to edit crew roles
        if (!$user->hasVesselPermission($vesselId, 'manage_crew')) {
            return false;
        }

        // Verify crew position belongs to current vessel or is global
        if ($crewPosition->vessel_id !== null && $crewPosition->vessel_id !== $vesselId) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $vessel = $this->route('vessel');
        $crewPositionId = $this->route('crewPosition');
        // Extract vessel ID (handle both model instance and ID)
        $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;

        // Resolve crew position manually (handle both model instance and ID)
        $crewPosition = is_object($crewPositionId) ? $crewPositionId : CrewPosition::findOrFail($crewPositionId);
        $isGlobal = $crewPosition->vessel_id === null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique per vessel (or global), ignoring current position
                Rule::unique('crew_positions', 'name')
                    ->ignore($crewPosition->id)
                    ->where(function ($query) use ($vesselId, $isGlobal) {
                        if ($isGlobal) {
                            $query->whereNull('vessel_id');
                        } else {
                            $query->where('vessel_id', $vesselId);
                        }
                    }),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'vessel_role_access_id' => [
                'nullable',
                'integer',
                Rule::exists('vessel_role_accesses', 'id')->where('is_active', true),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The crew role name is required.',
            'name.max' => 'The crew role name may not be greater than 255 characters.',
            'name.unique' => 'A crew role with this name already exists.',
            'description.max' => 'The description may not be greater than 1000 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->name),
            'description' => $this->description ? trim($this->description) : null,
        ]);
    }
}

