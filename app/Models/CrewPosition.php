<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrewPosition extends Model
{
    protected $fillable = [
        'name',
        'description',
        'vessel_id',
    ];

    /**
     * Get the vessel that owns the crew position.
     */
    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Get the crew members for the position.
     */
    public function crewMembers(): HasMany
    {
        return $this->hasMany(CrewMember::class, 'position_id');
    }
}
