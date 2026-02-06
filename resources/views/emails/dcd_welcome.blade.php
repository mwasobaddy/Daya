<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Daya</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .welcome-info { background-color: #e9ecef; padding: 15px; border-radius: 5px; text-align: center; margin: 20px 0; }
        .qr-info { background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Daya, {{ $user->name }}!</h1>
            <p>Your journey to earning through campaign distributions starts now.</p>
        </div>

        <div class="content">
            <h2>Your Role as a Digital Content Distributor</h2>
            <p>You're now part of the Daya network as a Digital Content Distributor (DCD). Here's what you can expect:</p>

            <div class="welcome-info">
                <strong>Welcome to the Daya Family!</strong><br>
                <p>You'll receive campaign assignments via email and earn commissions by successfully executing them.</p>
            </div>

            @if($referrer)
            <div style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h3 style="color: #155724; margin-top: 0;">🎉 Referral Bonus!</h3>
                <p style="margin-bottom: 0;">You were referred by <strong>{{ $referrer->name }}</strong> ({{ ucfirst($referrer->role) }}). Welcome to the Daya family!</p>
            </div>
            @endif

            <div class="qr-info">
                <h3 style="color: #0c5460; margin-top: 0;">📱 Your Personal QR Code</h3>
                <p><strong>Attached to this email is your personal DCD QR code PDF.</strong></p>
                <p>This QR code is unique to you and will be used for all your campaign activities. Keep it safe and accessible!</p>
            </div>

            <h3>How to Earn</h3>
            <ul>
                <li>Wait for campaign assignments to be sent to your email</li>
                <li>When assigned a campaign, you'll use your personal QR code (attached) for customer interactions</li>
                <li>Display your QR code prominently and direct customers to scan it</li>
                <li>You earn commissions on successful campaign completions (up to 60% of campaign budget)</li>
                <li>Track your earnings through monthly reports sent to this email</li>
            </ul>

            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h3 style="color: #856404; margin-top: 0;">🎯 Your Referral Program</h3>
                <p><strong>Earn additional income by referring new DCDs to the Daya network!</strong></p>
                <p>Your unique referral code: <strong style="font-family: monospace; background-color: #f8f9fa; padding: 2px 4px; border-radius: 3px;">{{ $user->referral_code ?? 'DCD-' . $user->id }}</strong></p>
                <p>Share this referral link with potential DCDs:</p>
                <p style="word-break: break-all; font-family: monospace; background-color: #f8f9fa; padding: 10px; border-radius: 3px; border: 1px solid #dee2e6;">
                    <div class="referral-link">{{ url('/?ref=' . $user->referral_code) }}</div>
                </p>
            </div>

            <p>Stay tuned for your first campaign assignment and start earning!</p>

            <p>Best regards,<br>
            The Daya Team</p>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>© 2024 Daya. All rights reserved.</p>
        </div>
    </div>
</body>
</html>