<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselUserRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vessel_id',
        'vessel_role_access_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns this vessel role.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vessel that this role belongs to.
     */
    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Get the role access definition.
     */
    public function vesselRoleAccess(): BelongsTo
    {
        return $this->belongsTo(VesselRoleAccess::class);
    }

    /**
     * Check if this role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->vesselRoleAccess->hasPermission($permission);
    }

    /**
     * Check if this role has any of the specified permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->vesselRoleAccess->hasAnyPermission($permissions);
    }

    /**
     * Get the role name.
     */
    public function getRoleNameAttribute(): string
    {
        return $this->vesselRoleAccess->name;
    }

    /**
     * Get the role display name.
     */
    public function getRoleDisplayNameAttribute(): string
    {
        return $this->vesselRoleAccess->display_name;
    }
}
