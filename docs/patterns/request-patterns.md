# Request Patterns

## Overview
Form requests handle validation, authorization, and data transformation for incoming HTTP requests.

## Structure and Naming Conventions

### Request Naming
- Store requests: `Store{Entity}Request` (e.g., `StoreTransactionRequest`)
- Update requests: `Update{Entity}Request` (e.g., `UpdateTransactionRequest`)
- Place in `app/Http/Requests/`

## 1. PHPDoc Property and Method Annotations (**MANDATORY**)
Every request class **must** include a PHPDoc block at the top of the class with:
- All input properties (fields expected from the request)
- All merged/derived properties (e.g., decoded IDs, computed fields)
- All route parameters accessed via `$this->route()`
- All methods used via magic methods (e.g., `input()`, `merge()`, `all()`, etc.)
- **Missing or incomplete docblocks will cause lint errors.**

### Preferred prepareForValidation style (MANDATORY)
Always decode/normalize inputs using direct per-field merge blocks. Do NOT build intermediate payload arrays.

Example (hashed IDs decoding):

```php
protected function prepareForValidation(): void
{
    // Decode the hashed IDs if they exist
    if ($this->input('answer_id')) {
        $this->merge([
            'answer_id' => EasyHashAction::decode($this->input('answer_id'), 'communication-id'),
        ]);
    }

    if ($this->input('user_from')) {
        $this->merge([
            'user_from' => EasyHashAction::decode($this->input('user_from'), 'user-id'),
        ]);
    }

    if ($this->input('user_to')) {
        $this->merge([
            'user_to' => EasyHashAction::decode($this->input('user_to'), 'user-id'),
        ]);
    }
}
```

This style is required to keep requests concise, readable, and consistent.

**MANDATORY: in requests:**
```php
/**
 * @method bool hasFile(string $key)
 * @method \Illuminate\Http\UploadedFile|null file(string $key)
 * @method mixed route(string $key = null)
 * @method bool boolean(string $key)
 * @method array file(string $key)
 * @method array all()
 * @method void merge(array $data)
 * @method array hasFile(string $key)
 * @method \Illuminate\Http\UploadedFile|null file(string $key)
 * @method array input(string $key = null, mixed $default = null)
 * @method bool boolean(string $key)
 */
```

## Basic Structure
```php
<?php

namespace App\Http\Requests\Panel\Example;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Tenant\SettingModel;

/**
 * @property string $title_en
 * @property string $title_pt
 * @property string $title_es
 * @property string $title_fr
 * @method bool hasFile(string $key)
 * @method \Illuminate\Http\UploadedFile|null file(string $key)
 * @method mixed route(string $key = null)
 * @method bool boolean(string $key)
 * @method array file(string $key)
 * @method array all()
 * @method void merge(array $data)
 * @method array hasFile(string $key)
 * @method \Illuminate\Http\UploadedFile|null file(string $key)
 * @method array input(string $key = null, mixed $default = null)
 * @method bool boolean(string $key)
 */
class ExampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title is required.',
            'title.max' => 'The title may not be greater than 255 characters.',
        ];
    }
}
```

## 2. Hashed ID Decoding in `prepareForValidation`
If the request receives hashed IDs (from input or route), **always decode/unhash** them in `prepareForValidation` (or `protected prepareForValidation`).
- Use `EasyHashAction::decode($value, 'type')` or equivalent.
- Merge the decoded value back into the request for validation.

**Example:**
```php
public function prepareForValidation() {
    if ($this->input('id')) {
        $this->merge([
            'id' => EasyHashAction::decode($this->id, 'booking-id')
        ]);
    }
}
```
- For arrays of IDs, map and decode each value.

## 3. Database Validation with Rule::exists and Rule::unique (**MANDATORY**)
**Always use** `Rule::exists()` and `Rule::unique()` for database validation instead of string-based rules.
- Use model classes instead of table names for better maintainability.
- For update requests, always use `ignore()` to exclude the current record from uniqueness checks.

**Examples:**

**Exists validation:**
```php
use Illuminate\Validation\Rule;
use App\Models\Tenant\RoomModel;
use App\Models\Tenant\ClientModel;

// ✅ Correct - using model class
'room_id' => ['required', 'integer', Rule::exists(RoomModel::class, 'id')],
'client_id' => ['required', 'integer', Rule::exists(ClientModel::class, 'id')],

// ❌ Incorrect - using string table name
'room_id' => 'required|integer|exists:rooms,id',
```

