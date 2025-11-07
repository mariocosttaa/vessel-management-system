<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $fillable = [
        'name',
        'code',
        'symbol',
        'symbol_2',
        'decimal_separator',
    ];

    protected $casts = [
        'decimal_separator' => 'integer',
    ];

    /**
     * Get the bank accounts that use this currency.
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class, 'currency', 'code');
    }

    /**
     * Get the transactions that use this currency.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'currency', 'code');
    }

    /**
     * Scope to get active currencies.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('code', ['EUR', 'USD', 'GBP', 'BRL', 'AOA', 'PTE', 'DEM', 'FRF', 'ITL', 'ESP', 'NLG', 'BEF', 'ATS', 'CHF', 'GBP', 'IEP', 'DKK', 'SEK', 'NOK', 'FIM']);
    }

    /**
     * Get currency by country code.
     */
    public static function getByCountryCode(string $countryCode): ?self
    {
        $currencyMap = [
            'PT' => 'EUR', 'ES' => 'EUR', 'FR' => 'EUR', 'DE' => 'EUR', 'IT' => 'EUR',
            'NL' => 'EUR', 'BE' => 'EUR', 'AT' => 'EUR', 'IE' => 'EUR', 'FI' => 'EUR',
            'US' => 'USD', 'CA' => 'USD', 'MX' => 'USD',
            'GB' => 'GBP', 'IE' => 'EUR',
            'BR' => 'BRL',
            'AO' => 'AOA',
            'CH' => 'CHF',
            'DK' => 'DKK',
            'SE' => 'SEK',
            'NO' => 'NOK',
        ];

        $currencyCode = $currencyMap[strtoupper($countryCode)] ?? null;

        if (!$currencyCode) {
            return null;
        }

        return self::where('code', $currencyCode)->first();
    }

    /**
     * Get formatted currency display.
     */
    public function getFormattedDisplayAttribute(): string
    {
        return "{$this->name} ({$this->symbol_2})";
    }

    /**
     * Get currency symbol for display.
     */
    public function getDisplaySymbolAttribute(): string
    {
        return $this->symbol;
    }
}
