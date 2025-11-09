<?php

namespace App\Http\Requests;

use App\Models\Country;
use App\Models\Currency;
use App\Models\VatProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property string|null $country_code
 * @property string|null $currency_code
 * @property int|null $vat_profile_id
 */
class StoreVesselSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get vessel ID from route parameter
        $vesselId = $this->route('vessel');

        // Check if user has permission to manage vessel settings
        // Only administrators and supervisors can manage settings
        return $this->user()?->hasAnyRoleForVessel($vesselId, ['Administrator', 'Supervisor']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'country_code' => ['nullable', 'string', 'size:2', Rule::exists(Country::class, 'code')],
            'currency_code' => ['nullable', 'string', 'size:3', Rule::exists(Currency::class, 'code')],
            'vat_profile_id' => ['nullable', 'integer', Rule::exists(VatProfile::class, 'id')],
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
            'country_code.exists' => 'The selected country is invalid.',
            'currency_code.exists' => 'The selected currency is invalid.',
            'vat_profile_id.exists' => 'The selected VAT profile is invalid.',
        ];
    }
}
