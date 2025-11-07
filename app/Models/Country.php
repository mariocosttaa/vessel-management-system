<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Country extends Model
{
    protected $fillable = [
        'name',
        'capital_city',
        'code',
        'calling_code',
    ];

    /**
     * Get the vessels registered in this country.
     */
    public function vessels(): HasMany
    {
        return $this->hasMany(Vessel::class, 'country_code', 'code');
    }

    /**
     * Get the suppliers from this country.
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'country_code', 'code');
    }

    /**
     * Get the crew members from this country.
     */
    public function crewMembers(): HasMany
    {
        return $this->hasMany(CrewMember::class, 'country_code', 'code');
    }

    /**
     * Get the bank accounts from this country.
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class, 'country_id');
    }

    /**
     * Get the currency for this country.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    /**
     * Scope to get countries by region or specific criteria.
     */
    public function scopeByRegion($query, $region = null)
    {
        if ($region) {
            // You can add region logic here based on country codes
            return $query->whereIn('code', $this->getRegionCountries($region));
        }

        return $query;
    }

    /**
     * Get formatted country display.
     */
    public function getFormattedDisplayAttribute(): string
    {
        return "{$this->name} ({$this->code})";
    }

    /**
     * Get country with calling code.
     */
    public function getWithCallingCodeAttribute(): string
    {
        return $this->calling_code
            ? "{$this->name} (+{$this->calling_code})"
            : $this->name;
    }

    /**
     * Get region countries based on country codes.
     */
    private function getRegionCountries($region): array
    {
        $regions = [
            'europe' => ['PT', 'ES', 'FR', 'DE', 'IT', 'NL', 'BE', 'AT', 'CH', 'GB', 'IE', 'DK', 'SE', 'NO', 'FI'],
            'americas' => ['US', 'CA', 'MX', 'BR', 'AR', 'CL', 'CO', 'PE', 'VE', 'UY', 'PY', 'BO', 'EC', 'GY', 'SR'],
            'africa' => ['AO', 'ZA', 'NG', 'EG', 'KE', 'GH', 'MA', 'TN', 'DZ', 'ET', 'UG', 'TZ', 'ZM', 'ZW', 'BW'],
            'asia' => ['CN', 'JP', 'KR', 'IN', 'TH', 'SG', 'MY', 'ID', 'PH', 'VN', 'BD', 'PK', 'LK', 'MM', 'KH'],
        ];

        return $regions[$region] ?? [];
    }

    /**
     * Scope a query to find a country by its ISO code.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $code
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper($code));
    }

    /**
     * Extract country code from IBAN (first 2 characters).
     *
     * @param string $iban
     * @return string|null
     */
    public static function extractCountryCodeFromIban(string $iban): ?string
    {
        $iban = strtoupper(preg_replace('/\s+/', '', $iban));
        return strlen($iban) >= 2 ? substr($iban, 0, 2) : null;
    }

    /**
     * Get currency for this country.
     */
    public function getCurrency(): ?Currency
    {
        return Currency::getByCountryCode($this->code);
    }

    /**
     * Get currency code for this country.
     */
    public function getCurrencyCode(): ?string
    {
        $currency = $this->getCurrency();
        return $currency ? $currency->code : null;
    }
}
