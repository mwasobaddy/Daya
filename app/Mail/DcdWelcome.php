<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\SerializesModels;

class DcdWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $referrer;
    public $qrCodeBase64;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $referrer, $qrCodeBase64 = null)
    {
        $this->user = $user;
        $this->referrer = $referrer;
        $this->qrCodeBase64 = $qrCodeBase64 ?? $user->qr_code;
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
            with: ['user' => $this->user, 'referrer' => $this->referrer],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    // public function attachments(): array
    // {
    //     if ($this->qrCodeBase64) {
    //         // If it's a file path in storage, attach from storage
    //         if (Storage::disk('public')->exists($this->qrCodeBase64)) {
    //             return [
    //                 Attachment::fromStorageDisk('public', $this->qrCodeBase64)
    //                     ->as('qr-code.pdf')
    //                     ->withMime('application/pdf'),
    //             ];
    //         }

    //         // If it's a URL, attach from URL
    //         if (filter_var($this->qrCodeBase64, FILTER_VALIDATE_URL)) {
    //             return [
    //                 Attachment::fromUrl($this->qrCodeBase64)
    //                     ->as('qr-code.pdf')
    //                     ->withMime('application/pdf'),
    //             ];
    //         }

    //         // Otherwise, assume it's base64 PDF data
    //         return [
    //             Attachment::fromData(fn () => base64_decode($this->qrCodeBase64), 'qr-code.pdf')
    //                 ->withMime('application/pdf'),
    //         ];
    //     }
    //     return [];
    // }
}
