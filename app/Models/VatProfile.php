<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VatProfile extends Model
{
    protected $fillable = [
        'country_id',
        'name',
        'percentage',
        'code',
        'description',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the country for this VAT profile.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the transactions using this VAT profile.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Movimentation::class, 'vat_profile_id');
    }

    /**
     * Scope a query to only include active VAT profiles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include default VAT profile.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get formatted VAT rate display.
     */
    public function getFormattedRateAttribute(): string
    {
        return number_format($this->percentage, 2, '.', '') . '%';
    }

    /**
     * Get display name with rate.
     */
    public function getDisplayNameAttribute(): string
    {
        $country = $this->country ? " ({$this->country->name})" : '';
        return "{$this->name} - {$this->formatted_rate}{$country}";
    }
}
