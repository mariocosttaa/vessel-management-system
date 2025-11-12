<?php

namespace App\Models;

use App\Actions\General\EasyHashAction;
use App\Actions\MoneyAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\VesselSetting;

class Maintenance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'maintenance_number',
        'vessel_id',
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'closed_at',
        'currency',
        'house_of_zeros',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
        'house_of_zeros' => 'integer',
    ];

    /**
     * Get the route key for the model.
     * Returns the hashed ID for use in URLs.
     */
    public function getRouteKey(): string
    {
        return EasyHashAction::encode($this->id, 'maintenance-id');
    }

    /**
     * Retrieve the model for route model binding.
     * Resolves hashed maintenance IDs from URLs.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if (empty($value)) {
            return null;
        }

        // Try to decode as hashed ID first
        $decoded = EasyHashAction::decode($value, 'maintenance-id');
        if ($decoded && is_numeric($decoded)) {
            $maintenance = $this->where($field ?: $this->getRouteKeyName(), (int) $decoded)->first();
            if ($maintenance) {
                return $maintenance;
            }
        }

        // Fallback to numeric ID for backward compatibility
        if (is_numeric($value)) {
            return $this->where($field ?: $this->getRouteKeyName(), (int) $value)->first();
        }

        return null;
    }

    /**
     * Get the vessel that owns the maintenance.
     */
    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Get the user that created the maintenance.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the transactions for the maintenance.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Scope a query to only include open maintenances.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope a query to only include closed maintenances.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Scope a query to only include cancelled maintenances.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to only include active maintenances (open).
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope a query to only include maintenances for a specific vessel.
     */
    public function scopeForVessel($query, $vesselId)
    {
        return $query->where('vessel_id', $vesselId);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($maintenance) {
            if (!$maintenance->maintenance_number && $maintenance->vessel_id) {
                $maintenance->maintenance_number = self::generateMaintenanceNumber($maintenance->vessel_id);
            }
        });
    }

    /**
     * Generate maintenance number based on vessel's starting number.
     */
    private static function generateMaintenanceNumber(int $vesselId): string
    {
        // Find the last maintenance for this vessel
        $lastMaintenance = self::where('vessel_id', $vesselId)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastMaintenance) {
            // Extract number from last maintenance (format: MANT2025000001)
            // Get the numeric part (last 6 digits)
            $lastNumber = (int) substr($lastMaintenance->maintenance_number, -6);
            $nextNumber = $lastNumber + 1;
        } else {
            // This is the first maintenance for this vessel, start at 1
            $nextNumber = 1;
        }

        $year = date('Y');
        return sprintf('MANT%s%06d', $year, $nextNumber);
    }

    /**
     * Get the next maintenance number for a vessel (for display purposes).
     */
    public static function getNextMaintenanceNumber(int $vesselId): string
    {
        return self::generateMaintenanceNumber($vesselId);
    }

    /**
     * Close the maintenance.
     */
    public function close(): void
    {
        if ($this->status === 'closed') {
            throw new \Exception('Maintenance is already closed.');
        }

        if ($this->status === 'cancelled') {
            throw new \Exception('Cannot close a cancelled maintenance.');
        }

        $this->update([
            'status' => 'closed',
            'closed_at' => Carbon::now(),
        ]);
    }

    /**
     * Cancel the maintenance.
     */
    public function cancel(): void
    {
        if ($this->status === 'closed') {
            throw new \Exception('Cannot cancel a closed maintenance.');
        }

        if ($this->status === 'cancelled') {
            throw new \Exception('Maintenance is already cancelled.');
        }

        $this->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Calculate total expenses from transactions.
     */
    public function getTotalExpensesAttribute(): int
    {
        return (int) ($this->transactions()
            ->where('type', 'expense')
            ->sum('total_amount') ?? 0);
    }

    /**
     * Get currency for this maintenance.
     */
    public function getCurrency(): ?string
    {
        if ($this->currency) {
            return strtoupper($this->currency);
        }

        // Fallback to vessel currency
        if ($this->vessel && $this->vessel->currency_code) {
            return strtoupper($this->vessel->currency_code);
        }

        return 'EUR'; // Final fallback
    }

    /**
     * Get house of zeros (decimal places) for this maintenance.
     */
    public function getHouseOfZeros(): int
    {
        return $this->house_of_zeros ?? 2;
    }

    /**
     * Get formatted total expenses attribute.
     */
    public function getFormattedTotalExpensesAttribute(): string
    {
        return MoneyAction::format(
            $this->total_expenses,
            $this->getHouseOfZeros(),
            $this->getCurrency(),
            true
        );
    }
}
