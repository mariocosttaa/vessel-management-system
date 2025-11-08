<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'vessel_id',
        'position_id',
        'phone',
        'date_of_birth',
        'hire_date',
        'house_of_zeros',
        'status',
        'notes',
        'login_permitted',
        'temporary_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factory_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'house_of_zeros' => 'integer',
            'login_permitted' => 'boolean',
        ];
    }

    /**
     * Get the attributes that should be appended to the model's array form.
     *
     * @return array<string>
     */
    protected function appends(): array
    {
        return [
            'roles',
            'permissions',
        ];
    }

    /**
     * Get the user's roles as an array of role names.
     */
    public function getRolesAttribute(): array
    {
        return $this->roles()->pluck('name')->toArray();
    }

    /**
     * Get the user's permissions based on their roles.
     */
    public function getPermissionsAttribute(): array
    {
        $permissions = [];

        // Admin has all permissions
        if ($this->hasRole('admin')) {
            $permissions = [
                'vessels.view' => true,
                'vessels.create' => true,
                'vessels.edit' => true,
                'vessels.delete' => true,
                'crew.view' => true,
                'crew.create' => true,
                'crew.edit' => true,
                'crew.delete' => true,
                'suppliers.view' => true,
                'suppliers.create' => true,
                'suppliers.edit' => true,
                'suppliers.delete' => true,
                'bank-accounts.view' => true,
                'bank-accounts.create' => true,
                'bank-accounts.edit' => true,
                'bank-accounts.delete' => true,
            ];
        }
        // Manager has most permissions except delete
        elseif ($this->hasRole('manager')) {
            $permissions = [
                'vessels.view' => true,
                'vessels.create' => true,
                'vessels.edit' => true,
                'vessels.delete' => false,
                'crew.view' => true,
                'crew.create' => true,
                'crew.edit' => true,
                'crew.delete' => false,
                'suppliers.view' => true,
                'suppliers.create' => true,
                'suppliers.edit' => true,
                'suppliers.delete' => false,
                'bank-accounts.view' => true,
                'bank-accounts.create' => true,
                'bank-accounts.edit' => true,
                'bank-accounts.delete' => false,
            ];
        }
        // Viewer has only view permissions
        elseif ($this->hasRole('viewer')) {
            $permissions = [
                'vessels.view' => true,
                'vessels.create' => false,
                'vessels.edit' => false,
                'vessels.delete' => false,
                'crew.view' => true,
                'crew.create' => false,
                'crew.edit' => false,
                'crew.delete' => false,
                'suppliers.view' => true,
                'suppliers.create' => false,
                'suppliers.edit' => false,
                'suppliers.delete' => false,
                'bank-accounts.view' => true,
                'bank-accounts.create' => false,
                'bank-accounts.edit' => false,
                'bank-accounts.delete' => false,
            ];
        }

        return $permissions;
    }

    /**
     * Get the roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * The vessel users that belong to the user.
     */
    public function vesselUsers(): HasMany
    {
        return $this->hasMany(VesselUser::class);
    }

    /**
     * The vessels that belong to the user.
     */
    public function vessels(): BelongsToMany
    {
        return $this->belongsToMany(Vessel::class, 'vessel_users')
                    ->withPivot(['is_active', 'role'])
                    ->withTimestamps()
                    ->wherePivot('is_active', true);
    }

    /**
     * Get the vessel user roles for this user.
     */
    public function vesselUserRoles(): HasMany
    {
        return $this->hasMany(VesselUserRole::class);
    }

    /**
     * Get vessels through vessel user roles.
     */
    public function vesselsThroughRoles(): BelongsToMany
    {
        return $this->belongsToMany(Vessel::class, 'vessel_user_roles')
                    ->withPivot(['vessel_role_access_id', 'is_active'])
                    ->withTimestamps()
                    ->wherePivot('is_active', true);
    }

    /**
     * Vessels owned by the user.
     */
    public function ownedVessels(): HasMany
    {
        return $this->hasMany(Vessel::class, 'owner_id');
    }

    /**
     * Get the transactions created by the user.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    /**
     * Get the attachments uploaded by the user.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'uploaded_by');
    }

    /**
     * Get the activity logs for the user.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Get the vessel this user works on (if they are a crew member).
     */
    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Get the salary compensations for this user.
     */
    public function salaryCompensations(): HasMany
    {
        return $this->hasMany(SalaryCompensation::class);
    }

    /**
     * Get the active salary compensation for this user.
     */
    public function activeSalaryCompensation(): HasMany
    {
        return $this->hasMany(SalaryCompensation::class)->where('is_active', true);
    }

    /**
     * Get the crew position this user has (if they are a crew member).
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(CrewPosition::class, 'position_id');
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Check if user has any of the specified roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check if user has all of the specified roles.
     */
    public function hasAllRoles(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->count() === count($roles);
    }

    /**
     * Check if user has access to a specific vessel.
     */
    public function hasAccessToVessel(int $vesselId): bool
    {
        return $this->vesselsThroughRoles()->where('vessels.id', $vesselId)->exists();
    }

    /**
     * Get user's role for a specific vessel.
     */
    public function getRoleForVessel(int $vesselId): ?string
    {
        $vesselUserRole = $this->vesselUserRoles()
            ->where('vessel_id', $vesselId)
            ->where('is_active', true)
            ->with('vesselRoleAccess')
            ->first();

        return $vesselUserRole?->vesselRoleAccess?->display_name;
    }

    /**
     * Check if user has specific role for vessel.
     */
    public function hasRoleForVessel(int $vesselId, string $role): bool
    {
        return $this->getRoleForVessel($vesselId) === $role;
    }

    /**
     * Check if user has any of the specified roles for vessel.
     */
    public function hasAnyRoleForVessel(int $vesselId, array $roles): bool
    {
        $userRole = $this->getRoleForVessel($vesselId);
        return $userRole && in_array($userRole, $roles);
    }

    /**
     * Check if user can create vessels.
     * Only paid_system users can create vessels.
     */
    public function canCreateVessels(): bool
    {
        return $this->user_type === 'paid_system';
    }

    /**
     * Check if user can edit a specific vessel.
     * Users can edit vessels if they have appropriate role access.
     */
    public function canEditVessel(int $vesselId): bool
    {
        $vesselUserRole = $this->vesselUserRoles()
            ->where('vessel_id', $vesselId)
            ->where('is_active', true)
            ->with('vesselRoleAccess')
            ->first();

        if (!$vesselUserRole) {
            return false;
        }

        return $vesselUserRole->hasAnyPermission(['edit_vessel_basic', 'edit_vessel_advanced']);
    }

    /**
     * Check if user can delete a specific vessel.
     * Only administrators can delete vessels.
     */
    public function canDeleteVessel(int $vesselId): bool
    {
        $vesselUserRole = $this->vesselUserRoles()
            ->where('vessel_id', $vesselId)
            ->where('is_active', true)
            ->with('vesselRoleAccess')
            ->first();

        if (!$vesselUserRole) {
            return false;
        }

        return $vesselUserRole->hasPermission('delete_vessel');
    }

    /**
     * Check if user can manage vessel users for a specific vessel.
     */
    public function canManageVesselUsers(int $vesselId): bool
    {
        $vesselUserRole = $this->vesselUserRoles()
            ->where('vessel_id', $vesselId)
            ->where('is_active', true)
            ->with('vesselRoleAccess')
            ->first();

        if (!$vesselUserRole) {
            return false;
        }

        return $vesselUserRole->hasPermission('manage_vessel_users');
    }

    /**
     * Get user's role access for a specific vessel.
     */
    public function getVesselRoleAccess(int $vesselId): ?VesselRoleAccess
    {
        $vesselUserRole = $this->vesselUserRoles()
            ->where('vessel_id', $vesselId)
            ->where('is_active', true)
            ->with('vesselRoleAccess')
            ->first();

        return $vesselUserRole?->vesselRoleAccess;
    }

    /**
     * Check if user has a specific permission for a vessel.
     */
    public function hasVesselPermission(int $vesselId, string $permission): bool
    {
        $vesselUserRole = $this->vesselUserRoles()
            ->where('vessel_id', $vesselId)
            ->where('is_active', true)
            ->with('vesselRoleAccess')
            ->first();

        if (!$vesselUserRole) {
            return false;
        }

        return $vesselUserRole->hasPermission($permission);
    }

    /**
     * Get vessels where user has specific role.
     */
    public function vesselsWithRole(string $role)
    {
        return $this->vessels()->wherePivot('role', $role);
    }

    /**
     * Get crew member record for a specific vessel.
     */
    public function getCrewMemberForVessel(int $vesselId): ?User
    {
        return $this->where('vessel_id', $vesselId)->first();
    }

    /**
     * Check if user can be added as crew member to a vessel.
     * User must have access to the vessel and not already be a crew member.
     */
    public function canBeCrewMemberOnVessel(int $vesselId): bool
    {
        return $this->hasAccessToVessel($vesselId) && !$this->isCrewMemberOnVessel($vesselId);
    }

    /**
     * Check if this user is a crew member (has vessel_id and position_id).
     */
    public function isCrewMember(): bool
    {
        return !is_null($this->vessel_id) && !is_null($this->position_id);
    }

    /**
     * Check if this user is a crew member on a specific vessel.
     */
    public function isCrewMemberOnVessel(int $vesselId): bool
    {
        return $this->isCrewMember() && $this->vessel_id == $vesselId;
    }

    /**
     * Check if this user has an existing account (not just a temporary crew member account).
     * A user has an existing account if:
     * 1. They have a user_type other than 'employee_of_vessel', OR
     * 2. They have login_permitted = true AND no temporary_password (meaning they have a real password).
     */
    public function hasExistingAccount(): bool
    {
        // If user_type is not 'employee_of_vessel', they have an existing account
        if ($this->user_type !== 'employee_of_vessel') {
            return true;
        }

        // If they have login_permitted and no temporary_password, they have a real account
        if ($this->login_permitted && is_null($this->temporary_password)) {
            return true;
        }

        return false;
    }

    /**
     * Generate a temporary password for crew members.
     */
    public function generateTemporaryPassword(): string
    {
        $password = 'temp_' . time() . '_' . $this->id;
        $this->update(['temporary_password' => $password]);
        return $password;
    }

    /**
     * Enable system access for this crew member.
     */
    public function enableSystemAccess(): void
    {
        $this->update([
            'login_permitted' => true,
            'temporary_password' => null,
        ]);
    }

    /**
     * Disable system access for this crew member.
     */
    public function disableSystemAccess(): void
    {
        $this->update([
            'login_permitted' => false,
            'temporary_password' => $this->generateTemporaryPassword(),
        ]);
    }
}
