<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Vessel;
use App\Traits\HasTranslations;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class CrewMemberInvitationCancelledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, HasTranslations;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public Vessel $vessel,
        public ?User $inviter = null
    ) {
        $this->onQueue('emails');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Set locale based on inviter's preference (the logged-in user who cancelled the invitation)
        // Use inviter's language if available, otherwise fallback to default
        $originalLocale = App::getLocale();
        $localeToUse = $this->inviter?->language ?? $originalLocale ?? 'en';
        App::setLocale($localeToUse);

        $subject = $this->transFrom('emails', 'Crew Member Invitation Cancelled') . ' - ' . config('app.name', 'Bindamy Mareas');

        // Restore original locale
        App::setLocale($originalLocale);

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Set locale for email content based on inviter's preference (the logged-in user who cancelled the invitation)
        // Use inviter's language if available, otherwise fallback to default
        $originalLocale = App::getLocale();
        $localeToUse = $this->inviter?->language ?? $originalLocale ?? 'en';
        App::setLocale($localeToUse);

        $content = new Content(
            view: 'emails.notifications.crew-member-invitation-cancelled',
            with: [
                'user' => $this->user,
                'vessel' => $this->vessel,
                'locale' => $localeToUse,
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
