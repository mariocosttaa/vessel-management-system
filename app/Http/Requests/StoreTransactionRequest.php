<?php

namespace App\Http\Requests;

use App\Actions\MoneyAction;
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
 * @property int|null $marea_id
 * @property int|null $maintenance_id
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
        if (!$vesselId) {
            return false;
        }

        // Check if user has access to vessel
        /** @var \App\Models\User $user */
        if (!$user->hasAccessToVessel(is_object($vesselId) ? $vesselId->id : (int) $vesselId)) {
            return false;
        }

        // Check transactions.create permission from config
        $vesselIdInt = is_object($vesselId) ? $vesselId->id : (int) $vesselId;
        $userRole = $user->getRoleForVessel($vesselIdInt);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));

        return $permissions['transactions.create'] ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists(TransactionCategory::class, 'id')],
            'type' => ['required', 'string', 'in:income,expense,transfer'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'amount_per_unit' => ['nullable', 'numeric', 'min:0', 'required_with:quantity'],
            'quantity' => ['nullable', 'integer', 'min:1', 'required_with:amount_per_unit'],
            'currency' => ['required', 'string', 'size:3'],
            'house_of_zeros' => ['nullable', 'integer', 'min:0', 'max:4'],
            'vat_profile_id' => ['nullable', 'integer', Rule::exists(VatProfile::class, 'id')],
            'amount_includes_vat' => ['nullable', 'boolean'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'supplier_id' => ['nullable', 'integer', Rule::exists(Supplier::class, 'id')],
            'crew_member_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'marea_id' => ['nullable', 'integer', Rule::exists('mareas', 'id')],
            'maintenance_id' => ['nullable', 'integer', Rule::exists('maintenances', 'id')],
            'status' => ['nullable', 'string', 'in:pending,completed,cancelled'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => [
                'file',
                'max:10240', // 10MB max
                'mimes:pdf,jpg,jpeg,png,gif,doc,docx,xls,xlsx,txt,csv',
            ],
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
            // Validate that either amount or both amount_per_unit and quantity are provided
            if (!$this->filled('amount') && (!($this->filled('amount_per_unit') && $this->filled('quantity')))) {
                $validator->errors()->add('amount', 'Either amount or both amount_per_unit and quantity must be provided.');
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

            // Validate marea belongs to current vessel (if provided)
            if ($this->marea_id) {
                $vesselId = $this->route('vessel');
                $marea = \App\Models\Marea::find($this->marea_id);

                if ($marea && $marea->vessel_id !== (int) $vesselId) {
                    $validator->errors()->add('marea_id', 'The selected marea does not belong to this vessel.');
                }
            }

            // Validate maintenance belongs to current vessel (if provided)
            if ($this->maintenance_id) {
                $vesselId = $this->route('vessel');
                $maintenance = \App\Models\Maintenance::find($this->maintenance_id);

                if ($maintenance && $maintenance->vessel_id !== (int) $vesselId) {
                    $validator->errors()->add('maintenance_id', 'The selected maintenance does not belong to this vessel.');
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
            'files.max' => 'You can upload a maximum of 10 files.',
            'files.*.max' => 'Each file must not exceed 10MB.',
            'files.*.mimes' => 'The file must be one of the following types: PDF, JPG, JPEG, PNG, GIF, DOC, DOCX, XLS, XLSX, TXT, CSV.',
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

        // Normalize amount_per_unit and quantity if provided
        if ($this->filled('amount_per_unit')) {
            $data['amount_per_unit'] = $this->normalizeMoney($this->amount_per_unit);
        }
        if ($this->filled('quantity')) {
            $data['quantity'] = (int) $this->quantity;
        }

        // Calculate amount from amount_per_unit * quantity if both are provided
        if ($this->filled('amount_per_unit') && $this->filled('quantity')) {
            $amountPerUnit = $this->normalizeMoney($this->amount_per_unit);
            $quantity = (int) $this->quantity;
            $data['amount'] = (int) round($amountPerUnit * $quantity);
        } elseif ($this->filled('amount')) {
            // Normalize money amount - handle both string and numeric inputs
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

