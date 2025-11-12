<?php

namespace App\Jobs;

use App\Mail\GroupedVesselNotificationMail;
use App\Models\EmailNotification;
use App\Models\User;
use App\Models\Vessel;
use App\Actions\EmailNotificationAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendGroupedEmailNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $vesselId
    ) {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Load vessel with name to ensure it's available in email templates
            $vessel = Vessel::find($this->vesselId);
            if (!$vessel) {
                Log::warning('Vessel not found for email notifications', ['vessel_id' => $this->vesselId]);
                return;
            }

            // Ensure vessel name is loaded
            if (empty($vessel->name)) {
                Log::warning('Vessel name is empty', ['vessel_id' => $this->vesselId]);
            }

            // Get all users who should receive notifications for this vessel
            $users = User::whereHas('vesselUserRoles', function ($query) {
                $query->where('vessel_id', $this->vesselId)
                    ->where('is_active', true);
            })
            ->where('vessel_admin_notification', true)
            ->get();

            foreach ($users as $user) {
                // Check if user has high-level access
                if (!$user->hasHighVesselAccess($this->vesselId)) {
                    continue;
                }

                // Get pending notifications for this user (last 5 minutes)
                $groupedNotifications = EmailNotificationAction::getPendingNotificationsForGrouping(
                    $user->id,
                    $this->vesselId,
                    5
                );

                if (empty($groupedNotifications)) {
                    continue;
                }

                // Process each notification type group
                foreach ($groupedNotifications as $type => $notifications) {
                    // Limit to last 3 notifications of each type to prevent spam
                    $notificationsToSend = collect($notifications)->take(3)->values()->all();

                    if (empty($notificationsToSend)) {
                        continue;
                    }

                    // Generate group ID
                    $groupId = now()->timestamp . '_' . $user->id . '_' . $this->vesselId;

                    // Mark notifications as grouped
                    $notificationIds = collect($notificationsToSend)->pluck('id')->toArray();
                    EmailNotificationAction::markNotificationsAsGrouped($notificationIds, $groupId);

                    try {
                        // Send grouped email
                        Mail::to($user->email)->send(
                            new GroupedVesselNotificationMail(
                                $user,
                                $vessel,
                                $type,
                                $notificationsToSend,
                                $groupId
                            )
                        );

                        // Mark as sent
                        EmailNotificationAction::markNotificationsAsSent($notificationIds);

                        Log::info('Grouped email notification sent', [
                            'user_id' => $user->id,
                            'vessel_id' => $this->vesselId,
                            'type' => $type,
                            'count' => count($notificationsToSend),
                            'group_id' => $groupId,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send grouped email notification', [
                            'user_id' => $user->id,
                            'vessel_id' => $this->vesselId,
                            'type' => $type,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to process grouped email notifications', [
                'vessel_id' => $this->vesselId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

