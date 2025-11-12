<?php

namespace App\Actions;

use App\Models\EmailNotification;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmailNotificationAction
{
    /**
     * Create a notification record for users who should receive it.
     * Does not send the email immediately - that's handled by the job.
     *
     * @param string $type Notification type (transaction_created, transaction_deleted, marea_started, marea_completed)
     * @param string $subjectType Model class name (Transaction, Marea)
     * @param int $subjectId Model ID
     * @param int $vesselId Vessel ID
     * @param int $actionByUserId User ID who performed the action (excluded from notifications)
     * @param array|null $subjectData Optional snapshot of subject data
     * @return void
     */
    public static function createNotification(
        string $type,
        string $subjectType,
        int $subjectId,
        int $vesselId,
        int $actionByUserId,
        ?array $subjectData = null
    ): void {
        try {
            // Get all users who should receive notifications for this vessel
            $users = User::whereHas('vesselUserRoles', function ($query) use ($vesselId) {
                $query->where('vessel_id', $vesselId)
                    ->where('is_active', true);
            })
            ->where('vessel_admin_notification', true)
            ->where('id', '!=', $actionByUserId) // Don't notify the user who made the change
            ->get();

            foreach ($users as $user) {
                // Check if user has high-level access to this vessel
                if (!$user->hasHighVesselAccess($vesselId)) {
                    continue;
                }

                // Create notification record
                EmailNotification::create([
                    'user_id' => $user->id,
                    'vessel_id' => $vesselId,
                    'type' => $type,
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'subject_data' => $subjectData,
                    'action_by_user_id' => $actionByUserId,
                ]);
            }

            // Dispatch job to process and send grouped notifications
            // Delay to group notifications (2 minutes window)
            // The job will check for pending notifications and group them
            \App\Jobs\SendGroupedEmailNotifications::dispatch($vesselId)
                ->delay(now()->addMinutes(2))
                ->onQueue('emails');
        } catch (\Exception $e) {
            Log::error('Failed to create email notification', [
                'type' => $type,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'vessel_id' => $vesselId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get pending notifications for a user and vessel, grouped by type.
     *
     * @param int $userId
     * @param int $vesselId
     * @param int $minutes Time window for grouping (default: 5 minutes)
     * @return array
     */
    public static function getPendingNotificationsForGrouping(int $userId, int $vesselId, int $minutes = 5): array
    {
        $notifications = EmailNotification::where('user_id', $userId)
            ->where('vessel_id', $vesselId)
            ->whereNull('sent_at')
            ->whereNull('grouped_at')
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->orderBy('created_at', 'asc')
            ->get();

        // Group by type
        $grouped = [];
        foreach ($notifications as $notification) {
            $type = $notification->type;
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $notification;
        }

        return $grouped;
    }

    /**
     * Mark notifications as grouped.
     *
     * @param array $notificationIds
     * @param string|int $groupId
     * @return void
     */
    public static function markNotificationsAsGrouped(array $notificationIds, string|int $groupId): void
    {
        EmailNotification::whereIn('id', $notificationIds)
            ->update([
                'is_grouped' => true,
                'group_id' => $groupId,
                'grouped_at' => now(),
            ]);
    }

    /**
     * Mark notifications as sent.
     *
     * @param array $notificationIds
     * @return void
     */
    public static function markNotificationsAsSent(array $notificationIds): void
    {
        EmailNotification::whereIn('id', $notificationIds)
            ->update(['sent_at' => now()]);
    }
}

