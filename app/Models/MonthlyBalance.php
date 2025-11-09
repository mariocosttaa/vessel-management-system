<?php

namespace App\Models;

use App\Actions\MoneyAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyBalance extends Model
{

    protected $fillable = [
        'vessel_id',
        'month',
        'year',
        'opening_balance',
        'total_income',
        'total_expense',
        'closing_balance',
        'currency',
        'house_of_zeros',
        'transaction_count',
        'last_calculated_at',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'opening_balance' => 'integer',
        'total_income' => 'integer',
        'total_expense' => 'integer',
        'closing_balance' => 'integer',
        'house_of_zeros' => 'integer',
        'transaction_count' => 'integer',
        'last_calculated_at' => 'datetime',
    ];

    /**
     * Get the vessel that owns the monthly balance.
     */
    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Scope a query to only include balances for a specific period.
     */
    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    /**
     * Scope a query to only include balances for a specific vessel.
     */
    public function scopeForVessel($query, $vesselId)
    {
        return $query->where('vessel_id', $vesselId);
    }

    /**
     * Get formatted opening balance attribute.
     */
    public function getFormattedOpeningBalanceAttribute(): string
    {
        return MoneyAction::format(
            $this->opening_balance,
            $this->house_of_zeros,
            $this->currency,
            true
        );
    }

    /**
     * Get formatted total income attribute.
     */
    public function getFormattedTotalIncomeAttribute(): string
    {
        return MoneyAction::format(
            $this->total_income,
            $this->house_of_zeros,
            $this->currency,
            true
        );
    }

    /**
     * Get formatted total expense attribute.
     */
    public function getFormattedTotalExpenseAttribute(): string
    {
        return MoneyAction::format(
            $this->total_expense,
            $this->house_of_zeros,
            $this->currency,
            true
        );
    }

    /**
     * Get formatted closing balance attribute.
     */
    public function getFormattedClosingBalanceAttribute(): string
    {
        return MoneyAction::format(
            $this->closing_balance,
            $this->house_of_zeros,
            $this->currency,
            true
        );
    }
}
