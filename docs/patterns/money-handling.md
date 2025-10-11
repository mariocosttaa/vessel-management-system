# Money Handling Patterns

## Storage Format

### Database Storage
All monetary values are stored as integers representing the smallest currency unit (cents for EUR/USD):

```sql
-- Example: €123.45 is stored as 12345
amount BIGINT NOT NULL, -- 12345
currency VARCHAR(3) NOT NULL DEFAULT 'EUR', -- EUR
house_of_zeros TINYINT NOT NULL DEFAULT 2, -- 2 (decimal places)
```

### Field Structure
```php
// Transaction Model
protected $fillable = [
    'amount',        // Integer: 12345 (represents €123.45)
    'currency',      // String: 'EUR'
    'house_of_zeros', // Integer: 2 (decimal places)
    'vat_amount',    // Integer: 2840 (represents €28.40 VAT)
    'total_amount',  // Integer: 15185 (amount + VAT)
];
```

## Conversion Functions

### MoneyService Class
```php
<?php

namespace App\Services;

class MoneyService
{
    /**
     * Convert float to integer (cents)
     */
    public static function toInteger(float $value, int $decimals = 2): int
    {
        return (int) round($value * pow(10, $decimals));
    }
    
    /**
     * Convert integer (cents) to float
     */
    public static function toFloat(int $value, int $decimals = 2): float
    {
        return $value / pow(10, $decimals);
    }
    
    /**
     * Format integer as currency string
     */
    public static function format(int $value, string $currency = 'EUR', int $decimals = 2): string
    {
        $float = self::toFloat($value, $decimals);
        return number_format($float, $decimals, ',', '.') . ' ' . $currency;
    }
    
    /**
     * Format without currency symbol
     */
    public static function formatWithoutSymbol(int $value, int $decimals = 2): string
    {
        $float = self::toFloat($value, $decimals);
        return number_format($float, $decimals, ',', '.');
    }
    
    /**
     * Calculate VAT amount
     */
    public static function calculateVat(int $amount, float $vatRate, int $decimals = 2): int
    {
        $vatAmount = ($amount * $vatRate) / 100;
        return (int) round($vatAmount);
    }
    
    /**
     * Add two money values
     */
    public static function add(int $amount1, int $amount2): int
    {
        return $amount1 + $amount2;
    }
    
    /**
     * Subtract two money values
     */
    public static function subtract(int $amount1, int $amount2): int
    {
        return $amount1 - $amount2;
    }
    
    /**
     * Multiply money value by factor
     */
    public static function multiply(int $amount, float $factor, int $decimals = 2): int
    {
        $result = $amount * $factor;
        return (int) round($result);
    }
    
    /**
     * Normalize string input to integer
     */
    public static function normalizeFromString(string $value, int $decimals = 2): int
    {
        // Remove currency symbols and convert to float
        $value = preg_replace('/[^\d.,]/', '', $value);
        $value = str_replace(',', '.', $value);
        
        return self::toInteger((float) $value, $decimals);
    }
}
```

### Usage Examples
```php
// Convert user input to storage format
$userInput = "123.45"; // User enters €123.45
$amount = MoneyService::toInteger((float) $userInput); // 12345

// Convert from storage to display
$amount = 12345; // From database
$formatted = MoneyService::format($amount); // "123,45 EUR"

// Calculate VAT
$amount = 10000; // €100.00
$vatAmount = MoneyService::calculateVat($amount, 23.0); // 2300 (€23.00)

// Add amounts
$total = MoneyService::add(10000, 2300); // 12300 (€123.00)
```

## Formatting for Display

### HasMoney Trait
```php
<?php

namespace App\Traits;

use App\Services\MoneyService;

trait HasMoney
{
    /**
     * Get formatted amount attribute
     */
    public function getFormattedAmountAttribute(): string
    {
        return MoneyService::format(
            $this->amount, 
            $this->currency, 
            $this->house_of_zeros
        );
    }
    
    /**
     * Get formatted VAT amount attribute
     */
    public function getFormattedVatAmountAttribute(): string
    {
        return MoneyService::format(
            $this->vat_amount, 
            $this->currency, 
            $this->house_of_zeros
        );
    }
    
    /**
     * Get formatted total amount attribute
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return MoneyService::format(
            $this->total_amount, 
            $this->currency, 
            $this->house_of_zeros
        );
    }
    
    /**
     * Format money value
     */
    protected function formatMoney(int $amount, string $currency, int $decimals): string
    {
        return MoneyService::format($amount, $currency, $decimals);
    }
    
    /**
     * Set money attribute from float
     */
    protected function setMoneyAttribute(string $attribute, float $value, int $decimals = 2): void
    {
        $this->attributes[$attribute] = MoneyService::toInteger($value, $decimals);
    }
    
    /**
     * Normalize money input
     */
    protected function normalizeMoney($value, int $decimals = 2): int
    {
        if (is_string($value)) {
            return MoneyService::normalizeFromString($value, $decimals);
        }
        
        return MoneyService::toInteger((float) $value, $decimals);
    }
}
```

