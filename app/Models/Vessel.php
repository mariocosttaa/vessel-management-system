<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vessel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'registration_number',
        'vessel_type',
        'capacity',
        'year_built',
        'status',
        'notes',
        'owner_id',
        'country_code',
        'currency_code',
    ];

    protected $casts = [
        'year_built' => 'integer',
        'capacity' => 'integer',
    ];

    /**
     * The vessel users that belong to the vessel.
     */
    public function vesselUsers(): HasMany
    {
        return $this->hasMany(VesselUser::class);
    }

    /**
     * The users that belong to the vessel.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'vessel_users')
                    ->withPivot(['is_active', 'role'])
                    ->withTimestamps()
                    ->wherePivot('is_active', true);
    }

    /**
     * Get the vessel user roles for this vessel.
     */
    public function vesselUserRoles(): HasMany
    {
        return $this->hasMany(VesselUserRole::class);
    }

    /**
     * Get users through vessel user roles.
     */
    public function usersThroughRoles(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'vessel_user_roles')
                    ->withPivot(['vessel_role_access_id', 'is_active'])
                    ->withTimestamps()
                    ->wherePivot('is_active', true);
    }

    /**
     * The owner of the vessel.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * The country where the vessel is registered.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /**
     * The default currency for the vessel.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    /**
     * Get the crew members for the vessel.
     */
    public function crewMembers(): HasMany
    {
        return $this->hasMany(User::class, 'vessel_id');
    }

    /**
     * Get the transactions for the vessel.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the mareas for the vessel.
     */
    public function mareas(): HasMany
    {
        return $this->hasMany(Marea::class);
    }

    /**
     * Get the recurring transactions for the vessel.
     */
    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    /**
     * Get the monthly balances for the vessel.
     */
    public function monthlyBalances(): HasMany
    {
        return $this->hasMany(MonthlyBalance::class);
    }

    /**
     * Get the settings for the vessel.
     */
    public function setting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(VesselSetting::class);
    }

    /**
     * Scope a query to only include active vessels.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include vessels in maintenance.
     */
    public function scopeMaintenance($query)
    {
        return $query->where('status', 'maintenance');
    }

    /**
     * Scope a query to only include suspended vessels.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Users with specific role for this vessel.
     */
    public function usersWithRole(string $role)
    {
        return $this->users()->wherePivot('role', $role);
    }

    /**
     * Check if user has access to this vessel.
     */
    public function hasUser(int $userId): bool
    {
        return $this->users()->where('user_id', $userId)->exists();
    }

    /**
     * Get user's role for this vessel.
     */
    public function getUserRole(int $userId): ?string
    {
        $pivot = $this->users()->where('user_id', $userId)->first()?->pivot;
        return $pivot?->role;
    }
}
