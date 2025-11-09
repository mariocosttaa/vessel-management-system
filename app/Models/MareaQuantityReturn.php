<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MareaQuantityReturn extends Model
{
    protected $table = 'marea_quantity_return';

    protected $fillable = [
        'marea_id',
        'name',
        'quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    /**
     * Get the marea that owns the quantity return.
     */
    public function marea(): BelongsTo
    {
        return $this->belongsTo(Marea::class);
    }

}
