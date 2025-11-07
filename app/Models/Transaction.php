<?php

namespace App\Models;

use App\Actions\MoneyAction;
use App\Services\MoneyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transaction_number',
        'vessel_id',
        'bank_account_id',
        'category_id',
        'supplier_id',
        'crew_member_id',
        'type',
        'amount',
        'currency',
        'house_of_zeros',
        'vat_rate_id',
        'vat_amount',
        'total_amount',
        'transaction_date',
        'transaction_month',
        'transaction_year',
        'description',
        'notes',
        'reference',
        'is_recurring',
        'recurring_transaction_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'is_recurring' => 'boolean',
        'amount' => 'integer',
        'vat_amount' => 'integer',
        'total_amount' => 'integer',
        'transaction_month' => 'integer',
        'transaction_year' => 'integer',
        'house_of_zeros' => 'integer',
    ];

    /**
     * Get the vessel that owns the transaction.
     */
    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Get the bank account that owns the transaction.
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Get the category that owns the transaction.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    /**
     * Get the supplier that owns the transaction.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the crew member that owns the transaction.
     */
    public function crewMember(): BelongsTo
    {
        return $this->belongsTo(CrewMember::class);
    }

    /**
     * Get the VAT rate that owns the transaction.
     */
    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    /**
     * Get the recurring transaction that owns the transaction.
     */
    public function recurringTransaction(): BelongsTo
    {
        return $this->belongsTo(RecurringTransaction::class);
    }

    /**
     * Get the user that created the transaction.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the attachments for the transaction.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Scope a query to only include income transactions.
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    /**
     * Scope a query to only include expense transactions.
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope a query to only include transfer transactions.
     */
    public function scopeTransfer($query)
    {
        return $query->where('type', 'transfer');
    }

    /**
     * Scope a query to only include completed transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include cancelled transactions.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to only include transactions for a specific period.
     */
    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('transaction_year', $year)
                    ->where('transaction_month', $month);
    }

    /**
     * Scope a query to only include transactions for a specific vessel.
     */
    public function scopeForVessel($query, $vesselId)
    {
        return $query->where('vessel_id', $vesselId);
    }

    /**
     * Scope a query to only include transactions for a specific bank account.
     */
    public function scopeForBankAccount($query, $bankAccountId)
    {
        return $query->where('bank_account_id', $bankAccountId);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (!$transaction->transaction_number) {
                $transaction->transaction_number = self::generateTransactionNumber();
            }

            // Extract month and year from date
            $date = \Carbon\Carbon::parse($transaction->transaction_date);
            $transaction->transaction_month = $date->month;
            $transaction->transaction_year = $date->year;

            // Calculate VAT if necessary
            if ($transaction->vat_rate_id && !$transaction->vat_amount) {
                $vatRate = VatRate::find($transaction->vat_rate_id);
                $transaction->vat_amount = MoneyService::calculateVat(
                    $transaction->amount,
                    (float) $vatRate->rate,
                    $transaction->house_of_zeros
                );
            }

            $transaction->total_amount = $transaction->amount + ($transaction->vat_amount ?? 0);
        });
    }

    /**
     * Generate transaction number.
     */
    private static function generateTransactionNumber(): string
    {
        $year = date('Y');
        $lastTransaction = self::whereYear('created_at', $year)
                              ->orderBy('id', 'desc')
                              ->first();

        $nextNumber = $lastTransaction ?
            (int) substr($lastTransaction->transaction_number, -6) + 1 : 1;

        return sprintf('TRX%s%06d', $year, $nextNumber);
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

    /**
     * Get formatted VAT amount attribute.
     */
    public function getFormattedVatAmountAttribute(): string
    {
        return MoneyAction::format(
            $this->vat_amount ?? 0,
            $this->house_of_zeros,
            $this->currency,
            true
        );
    }

    /**
     * Get formatted total amount attribute.
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return MoneyAction::format(
            $this->total_amount,
            $this->house_of_zeros,
            $this->currency,
            true
        );
    }
}
