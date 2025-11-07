# Money Handling Patterns

## Overview

This system uses `MoneyAction` class for all money-related operations (formatting, sanitization, and currency detection) and `MoneyService` class for business logic calculations (VAT, etc.).

### Key Principles
- **Storage**: All monetary values stored as integers (cents/minor units)
- **Formatting**: Use `MoneyAction::format()` for display
- **Input Sanitization**: Use `MoneyAction::sanitize()` for user input
- **Currency Detection**: Use `MoneyAction::getCurrencyFromCountry()` and `MoneyAction::getCurrencyFromIban()` for automatic currency detection
- **Business Logic**: Use `MoneyService` for VAT calculations
- **No Fallbacks**: Never use EUR as fallback - always detect currency from country/IBAN

---

## Storage Format

### Database Schema
All monetary values are stored as integers representing the smallest currency unit:

```sql
-- Example: €123.45 is stored as 12345
amount BIGINT NOT NULL,              -- 12345 (cents)
currency VARCHAR(3) NOT NULL,        -- 'EUR'
house_of_zeros TINYINT NOT NULL,     -- 2 (decimal places)
```

### Model Field Structure
```php
protected $fillable = [
    'amount',        // Integer: 12345 (€123.45)
    'currency',      // String: 'EUR'
    'house_of_zeros', // Integer: 2
];

protected $casts = [
    'amount' => 'integer',
    'house_of_zeros' => 'integer',
];
```

---

## MoneyAction Class

### Location
```
app/Actions/MoneyAction.php
```

### Methods

#### `format()`
Format integer amount as currency string for display.

```php
MoneyAction::format(
    float|int $amount,           // Amount in cents (12345)
    int|string|null $decimalPlaces, // Decimal places or null
    ?string $currency,           // Currency code ('eur', 'usd')
    bool $formatWithSymbol       // Include symbol (€, $)
): string
```

**Examples:**
```php
// Basic formatting
MoneyAction::format(12345, 2, 'eur', true);
// Returns: "€ 123,45"

MoneyAction::format(12345, 2, 'usd', true);
// Returns: "$ 123.45"

// Without symbol
MoneyAction::format(12345, 2, 'eur', false);
// Returns: "123,45"

// Auto-detect decimal places from currency
MoneyAction::format(12345, null, 'eur', true);
// Returns: "€ 123,45"
```

#### `sanitize()`
Clean and convert user input string to integer (cents).

```php
MoneyAction::sanitize(string $amount): int
```

**Examples:**
```php
MoneyAction::sanitize('123.45');      // Returns: 12345
MoneyAction::sanitize('123,45');      // Returns: 12345
MoneyAction::sanitize('€ 123.45');    // Returns: 12345
MoneyAction::sanitize('1.234,56');    // Returns: 123456
MoneyAction::sanitize('$ 1,234.56');  // Returns: 123456
```

#### `getCurrencyFromCountry()`
Get currency code from country code.

```php
MoneyAction::getCurrencyFromCountry(string $countryCode): ?string
```

**Examples:**
```php
MoneyAction::getCurrencyFromCountry('PT');  // Returns: 'EUR'
MoneyAction::getCurrencyFromCountry('US');  // Returns: 'USD'
MoneyAction::getCurrencyFromCountry('GB'); // Returns: 'GBP'
MoneyAction::getCurrencyFromCountry('BR'); // Returns: 'BRL'
MoneyAction::getCurrencyFromCountry('AO'); // Returns: 'AOA'
MoneyAction::getCurrencyFromCountry('XX'); // Returns: null
```

#### `getCurrencyFromIban()`
Extract currency code from IBAN string.

```php
MoneyAction::getCurrencyFromIban(string $iban): ?string
```

**Examples:**
```php
MoneyAction::getCurrencyFromIban('PT50000000000000000000000'); // Returns: 'EUR'
MoneyAction::getCurrencyFromIban('GB29NWBK60161331926819');   // Returns: 'GBP'
MoneyAction::getCurrencyFromIban('US64SVBKUS6S3300958879');   // Returns: 'USD'
MoneyAction::getCurrencyFromIban('INVALID_IBAN');             // Returns: null
```

---

## Model Integration

### Adding Formatted Attributes

Add formatted money attributes directly to your models:

