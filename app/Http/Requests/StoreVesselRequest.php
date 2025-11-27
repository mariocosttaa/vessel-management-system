<?php

namespace App\Http\Requests;

use App\Models\Vessel;
use App\Models\Country;
use App\Models\Currency;
use App\Models\VatProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property string $name
 * @property string $registration_number
 * @property int|null $capacity
 * @property int|null $year_built
 * @property string|null $notes
 * @property string $country_code
 * @property string $currency_code
 * @property int $vat_profile_id
 * @method array all()
 * @method mixed input(string $key = null, mixed $default = null)
 * @method void merge(array $data)
 */
class StoreVesselRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canCreateVessels() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'registration_number' => ['required', 'string', 'max:100', Rule::unique(Vessel::class, 'registration_number')],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'year_built' => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'notes' => ['nullable', 'string', 'max:1000'],
            'country_code' => ['required', 'string', 'size:2', Rule::exists(Country::class, 'code')],
            'currency_code' => ['required', 'string', 'size:3', Rule::exists(Currency::class, 'code')],
            'vat_profile_id' => ['required', 'integer', Rule::exists(VatProfile::class, 'id')],
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
            'capacity.min' => 'Capacity must be at least 1.',
            'year_built.min' => 'Year built must be at least 1900.',
            'year_built.max' => 'Year built cannot be in the future.',
            'notes.max' => 'Notes may not be greater than 1000 characters.',
            'country_code.required' => 'Please select a country.',
            'country_code.exists' => 'The selected country is invalid.',
            'currency_code.required' => 'Please select a currency.',
            'currency_code.exists' => 'The selected currency is invalid.',
            'vat_profile_id.required' => 'Please select a VAT profile.',
            'vat_profile_id.exists' => 'The selected VAT profile is invalid.',
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
