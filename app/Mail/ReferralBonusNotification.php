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
    public array $tokenNames;

    /**
     * Create a new message instance.
     */
    public function __construct(User $referrer, VentureShareService $ventureShareService)
    {
        $this->referrer = $referrer;
        $this->balances = $ventureShareService->getTotalShares($referrer);
        
        // Determine token names based on user's country
        $countryCode = $referrer->country ? strtoupper($referrer->country->code) : 'KE'; // Default to KE if no country set
        
        $this->tokenNames = [
            'dds' => $countryCode === 'NG' ? 'NgDDS' : 'KeDDS',
            'dws' => $countryCode === 'NG' ? 'NgDWS' : 'KeDWS',
        ];
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
                'tokenNames' => $this->tokenNames,
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