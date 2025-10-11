# Model Patterns

## Structure and Naming Conventions

### Model Naming
- Use singular, PascalCase: `Transaction`, `Vessel`, `CrewMember`
- Place in `app/Models/`
- Follow Laravel conventions

### Basic Structure
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasMoney;

class Transaction extends Model
{
    use SoftDeletes, HasMoney;
    
    protected $fillable = [
        'transaction_number', 'vessel_id', 'bank_account_id',
        'category_id', 'supplier_id', 'crew_member_id',
        'type', 'amount', 'currency', 'house_of_zeros',
        'vat_rate_id', 'vat_amount', 'total_amount',
        'transaction_date', 'transaction_month', 'transaction_year',
        'description', 'notes', 'reference',
        'is_recurring', 'recurring_transaction_id', 'status', 'created_by'
    ];
    
    protected $casts = [
        'transaction_date' => 'date',
        'is_recurring' => 'boolean',
        'amount' => 'integer',
        'vat_amount' => 'integer',
        'total_amount' => 'integer',
    ];
    
    // Relationships
    // Scopes
    // Accessors/Mutators
    // Boot methods
}
```

## Relationship Definitions

### BelongsTo Relationships
```php
// Transaction Model
public function vessel()
{
    return $this->belongsTo(Vessel::class);
}

public function bankAccount()
{
    return $this->belongsTo(BankAccount::class);
}

public function category()
{
    return $this->belongsTo(TransactionCategory::class);
}

public function supplier()
{
    return $this->belongsTo(Supplier::class);
}

public function crewMember()
{
    return $this->belongsTo(CrewMember::class);
}

public function vatRate()
{
    return $this->belongsTo(VatRate::class);
}

public function createdBy()
{
    return $this->belongsTo(User::class, 'created_by');
}
```

### HasMany Relationships
```php
// Vessel Model
public function transactions()
{
    return $this->hasMany(Transaction::class);
}

public function crewMembers()
{
    return $this->hasMany(CrewMember::class);
}

public function monthlyBalances()
{
    return $this->hasMany(MonthlyBalance::class);
}
```

### MorphMany Relationships
```php
// Transaction Model
public function attachments()
{
    return $this->morphMany(Attachment::class, 'attachable');
}

// Vessel Model
public function attachments()
{
    return $this->morphMany(Attachment::class, 'attachable');
}
```

### Polymorphic Relationships
```php
// Attachment Model
public function attachable()
{
    return $this->morphTo();
}
```

## Scopes (Query Scopes)

### Common Scopes
```php
// Transaction Model
public function scopeIncome($query)
{
    return $query->where('type', 'income');
}

public function scopeExpense($query)
{
    return $query->where('type', 'expense');
}

public function scopeTransfer($query)
{
    return $query->where('type', 'transfer');
}

public function scopeForPeriod($query, int $year, int $month)
{
    return $query->where('transaction_year', $year)
                ->where('transaction_month', $month);
}

public function scopeForVessel($query, int $vesselId)
{
    return $query->where('vessel_id', $vesselId);
}

public function scopeForBankAccount($query, int $bankAccountId)
{
    return $query->where('bank_account_id', $bankAccountId);
}

public function scopeCompleted($query)
{
    return $query->where('status', 'completed');
}

public function scopePending($query)
{
    return $query->where('status', 'pending');
}
```

### Vessel Scopes
```php
// Vessel Model
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

public function scopeInMaintenance($query)
{
    return $query->where('status', 'maintenance');
}

public function scopeInactive($query)
{
    return $query->where('status', 'inactive');
}

public function scopeByType($query, string $type)
{
    return $query->where('vessel_type', $type);
}
```

### Crew Member Scopes
```php
// CrewMember Model
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

public function scopeByPosition($query, int $positionId)
{
    return $query->where('position_id', $positionId);
}

