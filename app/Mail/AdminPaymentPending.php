<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminPaymentPending extends Mailable
{
    use Queueable, SerializesModels;

    public $earning;
    public $completeUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($earning)
    {
        $this->earning = $earning;

        $adminActionService = app(\App\Services\AdminActionService::class);
        $this->completeUrl = $adminActionService->generateActionLink('mark_payment_complete', $earning->id);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Pending - ' . $this->earning->user->name . ' - $' . number_format($this->earning->amount, 2),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin_payment_pending',
            with: [
                'earning' => $this->earning,
                'completeUrl' => $this->completeUrl,
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
