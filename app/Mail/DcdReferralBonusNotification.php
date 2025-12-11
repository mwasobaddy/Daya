<?php

namespace App\Mail;

use App\Models\User;
use App\Services\VentureShareService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DcdReferralBonusNotification extends Mailable
{
    use Queueable, SerializesModels;

    public User $dcdReferrer;
    public User $newDa;
    public array $balances;
    public array $tokenNames;

    /**
     * Create a new message instance.
     */
    public function __construct(User $dcdReferrer, User $newDa, VentureShareService $ventureShareService)
    {
        $this->dcdReferrer = $dcdReferrer;
        $this->newDa = $newDa;
        $this->balances = $ventureShareService->getTotalShares($dcdReferrer);
        
        // Determine token names based on DCD's country
        $countryCode = $dcdReferrer->country ? strtoupper($dcdReferrer->country->code) : 'KE'; // Default to KE if no country set
        
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
            subject: 'Excellent News! You Earned 2,000 Tokens from Your DA Referral',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.dcd_referral_bonus_notification',
            with: [
                'dcdReferrer' => $this->dcdReferrer,
                'newDa' => $this->newDa,
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