public function scopeForVessel($query, int $vesselId)
{
    return $query->where('vessel_id', $vesselId);
}
```

## Attributes (Accessors, Mutators, Casts)

### Casts
```php
protected $casts = [
    'transaction_date' => 'date',
    'hire_date' => 'date',
    'date_of_birth' => 'date',
    'is_recurring' => 'boolean',
    'is_active' => 'boolean',
    'amount' => 'integer',
    'vat_amount' => 'integer',
    'total_amount' => 'integer',
    'salary_amount' => 'integer',
    'current_balance' => 'integer',
    'initial_balance' => 'integer',
];
```

### Accessors
```php
// Transaction Model
public function getFormattedAmountAttribute(): string
{
    return $this->formatMoney($this->amount, $this->currency, $this->house_of_zeros);
}

public function getFormattedVatAmountAttribute(): string
{
    return $this->formatMoney($this->vat_amount, $this->currency, $this->house_of_zeros);
}

public function getFormattedTotalAmountAttribute(): string
{
    return $this->formatMoney($this->total_amount, $this->currency, $this->house_of_zeros);
}

public function getTypeLabelAttribute(): string
{
    return match($this->type) {
        'income' => 'Receita',
        'expense' => 'Despesa',
        'transfer' => 'Transferência',
        default => $this->type,
    };
}

public function getStatusLabelAttribute(): string
{
    return match($this->status) {
        'pending' => 'Pendente',
        'completed' => 'Concluída',
        'cancelled' => 'Cancelada',
        default => $this->status,
    };
}
```

### Mutators
```php
// Transaction Model
public function setAmountAttribute($value): void
{
    $this->attributes['amount'] = $this->normalizeMoney($value);
}

public function setVatAmountAttribute($value): void
{
    $this->attributes['vat_amount'] = $this->normalizeMoney($value);
}

public function setTotalAmountAttribute($value): void
{
    $this->attributes['total_amount'] = $this->normalizeMoney($value);
}

private function normalizeMoney($value): int
{
    if (is_string($value)) {
        $value = preg_replace('/[^\d.,]/', '', $value);
        $value = str_replace(',', '.', $value);
    }
    
    return (int) round((float) $value * 100);
}
```

## Money Handling Trait Integration

### HasMoney Trait
```php
// app/Traits/HasMoney.php
<?php

namespace App\Traits;

trait HasMoney
{
    public function getFormattedAmountAttribute(): string
    {
        return $this->formatMoney($this->amount, $this->currency, $this->house_of_zeros);
    }
    
    protected function formatMoney(int $amount, string $currency, int $decimals): string
    {
        $divisor = pow(10, $decimals);
        $value = $amount / $divisor;
        return number_format($value, $decimals, ',', '.') . ' ' . $currency;
    }
    
    protected function setMoneyAttribute(string $attribute, float $value, int $decimals = 2): void
    {
        $multiplier = pow(10, $decimals);
        $this->attributes[$attribute] = (int) round($value * $multiplier);
    }
    
    protected function normalizeMoney($value, int $decimals = 2): int
    {
        if (is_string($value)) {
            $value = preg_replace('/[^\d.,]/', '', $value);
            $value = str_replace(',', '.', $value);
        }
        
        return (int) round((float) $value * pow(10, $decimals));
    }
}
```

### Using HasMoney Trait
```php
// Transaction Model
use App\Traits\HasMoney;

class Transaction extends Model
{
    use SoftDeletes, HasMoney;
    
    // Money accessors using trait
    public function getFormattedAmountAttribute(): string
    {
        return $this->formatMoney($this->amount, $this->currency, $this->house_of_zeros);
    }
    
    // Money mutators using trait
    public function setAmountAttribute($value): void
    {
        $this->attributes['amount'] = $this->normalizeMoney($value, $this->house_of_zeros ?? 2);
    }
}
```

## Soft Deletes and Timestamps

### Soft Deletes
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
}
```

### Custom Timestamps
```php
// If using different timestamp names
const CREATED_AT = 'created_at';
const UPDATED_AT = 'updated_at';

// Or disable timestamps
public $timestamps = false;
```

## Boot Methods

