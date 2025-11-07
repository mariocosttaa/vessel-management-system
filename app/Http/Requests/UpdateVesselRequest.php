<?php

namespace App\Http\Requests;

use App\Models\Vessel;
use App\Models\Country;
use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property string $name
 * @property string $registration_number
 * @property string $vessel_type
 * @property int|null $capacity
 * @property int|null $year_built
 * @property string $status
 * @property string|null $notes
 * @property string|null $country_code
 * @property string|null $currency_code
 * @method array all()
 * @method mixed input(string $key = null, mixed $default = null)
 * @method void merge(array $data)
 * @method mixed route(string $key = null)
 */
class UpdateVesselRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $vessel = $this->route('vessel');
        return $this->user()?->canEditVessel($vessel->id) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $vessel = $this->route('vessel');

        return [
            'name' => ['required', 'string', 'max:255'],
            'registration_number' => ['required', 'string', 'max:100', Rule::unique(Vessel::class, 'registration_number')->ignore($vessel->id)],
            'vessel_type' => ['required', 'in:cargo,passenger,fishing,yacht'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'year_built' => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'status' => ['required', 'in:active,suspended,maintenance'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'country_code' => ['nullable', 'string', 'size:2', Rule::exists(Country::class, 'code')],
            'currency_code' => ['nullable', 'string', 'size:3', Rule::exists(Currency::class, 'code')],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The vessel name is required.',
            'name.max' => 'The vessel name may not be greater than 255 characters.',
            'registration_number.required' => 'The registration number is required.',
            'registration_number.unique' => 'This registration number is already in use.',
            'vessel_type.required' => 'Please select a vessel type.',
            'vessel_type.in' => 'The selected vessel type is invalid.',
            'capacity.min' => 'Capacity must be at least 1.',
            'year_built.min' => 'Year built must be at least 1900.',
            'year_built.max' => 'Year built cannot be in the future.',
            'status.required' => 'Please select a status.',
            'status.in' => 'The selected status is invalid.',
            'notes.max' => 'Notes may not be greater than 1000 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->name),
            'registration_number' => strtoupper(trim($this->registration_number)),
            'notes' => $this->notes ? trim($this->notes) : null,
        ]);
    }
}
