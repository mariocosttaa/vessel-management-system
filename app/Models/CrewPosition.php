<?php
namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrewPosition extends Model
{
    use HasTranslations;

    protected $fillable = [
        'name',
        'description',
        'vessel_id',
        'vessel_role_access_id',
    ];

    /**
     * Get the vessel that owns the crew position.
     */
    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Get the vessel role access associated with this crew position.
     */
    public function vesselRoleAccess(): BelongsTo
    {
        return $this->belongsTo(VesselRoleAccess::class);
    }

    /**
     * Get the crew members (users) for the position.
     */
    public function crewMembers(): HasMany
    {
        return $this->hasMany(User::class, 'position_id');
    }

    /**
     * Get the translated name of the crew position.
     * Falls back to the original name if translation is not available.
     *
     * @return string
     */
    public function getTranslatedNameAttribute(): string
    {
        return $this->transFrom('crew-positions', $this->name);
    }
}
