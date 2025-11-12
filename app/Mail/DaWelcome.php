<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DaWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $referrer;
    public $qrCodeUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $referrer = null)
    {
        $this->user = $user;
        $this->referrer = $referrer;
        $this->qrCodeUrl = $user->qr_code ? \Storage::disk('public')->url($user->qr_code) : null;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Daya - Your Referral Code',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.da_welcome',
            with: ['user' => $this->user, 'referrer' => $this->referrer, 'qrCodeUrl' => $this->qrCodeUrl],
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
