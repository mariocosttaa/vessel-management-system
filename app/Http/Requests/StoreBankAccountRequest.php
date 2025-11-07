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
 * @property int|null $country_id
 * @property int $initial_balance
 * @property string $status
 * @property string|null $notes
 *
 * Route parameters:
 * @property int $vessel (vessel_id comes from route parameter, validated by middleware)
 *
 * Magic/inherited methods (MANDATORY):
 * @method bool hasFile(string $key)
 * @method \Illuminate\Http\UploadedFile|null file(string $key)
 * @method mixed route(string $key = null)
 * @method bool boolean(string $key)
 * @method array all()
 * @method void merge(array $data)
 * @method array input(string $key = null, mixed $default = null)
 * @method bool filled(string $key)
 * @method \Illuminate\Contracts\Auth\Authenticatable|null user()
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
            'country_id' => ['nullable', 'integer', Rule::exists(Country::class, 'id')],
            // vessel_id comes from route parameter (validated by middleware), not from form
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
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

            // If account_number is provided (and not IBAN), country_id is mandatory
            if (!empty($this->account_number) && empty($this->iban) && empty($this->country_id)) {
                $validator->errors()->add('country_id', 'Country is required when using Account Number. Please select a country from the dropdown.');
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
            'country_id.exists' => 'The selected country is invalid.',
            'initial_balance.numeric' => 'The initial balance must be a valid number.',
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
            'name' => trim($this->name),
            'bank_name' => trim($this->bank_name),
            'account_number' => $this->account_number ? trim($this->account_number) : null,
        ];

        // Handle initial balance - only normalize if provided
        if ($this->filled('initial_balance') && $this->initial_balance !== null && $this->initial_balance !== '') {
            $data['initial_balance'] = $this->normalizeMoney($this->initial_balance);
        } else {
            $data['initial_balance'] = 0;
        }

        // Normalize IBAN (country detection is done in controller)
        if ($this->iban) {
            $iban = strtoupper(preg_replace('/\s+/', '', trim($this->iban)));
            $data['iban'] = $iban;
        }

        $this->merge($data);
    }

    private function normalizeMoney($value): int
    {
        if (is_string($value)) {
            return MoneyAction::sanitize($value);
        }

        // If it's already an integer, it's already in cents (from MoneyInput with return-type="int")
        if (is_int($value)) {
            return $value;
        }

        // If it's a float, convert to cents
        if (is_float($value)) {
            return (int) round($value * 100);
        }

        // Default: treat as integer (already in cents)
        return (int) $value;
    }
}
