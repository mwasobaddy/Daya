<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class DaWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $referrer;
    public $qrCodeBase64;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $referrer = null)
    {
        $this->user = $user;
        $this->referrer = $referrer;
        $this->qrCodeBase64 = $user->qr_code;
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
            with: ['user' => $this->user, 'referrer' => $this->referrer],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if ($this->qrCodeBase64) {
            return [
                Attachment::fromData(fn () => base64_decode($this->qrCodeBase64), 'referral-qr-code.pdf')
                    ->withMime('application/pdf'),
            ];
        }
        return [];
    }
}
