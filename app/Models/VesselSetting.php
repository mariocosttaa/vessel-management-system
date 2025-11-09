<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselSetting extends Model
{
    protected $fillable = [
        'vessel_id',
        'country_code',
        'currency_code',
        'vat_profile_id',
    ];

    /**
     * Get the vessel for this setting.
     */
    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Get the country for this setting.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /**
     * Get the currency for this setting.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    /**
     * Get the VAT profile for this setting.
     */
    public function vatProfile(): BelongsTo
    {
        return $this->belongsTo(VatProfile::class);
    }

    /**
     * Get or create settings for a vessel.
     */
    public static function getForVessel(int $vesselId): self
    {
        return self::firstOrCreate(
            ['vessel_id' => $vesselId],
            [
                'country_code' => null,
                'currency_code' => null,
                'vat_profile_id' => null,
            ]
        );
    }

    /**
     * Update settings for a vessel.
     */
    public static function updateForVessel(int $vesselId, array $data): self
    {
        $setting = self::getForVessel($vesselId);
        $setting->update($data);
        return $setting->fresh();
    }
}
