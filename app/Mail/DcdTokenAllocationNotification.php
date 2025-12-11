<?php

namespace App\Mail;

use App\Models\User;
use App\Services\VentureShareService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DcdTokenAllocationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public User $dcd;
    public array $balances;
    public array $tokenNames;

    /**
     * Create a new message instance.
     */
    public function __construct(User $dcd, VentureShareService $ventureShareService)
    {
        $this->dcd = $dcd;
        $this->balances = $ventureShareService->getTotalShares($dcd);
        
        // Determine token names based on user's country
        $countryCode = $dcd->country ? strtoupper($dcd->country->code) : 'KE'; // Default to KE if no country set
        
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
            subject: 'Welcome Bonus: Your Initial Daya Tokens Have Been Allocated!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.dcd_token_allocation_notification',
            with: [
                'dcd' => $this->dcd,
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