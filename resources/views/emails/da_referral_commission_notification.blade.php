<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DA Referral Commission Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa; }
        .header { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0; }
        .content { background: white; padding: 30px; border-radius: 0 0 12px 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .commission-highlight { background: linear-gradient(135deg, #fef3c7, #fed7aa); padding: 25px; margin: 25px 0; border-radius: 10px; border-left: 5px solid #f59e0b; }
        .da-details { background: #f1f5f9; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #3b82f6; }
        .earnings-info { background: #ecfdf5; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #10b981; }
        .footer { text-align: center; color: #6b7280; font-size: 14px; margin-top: 30px; padding: 20px; }
        .detail-row { margin: 10px 0; }
        .label { font-weight: bold; color: #374151; }
        .value { color: #6b7280; }
        .commission-rate { font-size: 2.5em; font-weight: bold; color: #059669; text-align: center; margin: 15px 0; }
        .cta-button { display: inline-block; background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 15px 0; }
        .icon { font-size: 1.2em; margin-right: 8px; }
        ul { padding-left: 20px; }
        li { margin: 8px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Congratulations!</h1>
            <p style="margin: 10px 0; font-size: 18px;">Your referral just became a Digital Ambassador</p>
        </div>

        <div class="content">
            <p>Dear {{ $referrer->name }},</p>

            <p>Excellent news! Someone you referred has successfully signed up as a <strong>Digital Ambassador (DA)</strong> on the Daya platform, and this means more earnings for you!</p>

            <div class="commission-highlight">
                <div style="text-align: center;">
                    <h2 style="margin: 0 0 10px 0; color: #d97706;">💰 Commission Earnings</h2>
                    <div class="commission-rate">5%</div>
                    <p style="margin: 0; font-size: 16px; color: #92400e;"><strong>of every campaign budget</strong> from this DA's campaigns</p>
                </div>
            </div>

            <div class="da-details">
                <h3 style="margin-top: 0;"><span class="icon">👤</span>New Digital Ambassador Details</h3>
                <div class="detail-row">
                    <span class="label">Name:</span>
                    <span class="value">{{ $newDa->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Email:</span>
                    <span class="value">{{ $newDa->email }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Registration Date:</span>
                    <span class="value">{{ $newDa->created_at->format('M j, Y g:i A') }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Referral Code Used:</span>
                    <span class="value">{{ $referrer->referral_code }}</span>
                </div>
            </div>

            <div class="earnings-info">
                <h3 style="margin-top: 0; color: #065f46;"><span class="icon">💚</span>How Your Earnings Work</h3>
                <ul style="color: #047857;">
                    <li><strong>Automatic Commission:</strong> You'll earn 5% of every campaign budget that {{ $newDa->name }} manages</li>
                    <li><strong>Passive Income:</strong> No additional work required - earnings are calculated automatically</li>
                    <li><strong>Long-term Benefits:</strong> As long as {{ $newDa->name }} is active, you keep earning</li>
                    <li><strong>Track Earnings:</strong> Monitor your commissions in your dashboard</li>
                </ul>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <h3 style="color: #374151;">💡 Maximize Your Earnings</h3>
                <p>The more active your referred DA becomes with campaigns, the more you earn! Encourage {{ $newDa->name }} to:</p>
                <ul style="text-align: left; max-width: 400px; margin: 20px auto;">
                    <li>Connect with high-budget clients</li>
                    <li>Manage multiple campaigns</li>
                    <li>Build long-term client relationships</li>
                </ul>
            </div>

            <p>Keep up the great work with referrals! The more Digital Ambassadors you bring to the platform, the more commission opportunities you create.</p>

            <p style="margin-top: 30px;">
                Best regards,<br>
                <strong>The Daya Team</strong>
            </p>
        </div>

        <div class="footer">
            <p>This is an automated notification. You'll receive updates when your referred DA starts managing campaigns.</p>
            <p>© {{ date('Y') }} Daya. All rights reserved.</p>
        </div>
    </div>
</body>
</html>