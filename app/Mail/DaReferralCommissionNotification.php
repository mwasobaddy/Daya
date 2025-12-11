<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DaReferralCommissionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public User $referrer;
    public User $newDa;

    /**
     * Create a new message instance.
     */
    public function __construct(User $referrer, User $newDa)
    {
        $this->referrer = $referrer;
        $this->newDa = $newDa;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Great News! You\'ll Earn 5% Commission from Your DA Referral',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.da_referral_commission_notification',
            with: [
                'referrer' => $this->referrer,
                'newDa' => $this->newDa,
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