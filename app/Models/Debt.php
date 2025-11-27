<?php

namespace App\Models;

use App\Actions\General\EasyHashAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vessel_id',
        'holder',
        'description',
        'amount',
        'paid_amount',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date'    => 'date',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKey(): string
    {
        return EasyHashAction::encode($this->id, 'debt-id');
    }

    /**
     * Retrieve the model for route model binding.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if (empty($value)) {
            return null;
        }

        $decoded = EasyHashAction::decode($value, 'debt-id');
        if ($decoded && is_numeric($decoded)) {
            $debt = $this->where($field ?: $this->getRouteKeyName(), (int) $decoded)->first();
            if ($debt) {
                return $debt;
            }
        }

        if (is_numeric($value)) {
            return $this->where($field ?: $this->getRouteKeyName(), (int) $value)->first();
        }

        return null;
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Get the remaining amount to be paid.
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->amount - $this->paid_amount;
    }
}