```php
<?php

namespace App\Models;

use App\Actions\MoneyAction;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'amount',
        'currency',
        'house_of_zeros',
        'vat_amount',
        'total_amount',
    ];

    protected $casts = [
        'amount' => 'integer',
        'vat_amount' => 'integer',
        'total_amount' => 'integer',
        'house_of_zeros' => 'integer',
    ];

    /**
     * Get formatted amount attribute.
     */
    public function getFormattedAmountAttribute(): string
    {
        return MoneyAction::format(
            $this->amount,
            $this->house_of_zeros,
            $this->currency,
            true
        );
    }

    /**
     * Get formatted VAT amount attribute.
     */
    public function getFormattedVatAmountAttribute(): string
    {
        return MoneyAction::format(
            $this->vat_amount ?? 0,
            $this->house_of_zeros,
            $this->currency,
            true
        );
    }

    /**
     * Get formatted total amount attribute.
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return MoneyAction::format(
            $this->total_amount,
            $this->house_of_zeros,
            $this->currency,
            true
        );
    }
}
```

### Usage in Code

```php
$transaction = Transaction::find(1);

// Display formatted values
echo $transaction->formatted_amount;      // "€ 123,45"
echo $transaction->formatted_vat_amount;  // "€ 28,39"
echo $transaction->formatted_total_amount; // "€ 151,84"

// Access raw values
echo $transaction->amount;        // 12345
echo $transaction->vat_amount;    // 2839
echo $transaction->total_amount;  // 15184
```

---

## Request Validation & Normalization

### Form Request Pattern

Always normalize money input in `prepareForValidation()`:

```php
<?php

namespace App\Http\Requests;

use App\Actions\MoneyAction;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'house_of_zeros' => ['required', 'integer', 'min:0', 'max:4'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->normalizeMoney($this->amount),
            'currency' => strtoupper($this->currency ?? 'EUR'),
            'house_of_zeros' => $this->house_of_zeros ?? 2,
        ]);
    }

    private function normalizeMoney($value): int
    {
        if (is_string($value)) {
            return MoneyAction::sanitize($value);
        }

        return (int) round((float) $value * 100);
    }
}
```

### Real-World Examples

```php
// Bank Account Request
protected function prepareForValidation(): void
{
    $data = [
        'initial_balance' => $this->normalizeMoney($this->initial_balance),
        'name' => trim($this->name),
    ];
    $this->merge($data);
}

// Crew Member Request
protected function prepareForValidation(): void
{
    $this->merge([
        'salary_amount' => $this->normalizeMoney($this->salary_amount),
        'salary_currency' => strtoupper($this->salary_currency ?? 'EUR'),
        'house_of_zeros' => $this->house_of_zeros ?? 2,
    ]);
}
```

---

## Resource Formatting

### API Resource Pattern

Always provide both raw and formatted values:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            
            // Raw integer values (for calculations)
            'amount' => $this->amount,
            'vat_amount' => $this->vat_amount,
            'total_amount' => $this->total_amount,
            
            // Formatted string values (for display)
            'formatted_amount' => $this->formatted_amount,
            'formatted_vat_amount' => $this->formatted_vat_amount,
            'formatted_total_amount' => $this->formatted_total_amount,
            
            // Currency metadata
            'currency' => $this->currency,
            'house_of_zeros' => $this->house_of_zeros,
        ];
    }
}
```

### Why Both Raw and Formatted?

- **Raw values**: Frontend can do calculations, sorting, filtering
- **Formatted values**: Direct display without frontend formatting logic

---

## MoneyService (Business Logic)

### Location
```
app/Services/MoneyService.php
```

### Methods

Used for VAT calculations and business logic:

```php
// Calculate VAT amount
MoneyService::calculateVat(int $amount, float $vatRate, int $decimals = 2): int

// Calculate total
MoneyService::calculateTotal(int $amount, int $vatAmount): int

// Parse money string (alternative to MoneyAction::sanitize)
MoneyService::parseMoneyString(string $value): int
```

### VAT Calculation Example

```php
use App\Services\MoneyService;
use App\Models\VatRate;

// In model boot method
protected static function boot()
{
    parent::boot();
    
    static::creating(function ($transaction) {
        // Calculate VAT if necessary
        if ($transaction->vat_rate_id && !$transaction->vat_amount) {
            $vatRate = VatRate::find($transaction->vat_rate_id);
            $transaction->vat_amount = MoneyService::calculateVat(
                $transaction->amount,
                $vatRate->rate,
                $transaction->house_of_zeros
            );
        }
        
        $transaction->total_amount = $transaction->amount + ($transaction->vat_amount ?? 0);
    });
}
```

---

## Frontend Integration

### Vue.js Composable

```typescript
// composables/useMoney.ts
import { computed } from 'vue'

