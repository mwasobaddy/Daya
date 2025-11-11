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
        .qr-code { text-align: center; margin: 20px 0; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Daya, {{ $user->name }}!</h1>
            <p>You're now part of the Daya network. Start earning by scanning campaigns!</p>
        </div>

        <div class="content">
            <h2>Your QR Code</h2>
            <p>Here's your unique QR code for campaign scanning:</p>

            <div class="qr-code">
                @if($qrCodeUrl)
                    <img src="{{ $qrCodeUrl }}" alt="Your QR Code" style="max-width: 200px;" />
                @else
                    <p>QR Code will be generated shortly.</p>
                @endif
            </div>

            <h3>How to Earn</h3>
            <ul>
                <li>Share your QR code with clients who want to run campaigns</li>
                <li>When clients scan your QR code, they can submit campaigns</li>
                <li>You earn commissions on successful campaign completions</li>
                <li>Track your earnings through monthly reports sent to this email</li>
            </ul>

            @if($referrer)
                <p><strong>Referred by: {{ $referrer->name }} ({{ $referrer->email }})</strong></p>
                <p>Thank you for joining through their referral!</p>
            @endif

            <p>Start sharing your QR code and building your campaign network today!</p>

            <p>Best regards,<br>
            The Daya Team</p>
        </div>

        &lt;div class="footer"&gt;
            &lt;p&gt;This is an automated message. Please do not reply to this email.&lt;/p&gt;
            &lt;p&gt;© 2024 Daya. All rights reserved.&lt;/p&gt;
        &lt;/div&gt;
    &lt;/div&gt;
&lt;/body&gt;
&lt;/html&gt;