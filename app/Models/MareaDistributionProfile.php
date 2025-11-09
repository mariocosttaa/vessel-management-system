<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MareaDistributionProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_default',
        'is_system',
        'created_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Get the user that created the profile.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the items for the profile.
     */
    public function items(): HasMany
    {
        return $this->hasMany(MareaDistributionProfileItem::class, 'distribution_profile_id')
            ->orderBy('order_index');
    }

    /**
     * Scope a query to only include default profile.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to only include system profiles.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope a query to only include user-created profiles.
     */
    public function scopeUserCreated($query)
    {
        return $query->where('is_system', false);
    }
}