**Unique validation for updates:**
```php
// ✅ Correct - with ignore for updates
$roomId = EasyHashAction::decode($this->route('roomIdHashed'), 'room-id');
'number' => ['required', 'integer', 'min:1', Rule::unique(RoomModel::class, 'number')->ignore($roomId)],

// ✅ Correct - with ignore using decoded ID
'email' => ['required', 'email', Rule::unique(ClientModel::class, 'email')->ignore($this->id)],
```

**Unique validation for creates:**
```php
// ✅ Correct - without ignore for creates
'email' => ['required', 'email', Rule::unique(ClientModel::class, 'email')],
'name' => ['required', 'string', Rule::unique(RoomTypeModel::class, 'name_pt')],
```

## Validation Rules Organization

### Grouped Rules by Entity Type

#### Transaction Rules
```php
public function rules(): array
{
    return [
        // Required relationships
        'bank_account_id' => ['required', 'integer', Rule::exists(BankAccount::class, 'id')],
        'category_id' => ['required', 'integer', Rule::exists(TransactionCategory::class, 'id')],
        
        // Optional relationships
        'vessel_id' => ['nullable', 'integer', Rule::exists(Vessel::class, 'id')],
        'supplier_id' => ['nullable', 'integer', Rule::exists(Supplier::class, 'id')],
        'crew_member_id' => ['nullable', 'integer', Rule::exists(CrewMember::class, 'id')],
        
        // Transaction details
        'type' => ['required', 'in:income,expense,transfer'],
        'amount' => ['required', 'numeric', 'min:0'],
        'currency' => ['required', 'string', 'size:3'],
        
        // VAT
        'vat_rate_id' => ['nullable', 'integer', Rule::exists(VatRate::class, 'id')],
        
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
        'registration_number' => ['required', 'string', 'max:100', Rule::unique(Vessel::class, 'registration_number')],
        'vessel_type' => ['required', 'in:cargo,passenger,fishing,yacht'],
        'capacity' => ['nullable', 'integer', 'min:1'],
        'year_built' => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
        'status' => ['required', 'in:active,maintenance,inactive'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ];
}
```

## Multilanguage Field Validation

When dealing with multilanguage fields, always respect the tenant's language settings:

### 1. Check Multilanguage Status
```php
public function rules(): array
{
    $setting = request()->get('setting-model') ?? SettingModel::first();
    $isMultilanguage = $setting?->multilanguage ?? false;
    $defaultLang = $setting?->default_language ?? 'en';
    
    if (!$isMultilanguage) {
        // Single language - only show default language field
        return [
            "title_{$defaultLang}" => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
    
    // Multilanguage - default language required, others optional
    return [
        'title_en' => [$defaultLang === 'en' ? 'required' : 'nullable', 'string', 'max:255'],
        'title_pt' => [$defaultLang === 'pt' ? 'required' : 'nullable', 'string', 'max:255'],
        'title_es' => [$defaultLang === 'es' ? 'required' : 'nullable', 'string', 'max:255'],
        'title_fr' => [$defaultLang === 'fr' ? 'required' : 'nullable', 'string', 'max:255'],
        'description' => ['nullable', 'string', 'max:1000'],
    ];
}
```

### 2. Dynamic Error Messages
```php
public function messages(): array
{
    $setting = request()->get('setting-model') ?? SettingModel::first();

    $isMultilanguage = $setting?->multilanguage ?? false;
    $defaultLang = $setting?->default_language ?? 'en';
    
    $languageLabel = match ($defaultLang) {
        'pt' => 'Portuguese', 'es' => 'Spanish', 'fr' => 'French', default => 'English',
    };
    
    if (!$isMultilanguage) {
        return [
            "title_{$defaultLang}.required" => "The {$languageLabel} title is required.",
            "title_{$defaultLang}.max" => "The {$languageLabel} title may not be greater than 255 characters.",
        ];
    }
    
    $messages = [];
    $languages = ['en' => 'English', 'pt' => 'Portuguese', 'es' => 'Spanish', 'fr' => 'French'];
    
    foreach ($languages as $code => $label) {
        $required = $code === $defaultLang ? 'required' : 'optional';
        $messages["title_{$code}.required"] = "The {$label} title is required.";
        $messages["title_{$code}.max"] = "The {$label} title may not be greater than 255 characters.";
    }
    
    return $messages;
}
```

