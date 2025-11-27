<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreCrewPositionRequest validates creating a new crew position.
 *
 * Input fields:
 * @property string $name
 * @property bool $is_global
 * @property bool $is_administrative
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
        /** @var \App\Models\User|null $user */
        $user = $this->user();

        if (! $user) {
            return false;
        }

        // Get vessel ID from request attributes (set by EnsureVesselAccess middleware)
        $vesselId = (int) $this->attributes->get('vessel_id', 0);

        if (! $vesselId) {
            return false;
        }

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
        // Get vessel ID from request attributes (set by EnsureVesselAccess middleware)
        $vesselId = (int) $this->attributes->get('vessel_id', 0);
        $isGlobal = $this->boolean('is_global');

        return [
            'name'      => [
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
            'is_global'            => ['nullable', 'boolean'],
            'is_administrative'    => ['nullable', 'boolean'],
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
            'name.max'      => 'The crew role name may not be greater than 255 characters.',
            'name.unique'   => 'A crew role with this name already exists.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'               => trim($this->name),
            'is_global'          => $this->boolean('is_global') ?? false,
            'is_administrative'  => $this->boolean('is_administrative') ?? false,
        ]);
    }
}
