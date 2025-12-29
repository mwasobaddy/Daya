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
    public User $referredUser;
    public string $referralType;

    /**
     * Create a new message instance.
     */
    public function __construct(User $referrer, User $referredUser)
    {
        $this->referrer = $referrer;
        $this->referredUser = $referredUser;
        $this->referralType = $referredUser->role; // 'da' or 'client'
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->referralType === 'da' 
            ? 'Great News! You\'ll Earn 5% Commission from Your DA Referral'
            : 'Great News! You\'ll Earn 5% Commission from Your Client Referral';
            
        return new Envelope(
            subject: $subject,
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
                'referredUser' => $this->referredUser,
                'referralType' => $this->referralType,
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