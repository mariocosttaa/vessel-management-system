<?php

namespace App\Models;

use App\Actions\MoneyAction;
use App\Services\MoneyService;
use App\Models\VatProfile;
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
        'marea_id',
        'category_id',
        'supplier_id',
        'crew_member_id',
        'type',
        'amount',
        'amount_per_unit',
        'quantity',
        'currency',
        'house_of_zeros',
        'vat_profile_id',
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
        'amount_per_unit' => 'integer',
        'quantity' => 'integer',
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
     * Get the marea that owns the transaction.
     */
    public function marea(): BelongsTo
    {
        return $this->belongsTo(Marea::class);
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
     * Crew members are now User models with vessel_id and position_id.
     */
    public function crewMember(): BelongsTo
    {
        return $this->belongsTo(User::class, 'crew_member_id');
    }

    /**
     * Get the VAT profile that owns the transaction.
     */
    public function vatProfile(): BelongsTo
    {
        return $this->belongsTo(VatProfile::class);
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
     * Get the files for the transaction.
     */
    public function files(): HasMany
    {
        return $this->hasMany(TransactionFile::class);
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

            // Auto-generate reference number if not provided
            if (!$transaction->reference) {
                $transaction->reference = self::generateReferenceNumber();
            }

            // Extract month and year from date
            if ($transaction->transaction_date) {
                $date = \Carbon\Carbon::parse($transaction->transaction_date);
                $transaction->transaction_month = $date->month;
                $transaction->transaction_year = $date->year;
            }

            // For income transactions, ensure vat_profile_id is set from vessel settings if not provided
            if ($transaction->type === 'income' && !$transaction->vat_profile_id && $transaction->vessel_id) {
                $vesselSetting = \App\Models\VesselSetting::getForVessel($transaction->vessel_id);
                if ($vesselSetting && $vesselSetting->vat_profile_id) {
                    $transaction->vat_profile_id = $vesselSetting->vat_profile_id;
                } else {
                    // Fallback to default VAT profile
                    $defaultVatProfile = VatProfile::where('is_default', true)->first();
                    if ($defaultVatProfile) {
                        $transaction->vat_profile_id = $defaultVatProfile->id;
                    }
                }
            }

            // For expense transactions, ensure vat_profile_id is null
            if ($transaction->type === 'expense') {
                $transaction->vat_profile_id = null;
                $transaction->vat_amount = 0;
            }

            // Calculate VAT if not already set (controller may have set it)
            // Only calculate if vat_amount is not set and we have a VAT profile
            if (!$transaction->vat_amount && $transaction->vat_profile_id) {
                $vatProfile = VatProfile::find($transaction->vat_profile_id);
                if ($vatProfile) {
                    // VAT calculation is handled in controller based on amount_includes_vat flag
                    // Here we just set default if not provided
                    $transaction->vat_amount = MoneyService::calculateVat(
                        $transaction->amount,
                        (float) $vatProfile->percentage,
                        $transaction->house_of_zeros ?? 2
                    );
                }
            }

            // Calculate total amount if not already set
            if (!$transaction->total_amount) {
                $transaction->total_amount = $transaction->amount + ($transaction->vat_amount ?? 0);
            }
        });

        static::updating(function ($transaction) {
            // Recalculate month and year if date changed
            if ($transaction->isDirty('transaction_date') && $transaction->transaction_date) {
                $date = \Carbon\Carbon::parse($transaction->transaction_date);
                $transaction->transaction_month = $date->month;
                $transaction->transaction_year = $date->year;
            }

            // For income transactions, ensure vat_profile_id is set from vessel settings if not provided
            if ($transaction->type === 'income' && !$transaction->vat_profile_id && $transaction->vessel_id) {
                $vesselSetting = \App\Models\VesselSetting::getForVessel($transaction->vessel_id);
                if ($vesselSetting && $vesselSetting->vat_profile_id) {
                    $transaction->vat_profile_id = $vesselSetting->vat_profile_id;
                } else {
                    // Fallback to default VAT profile
                    $defaultVatProfile = VatProfile::where('is_default', true)->first();
                    if ($defaultVatProfile) {
                        $transaction->vat_profile_id = $defaultVatProfile->id;
                    }
                }
            }

            // For expense transactions, ensure vat_profile_id is null
            if ($transaction->type === 'expense') {
                $transaction->vat_profile_id = null;
                $transaction->vat_amount = 0;
            }

            // Recalculate VAT if amount or VAT profile changed
            if ($transaction->isDirty('amount') || $transaction->isDirty('vat_profile_id') || $transaction->isDirty('house_of_zeros')) {
                if ($transaction->vat_profile_id && $transaction->type === 'income') {
                    $vatProfile = VatProfile::find($transaction->vat_profile_id);
                    if ($vatProfile) {
                        $transaction->vat_amount = MoneyService::calculateVat(
                            $transaction->amount,
                            (float) $vatProfile->percentage,
                            $transaction->house_of_zeros ?? 2
                        );
                    }
                } else {
                    $transaction->vat_amount = 0;
                }
            }

            // Recalculate total amount if amount or VAT amount changed
            if ($transaction->isDirty('amount') || $transaction->isDirty('vat_amount')) {
                $transaction->total_amount = $transaction->amount + ($transaction->vat_amount ?? 0);
            }
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
     * Generate reference number (auto-generated if not provided).
     */
    private static function generateReferenceNumber(): string
    {
        $year = date('Y');
        $month = date('m');

        // Get the last transaction with a reference number in this month/year
        $lastTransaction = self::whereYear('created_at', $year)
                              ->whereMonth('created_at', $month)
                              ->whereNotNull('reference')
                              ->where('reference', 'like', 'REF' . $year . $month . '%')
                              ->orderBy('id', 'desc')
                              ->first();

        if ($lastTransaction && $lastTransaction->reference) {
            // Extract the number part from the reference (last 6 digits)
            $lastNumber = (int) substr($lastTransaction->reference, -6);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('REF%s%s%06d', $year, $month, $nextNumber);
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
