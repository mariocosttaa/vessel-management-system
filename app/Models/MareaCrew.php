<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MareaCrew extends Model
{
    protected $table = 'marea_crew';

    protected $fillable = [
        'marea_id',
        'user_id',
        'notes',
    ];

    /**
     * Get the marea that owns the crew member.
     */
    public function marea(): BelongsTo
    {
        return $this->belongsTo(Marea::class);
    }

    /**
     * Get the user (crew member).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