export function useMoney() {
  const toInteger = (value: number, decimals: number = 2): number => {
    return Math.round(value * Math.pow(10, decimals))
  }
  
  const toFloat = (value: number, decimals: number = 2): number => {
    return value / Math.pow(10, decimals)
  }
  
  const format = (value: number, currency: string = 'EUR', decimals: number = 2): string => {
    const float = toFloat(value, decimals)
    return new Intl.NumberFormat('pt-PT', {
      style: 'currency',
      currency: currency,
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    }).format(float)
  }
  
  const sanitize = (value: string, decimals: number = 2): number => {
    const cleanValue = value.replace(/[^\d.,]/g, '').replace(',', '.')
    return toInteger(parseFloat(cleanValue) || 0, decimals)
  }
  
  return {
    toInteger,
    toFloat,
    format,
    sanitize
  }
}
```

### Component Usage

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { useMoney } from '@/composables/useMoney'

const { format } = useMoney()

const props = defineProps<{
  amount: number
  currency: string
  decimals?: number
}>()

const formattedAmount = computed(() => {
  return format(props.amount, props.currency, props.decimals ?? 2)
})
</script>

<template>
  <div>
    <p>Amount: {{ formattedAmount }}</p>
  </div>
</template>
```

---

## Complete Examples

### Example 1: BankAccount Model with Currency Detection

```php
<?php

namespace App\Models;

use App\Actions\MoneyAction;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'name',
        'initial_balance',
        'current_balance',
        'iban',
    ];

    protected $casts = [
        'initial_balance' => 'integer',
        'current_balance' => 'integer',
    ];

    protected $appends = [
        'formatted_initial_balance',
        'formatted_current_balance',
    ];

    /**
     * Get currency for this bank account based on country.
     */
    public function getCurrency(): ?string
    {
        if ($this->country) {
            return $this->country->getCurrencyCode();
        }
        
        // Fallback to IBAN detection if no country
        if ($this->iban) {
            return MoneyAction::getCurrencyFromIban($this->iban);
        }
        
        return null;
    }

    public function getFormattedInitialBalanceAttribute(): string
    {
        $currency = $this->getCurrency();
        return MoneyAction::format($this->initial_balance, null, $currency, true);
    }

    public function getFormattedCurrentBalanceAttribute(): string
    {
        $currency = $this->getCurrency();
        return MoneyAction::format($this->current_balance, null, $currency, true);
    }
}
```

### Example 2: CrewMember Model

```php
<?php

namespace App\Models;

use App\Actions\MoneyAction;
use Illuminate\Database\Eloquent\Model;

class CrewMember extends Model
{
    protected $fillable = [
        'name',
        'salary_amount',
        'salary_currency',
        'house_of_zeros',
    ];

    protected $casts = [
        'salary_amount' => 'integer',
        'house_of_zeros' => 'integer',
    ];

    protected $appends = [
        'formatted_salary_amount',
    ];

    public function getFormattedSalaryAmountAttribute(): string
    {
        return MoneyAction::format(
            $this->salary_amount,
            $this->house_of_zeros,
            $this->salary_currency,
            true
        );
    }
}
```

### Example 3: Transaction with VAT

```php
<?php

namespace App\Models;

use App\Actions\MoneyAction;
use App\Services\MoneyService;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'amount',
        'currency',
        'house_of_zeros',
        'vat_rate_id',
        'vat_amount',
        'total_amount',
    ];

    protected $casts = [
        'amount' => 'integer',
        'vat_amount' => 'integer',
        'total_amount' => 'integer',
        'house_of_zeros' => 'integer',
    ];

    // Formatted attributes
    public function getFormattedAmountAttribute(): string
    {
        return MoneyAction::format(
            $this->amount,
            $this->house_of_zeros,
            $this->currency,
            true
        );
    }

    public function getFormattedVatAmountAttribute(): string
    {
        return MoneyAction::format(
            $this->vat_amount ?? 0,
            $this->house_of_zeros,
            $this->currency,
            true
        );
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        return MoneyAction::format(
            $this->total_amount,
            $this->house_of_zeros,
            $this->currency,
            true
        );
    }

    // Auto-calculate VAT on create
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($transaction) {
            if ($transaction->vat_rate_id && !$transaction->vat_amount) {
                $vatRate = VatRate::find($transaction->vat_rate_id);
                $transaction->vat_amount = MoneyService::calculateVat(
                    $transaction->amount,
                    $vatRate->rate,
                    $transaction->house_of_zeros
                );
            }
            
            $transaction->total_amount = $transaction->amount + ($transaction->vat_amount ?? 0);
        });
    }
}
```

