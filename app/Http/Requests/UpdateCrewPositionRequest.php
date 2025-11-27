<?php
namespace App\Http\Requests;

use App\Actions\General\EasyHashAction;
use App\Models\CrewPosition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateCrewPositionRequest validates updating an existing crew position.
 *
 * Input fields:
 * @property string $name
 * @property bool $is_administrative
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

        // Get crew position from route parameter
        $crewPositionId = $this->route('crewPosition');

        // Debug logging
        \Illuminate\Support\Facades\Log::info('UpdateCrewPositionRequest authorize', [
            'crewPositionId_type' => gettype($crewPositionId),
            'crewPositionId_value' => is_object($crewPositionId) ? get_class($crewPositionId) : $crewPositionId,
            'is_object' => is_object($crewPositionId),
            'is_instanceof' => is_object($crewPositionId) && $crewPositionId instanceof CrewPosition,
        ]);

        // Resolve crew position manually (handle both model instance, numeric ID, and hashed ID)
        if (is_object($crewPositionId) && $crewPositionId instanceof CrewPosition) {
            $crewPosition = $crewPositionId;
        } elseif (is_numeric($crewPositionId)) {
            $crewPosition = CrewPosition::findOrFail((int) $crewPositionId);
        } else {
            // Handle hashed ID
            $decoded = EasyHashAction::decode($crewPositionId, 'crewposition-id');
            if (! $decoded || ! is_numeric($decoded)) {
                \Illuminate\Support\Facades\Log::warning('UpdateCrewPositionRequest authorize - Failed to decode hashed ID', [
                    'crewPositionId' => $crewPositionId,
                    'decoded' => $decoded,
                ]);
                return false;
            }
            $crewPosition = CrewPosition::findOrFail((int) $decoded);
        }

        // Check if user can manage crew (for crew roles management)
        // This allows administrators and supervisors to edit crew roles
        if (! $user->hasVesselPermission($vesselId, 'manage_crew')) {
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
        // Get vessel ID from request attributes (set by EnsureVesselAccess middleware)
        $vesselId = (int) $this->attributes->get('vessel_id', 0);

        // Get crew position from route parameter
        $crewPositionId = $this->route('crewPosition');

        // Resolve crew position manually (handle both model instance, numeric ID, and hashed ID)
        if (is_object($crewPositionId) && $crewPositionId instanceof CrewPosition) {
            $crewPosition = $crewPositionId;
        } elseif (is_numeric($crewPositionId)) {
            $crewPosition = CrewPosition::findOrFail((int) $crewPositionId);
        } else {
            // Handle hashed ID
            $decoded = EasyHashAction::decode($crewPositionId, 'crewposition-id');
            if (! $decoded || ! is_numeric($decoded)) {
                abort(404, 'Crew position not found.');
            }
            $crewPosition = CrewPosition::findOrFail((int) $decoded);
        }
        $isGlobal     = $crewPosition->vessel_id === null;

        return [
            'name'                  => [
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
            'name'              => trim($this->name),
            'is_administrative' => $this->boolean('is_administrative') ?? false,
        ]);
    }
}
