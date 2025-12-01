<?php

namespace App\Mail;

use App\Models\User;
use App\Services\VentureShareService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReferralBonusNotification extends Mailable
{
    use Queueable, SerializesModels;

    public User $referrer;
    public array $balances;

    /**
     * Create a new message instance.
     */
    public function __construct(User $referrer, VentureShareService $ventureShareService)
    {
        $this->referrer = $referrer;
        $this->balances = $ventureShareService->getTotalShares($referrer);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Referral Bonus Update - Your Venture Share Balances',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.referral_bonus_notification',
            with: [
                'referrer' => $this->referrer,
                'balances' => $this->balances,
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