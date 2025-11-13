<?php

namespace App\Http\Requests;

use App\Actions\General\EasyHashAction;
use App\Actions\MoneyAction;
use App\Models\MovimentationCategory;
use App\Models\User;
use App\Models\VatProfile;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateMovimentationRequest validates updating an existing transaction.
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
 * @property string $status
 *
 * Route parameters:
 * @property int $vessel (vessel_id comes from route parameter, validated by middleware)
 * @property \App\Models\Movimentation $transaction (transaction being updated)
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
class UpdateMovimentationRequest extends FormRequest
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
        $vessel = $this->route('vessel');
        if (!$vessel) {
            return false;
        }

        // Handle both route model binding (object) and hashed ID (string)
        if (is_object($vessel)) {
            $vesselIdInt = $vessel->id;
        } elseif (is_numeric($vessel)) {
            $vesselIdInt = (int) $vessel;
        } else {
            // Decode hashed vessel ID
            $decoded = \App\Actions\General\EasyHashAction::decode($vessel, 'vessel-id');
            $vesselIdInt = $decoded && is_numeric($decoded) ? (int) $decoded : null;
            if (!$vesselIdInt) {
                return false;
            }
        }
        if (!$user->hasAccessToVessel($vesselIdInt)) {
            return false;
        }

        // Check movimentations.edit permission from config
        $userRole = $user->getRoleForVessel($vesselIdInt);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));

        return $permissions['movimentations.edit'] ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists(MovimentationCategory::class, 'id')],
            'type' => ['required', 'string', 'in:income,expense,transfer'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'amount_per_unit' => ['nullable', 'numeric', 'min:0', 'required_with:quantity'],
            'quantity' => ['nullable', 'integer', 'min:1', 'required_with:amount_per_unit'],
            'currency' => ['required', 'string', 'size:3'],
            'house_of_zeros' => ['nullable', 'integer', 'min:0', 'max:4'],
            'vat_profile_id' => ['nullable', 'integer', Rule::exists(VatProfile::class, 'id')],
            'amount_includes_vat' => ['nullable', 'boolean'],
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reference' => ['nullable', 'string', 'max:100'],
            'supplier_id' => ['nullable', 'integer', Rule::exists(Supplier::class, 'id')],
            'crew_member_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'status' => ['required', 'string', 'in:pending,completed,cancelled'],
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
                $category = MovimentationCategory::find($this->category_id);

                if ($category && $category->type !== $this->type) {
                    $validator->errors()->add('category_id', 'The selected category type does not match the transaction type.');
                }
            }

            // Verify transaction belongs to current vessel
            $transaction = $this->route('transaction');
            if ($transaction) {
                $vesselId = $this->route('vessel');

                if ($transaction->vessel_id !== (int) $vesselId) {
                    $validator->errors()->add('transaction', 'This transaction does not belong to the current vessel.');
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
            'vat_profile_id.exists' => 'The selected VAT profile is invalid.',
            'supplier_id.exists' => 'The selected supplier is invalid.',
            'crew_member_id.exists' => 'The selected crew member is invalid.',
            'status.required' => 'Status is required.',
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

        // Get vessel ID from route parameter (validated by EnsureVesselAccess middleware)
        // CRITICAL: Never get vessel_id from transaction model in prepareForValidation
        // Route model binding may not have occurred yet, and we should trust the route parameter
        $vessel = $this->route('vessel');

        // Handle both route model binding (object) and hashed ID (string)
        if (is_object($vessel)) {
            $vesselId = $vessel->id;
        } elseif (is_numeric($vessel)) {
            $vesselId = (int) $vessel;
        } else {
            // Decode hashed vessel ID
            $decoded = \App\Actions\General\EasyHashAction::decode($vessel, 'vessel-id');
            $vesselId = $decoded && is_numeric($decoded) ? (int) $decoded : null;
            if (!$vesselId) {
                abort(404, 'Vessel not found.');
            }
        }

        // Get currency from vessel_settings (priority) or vessel currency_code
        $vesselSetting = \App\Models\VesselSetting::getForVessel($vesselId);
        $vessel = \App\Models\Vessel::find($vesselId);
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel?->currency_code ?? 'EUR';

        // IMPORTANT: Preserve ALL fields from request, especially category_id, transaction_date, and amount
        // These are required fields and must be preserved exactly as sent
        $data = [];

        // Unhash IDs from frontend
        if ($this->filled('category_id')) {
            // Handle both hashed and numeric category IDs
            if (is_numeric($this->category_id)) {
                $data['category_id'] = (int) $this->category_id;
            } else {
                $decoded = EasyHashAction::decode($this->category_id, 'transactioncategory-id');
                $data['category_id'] = $decoded && is_numeric($decoded) ? (int) $decoded : null;
            }
        }

        if ($this->filled('vat_profile_id')) {
            $data['vat_profile_id'] = EasyHashAction::decode($this->vat_profile_id, 'vatprofile-id');
        }

        if ($this->filled('supplier_id')) {
            $data['supplier_id'] = EasyHashAction::decode($this->supplier_id, 'supplier-id');
        }

        if ($this->filled('crew_member_id')) {
            $data['crew_member_id'] = EasyHashAction::decode($this->crew_member_id, 'user-id');
        }

        // Preserve transaction_date - required field, must be present
        if ($this->has('transaction_date') && $this->transaction_date !== null && $this->transaction_date !== '') {
            $data['transaction_date'] = $this->normalizeDate($this->transaction_date);
        }

        // Preserve type - required field
        if ($this->has('type')) {
            $data['type'] = $this->type ?? 'expense';
        } else {
            $data['type'] = 'expense';
        }

        // Preserve status - required field
        if ($this->has('status')) {
            $data['status'] = $this->status ?? 'completed';
        } else {
            $data['status'] = 'completed';
        }

        // Handle amount - can come from amount_per_unit * quantity OR direct amount
        // Priority: amount_per_unit * quantity > direct amount
        if ($this->has('amount_per_unit') && $this->has('quantity') &&
            $this->amount_per_unit !== null && $this->quantity !== null) {
            // Calculate amount from amount_per_unit * quantity
            $amountPerUnit = $this->normalizeMoney($this->amount_per_unit);
            $quantity = (int) $this->quantity;
            $data['amount'] = (int) round($amountPerUnit * $quantity);
            $data['amount_per_unit'] = $amountPerUnit;
            $data['quantity'] = $quantity;
        } elseif ($this->has('amount') && $this->amount !== null) {
            // Use direct amount
            $data['amount'] = $this->normalizeMoney($this->amount);
            $data['amount_per_unit'] = null;
            $data['quantity'] = null;
        } else {
            // No amount provided - set to null (validation will catch this)
            $data['amount'] = null;
            $data['amount_per_unit'] = null;
            $data['quantity'] = null;
        }

        // Preserve currency - use from request if provided, otherwise use vessel settings
        if ($this->has('currency') && $this->currency !== null && $this->currency !== '') {
            $data['currency'] = strtoupper($this->currency);
        } else {
            $data['currency'] = strtoupper($defaultCurrency);
        }

        // Preserve house_of_zeros
        if ($this->has('house_of_zeros')) {
            $data['house_of_zeros'] = $this->house_of_zeros ?? 2;
        } else {
            $data['house_of_zeros'] = 2;
        }

        // Preserve optional fields
        if ($this->has('vat_profile_id')) {
            $data['vat_profile_id'] = $this->vat_profile_id ? (int) $this->vat_profile_id : null;
        }

        if ($this->has('amount_includes_vat')) {
            $data['amount_includes_vat'] = (bool) $this->amount_includes_vat;
        }

        if ($this->has('description')) {
            $data['description'] = $this->description ? trim($this->description) : null;
        }

        if ($this->has('notes')) {
            $data['notes'] = $this->notes ? trim($this->notes) : null;
        }

        if ($this->has('reference')) {
            $data['reference'] = $this->reference ? trim($this->reference) : null;
        }

        if ($this->has('supplier_id')) {
            $data['supplier_id'] = $this->supplier_id ? (int) $this->supplier_id : null;
        }

        if ($this->has('crew_member_id')) {
            $data['crew_member_id'] = $this->crew_member_id ? (int) $this->crew_member_id : null;
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

