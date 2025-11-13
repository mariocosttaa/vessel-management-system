<?php
namespace App\Http\Requests;

use App\Models\MovimentationCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreMovimentationCategoryRequest validates creating a new category.
 *
 * Input fields:
 * @property string $name
 * @property string $type (income or expense)
 * @property string|null $color
 * @property string|null $description
 *
 * Route parameters:
 * @property int $vessel (vessel_id comes from route parameter, validated by middleware)
 *
 * @mixin \Illuminate\Http\Request
 */
class StoreMovimentationCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get vessel ID from route parameter (may be hashed)
        $vesselParam = $this->route('vessel');
        $user        = $this->user();

        if (! $user) {
            return false;
        }

        // Check if user has admin or supervisor role for this vessel
        if (is_numeric($vesselParam)) {
            $vesselId = (int) $vesselParam;
        } else {
            // Unhash vessel ID
            $vessel = (new \App\Models\Vessel())->resolveRouteBinding($vesselParam);
            if (! $vessel) {
                return false;
            }
            $vesselId = $vessel->id;
        }

        return $user->hasAnyRoleForVessel($vesselId, ['Administrator', 'Supervisor']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Get vessel ID for unique name validation per vessel
        $vesselParam = $this->route('vessel');
        if (is_numeric($vesselParam)) {
            $vesselId = (int) $vesselParam;
        } else {
            $vessel   = (new \App\Models\Vessel())->resolveRouteBinding($vesselParam);
            $vesselId = $vessel?->id;
        }

        return [
            'name'        => [
                'required',
                'string',
                'max:100',
                // Unique name per vessel (system categories have vessel_id = null, so they won't conflict)
                Rule::unique('transaction_categories', 'name')
                    ->where(function ($query) use ($vesselId) {
                        $query->where('vessel_id', $vesselId)
                            ->where('type', $this->input('type'));
                    }),
            ],
            'type'        => ['required', 'string', 'in:income,expense'],
            'color'       => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The category name is required.',
            'name.unique'   => 'A category with this name already exists for this vessel.',
            'type.required' => 'The category type is required.',
            'type.in'       => 'The category type must be either income or expense.',
            'color.regex'   => 'The color must be a valid hex color code (e.g., #FF5733).',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'        => trim($this->name),
            'description' => $this->description ? trim($this->description) : null,
        ]);
    }
}
