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

        // Set locale for the entire email
        // Use user's language if available (they're receiving the email), otherwise inviter's language
        $localeToUse = $this->user->language
            ?? $this->inviter?->language
            ?? App::getLocale()
            ?? 'en';
        $this->locale($localeToUse);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Locale is already set in constructor via $this->locale()
        // Set locale temporarily for subject translation
        $originalLocale = App::getLocale();
        $localeToUse = $this->user->language
            ?? $this->inviter?->language
            ?? $originalLocale
            ?? 'en';
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
        // Locale is already set in constructor via $this->locale()
        // Get locale for passing to view (for view composer if needed)
        $localeToUse = $this->user->language
            ?? $this->inviter?->language
            ?? App::getLocale()
            ?? 'en';

        return new Content(
            view: 'emails.notifications.crew-member-invitation-cancelled',
            with: [
                'user' => $this->user,
                'vessel' => $this->vessel,
                'locale' => $localeToUse,
            ],
        );
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
