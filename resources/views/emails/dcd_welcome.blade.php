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
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Daya, {{ $user->name }}!</h1>
            <p>You're now part of the Daya network. Start earning by scanning campaigns!</p>
        </div>

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

            <h3>How to Earn</h3>
            <ul>
                <li>Wait for campaign assignments to be sent to your email</li>
                <li>When assigned a campaign, you'll receive detailed instructions and QR codes</li>
                <li>Execute the campaign by displaying QR codes and directing customers to the client's products</li>
                <li>You earn commissions on successful campaign completions (up to 20% of campaign budget)</li>
                <li>Track your earnings through monthly reports sent to this email</li>
            </ul>

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