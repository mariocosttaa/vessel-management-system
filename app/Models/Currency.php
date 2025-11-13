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
        return $this->hasMany(Movimentation::class, 'currency', 'code');
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
     * Returns currency from database if exists, otherwise returns currency code from mapping.
     */
    public static function getByCountryCode(string $countryCode): ?self
    {
        $currencyMap = [
            'PT' => 'EUR', 'ES' => 'EUR', 'FR' => 'EUR', 'DE' => 'EUR', 'IT' => 'EUR',
            'NL' => 'EUR', 'BE' => 'EUR', 'AT' => 'EUR', 'IE' => 'EUR', 'FI' => 'EUR',
            'US' => 'USD', 'CA' => 'USD', 'MX' => 'USD',
            'GB' => 'GBP',
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

        // Try to get from database first
        $currency = self::where('code', $currencyCode)->first();

        // If not in database, create a temporary currency object with default values
        if (!$currency) {
            $currency = new self();
            $currency->code = $currencyCode;
            $currency->symbol = self::getDefaultSymbol($currencyCode);
            $currency->decimal_separator = 2;
        }

        return $currency;
    }

    /**
     * Get default currency symbol for currency code.
     */
    private static function getDefaultSymbol(string $currencyCode): string
    {
        $symbols = [
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'BRL' => 'R$',
            'AOA' => 'Kz',
            'CHF' => 'CHF',
            'DKK' => 'kr',
            'SEK' => 'kr',
            'NOK' => 'kr',
        ];

        return $symbols[strtoupper($currencyCode)] ?? $currencyCode;
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
