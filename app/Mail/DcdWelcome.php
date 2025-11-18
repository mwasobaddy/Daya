<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class DcdWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $referrer;
    public $qrCodeUrl;
    public $qrCodeFilename;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $referrer, $qrCodeFilename = null)
    {
        $this->user = $user;
        $this->referrer = $referrer;
        // Accept a provided filename or fallback to what's stored on the user
        $this->qrCodeFilename = $qrCodeFilename ?? $user->qr_code;
        $this->qrCodeUrl = $this->qrCodeFilename ? \Storage::disk('public')->url($this->qrCodeFilename) : null;

        // Attach the QR code file from the public disk so recipients can download it
        if ($this->qrCodeFilename && \Storage::disk('public')->exists($this->qrCodeFilename)) {
            try {
                $this->attachFromStorageDisk('public', $this->qrCodeFilename, basename($this->qrCodeFilename), ['mime' => 'image/svg+xml']);
            } catch (\Exception $e) {
                // No-op: don't block sending email if attaching fails
                \Log::warning('Failed to attach QR code to welcome email: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Daya - Start Scanning Campaigns',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.dcd_welcome',
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
        if (! $this->qrCodeFilename) {
            return [];
        }

        // Ensure the file exists in storage before attaching
        if (! \Storage::disk('public')->exists($this->qrCodeFilename)) {
            return [];
        }

        // Attach the QR code from the public disk (storage/app/public)
        return [
            Attachment::fromStorageDisk('public', $this->qrCodeFilename)
                ->as(basename($this->qrCodeFilename))
                ->withMime('image/svg+xml'),
        ];
    }
}
