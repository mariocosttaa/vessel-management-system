<?php

namespace App\Http\Requests;

use App\Actions\General\EasyHashAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreCrewPositionRequest validates creating a new crew position.
 *
 * Input fields:
 * @property string $name
 * @property string|null $description
 * @property bool $is_global
 * @property int|null $vessel_role_access_id
 *
 * Route parameters (for authorization only):
 * @property int $vessel (accessed via $this->route('vessel') for authorization)
 *
 * @method mixed route(string $key = null)
 * @method \Illuminate\Contracts\Auth\Authenticatable|null user()
 * @method bool boolean(string $key)
 * @method array all()
 * @method void merge(array $data)
 * @method array input(string $key = null, mixed $default = null)
 */
class StoreCrewPositionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get vessel ID from route parameter for authorization check only
        $vessel = $this->route('vessel');
        /** @var \App\Models\User|null $user */
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // Extract vessel ID (handle both model instance and ID)
        $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;

        // Check if user can manage crew (for crew roles management)
        // This allows administrators and supervisors to create crew roles
        return $user->hasVesselPermission($vesselId, 'manage_crew');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $vessel = $this->route('vessel');
        // Extract vessel ID (handle both model instance and ID)
        $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
        $isGlobal = $this->boolean('is_global');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique per vessel (or global if is_global is true)
                Rule::unique('crew_positions', 'name')
                    ->where(function ($query) use ($vesselId, $isGlobal) {
                        if ($isGlobal) {
                            $query->whereNull('vessel_id');
                        } else {
                            $query->where('vessel_id', $vesselId);
                        }
                    }),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_global' => ['nullable', 'boolean'],
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
        $data = [];

        // Unhash IDs from frontend
        if ($this->filled('vessel_role_access_id')) {
            $data['vessel_role_access_id'] = EasyHashAction::decode($this->vessel_role_access_id, 'vesselroleaccess-id');
        }

        $this->merge(array_merge($data, [
            'name' => trim($this->name),
            'description' => $this->description ? trim($this->description) : null,
            'is_global' => $this->boolean('is_global') ?? false,
        ]));
    }
}

