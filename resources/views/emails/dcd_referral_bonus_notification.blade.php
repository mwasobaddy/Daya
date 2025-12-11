<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DCD Referral Bonus Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa; }
        .header { background: linear-gradient(135deg, #8e44ad, #3498db); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0; }
        .content { background: white; padding: 30px; border-radius: 0 0 12px 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .bonus-highlight { background: linear-gradient(135deg, #e8f8f5, #d5f4e6); padding: 25px; margin: 25px 0; border-radius: 10px; border-left: 5px solid #27ae60; text-align: center; }
        .da-details { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #3498db; }
        .token-balance { background: #fef5e7; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #f39c12; }
        .footer { text-align: center; color: #6b7280; font-size: 14px; margin-top: 30px; padding: 20px; }
        .token-amount { font-size: 2.8em; font-weight: bold; color: #27ae60; margin: 15px 0; }
        .token-breakdown { font-size: 1.1em; color: #8e44ad; font-weight: 600; }
        .cta-button { display: inline-block; background: #8e44ad; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 15px 0; }
        .icon { font-size: 1.2em; margin-right: 8px; }
        ul { padding-left: 20px; }
        li { margin: 8px 0; }
        .highlight-text { color: #27ae60; font-weight: bold; }
        .balance-grid { display: flex; justify-content: space-between; margin: 15px 0; }
        .balance-item { text-align: center; flex: 1; }
        .balance-number { font-size: 1.8em; font-weight: bold; color: #f39c12; }
        .balance-label { font-size: 14px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Fantastic News!</h1>
            <p style="margin: 10px 0; font-size: 18px;">Your referral just became a Digital Ambassador</p>
        </div>

        <div class="content">
            <p>Dear {{ $dcdReferrer->name }},</p>

            <p>Outstanding news! Someone you referred has successfully registered as a <strong>Digital Ambassador (DA)</strong> on the Daya platform, and you've earned a substantial token bonus!</p>

            <div class="bonus-highlight">
                <h2 style="margin: 0 0 15px 0; color: #27ae60;">🏆 Your Referral Bonus</h2>
                <div class="token-amount">2,000</div>
                <div class="token-breakdown">1,000 {{ $tokenNames['dds'] }} + 1,000 {{ $tokenNames['dws'] }} Tokens</div>
                <p style="margin: 15px 0 0 0; color: #155724; font-size: 16px;"><strong>Reward for referring a Digital Ambassador!</strong></p>
            </div>

            <div class="da-details">
                <h3 style="margin-top: 0;"><span class="icon">🌟</span>New Digital Ambassador You Referred</h3>
                <div style="margin: 10px 0;">
                    <strong>Name:</strong> {{ $newDa->name }}
                </div>
                <div style="margin: 10px 0;">
                    <strong>Email:</strong> {{ $newDa->email }}
                </div>
                <div style="margin: 10px 0;">
                    <strong>Registration Date:</strong> {{ $newDa->created_at->format('M j, Y g:i A') }}
                </div>
                <div style="margin: 10px 0;">
                    <strong>Your Referral Code:</strong> <span class="highlight-text">{{ $dcdReferrer->referral_code }}</span>
                </div>
            </div>

            <div class="token-balance">
                <h3 style="margin-top: 0; color: #d68910;"><span class="icon">💰</span>Your Updated Token Balance</h3>
                <div class="balance-grid">
                    <div class="balance-item">
                        <div class="balance-number">{{ number_format($balances['kedds'], 2) }}</div>
                        <div class="balance-label">{{ $tokenNames['dds'] }} Tokens</div>
                    </div>
                    <div class="balance-item">
                        <div class="balance-number">{{ number_format($balances['kedws'], 2) }}</div>
                        <div class="balance-label">{{ $tokenNames['dws'] }} Tokens</div>
                    </div>
                </div>
                <p style="margin: 15px 0 0 0; color: #b7950b; font-size: 14px; text-align: center;">
                    <strong>Your tokens have been automatically credited!</strong>
                </p>
            </div>

            <div style="background: #eaf2f8; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #3498db;">
                <h3 style="margin-top: 0; color: #2874a6;"><span class="icon">🚀</span>What This Means for You</h3>
                <ul style="color: #2874a6;">
                    <li><strong>Increased Earnings Potential:</strong> More tokens mean greater rewards in monthly payouts</li>
                    <li><strong>Platform Growth:</strong> You're helping expand the Daya network with quality Digital Ambassadors</li>
                    <li><strong>Future Opportunities:</strong> {{ $newDa->name }} will now connect businesses to DCD networks</li>
                    <li><strong>Passive Income:</strong> Tokens continue to accumulate value as the platform grows</li>
                </ul>
            </div>

            <div style="background: #fdf2e9; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #e67e22;">
                <h3 style="margin-top: 0; color: #d35400;"><span class="icon">💡</span>Keep Growing Your Network</h3>
                <p style="color: #d35400; margin-bottom: 15px;">Your referral success shows the value of the Daya platform! Consider:</p>
                <ul style="color: #d35400;">
                    <li>Sharing your referral code with other potential Digital Ambassadors</li>
                    <li>Explaining the benefits of joining the Daya ecosystem</li>
                    <li>Encouraging business owners to become DCDs</li>
                    <li>Building your local network for maximum earning potential</li>
                </ul>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/dashboard') }}" class="cta-button">View Your Dashboard</a>
            </div>

            <p>Thank you for being an active member of the Daya community and helping us grow our network of Digital Ambassadors!</p>

            <p style="margin-top: 30px;">
                Best regards,<br>
                <strong>The Daya Team</strong>
            </p>
        </div>

        <div class="footer">
            <p>These tokens represent real value in the Daya ecosystem. Keep referring quality members to maximize your earnings!</p>
            <p>© {{ date('Y') }} Daya. All rights reserved.</p>
        </div>
    </div>
</body>
</html>