### Model Integration
```php
<?php

namespace App\Models;

use App\Traits\HasMoney;

class Transaction extends Model
{
    use HasMoney;
    
    protected $casts = [
        'amount' => 'integer',
        'vat_amount' => 'integer',
        'total_amount' => 'integer',
    ];
    
    // Accessors automatically available:
    // - formatted_amount
    // - formatted_vat_amount  
    // - formatted_total_amount
    
    // Mutators
    public function setAmountAttribute($value): void
    {
        $this->attributes['amount'] = $this->normalizeMoney($value, $this->house_of_zeros ?? 2);
    }
    
    public function setVatAmountAttribute($value): void
    {
        $this->attributes['vat_amount'] = $this->normalizeMoney($value, $this->house_of_zeros ?? 2);
    }
}
```

## VAT Calculations

### VAT Calculation Service
```php
<?php

namespace App\Services;

use App\Models\VatRate;

class VatCalculationService
{
    /**
     * Calculate VAT for a transaction
     */
    public static function calculateTransactionVat(int $amount, ?int $vatRateId, int $decimals = 2): array
    {
        if (!$vatRateId) {
            return [
                'vat_amount' => 0,
                'total_amount' => $amount,
                'vat_rate' => null,
            ];
        }
        
        $vatRate = VatRate::find($vatRateId);
        if (!$vatRate) {
            return [
                'vat_amount' => 0,
                'total_amount' => $amount,
                'vat_rate' => null,
            ];
        }
        
        $vatAmount = MoneyService::calculateVat($amount, $vatRate->rate, $decimals);
        $totalAmount = MoneyService::add($amount, $vatAmount);
        
        return [
            'vat_amount' => $vatAmount,
            'total_amount' => $totalAmount,
            'vat_rate' => $vatRate,
        ];
    }
    
    /**
     * Calculate VAT breakdown
     */
    public static function calculateVatBreakdown(int $totalAmount, float $vatRate, int $decimals = 2): array
    {
        // Calculate amount without VAT
        $amountWithoutVat = (int) round($totalAmount / (1 + $vatRate / 100));
        $vatAmount = $totalAmount - $amountWithoutVat;
        
        return [
            'amount_without_vat' => $amountWithoutVat,
            'vat_amount' => $vatAmount,
            'total_amount' => $totalAmount,
        ];
    }
}
```

### Model Boot Method for VAT
```php
protected static function boot()
{
    parent::boot();
    
    static::creating(function ($transaction) {
        // Calculate VAT if VAT rate is provided
        if ($transaction->vat_rate_id && !$transaction->vat_amount) {
            $vatCalculation = VatCalculationService::calculateTransactionVat(
                $transaction->amount,
                $transaction->vat_rate_id,
                $transaction->house_of_zeros
            );
            
            $transaction->vat_amount = $vatCalculation['vat_amount'];
            $transaction->total_amount = $vatCalculation['total_amount'];
        } else {
            $transaction->total_amount = $transaction->amount + ($transaction->vat_amount ?? 0);
        }
    });
    
    static::updating(function ($transaction) {
        // Recalculate VAT if amount or VAT rate changed
        if ($transaction->isDirty(['amount', 'vat_rate_id'])) {
            $vatCalculation = VatCalculationService::calculateTransactionVat(
                $transaction->amount,
                $transaction->vat_rate_id,
                $transaction->house_of_zeros
            );
            
            $transaction->vat_amount = $vatCalculation['vat_amount'];
            $transaction->total_amount = $vatCalculation['total_amount'];
        }
    });
}
```

## Frontend useMoney Composable