### 3. File Upload Validation
```php
public function rules(): array
{
    $setting = request()->get('setting-model') ?? SettingModel::first();

    $isMultilanguage = $setting?->multilanguage ?? false;
    $defaultLang = $setting?->default_language ?? 'en';
    
    $baseRules = [
        'file' => ['required', 'file', 'mimes:png,jpg,jpeg,gif,webp', 'max:10240'],
        'notes' => ['nullable', 'string', 'max:1000'],
        'show' => ['boolean'],
    ];
    
    if (!$isMultilanguage) {
        $baseRules["title_{$defaultLang}"] = ['required', 'string', 'max:255'];
        return $baseRules;
    }
    
    $baseRules['title_en'] = [$defaultLang === 'en' ? 'required' : 'nullable', 'string', 'max:255'];
    $baseRules['title_pt'] = [$defaultLang === 'pt' ? 'required' : 'nullable', 'string', 'max:255'];
    $baseRules['title_es'] = [$defaultLang === 'es' ? 'required' : 'nullable', 'string', 'max:255'];
    $baseRules['title_fr'] = [$defaultLang === 'fr' ? 'required' : 'nullable', 'string', 'max:255'];
    
    return $baseRules;
}
```

## Key Principles

1. **Always check multilanguage setting first** - Use `request()->get('setting-model') ?? SettingModel::first()`
2. **Default language is always required** - Regardless of multilanguage status
3. **Other languages are optional** - Only when multilanguage is enabled
4. **Dynamic validation rules** - Based on current tenant settings
5. **Consistent error messages** - Match the language being validated
6. **Fallback to English** - If no default language is set

## Common Patterns

### Store Request
```php
public function rules(): array
{
    $setting = request()->get('setting-model') ?? SettingModel::first();

    $isMultilanguage = $setting?->multilanguage ?? false;
    $defaultLang = $setting?->default_language ?? 'en';
    
    if (!$isMultilanguage) {
        return [
            "title_{$defaultLang}" => ['required', 'string', 'max:255'],
            // other fields...
        ];
    }
    
    return [
        'title_en' => [$defaultLang === 'en' ? 'required' : 'nullable', 'string', 'max:255'],
        'title_pt' => [$defaultLang === 'pt' ? 'required' : 'nullable', 'string', 'max:255'],
        'title_es' => [$defaultLang === 'es' ? 'required' : 'nullable', 'string', 'max:255'],
        'title_fr' => [$defaultLang === 'fr' ? 'required' : 'nullable', 'string', 'max:255'],
        // other fields...
    ];
}
```

### Update Request
```php
public function rules(): array
{
    $setting = request()->get('setting-model') ?? SettingModel::first();

    $isMultilanguage = $setting?->multilanguage ?? false;
    $defaultLang = $setting?->default_language ?? 'en';
    
    if (!$isMultilanguage) {
        return [
            "title_{$defaultLang}" => ['required', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,gif,webp', 'max:10240'],
            // other fields...
        ];
    }
    
    return [
        'title_en' => [$defaultLang === 'en' ? 'required' : 'nullable', 'string', 'max:255'],
        'title_pt' => [$defaultLang === 'pt' ? 'required' : 'nullable', 'string', 'max:255'],
        'title_es' => [$defaultLang === 'es' ? 'required' : 'nullable', 'string', 'max:255'],
        'title_fr' => [$defaultLang === 'fr' ? 'required' : 'nullable', 'string', 'max:255'],
        'file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,gif,webp', 'max:10240'],
        // other fields...
    ];
}
```

## Complete Examples

