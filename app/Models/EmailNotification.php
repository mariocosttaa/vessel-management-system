<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailNotification extends Model
{
    protected $fillable = [
        'user_id',
        'vessel_id',
        'type',
        'subject_type',
        'subject_id',
        'subject_data',
        'action_by_user_id',
        'sent_at',
        'grouped_at',
        'is_grouped',
        'group_id',
    ];

    protected $casts = [
        'subject_data' => 'array',
        'sent_at' => 'datetime',
        'grouped_at' => 'datetime',
        'is_grouped' => 'boolean',
    ];

    /**
     * Get the user who should receive this notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vessel associated with this notification.
     */
    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Scope to get pending notifications (not sent yet).
     */
    public function scopePending($query)
    {
        return $query->whereNull('sent_at');
    }

    /**
     * Scope to get notifications for grouping.
     */
    public function scopeForGrouping($query, int $userId, int $vesselId, string $type, int $minutes = 5)
    {
        return $query->where('user_id', $userId)
            ->where('vessel_id', $vesselId)
            ->where('type', $type)
            ->whereNull('sent_at')
            ->whereNull('grouped_at')
            ->where('created_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope to get grouped notifications.
     */
    public function scopeGrouped($query, string|int $groupId)
    {
        return $query->where('group_id', $groupId)
            ->where('is_grouped', true);
    }

    /**
     * Mark notification as sent.
     */
    public function markAsSent(): void
    {
        $this->update(['sent_at' => now()]);
    }

    /**
     * Mark notification as grouped.
     */
    public function markAsGrouped(string|int $groupId): void
    {
        $this->update([
            'is_grouped' => true,
            'group_id' => $groupId,
            'grouped_at' => now(),
        ]);
    }
}

