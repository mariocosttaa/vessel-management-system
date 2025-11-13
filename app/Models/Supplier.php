<?php

namespace App\Models;

use App\Actions\General\EasyHashAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_name',
        'description',
        'email',
        'phone',
        'address',
        'notes',
        'vessel_id',
    ];

    /**
     * Get the route key for the model.
     * Returns the hashed ID for use in URLs.
     */
    public function getRouteKey(): string
    {
        return EasyHashAction::encode($this->id, 'supplier-id');
    }

    /**
     * Retrieve the model for route model binding.
     * Resolves hashed supplier IDs from URLs.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if (empty($value)) {
            return null;
        }

        // Try to decode as hashed ID first
        $decoded = EasyHashAction::decode($value, 'supplier-id');
        if ($decoded && is_numeric($decoded)) {
            $supplier = $this->where($field ?: $this->getRouteKeyName(), (int) $decoded)->first();
            if ($supplier) {
                return $supplier;
            }
        }

        // Fallback to numeric ID for backward compatibility
        if (is_numeric($value)) {
            return $this->where($field ?: $this->getRouteKeyName(), (int) $value)->first();
        }

        return null;
    }

    /**
     * Get the vessel that owns the supplier.
     */
    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Get the transactions for the supplier.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Movimentation::class);
    }

    /**
     * Get the recurring transactions for the supplier.
     */
    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringMovimentation::class);
    }
}