### Example: Public StartCheckoutRequest
```php
<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Models\Tenant\RoomModel;
use App\Models\Tenant\RoomTypeModel;

/**
 * StartCheckoutRequest validates starting a public checkout session.
 *
 * Route params:
 * @property string $roomTypeSlug
 *
 * Input fields:
 * @property string $checkin
 * @property string $checkout
 * @property int|null $adults
 * @property int|null $children
 * @property int|null $babies
 * @property int|null $pets
 * @property int $room_id
 * @property string|null $preferred_currency
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
class StartCheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'checkin' => ['required', 'date', 'date_format:Y-m-d'],
            'checkout' => ['required', 'date', 'date_format:Y-m-d', 'after:checkin'],
            'adults' => ['nullable', 'integer', 'min:1'],
            'children' => ['nullable', 'integer', 'min:0'],
            'babies' => ['nullable', 'integer', 'min:0'],
            'pets' => ['nullable', 'integer', 'min:0'],
            'room_id' => ['required', 'integer', Rule::exists(RoomModel::class, 'id')],
            'preferred_currency' => ['nullable', 'string', 'size:3', 'in:EUR,USD,AOA,BRL,NGN'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'adults' => $this->input('adults') !== null ? (int) $this->input('adults') : null,
            'children' => $this->input('children') !== null ? (int) $this->input('children') : null,
            'babies' => $this->input('babies') !== null ? (int) $this->input('babies') : null,
            'pets' => $this->input('pets') !== null ? (int) $this->input('pets') : null,
            'room_id' => $this->input('room_id') !== null ? (int) $this->input('room_id') : null,
            'preferred_currency' => $this->input('preferred_currency') ? strtoupper((string) $this->input('preferred_currency')) : null,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $roomTypeSlug = (string) $this->route('roomTypeSlug');
            $roomId = (int) $this->input('room_id');

            $roomType = RoomTypeModel::where(function ($q) use ($roomTypeSlug) {
                $q->where('slug_en', $roomTypeSlug)
                  ->orWhere('slug_pt', $roomTypeSlug)
                  ->orWhere('slug_es', $roomTypeSlug)
                  ->orWhere('slug_fr', $roomTypeSlug);
            })->first();
            if (!$roomType) {
                $v->errors()->add('room_type_slug', __('Invalid room type.'));
                return;
            }

            $room = RoomModel::where('id', $roomId)->where('room_type_id', $roomType->id)->first();
            if (!$room) {
                $v->errors()->add('room_id', __('Selected room does not belong to the chosen room type.'));
            }
        });
    }
}
```

### Example: Panel Client Create Request
```php
<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Tenant\ClientModel;

/**
 * @property string $name
 * @property string|null $surname
 * @property string|null $email
 * @property string|null $phone_code
 * @property string|null $phone
 * @property array|null $documents
 * @method array all()
 * @method mixed input(string $key = null, mixed $default = null)
 * @method void merge(array $data)
 */
class PanelClientCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ClientModel::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique(ClientModel::class, 'email')],
            'phone_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'documents' => ['nullable', 'array'],
            'documents.*.type' => ['required_with:documents', 'string', 'in:passport,id_card,driver_license'],
            'documents.*.number' => ['required_with:documents', 'string', 'max:50'],
            'documents.*.expiry_date' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The client name is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'documents.*.type.required_with' => 'Document type is required when providing documents.',
            'documents.*.number.required_with' => 'Document number is required when providing documents.',
            'documents.*.expiry_date.after' => 'Document expiry date must be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->name),
            'surname' => $this->surname ? trim($this->surname) : null,
            'email' => $this->email ? strtolower(trim($this->email)) : null,
            'phone' => $this->phone ? preg_replace('/[^\d+]/', '', $this->phone) : null,
        ]);
    }
}
```

## 4. Property Typing and Docblocks
- All properties in the PHPDoc must be **fully typed** (e.g., `int`, `string`, `array`, `bool`, etc.).
- Document all expected request fields, including optional/nullable ones.
- Document all route parameters and merged fields.

## 5. Method Typing and Docblocks
- All custom methods must have return types and docblocks.
- If using custom validation logic (e.g., `withValidator`, custom validation callbacks), document their purpose and expected behavior.

## 6. Validation Rules and Messages
- All rules must be clearly typed and documented.
- Use `Rule::exists`, `Rule::unique`, and other helpers for database validation.
- Provide custom error messages for all fields.
- If validation depends on settings or context, document the logic in comments.

## 7. Consistency with Model/Resource Patterns
- Field naming, typing, and order should be consistent with the patterns in `.cursor-rules/model-resource-patterns.md`.
- If a model/resource uses hashed IDs, the request must decode them.
- Multilingual fields, price fields, and relationships should be handled in a way that matches the resource conventions.

## 8. Example Request Pattern
```php
/**
 * @property string $name
 * @property string|null $surname
 * @property string|null $email
 * @property string|null $phone_code
 * @property string|null $phone
 * @property array|null $documents
 * @method array all()
 * @method mixed input(string $key = null, mixed $default = null)
 * @method void merge(array $data)
 */
class PanelClientCreateRequest extends FormRequest {
    public function authorize() { return true; }
    public function rules() { ... }
    // Custom validation logic, docblocks, etc.
}
```

## 9. Linting and Enforcement
- **All of the above is mandatory.**
- Missing property/method docblocks, missing decoding logic, or inconsistent typing will cause lint errors and must be fixed before merging.

---

**See also:**
- [.cursor-rules/model-resource-patterns.md](./model-resource-patterns.md) for model/resource conventions.

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