### Vue.js Composable
```typescript
// Composables/useMoney.ts
import { computed } from 'vue'

export function useMoney() {
  /**
   * Convert float to integer (cents)
   */
  const toInteger = (value: number, decimals: number = 2): number => {
    return Math.round(value * Math.pow(10, decimals))
  }
  
  /**
   * Convert integer (cents) to float
   */
  const toFloat = (value: number, decimals: number = 2): number => {
    return value / Math.pow(10, decimals)
  }
  
  /**
   * Format integer as currency string
   */
  const format = (value: number, currency: string = 'EUR', decimals: number = 2): string => {
    const float = toFloat(value, decimals)
    return new Intl.NumberFormat('pt-PT', {
      style: 'currency',
      currency: currency,
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    }).format(float)
  }
  
  /**
   * Format without currency symbol
   */
  const formatWithoutSymbol = (value: number, decimals: number = 2): string => {
    const float = toFloat(value, decimals)
    return new Intl.NumberFormat('pt-PT', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    }).format(float)
  }
  
  /**
   * Calculate VAT amount
   */
  const calculateVat = (amount: number, vatRate: number, decimals: number = 2): number => {
    return Math.round((amount * vatRate) / 100)
  }
  
  /**
   * Add two money values
   */
  const add = (amount1: number, amount2: number): number => {
    return amount1 + amount2
  }
  
  /**
   * Subtract two money values
   */
  const subtract = (amount1: number, amount2: number): number => {
    return amount1 - amount2
  }
  
  /**
   * Normalize string input to integer
   */
  const normalizeFromString = (value: string, decimals: number = 2): number => {
    // Remove currency symbols and convert to float
    const cleanValue = value.replace(/[^\d.,]/g, '').replace(',', '.')
    return toInteger(parseFloat(cleanValue) || 0, decimals)
  }
  
  return {
    toInteger,
    toFloat,
    format,
    formatWithoutSymbol,
    calculateVat,
    add,
    subtract,
    normalizeFromString
  }
}
```

### Usage in Components
```vue
<script setup lang="ts">
import { computed } from 'vue'
import { useMoney } from '@/Composables/useMoney'

const { format, calculateVat, add } = useMoney()

const props = defineProps<{
  amount: number
  currency: string
  vatRate?: number
}>()

const vatAmount = computed(() => {
  if (!props.vatRate) return 0
  return calculateVat(props.amount, props.vatRate)
})

const totalAmount = computed(() => {
  return add(props.amount, vatAmount.value)
})

const formattedAmount = computed(() => {
  return format(props.amount, props.currency)
})

const formattedTotal = computed(() => {
  return format(totalAmount.value, props.currency)
})
</script>

<template>
  <div class="space-y-2">
    <div class="flex justify-between">
      <span>Amount:</span>
      <span>{{ formattedAmount }}</span>
    </div>
    <div v-if="vatRate" class="flex justify-between">
      <span>VAT ({{ vatRate }}%):</span>
      <span>{{ format(vatAmount, currency) }}</span>
    </div>
    <div class="flex justify-between font-semibold">
      <span>Total:</span>
      <span>{{ formattedTotal }}</span>
    </div>
  </div>
</template>
```

## Request Normalization

### Form Request Money Handling
```php
<?php

namespace App\Http\Requests;

use App\Services\MoneyService;

class StoreTransactionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->normalizeMoney($this->amount),
            'currency' => strtoupper($this->currency ?? 'EUR'),
            'house_of_zeros' => 2,
        ]);
    }
    
    private function normalizeMoney($value): int
    {
        if (is_string($value)) {
            return MoneyService::normalizeFromString($value);
        }
        
        return MoneyService::toInteger((float) $value);
    }
}
```

## Resource Formatting

### API Resource Money Formatting
```php
<?php

namespace App\Http\Resources;

use App\Services\MoneyService;

class TransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            
            // Raw integer values
            'amount' => $this->amount,
            'vat_amount' => $this->vat_amount,
            'total_amount' => $this->total_amount,
            
            // Formatted string values
            'formatted_amount' => MoneyService::format(
                $this->amount, 
                $this->currency, 
                $this->house_of_zeros
            ),
            'formatted_vat_amount' => MoneyService::format(
                $this->vat_amount, 
                $this->currency, 
                $this->house_of_zeros
            ),
            'formatted_total_amount' => MoneyService::format(
                $this->total_amount, 
                $this->currency, 
                $this->house_of_zeros
            ),
            
            // Currency information
            'currency' => $this->currency,
            'house_of_zeros' => $this->house_of_zeros,
        ];
    }
}
```

## Balance Calculations

