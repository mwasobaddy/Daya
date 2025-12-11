<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Daya Wallet Has Been Created</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 30px; background-color: #f8f9fa; border-radius: 0 0 10px 10px; }
        .wallet-info { background-color: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea; }
        .wallet-pin { background-color: #e9ecef; padding: 15px; border-radius: 5px; font-family: monospace; text-align: center; margin: 20px 0; font-size: 18px; font-weight: bold; color: #495057; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 10px 10px; }
        .button { display: inline-block; background-color: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Your Daya Wallet is Ready!</h1>
            <p>Welcome to the Daya earning ecosystem</p>
        </div>

        <div class="content">
            <h2>Hello {{ $user->name }}!</h2>

            <p>Congratulations! Your Daya wallet has been successfully created and activated. You can now start earning rewards through our innovative distribution network.</p>

            <div class="wallet-info">
                <h3>📱 Your Wallet Details</h3>
                <p><strong>Wallet Type:</strong> {{ ucfirst($user->wallet_type) }}</p>
                <p><strong>Wallet Status:</strong> <span style="color: #28a745; font-weight: bold;">Active</span></p>
                <p><strong>Role:</strong> {{ strtoupper($user->role) }}</p>
            </div>

            <div class="wallet-pin">
                🔐 Your Wallet PIN: {{ $user->wallet_pin ? $user->wallet_pin : 'Not set' }}
                <br><small style="font-size: 12px; color: #666;">(Keep this PIN secure and confidential)</small>
            </div>

            <div class="warning">
                <strong>⚠️ Critical Security Notice:</strong><br>
                Your wallet PIN above is your ACTUAL PIN number for accessing your earnings and managing transactions.
                <strong>Never share this PIN with anyone</strong> - store it securely and delete this email after noting down your PIN.
                This PIN cannot be recovered if lost.
            </div>

            <h3>💰 How to Start Earning</h3>
            <ul>
                @if($user->role === 'da')
                    <li>Share your referral link with potential Digital Content Distributors (DCDs)</li>
                    <li>Earn commissions when your referrals register and start campaigns</li>
                    <li>Receive bonuses for successful campaign completions</li>
                @elseif($user->role === 'dcd')
                    <li>Accept campaign assignments from clients</li>
                    <li>Display promotional content in your business location</li>
                    <li>Earn based on verified customer interactions and scans</li>
                @endif
                <li>Monitor your earnings through your dashboard</li>
                <li>Withdraw funds once minimum thresholds are met</li>
            </ul>

            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/login') }}" class="button">Access Your Dashboard</a>
            </p>

            <p>If you have any questions about your wallet or how to start earning, please don't hesitate to contact our support team.</p>

            <p>Happy earning!<br>
            <strong>The Daya Team</strong></p>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; 2025 Daya - Digital Distribution System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>