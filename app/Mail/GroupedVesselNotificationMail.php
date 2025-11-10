<?php

namespace App\Mail;

use App\Models\EmailNotification;
use App\Models\User;
use App\Models\Vessel;
use App\Traits\HasTranslations;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class GroupedVesselNotificationMail extends Mailable
{
    use Queueable, SerializesModels, HasTranslations;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public Vessel $vessel,
        public string $type,
        public array $notifications,
        public string $groupId
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Set locale based on user's preference
        $originalLocale = App::getLocale();
        if ($this->user->language) {
            App::setLocale($this->user->language);
        }

        $typeLabels = [
            'transaction_created' => $this->transFrom('emails', 'Transactions Created'),
            'transaction_deleted' => $this->transFrom('emails', 'Transactions Deleted'),
            'marea_started' => $this->transFrom('emails', 'Mareas Started'),
            'marea_completed' => $this->transFrom('emails', 'Mareas Completed'),
        ];

        $subject = $typeLabels[$this->type] ?? $this->transFrom('emails', 'System Notifications');
        $count = count($this->notifications);

        if ($count > 1) {
            $itemLabel = $this->transFrom('emails', 'items');
            $subject .= " ({$count} {$itemLabel})";
        }

        // Restore original locale
        App::setLocale($originalLocale);

        return new Envelope(
            subject: $subject . ' - ' . config('app.name', 'Bindamy Mareas'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $viewMap = [
            'transaction_created' => 'emails.notifications.grouped-transactions-created',
            'transaction_deleted' => 'emails.notifications.grouped-transactions-deleted',
            'marea_started' => 'emails.notifications.grouped-mareas-started',
            'marea_completed' => 'emails.notifications.grouped-mareas-completed',
        ];

        $view = $viewMap[$this->type] ?? 'emails.notifications.grouped-default';

        // Set locale for email content
        $originalLocale = App::getLocale();
        if ($this->user->language) {
            App::setLocale($this->user->language);
        }

        $content = new Content(
            view: $view,
            with: [
                'user' => $this->user,
                'vessel' => $this->vessel,
                'notifications' => $this->notifications,
                'count' => count($this->notifications),
                'type' => $this->type,
                'locale' => $this->user->language ?? 'en',
            ],
        );

        // Restore original locale
        App::setLocale($originalLocale);

        return $content;
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

