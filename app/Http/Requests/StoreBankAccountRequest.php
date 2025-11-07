<?php

namespace App\Http\Requests;

use App\Actions\MoneyAction;
use App\Models\BankAccount;
use App\Models\Country;
use App\Models\Vessel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreBankAccountRequest validates creating a new bank account.
 *
 * Input fields:
 * @property string $name
 * @property string $bank_name
 * @property string|null $account_number
 * @property string|null $iban
 * @property int $vessel_id
 * @property int $initial_balance
 * @property string $status
 * @property string|null $notes
 *
 * Magic/inherited methods (MANDATORY):
 * @method bool hasFile(string $key)
 * @method \Illuminate\Http\UploadedFile|null file(string $key)
 * @method mixed route(string $key = null)
 * @method bool boolean(string $key)
 * @method array all()
 * @method void merge(array $data)
 * @method array input(string $key = null, mixed $default = null)
 *
 * @mixin \Illuminate\Http\Request
 */
class StoreBankAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get vessel ID from route parameter
        $vesselId = $this->route('vessel');

        // Check if user has admin or manager role for this specific vessel
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
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'iban' => ['nullable', 'string', 'max:34', 'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', Rule::unique(BankAccount::class, 'iban')],
            'country_id' => ['required', 'integer', Rule::exists(Country::class, 'id')],
            'vessel_id' => ['required', 'integer', Rule::exists(Vessel::class, 'id')],
            'initial_balance' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Require either IBAN or account_number
            if (empty($this->iban) && empty($this->account_number)) {
                $validator->errors()->add('iban', 'Either IBAN or Account Number must be provided.');
                $validator->errors()->add('account_number', 'Either IBAN or Account Number must be provided.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The account name is required.',
            'bank_name.required' => 'The bank name is required.',
            'iban.regex' => 'The IBAN format is invalid.',
            'iban.unique' => 'This IBAN is already registered.',
            'country_id.required' => 'Please select a country.',
            'country_id.exists' => 'The selected country is invalid.',
            'initial_balance.required' => 'The initial balance is required.',
            'initial_balance.min' => 'The initial balance must be at least 0.',
            'status.required' => 'Please select a status.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [
            'status' => $this->status ?? 'active',
            'initial_balance' => $this->normalizeMoney($this->initial_balance),
            'name' => trim($this->name),
            'bank_name' => trim($this->bank_name),
            'account_number' => $this->account_number ? trim($this->account_number) : null,
        ];

        // Normalize IBAN and auto-detect country
        if ($this->iban) {
            $iban = strtoupper(preg_replace('/\s+/', '', trim($this->iban)));
            $data['iban'] = $iban;

            // Auto-detect country from IBAN if not provided
            if (!$this->filled('country_id') && strlen($iban) >= 2) {
                $countryCode = substr($iban, 0, 2);
                $country = Country::byCode($countryCode)->first();
                if ($country) {
                    $data['country_id'] = $country->id;
                }
            }
        }

        $this->merge($data);
    }

    private function normalizeMoney($value): int
    {
        if (is_string($value)) {
            return MoneyAction::sanitize($value);
        }

        return (int) round((float) $value * 100); // Convert to cents for numeric input
    }
}