### Balance Service
```php
<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\BankAccount;

class BalanceService
{
    /**
     * Calculate current balance for account
     */
    public function calculateCurrentBalance(int $bankAccountId): int
    {
        $account = BankAccount::find($bankAccountId);
        if (!$account) {
            return 0;
        }
        
        $income = Transaction::where('bank_account_id', $bankAccountId)
            ->where('type', 'income')
            ->where('status', 'completed')
            ->sum('total_amount');
            
        $expense = Transaction::where('bank_account_id', $bankAccountId)
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->sum('total_amount');
            
        return $account->initial_balance + $income - $expense;
    }
    
    /**
     * Update account balance
     */
    public function updateAccountBalance(int $bankAccountId): void
    {
        $account = BankAccount::find($bankAccountId);
        if (!$account) {
            return;
        }
        
        $currentBalance = $this->calculateCurrentBalance($bankAccountId);
        $account->update(['current_balance' => $currentBalance]);
    }
    
    /**
     * Recalculate balances after transaction change
     */
    public function recalculateBalances(Transaction $transaction): void
    {
        $this->updateAccountBalance($transaction->bank_account_id);
        
        // If transfer, update both accounts
        if ($transaction->type === 'transfer') {
            $transfer = $transaction->accountTransfer;
            if ($transfer) {
                $this->updateAccountBalance($transfer->to_account_id);
            }
        }
    }
}
```

## Multi-Currency Support

### Currency Configuration
```php
<?php

namespace App\Models;

class Currency extends Model
{
    protected $fillable = [
        'code', 'name', 'symbol', 'decimals', 'is_active'
    ];
    
    protected $casts = [
        'decimals' => 'integer',
        'is_active' => 'boolean',
    ];
    
    public static function getActiveCurrencies(): array
    {
        return self::where('is_active', true)
            ->orderBy('code')
            ->get()
            ->toArray();
    }
}
```

### Multi-Currency Money Service
```php
<?php

namespace App\Services;

use App\Models\Currency;

class MultiCurrencyMoneyService extends MoneyService
{
    /**
     * Format money with currency-specific formatting
     */
    public static function formatWithCurrency(int $amount, string $currencyCode): string
    {
        $currency = Currency::where('code', $currencyCode)->first();
        if (!$currency) {
            return parent::format($amount, $currencyCode);
        }
        
        $float = self::toFloat($amount, $currency->decimals);
        
        return $currency->symbol . ' ' . 
               number_format($float, $currency->decimals, ',', '.');
    }
    
    /**
     * Convert between currencies (requires exchange rate service)
     */
    public static function convert(int $amount, string $fromCurrency, string $toCurrency): int
    {
        // This would integrate with an exchange rate API
        $rate = ExchangeRateService::getRate($fromCurrency, $toCurrency);
        return self::multiply($amount, $rate);
    }
}
```

## Testing Money Operations

### Money Test Helpers
```php
<?php

namespace Tests\Helpers;

use App\Services\MoneyService;

class MoneyTestHelper
{
    public static function assertMoneyEquals(int $expected, int $actual, string $message = ''): void
    {
        self::assertEquals($expected, $actual, $message ?: "Expected {$expected} but got {$actual}");
    }
    
    public static function assertMoneyFormatted(string $expected, int $amount, string $currency = 'EUR'): void
    {
        $formatted = MoneyService::format($amount, $currency);
        self::assertEquals($expected, $formatted);
    }
    
    public static function createMoneyAmount(float $value, int $decimals = 2): int
    {
        return MoneyService::toInteger($value, $decimals);
    }
}
```

### Test Examples
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MoneyService;
use Tests\Helpers\MoneyTestHelper;

class MoneyServiceTest extends TestCase
{
    public function test_to_integer_conversion(): void
    {
        $this->assertEquals(12345, MoneyService::toInteger(123.45));
        $this->assertEquals(123450, MoneyService::toInteger(1234.50));
    }
    
    public function test_to_float_conversion(): void
    {
        $this->assertEquals(123.45, MoneyService::toFloat(12345));
        $this->assertEquals(1234.50, MoneyService::toFloat(123450));
    }
    
    public function test_formatting(): void
    {
        $this->assertEquals('123,45 EUR', MoneyService::format(12345));
        $this->assertEquals('1.234,50 EUR', MoneyService::format(123450));
    }
    
    public function test_vat_calculation(): void
    {
        $this->assertEquals(2300, MoneyService::calculateVat(10000, 23.0)); // €23.00 VAT on €100.00
        $this->assertEquals(1150, MoneyService::calculateVat(5000, 23.0));  // €11.50 VAT on €50.00
    }
    
    public function test_string_normalization(): void
    {
        $this->assertEquals(12345, MoneyService::normalizeFromString('123.45'));
        $this->assertEquals(12345, MoneyService::normalizeFromString('123,45'));
        $this->assertEquals(12345, MoneyService::normalizeFromString('€123.45'));
        $this->assertEquals(12345, MoneyService::normalizeFromString('123.45 EUR'));
    }
}
```
