<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MareaDistributionItem extends Model
{
    protected $table = 'marea_distribution_items';

    protected $fillable = [
        'marea_id',
        'profile_item_id',
        'order_index',
        'name',
        'description',
        'value_type',
        'value_amount',
        'reference_item_id',
        'operation',
        'reference_operation_item_id',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'value_amount' => 'decimal:2',
    ];

    /**
     * Get the marea that owns the item.
     */
    public function marea(): BelongsTo
    {
        return $this->belongsTo(Marea::class);
    }

    /**
     * Get the profile item that this item is based on (if any).
     */
    public function profileItem(): BelongsTo
    {
        return $this->belongsTo(MareaDistributionProfileItem::class, 'profile_item_id');
    }

    /**
     * Get the referenced item (for value_type = 'reference_item').
     */
    public function referenceItem(): BelongsTo
    {
        return $this->belongsTo(MareaDistributionItem::class, 'reference_item_id');
    }

    /**
     * Get the referenced operation item.
     */
    public function referenceOperationItem(): BelongsTo
    {
        return $this->belongsTo(MareaDistributionItem::class, 'reference_operation_item_id');
    }
}
