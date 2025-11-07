<?php

namespace App\Models;

use App\Actions\MoneyAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'bank_name',
        'account_number',
        'iban',
        'country_id',
        'vessel_id',
        'initial_balance',
        'current_balance',
        'status',
        'notes',
    ];

    protected $casts = [
        'initial_balance' => 'integer',
        'current_balance' => 'integer',
        'country_id' => 'integer',
        'vessel_id' => 'integer',
    ];

    protected $appends = [
        'formatted_initial_balance',
        'formatted_current_balance',
    ];

    /**
     * Get the vessel that owns the bank account.
     */
    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Get the country that owns the bank account.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the transactions for the bank account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the monthly balances for the bank account.
     */
    public function monthlyBalances(): HasMany
    {
        return $this->hasMany(MonthlyBalance::class);
    }

    /**
     * Scope a query to only include active bank accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include inactive bank accounts.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Get currency for this bank account based on country.
     * Uses MoneyAction::getCurrencyFromCountry() following money-handling.md pattern.
     */
    public function getCurrency(): ?string
    {
        if ($this->country) {
            // Use MoneyAction to get currency from country code (following money-handling.md pattern)
            return MoneyAction::getCurrencyFromCountry($this->country->code);
        }

        // Fallback to IBAN detection if no country
        if ($this->iban) {
            return MoneyAction::getCurrencyFromIban($this->iban);
        }

        return null;
    }

    /**
     * Get formatted initial balance attribute.
     * Format with symbol for show modal (we know the country/currency).
     */
    public function getFormattedInitialBalanceAttribute(): string
    {
        $currency = $this->getCurrency();
        // Format with symbol for display in show modal
        return MoneyAction::format($this->initial_balance, null, $currency, true);
    }

    /**
     * Get formatted current balance attribute.
     * Format with symbol for show modal (we know the country/currency).
     */
    public function getFormattedCurrentBalanceAttribute(): string
    {
        $currency = $this->getCurrency();
        // Format with symbol for display in show modal
        return MoneyAction::format($this->current_balance, null, $currency, true);
    }
}
