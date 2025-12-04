<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Bonus Update</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .balances { background-color: #e9ecef; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Referral Bonus Update</h1>
        </div>

        <div class="content">
            <p>Dear {{ $referrer->name }},</p>

            <p>Congratulations! You have successfully referred a new member to the Daya platform. As a token of appreciation, your venture share balances have been updated.</p>

            <div class="balances">
                <h3>Your Current Venture Share Balances:</h3>
                <p><strong>{{ $tokenNames['dds'] }} Tokens:</strong> {{ number_format($balances['kedds'], 2) }}</p>
                <p><strong>{{ $tokenNames['dws'] }} Tokens:</strong> {{ number_format($balances['kedws'], 2) }}</p>
            </div>

            <p>These tokens represent your earnings and can be redeemed for various benefits on the platform. Keep referring more members to increase your balances!</p>

            <p>If you have any questions about your venture shares, please contact our support team.</p>

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