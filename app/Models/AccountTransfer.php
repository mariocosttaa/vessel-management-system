<?php

namespace App\Models;

use App\Actions\MoneyAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountTransfer extends Model
{

    protected $fillable = [
        'from_account_id',
        'to_account_id',
        'from_transaction_id',
        'to_transaction_id',
        'amount',
        'currency',
        'house_of_zeros',
        'transfer_date',
        'description',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount' => 'integer',
        'house_of_zeros' => 'integer',
    ];

    /**
     * Get the from account.
     */
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'from_account_id');
    }

    /**
     * Get the to account.
     */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'to_account_id');
    }

    /**
     * Get the from transaction.
     */
    public function fromTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'from_transaction_id');
    }

    /**
     * Get the to transaction.
     */
    public function toTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'to_transaction_id');
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