### Auto-Generation and Calculations
```php
// Transaction Model
protected static function boot()
{
    parent::boot();
    
    static::creating(function ($transaction) {
        // Generate transaction number
        if (!$transaction->transaction_number) {
            $transaction->transaction_number = self::generateTransactionNumber();
        }
        
        // Extract month and year from date
        $date = \Carbon\Carbon::parse($transaction->transaction_date);
        $transaction->transaction_month = $date->month;
        $transaction->transaction_year = $date->year;
        
        // Calculate VAT if necessary
        if ($transaction->vat_rate_id && !$transaction->vat_amount) {
            $vatRate = VatRate::find($transaction->vat_rate_id);
            $transaction->vat_amount = MoneyService::calculateVat(
                $transaction->amount,
                $vatRate->rate,
                $transaction->house_of_zeros
            );
        }
        
        // Calculate total amount
        $transaction->total_amount = $transaction->amount + ($transaction->vat_amount ?? 0);
        
        // Set created_by
        if (!$transaction->created_by && auth()->check()) {
            $transaction->created_by = auth()->id();
        }
    });
    
    static::updating(function ($transaction) {
        // Recalculate VAT and total if amount or VAT rate changed
        if ($transaction->isDirty(['amount', 'vat_rate_id'])) {
            if ($transaction->vat_rate_id) {
                $vatRate = VatRate::find($transaction->vat_rate_id);
                $transaction->vat_amount = MoneyService::calculateVat(
                    $transaction->amount,
                    $vatRate->rate,
                    $transaction->house_of_zeros
                );
            } else {
                $transaction->vat_amount = 0;
            }
            
            $transaction->total_amount = $transaction->amount + $transaction->vat_amount;
        }
        
        // Update month/year if date changed
        if ($transaction->isDirty('transaction_date')) {
            $date = \Carbon\Carbon::parse($transaction->transaction_date);
            $transaction->transaction_month = $date->month;
            $transaction->transaction_year = $date->year;
        }
    });
    
    static::deleted(function ($transaction) {
        // Update balances when transaction is deleted
        BalanceService::recalculateBalances($transaction);
    });
}

private static function generateTransactionNumber(): string
{
    $year = date('Y');
    $lastTransaction = self::whereYear('created_at', $year)
                          ->orderBy('id', 'desc')
                          ->first();
    
    $nextNumber = $lastTransaction ? 
        (int) substr($lastTransaction->transaction_number, -6) + 1 : 1;
    
    return sprintf('TRX%s%06d', $year, $nextNumber);
}
```

### Vessel Boot Method
```php
// Vessel Model
protected static function boot()
{
    parent::boot();
    
    static::creating(function ($vessel) {
        // Set default status
        if (!$vessel->status) {
            $vessel->status = 'active';
        }
    });
    
    static::updating(function ($vessel) {
        // If vessel becomes inactive, deactivate crew members
        if ($vessel->isDirty('status') && $vessel->status === 'inactive') {
            $vessel->crewMembers()->update(['status' => 'inactive']);
        }
    });
}
```

## Complete Model Examples

### Transaction Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasMoney;
use App\Services\MoneyService;
use App\Services\BalanceService;

class Transaction extends Model
{
    use SoftDeletes, HasMoney;
    
    protected $fillable = [
        'transaction_number', 'vessel_id', 'bank_account_id',
        'category_id', 'supplier_id', 'crew_member_id',
        'type', 'amount', 'currency', 'house_of_zeros',
        'vat_rate_id', 'vat_amount', 'total_amount',
        'transaction_date', 'transaction_month', 'transaction_year',
        'description', 'notes', 'reference',
        'is_recurring', 'recurring_transaction_id', 'status', 'created_by'
    ];
    
    protected $casts = [
        'transaction_date' => 'date',
        'is_recurring' => 'boolean',
        'amount' => 'integer',
        'vat_amount' => 'integer',
        'total_amount' => 'integer',
    ];
    
