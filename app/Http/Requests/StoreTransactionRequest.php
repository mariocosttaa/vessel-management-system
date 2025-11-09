<?php

namespace App\Http\Requests;

use App\Actions\MoneyAction;
use App\Models\BankAccount;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Models\VatProfile;
use App\Models\Vessel;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreTransactionRequest validates creating a new transaction.
 *
 * Input fields:
 * @property int $bank_account_id
 * @property int $category_id
 * @property string $type
 * @property int $amount
 * @property string $currency
 * @property int $house_of_zeros
 * @property int|null $vat_profile_id
 * @property bool $amount_includes_vat
 * @property string $transaction_date
 * @property string|null $description
 * @property string|null $notes
 * @property string|null $reference
 * @property int|null $supplier_id
 * @property int|null $crew_member_id
 * @property string $status
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
class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // Get vessel ID from route parameter
        $vesselId = $this->route('vessel');

        // Check if user has admin or supervisor role for this specific vessel
        /** @var \App\Models\User $user */
        return $user->hasAnyRoleForVessel($vesselId, ['Administrator', 'Supervisor']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get vessel ID to check if bank accounts exist
        $vesselId = $this->route('vessel');
        $hasBankAccounts = BankAccount::where('vessel_id', $vesselId)->exists();

        return [
            'bank_account_id' => $hasBankAccounts
                ? ['required', 'integer', Rule::exists(BankAccount::class, 'id')]
                : ['nullable', 'integer', Rule::exists(BankAccount::class, 'id')],
            'category_id' => ['required', 'integer', Rule::exists(TransactionCategory::class, 'id')],
            'type' => ['required', 'string', 'in:income,expense,transfer'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'house_of_zeros' => ['nullable', 'integer', 'min:0', 'max:4'],
            'vat_profile_id' => ['nullable', 'integer', Rule::exists(VatProfile::class, 'id')],
            'amount_includes_vat' => ['nullable', 'boolean'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'supplier_id' => ['nullable', 'integer', Rule::exists(Supplier::class, 'id')],
            'crew_member_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'status' => ['nullable', 'string', 'in:pending,completed,cancelled'],
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
            // Validate bank account belongs to current vessel
            if ($this->bank_account_id) {
                $vesselId = $this->route('vessel');
                $bankAccount = BankAccount::find($this->bank_account_id);

                if ($bankAccount && $bankAccount->vessel_id !== (int) $vesselId) {
                    $validator->errors()->add('bank_account_id', 'The selected bank account does not belong to this vessel.');
                }
            }

            // Validate supplier belongs to current vessel (if provided)
            if ($this->supplier_id) {
                $vesselId = $this->route('vessel');
                $supplier = Supplier::find($this->supplier_id);

                if ($supplier && $supplier->vessel_id !== (int) $vesselId) {
                    $validator->errors()->add('supplier_id', 'The selected supplier does not belong to this vessel.');
                }
            }

            // Validate crew member belongs to current vessel (if provided)
            if ($this->crew_member_id) {
                $vesselId = $this->route('vessel');
                $crewMember = User::find($this->crew_member_id);

                if ($crewMember && $crewMember->vessel_id !== (int) $vesselId) {
                    $validator->errors()->add('crew_member_id', 'The selected crew member does not belong to this vessel.');
                }
            }

            // Validate category type matches transaction type
            if ($this->category_id) {
                $category = TransactionCategory::find($this->category_id);

                if ($category && $category->type !== $this->type) {
                    $validator->errors()->add('category_id', 'The selected category type does not match the transaction type.');
                }
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
            'bank_account_id.required' => 'Please select a bank account.',
            'bank_account_id.exists' => 'The selected bank account is invalid.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'type.required' => 'Please select transaction type.',
            'type.in' => 'Transaction type must be income, expense, or transfer.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount must be greater than zero.',
            'currency.required' => 'Currency is required.',
            'currency.size' => 'Currency must be a 3-character code (e.g., EUR, USD).',
            'transaction_date.required' => 'Transaction date is required.',
            'transaction_date.date' => 'Transaction date must be a valid date.',
            'transaction_date.before_or_equal' => 'Transaction date cannot be in the future.',
            'supplier_id.exists' => 'The selected supplier is invalid.',
            'crew_member_id.exists' => 'The selected crew member is invalid.',
            'status.in' => 'Status must be pending, completed, or cancelled.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Get vessel ID and currency from vessel_settings (priority) or vessel currency_code
        $vesselId = $this->route('vessel');
        $vesselSetting = \App\Models\VesselSetting::getForVessel($vesselId);
        $vessel = \App\Models\Vessel::find($vesselId);
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel?->currency_code ?? 'EUR';

        $data = [
            'type' => $this->type ?? 'expense',
            'status' => $this->status ?? 'completed',
            // Use currency from request if provided, otherwise use vessel settings currency (not EUR hardcoded)
            'currency' => strtoupper($this->currency ?? $defaultCurrency),
            'house_of_zeros' => $this->house_of_zeros ?? 2,
            'description' => $this->description ? trim($this->description) : null,
            'notes' => $this->notes ? trim($this->notes) : null,
        ];

        // Normalize money amount - handle both string and numeric inputs
        if ($this->filled('amount')) {
            $data['amount'] = $this->normalizeMoney($this->amount);
        }

        // Normalize transaction date
        if ($this->filled('transaction_date')) {
            $data['transaction_date'] = $this->normalizeDate($this->transaction_date);
        }

        $this->merge($data);
    }

    /**
     * Normalize money input to integer (cents).
     */
    private function normalizeMoney($value): int
    {
        // If it's already an integer, it's already in cents (from MoneyInput with return-type="int")
        if (is_int($value)) {
            return $value;
        }

        // If it's a string, sanitize it
        if (is_string($value)) {
            // MoneyAction::sanitize removes ALL non-numeric, so "123.45" becomes "12345"
            // But we need to handle it properly - if user enters "123.45", that means 123.45 EUR
            // So we need to detect if there's a decimal point and handle accordingly
            $hasDecimal = strpos($value, '.') !== false || strpos($value, ',') !== false;

            if ($hasDecimal) {
                // Remove currency symbols and normalize decimal separator
                $cleanValue = preg_replace('/[^\d.,]/', '', $value);
                $cleanValue = str_replace(',', '.', $cleanValue);
                // Convert to float then to cents
                return (int) round((float) $cleanValue * 100);
            } else {
                // No decimal point, treat as already in smallest unit
                // But if it's a large number like "12345", assume it's 123.45 EUR (cents)
                // Actually, if it comes from MoneyInput, it should already be in cents
                return MoneyAction::sanitize($value);
            }
        }

        // If it's a float, convert to cents
        if (is_float($value)) {
            return (int) round($value * 100);
        }

        // Default: treat as integer (already in cents)
        return (int) $value;
    }

    /**
     * Normalize date input.
     */
    private function normalizeDate($date): string
    {
        if (empty($date)) {
            return $date;
        }

        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return $date; // Let validation handle invalid dates
        }
    }
}

