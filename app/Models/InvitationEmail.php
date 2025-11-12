<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationEmail extends Model
{
    protected $fillable = [
        'user_id',
        'vessel_id',
        'email_type',
        'invitation_token',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }
}