---

## Best Practices

### ✅ DO

```php
// Use MoneyAction for formatting
$formatted = MoneyAction::format($amount, 2, 'eur', true);

// Use MoneyAction for sanitizing user input
$clean = MoneyAction::sanitize($userInput);

// Use currency detection from country/IBAN
$currency = MoneyAction::getCurrencyFromCountry('PT'); // Returns 'EUR'
$currency = MoneyAction::getCurrencyFromIban('PT50000000000000000000000'); // Returns 'EUR'

// Add formatted attributes to models with currency detection
public function getFormattedAmountAttribute(): string
{
    $currency = $this->getCurrency(); // Detect from country/IBAN
    return MoneyAction::format($this->amount, $this->house_of_zeros, $currency, true);
}

// Normalize in prepareForValidation()
protected function prepareForValidation(): void
{
    $this->merge([
        'amount' => $this->normalizeMoney($this->amount),
    ]);
}

// Return both raw and formatted in resources
'amount' => $this->amount,
'formatted_amount' => $this->formatted_amount,
```

### ❌ DON'T

```php
// Don't use number_format directly
$formatted = number_format($amount / 100, 2, ',', '.');

// Don't hardcode currency symbols
$formatted = '€ ' . number_format($amount, 2);

// Don't use floats for money
$amount = 123.45; // Bad!

// Don't forget to sanitize user input
$transaction->amount = $request->amount; // Bad!

// Don't format in the controller
$transaction->formatted_amount = '€ ' . number_format($amount, 2); // Bad!

// Don't use EUR as fallback - always detect currency
return MoneyAction::format($amount, null, 'eur', true); // Bad!

// Don't hardcode currency in models
public function getFormattedAmountAttribute(): string
{
    return MoneyAction::format($this->amount, null, 'eur', true); // Bad!
}
```

---

## Testing

### Test Examples

```php
use App\Actions\MoneyAction;
use App\Services\MoneyService;

test('MoneyAction formats correctly', function () {
    expect(MoneyAction::format(12345, 2, 'eur', true))
        ->toBe('€ 123,45');
});

test('MoneyAction sanitizes correctly', function () {
    expect(MoneyAction::sanitize('€ 123,45'))->toBe(12345);
    expect(MoneyAction::sanitize('$1,234.56'))->toBe(123456);
});

test('MoneyService calculates VAT correctly', function () {
    $amount = 10000; // €100.00
    $vatAmount = MoneyService::calculateVat($amount, 23.0);
    
    expect($vatAmount)->toBe(2300); // €23.00
});

test('Transaction formats money correctly', function () {
    $transaction = Transaction::factory()->create([
        'amount' => 12345,
        'currency' => 'EUR',
        'house_of_zeros' => 2,
    ]);
    
    expect($transaction->formatted_amount)->toBe('€ 123,45');
});
```

---

## Summary

| Task | Use This | Example |
|------|----------|---------|
| Format for display | `MoneyAction::format()` | `MoneyAction::format(12345, 2, 'eur', true)` |
| Sanitize user input | `MoneyAction::sanitize()` | `MoneyAction::sanitize('€123.45')` |
| Detect currency from country | `MoneyAction::getCurrencyFromCountry()` | `MoneyAction::getCurrencyFromCountry('PT')` |
| Detect currency from IBAN | `MoneyAction::getCurrencyFromIban()` | `MoneyAction::getCurrencyFromIban('PT50000000000000000000000')` |
| Calculate VAT | `MoneyService::calculateVat()` | `MoneyService::calculateVat(10000, 23.0)` |
| Model accessor | Direct `MoneyAction` with currency detection | `MoneyAction::format($this->amount, ..., $this->getCurrency(), true)` |
| Request normalization | `MoneyAction::sanitize()` | In `prepareForValidation()` |
| Resource output | Model accessor | `'formatted_amount' => $this->formatted_amount` |

**Key Principle**: One unified system through `MoneyAction` for all money formatting, sanitization, and currency detection. Never use EUR as fallback - always detect currency from country/IBAN. Simple, direct, and maintainable.
