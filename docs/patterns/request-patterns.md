# Request Patterns

## Structure and Naming Conventions

### Request Naming
- Store requests: `Store{Entity}Request` (e.g., `StoreTransactionRequest`)
- Update requests: `Update{Entity}Request` (e.g., `UpdateTransactionRequest`)
- Place in `app/Http/Requests/`

### Basic Structure
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Transaction::class);
    }

    public function rules(): array
    {
        return [
            'vessel_id' => ['nullable', 'exists:vessels,id'],
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'category_id' => ['required', 'exists:transaction_categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'crew_member_id' => ['nullable', 'exists:crew_members,id'],
            'type' => ['required', 'in:income,expense,transfer'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'vat_rate_id' => ['nullable', 'exists:vat_rates,id'],
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_account_id.required' => 'Please select a bank account.',
            'category_id.required' => 'Please select a category.',
            'type.required' => 'Please select transaction type.',
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be greater than zero.',
            'transaction_date.required' => 'Transaction date is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->normalizeMoney($this->amount),
            'currency' => strtoupper($this->currency ?? 'EUR'),
        ]);
    }

    private function normalizeMoney($value): int
    {
        if (is_string($value)) {
            // Remove currency symbols and convert to float
            $value = preg_replace('/[^\d.,]/', '', $value);
            $value = str_replace(',', '.', $value);
        }
        
        return (int) round((float) $value * 100); // Convert to cents
    }
}
```

## Validation Rules Organization

### Grouped Rules by Entity Type

#### Transaction Rules
```php
public function rules(): array
{
    return [
        // Required relationships
        'bank_account_id' => ['required', 'exists:bank_accounts,id'],
        'category_id' => ['required', 'exists:transaction_categories,id'],
        
        // Optional relationships
        'vessel_id' => ['nullable', 'exists:vessels,id'],
        'supplier_id' => ['nullable', 'exists:suppliers,id'],
        'crew_member_id' => ['nullable', 'exists:crew_members,id'],
        
        // Transaction details
        'type' => ['required', 'in:income,expense,transfer'],
        'amount' => ['required', 'numeric', 'min:0'],
        'currency' => ['required', 'string', 'size:3'],
        
        // VAT
        'vat_rate_id' => ['nullable', 'exists:vat_rates,id'],
        
        // Dates and descriptions
        'transaction_date' => ['required', 'date', 'before_or_equal:today'],
        'description' => ['nullable', 'string', 'max:500'],
        'notes' => ['nullable', 'string', 'max:1000'],
        'reference' => ['nullable', 'string', 'max:100'],
    ];
}
```

#### Vessel Rules
```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'registration_number' => ['required', 'string', 'max:100', 'unique:vessels'],
        'vessel_type' => ['required', 'in:cargo,passenger,fishing,yacht'],
        'capacity' => ['nullable', 'integer', 'min:1'],
        'year_built' => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
        'status' => ['required', 'in:active,maintenance,inactive'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ];
}
```

#### Crew Member Rules
```php
public function rules(): array
{
    return [
        'vessel_id' => ['nullable', 'exists:vessels,id'],
        'position_id' => ['required', 'exists:crew_positions,id'],
        'name' => ['required', 'string', 'max:255'],
        'document_number' => ['required', 'string', 'max:50', 'unique:crew_members'],
        'email' => ['nullable', 'email', 'max:255'],
        'phone' => ['nullable', 'string', 'max:50'],
        'date_of_birth' => ['nullable', 'date', 'before:today'],
        'hire_date' => ['required', 'date', 'before_or_equal:today'],
        'salary_amount' => ['required', 'numeric', 'min:0'],
        'salary_currency' => ['required', 'string', 'size:3'],
        'payment_frequency' => ['required', 'in:weekly,biweekly,monthly'],
        'status' => ['required', 'in:active,inactive,on_leave'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ];
}
```

## Authorization Logic

### Policy-Based Authorization
```php
public function authorize(): bool
{
    return $this->user()->can('create', Transaction::class);
}

// For update requests
public function authorize(): bool
{
    return $this->user()->can('update', $this->route('transaction'));
}
```

### Role-Based Authorization
```php
public function authorize(): bool
{
    return $this->user()->hasRole(['admin', 'manager']);
}

// Or more specific
public function authorize(): bool
{
    if ($this->user()->hasRole('admin')) {
        return true;
    }
    
    if ($this->user()->hasRole('manager')) {
        return $this->type !== 'transfer'; // Managers can't create transfers
    }
    
    return false;
}
```

## Data Normalization

### Money Normalization
```php
protected function prepareForValidation(): void
{
    $this->merge([
        'amount' => $this->normalizeMoney($this->amount),
        'currency' => strtoupper($this->currency ?? 'EUR'),
        'house_of_zeros' => 2, // Default to 2 decimal places
    ]);
}

private function normalizeMoney($value): int
{
    if (is_string($value)) {
        // Remove currency symbols and convert to float
        $value = preg_replace('/[^\d.,]/', '', $value);
        $value = str_replace(',', '.', $value);
    }
    
    return (int) round((float) $value * 100); // Convert to cents
}
```

### Date Normalization
```php
protected function prepareForValidation(): void
{
    $this->merge([
        'transaction_date' => $this->normalizeDate($this->transaction_date),
        'hire_date' => $this->normalizeDate($this->hire_date),
    ]);
}

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
```

### String Normalization
```php
protected function prepareForValidation(): void
{
    $this->merge([
        'name' => trim($this->name),
        'email' => strtolower(trim($this->email)),
        'phone' => preg_replace('/[^\d+]/', '', $this->phone),
    ]);
}
```

## Custom Validation Messages

### Contextual Messages
```php
public function messages(): array
{
    return [
        // Field-specific messages
        'bank_account_id.required' => 'Please select a bank account.',
        'bank_account_id.exists' => 'The selected bank account is invalid.',
        
        'category_id.required' => 'Please select a category.',
        'category_id.exists' => 'The selected category is invalid.',
        
        'type.required' => 'Please select transaction type.',
        'type.in' => 'Transaction type must be income, expense, or transfer.',
        
        'amount.required' => 'Amount is required.',
        'amount.numeric' => 'Amount must be a valid number.',
        'amount.min' => 'Amount must be greater than zero.',
        
        'transaction_date.required' => 'Transaction date is required.',
        'transaction_date.date' => 'Transaction date must be a valid date.',
        'transaction_date.before_or_equal' => 'Transaction date cannot be in the future.',
        
        // Conditional messages based on type
        'supplier_id.required' => 'Supplier is required for expense transactions.',
        'crew_member_id.required' => 'Crew member is required for salary payments.',
    ];
}
```

### Dynamic Messages
```php
public function messages(): array
{
    $messages = [
        'amount.required' => 'Amount is required.',
        'amount.min' => 'Amount must be greater than zero.',
    ];
    
    // Add conditional messages based on transaction type
    if ($this->type === 'expense') {
        $messages['supplier_id.required'] = 'Supplier is required for expense transactions.';
    }
    
    if ($this->category_id && $this->isSalaryCategory()) {
        $messages['crew_member_id.required'] = 'Crew member is required for salary payments.';
    }
    
    return $messages;
}

private function isSalaryCategory(): bool
{
    $category = TransactionCategory::find($this->category_id);
    return $category && strtolower($category->name) === 'salários';
}
```

## Conditional Validation

### Rules Based on Other Fields
```php
public function rules(): array
{
    $rules = [
        'type' => ['required', 'in:income,expense,transfer'],
        'amount' => ['required', 'numeric', 'min:0'],
    ];
    
    // Conditional rules based on type
    if ($this->type === 'expense') {
        $rules['supplier_id'] = ['nullable', 'exists:suppliers,id'];
    }
    
    if ($this->type === 'transfer') {
        $rules['to_account_id'] = ['required', 'exists:bank_accounts,id', 'different:bank_account_id'];
    }
    
    // Conditional rules based on category
    if ($this->category_id && $this->isSalaryCategory()) {
        $rules['crew_member_id'] = ['required', 'exists:crew_members,id'];
    }
    
    return $rules;
}
```

### After Validation Hook
```php
public function withValidator($validator): void
{
    $validator->after(function ($validator) {
        // Custom validation logic
        if ($this->type === 'transfer' && $this->bank_account_id === $this->to_account_id) {
            $validator->errors()->add('to_account_id', 'Cannot transfer to the same account.');
        }
        
        // Check if amount exceeds account balance
        if ($this->type === 'expense' && $this->amount > $this->getAccountBalance()) {
            $validator->errors()->add('amount', 'Insufficient account balance.');
        }
    });
}

private function getAccountBalance(): int
{
    $account = BankAccount::find($this->bank_account_id);
    return $account ? $account->current_balance : 0;
}
```

## Examples

### Complete StoreTransactionRequest
```php
<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Transaction::class);
    }

    public function rules(): array
    {
        $rules = [
            'vessel_id' => ['nullable', 'exists:vessels,id'],
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'category_id' => ['required', 'exists:transaction_categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'crew_member_id' => ['nullable', 'exists:crew_members,id'],
            'type' => ['required', 'in:income,expense,transfer'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'vat_rate_id' => ['nullable', 'exists:vat_rates,id'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
        
        // Conditional rules
        if ($this->type === 'expense' && $this->isSalaryCategory()) {
            $rules['crew_member_id'] = ['required', 'exists:crew_members,id'];
        }
        
        return $rules;
    }

    public function messages(): array
    {
        return [
            'bank_account_id.required' => 'Please select a bank account.',
            'category_id.required' => 'Please select a category.',
            'type.required' => 'Please select transaction type.',
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be greater than zero.',
            'transaction_date.required' => 'Transaction date is required.',
            'transaction_date.before_or_equal' => 'Transaction date cannot be in the future.',
            'crew_member_id.required' => 'Crew member is required for salary payments.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->normalizeMoney($this->amount),
            'currency' => strtoupper($this->currency ?? 'EUR'),
            'house_of_zeros' => 2,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->type === 'expense' && $this->amount > $this->getAccountBalance()) {
                $validator->errors()->add('amount', 'Insufficient account balance.');
            }
        });
    }

    private function normalizeMoney($value): int
    {
        if (is_string($value)) {
            $value = preg_replace('/[^\d.,]/', '', $value);
            $value = str_replace(',', '.', $value);
        }
        
        return (int) round((float) $value * 100);
    }

    private function isSalaryCategory(): bool
    {
        if (!$this->category_id) {
            return false;
        }
        
        $category = TransactionCategory::find($this->category_id);
        return $category && strtolower($category->name) === 'salários';
    }

    private function getAccountBalance(): int
    {
        $account = BankAccount::find($this->bank_account_id);
        return $account ? $account->current_balance : 0;
    }
}
```

### UpdateTransactionRequest
```php
<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('transaction'));
    }

    public function rules(): array
    {
        $transaction = $this->route('transaction');
        
        return [
            'vessel_id' => ['nullable', 'exists:vessels,id'],
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'category_id' => ['required', 'exists:transaction_categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'crew_member_id' => ['nullable', 'exists:crew_members,id'],
            'type' => ['required', 'in:income,expense,transfer'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'vat_rate_id' => ['nullable', 'exists:vat_rates,id'],
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reference' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:pending,completed,cancelled'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->normalizeMoney($this->amount),
            'currency' => strtoupper($this->currency ?? 'EUR'),
        ]);
    }

    private function normalizeMoney($value): int
    {
        if (is_string($value)) {
            $value = preg_replace('/[^\d.,]/', '', $value);
            $value = str_replace(',', '.', $value);
        }
        
        return (int) round((float) $value * 100);
    }
}
```
