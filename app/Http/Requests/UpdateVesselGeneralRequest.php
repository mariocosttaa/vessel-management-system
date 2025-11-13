<?php
namespace App\Http\Requests;

use App\Models\Vessel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateVesselGeneralRequest validates updating vessel general information.
 *
 * Input fields:
 * @property string $name
 * @property string $registration_number
 * @property string $vessel_type
 * @property int|null $capacity
 * @property int|null $year_built
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Http\UploadedFile|null $logo
 *
 * Route parameters:
 * @property int $vessel (accessed via $this->route('vessel') for authorization)
 *
 * @method mixed route(string $key = null)
 * @method \Illuminate\Contracts\Auth\Authenticatable|null user()
 * @method bool boolean(string $key)
 * @method array all()
 * @method array input(string $key = null, mixed $default = null)
 */
class UpdateVesselGeneralRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = $this->user();

        if (! $user) {
            return false;
        }

        // Get vessel ID from route parameter
        $vessel = $this->route('vessel');
        if (! $vessel) {
            return false;
        }

        // Handle both route model binding (object) and hashed ID (string)
        $vesselIdInt = null;
        if (is_object($vessel)) {
            $vesselIdInt = $vessel->id;
        } elseif (is_numeric($vessel)) {
            $vesselIdInt = (int) $vessel;
        } else {
            // Decode hashed vessel ID
            $decoded     = \App\Actions\General\EasyHashAction::decode($vessel, 'vessel-id');
            $vesselIdInt = $decoded && is_numeric($decoded) ? (int) $decoded : null;
            if (! $vesselIdInt) {
                return false;
            }
        }

        if (! $user->hasAccessToVessel($vesselIdInt)) {
            return false;
        }

        // Check settings.access permission from config
        $userRole    = $user->getRoleForVessel($vesselIdInt);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));

        return $permissions['settings.access'] ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $vessel = $this->route('vessel');

        // Handle both route model binding (object) and hashed ID (string)
        $vesselIdInt = null;
        if (is_object($vessel)) {
            $vesselIdInt = $vessel->id;
        } elseif (is_numeric($vessel)) {
            $vesselIdInt = (int) $vessel;
        } else {
            // Decode hashed vessel ID
            $decoded     = \App\Actions\General\EasyHashAction::decode($vessel, 'vessel-id');
            $vesselIdInt = $decoded && is_numeric($decoded) ? (int) $decoded : null;
        }

        return [
            'name'                => ['required', 'string', 'max:255'],
            'registration_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique(Vessel::class, 'registration_number')->ignore($vesselIdInt),
            ],
            'vessel_type'         => ['required', 'string', 'max:100'],
            'capacity'            => ['nullable', 'integer', 'min:0'],
            'year_built'          => ['nullable', 'integer', 'min:1800', 'max:' . (date('Y') + 1)],
            'status'              => ['required', 'string', Rule::in(['active', 'suspended', 'maintenance'])],
            'notes'               => ['nullable', 'string'],
            'logo'                => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            'remove_logo'         => ['nullable', 'boolean'],
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
            'name.required'                => 'The vessel name is required.',
            'registration_number.required' => 'The registration number is required.',
            'registration_number.unique'   => 'This registration number is already in use.',
            'vessel_type.required'         => 'The vessel type is required.',
            'capacity.integer'             => 'The capacity must be a number.',
            'capacity.min'                 => 'The capacity must be at least 0.',
            'year_built.integer'           => 'The year built must be a valid year.',
            'year_built.min'               => 'The year built must be after 1800.',
            'year_built.max'               => 'The year built cannot be in the future.',
            'status.required'              => 'The status is required.',
            'status.in'                    => 'The status must be active, suspended, or maintenance.',
        ];
    }
}
