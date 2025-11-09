<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MareaDistributionProfileItem extends Model
{
    protected $table = 'marea_distribution_profile_items';

    protected $fillable = [
        'distribution_profile_id',
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
     * Get the distribution profile that owns the item.
     */
    public function distributionProfile(): BelongsTo
    {
        return $this->belongsTo(MareaDistributionProfile::class, 'distribution_profile_id');
    }

    /**
     * Get the referenced item (for value_type = 'reference_item').
     */
    public function referenceItem(): BelongsTo
    {
        return $this->belongsTo(MareaDistributionProfileItem::class, 'reference_item_id');
    }

    /**
     * Get the referenced operation item.
     */
    public function referenceOperationItem(): BelongsTo
    {
        return $this->belongsTo(MareaDistributionProfileItem::class, 'reference_operation_item_id');
    }
}
