<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryCompensation extends Model
{
    use HasFactory;

    protected $table = 'salary_compensations';

    protected $fillable = [
        'user_id',
        'compensation_type',
        'fixed_amount',
        'percentage',
        'currency',
        'payment_frequency',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'fixed_amount' => 'integer',
        'percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the salary compensation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the formatted fixed amount for display.
     */
    public function getFormattedFixedAmountAttribute(): string
    {
        if (!$this->fixed_amount) {
            return '0,00';
        }

        return number_format($this->fixed_amount / 100, 2, ',', '.');
    }

    /**
     * Get the formatted percentage for display.
     */
    public function getFormattedPercentageAttribute(): string
    {
        if (!$this->percentage) {
            return '0,00%';
        }

        return number_format($this->percentage, 2, ',', '.') . '%';
    }

    /**
     * Scope to get active compensations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get fixed compensations.
     */
    public function scopeFixed($query)
    {
        return $query->where('compensation_type', 'fixed');
    }

    /**
     * Scope to get percentage compensations.
     */
    public function scopePercentage($query)
    {
        return $query->where('compensation_type', 'percentage');
    }
}