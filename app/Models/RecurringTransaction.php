<?php

namespace App\Models;

use App\Actions\MoneyAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vessel_id',
        'bank_account_id',
        'category_id',
        'supplier_id',
        'name',
        'type',
        'amount',
        'currency',
        'house_of_zeros',
        'vat_rate_id',
        'frequency',
        'start_date',
        'end_date',
        'next_occurrence_date',
        'last_generated_date',
        'description',
        'auto_generate',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_occurrence_date' => 'date',
        'last_generated_date' => 'date',
        'amount' => 'integer',
        'house_of_zeros' => 'integer',
        'auto_generate' => 'boolean',
    ];

    /**
     * Get the vessel that owns the recurring transaction.
     */
    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Get the bank account that owns the recurring transaction.
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Get the category that owns the recurring transaction.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    /**
     * Get the supplier that owns the recurring transaction.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the VAT rate that owns the recurring transaction.
     */
    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    /**
     * Get the transactions for the recurring transaction.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Scope a query to only include active recurring transactions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include paused recurring transactions.
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    /**
     * Scope a query to only include completed recurring transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include recurring transactions due for generation.
     */
    public function scopeDueForGeneration($query)
    {
        return $query->where('status', 'active')
                    ->where('auto_generate', true)
                    ->where('next_occurrence_date', '<=', now()->toDateString());
    }

    /**
     * Get formatted amount attribute.
     */
    public function getFormattedAmountAttribute(): string
    {
        return MoneyAction::format(
            $this->amount,
            $this->house_of_zeros,
            $this->currency,
            true
        );
    }
}
