<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionCategory extends Model
{
    protected $fillable = [
        'name',
        'type',
        'parent_id',
        'description',
        'color',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(TransactionCategory::class, 'parent_id');
    }

    /**
     * Get the transactions for the category.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }

    /**
     * Scope a query to only include income categories.
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    /**
     * Scope a query to only include expense categories.
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope a query to only include system categories.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope a query to only include custom categories.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }
}