    // Relationships
    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }
    
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
    
    public function category()
    {
        return $this->belongsTo(TransactionCategory::class);
    }
    
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    
    public function crewMember()
    {
        return $this->belongsTo(CrewMember::class);
    }
    
    public function vatRate()
    {
        return $this->belongsTo(VatRate::class);
    }
    
    public function recurringTransaction()
    {
        return $this->belongsTo(RecurringTransaction::class);
    }
    
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
    
    // Scopes
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }
    
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }
    
    public function scopeTransfer($query)
    {
        return $query->where('type', 'transfer');
    }
    
    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('transaction_year', $year)
                    ->where('transaction_month', $month);
    }
    
    public function scopeForVessel($query, int $vesselId)
    {
        return $query->where('vessel_id', $vesselId);
    }
    
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
    
    // Accessors
    public function getFormattedAmountAttribute(): string
    {
        return $this->formatMoney($this->amount, $this->currency, $this->house_of_zeros);
    }
    
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'income' => 'Receita',
            'expense' => 'Despesa',
            'transfer' => 'Transferência',
            default => $this->type,
        };
    }
    
    // Boot
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($transaction) {
            if (!$transaction->transaction_number) {
                $transaction->transaction_number = self::generateTransactionNumber();
            }
            
            $date = \Carbon\Carbon::parse($transaction->transaction_date);
            $transaction->transaction_month = $date->month;
            $transaction->transaction_year = $date->year;
            
            if ($transaction->vat_rate_id && !$transaction->vat_amount) {
                $vatRate = VatRate::find($transaction->vat_rate_id);
                $transaction->vat_amount = MoneyService::calculateVat(
                    $transaction->amount,
                    $vatRate->rate,
                    $transaction->house_of_zeros
                );
            }
            
            $transaction->total_amount = $transaction->amount + ($transaction->vat_amount ?? 0);
            
            if (!$transaction->created_by && auth()->check()) {
                $transaction->created_by = auth()->id();
            }
        });
        
        static::updating(function ($transaction) {
            if ($transaction->isDirty(['amount', 'vat_rate_id'])) {
                if ($transaction->vat_rate_id) {
                    $vatRate = VatRate::find($transaction->vat_rate_id);
                    $transaction->vat_amount = MoneyService::calculateVat(
                        $transaction->amount,
                        $vatRate->rate,
                        $transaction->house_of_zeros
                    );
                } else {
                    $transaction->vat_amount = 0;
                }
                
                $transaction->total_amount = $transaction->amount + $transaction->vat_amount;
            }
            
            if ($transaction->isDirty('transaction_date')) {
                $date = \Carbon\Carbon::parse($transaction->transaction_date);
                $transaction->transaction_month = $date->month;
                $transaction->transaction_year = $date->year;
            }
        });
        
        static::deleted(function ($transaction) {
            BalanceService::recalculateBalances($transaction);
        });
    }
    
    private static function generateTransactionNumber(): string
    {
        $year = date('Y');
        $lastTransaction = self::whereYear('created_at', $year)
                              ->orderBy('id', 'desc')
                              ->first();
        
        $nextNumber = $lastTransaction ? 
            (int) substr($lastTransaction->transaction_number, -6) + 1 : 1;
        
        return sprintf('TRX%s%06d', $year, $nextNumber);
    }
}
```

### Vessel Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vessel extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name', 'registration_number', 'vessel_type',
        'capacity', 'year_built', 'status', 'notes'
    ];
    
    protected $casts = [
        'capacity' => 'integer',
        'year_built' => 'integer',
    ];
    
    // Relationships
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    
    public function crewMembers()
    {
        return $this->hasMany(CrewMember::class);
    }
    
    public function monthlyBalances()
    {
        return $this->hasMany(MonthlyBalance::class);
    }
    
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function scopeByType($query, string $type)
    {
        return $query->where('vessel_type', $type);
    }
    
    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Ativa',
            'maintenance' => 'Manutenção',
            'inactive' => 'Inativa',
            default => $this->status,
        };
    }
    
    public function getTypeLabelAttribute(): string
    {
        return match($this->vessel_type) {
            'cargo' => 'Carga',
            'passenger' => 'Passageiros',
            'fishing' => 'Pesca',
            'yacht' => 'Iate',
            default => $this->vessel_type,
        };
    }
    
    // Boot
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($vessel) {
            if (!$vessel->status) {
                $vessel->status = 'active';
            }
        });
        
        static::updating(function ($vessel) {
            if ($vessel->isDirty('status') && $vessel->status === 'inactive') {
                $vessel->crewMembers()->update(['status' => 'inactive']);
            }
        });
    }
}
```
