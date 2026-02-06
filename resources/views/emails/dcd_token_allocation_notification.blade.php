<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DCD Token Allocation Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa; }
        .header { background: linear-gradient(135deg, #16a085, #27ae60); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0; }
        .content { background: white; padding: 30px; border-radius: 0 0 12px 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .token-highlight { background: linear-gradient(135deg, #e8f5e8, #d4edda); padding: 25px; margin: 25px 0; border-radius: 10px; border-left: 5px solid #27ae60; text-align: center; }
        .balance-card { background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #16a085; }
        .welcome-info { background: #e3f2fd; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #2196f3; }
        .footer { text-align: center; color: #6b7280; font-size: 14px; margin-top: 30px; padding: 20px; }
        .token-amount { font-size: 2.2em; font-weight: bold; color: #27ae60; margin: 10px 0; }
        .token-type { font-size: 1.1em; color: #16a085; font-weight: 600; }
        .cta-button { display: inline-block; background: #27ae60; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 15px 0; }
        .icon { font-size: 1.2em; margin-right: 8px; }
        ul { padding-left: 20px; }
        li { margin: 8px 0; }
        .highlight-box { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 6px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Welcome Tokens Allocated!</h1>
            <p style="margin: 10px 0; font-size: 18px;">Your Daya journey starts with exclusive tokens</p>
        </div>

        <div class="content">
            <p>Dear {{ $dcd->name }},</p>

            <p>Congratulations on joining the Daya platform as a <strong>Digital Content Distributor (DCD)</strong>! As a warm welcome, we've allocated your initial token bonus to get you started on your earning journey.</p>

            <div class="token-highlight">
                <h2 style="margin: 0 0 15px 0; color: #27ae60;">💰 Your Welcome Bonus</h2>
                <div class="token-amount">1000 {{ $tokenNames['dds'] }} Tokens</div>
                <div class="token-amount">1000 {{ $tokenNames['dws'] }} Tokens</div>
                <p style="margin: 15px 0 0 0; color: #155724; font-size: 14px;">Ready to start earning from day one!</p>
            </div>

            <div class="balance-card">
                <h3 style="margin-top: 0;"><span class="icon">💎</span>Your Current Token Balance</h3>
                <div style="display: flex; justify-content: space-between; margin: 15px 0; flex-direction: column; gap: 20px;">
                    <div style="text-align: center; flex: 1;">
                        <div style="font-size: 1.5em; font-weight: bold; color: #16a085;">{{ number_format($balances['kedds'], 2) }}</div>
                        <div style="font-size: 14px; color: #666;">{{ $tokenNames['dds'] }} Tokens</div>
                    </div>
                    <div style="text-align: center; flex: 1;">
                        <div style="font-size: 1.5em; font-weight: bold; color: #16a085;">{{ number_format($balances['kedws'], 2) }}</div>
                        <div style="font-size: 14px; color: #666;">{{ $tokenNames['dws'] }} Tokens</div>
                    </div>
                </div>
            </div>

            <div class="welcome-info">
                <h3 style="margin-top: 0; color: #1565c0;"><span class="icon">🚀</span>What These Tokens Mean</h3>
                <ul style="color: #1976d2;">
                    <li><strong>{{ $tokenNames['dds'] }} Tokens:</strong> Digital Distribution Share - represents your earnings from successful campaign completions</li>
                    <li><strong>{{ $tokenNames['dws'] }} Tokens:</strong> Digital Wallet Share - accumulates value and can be redeemed for various platform benefits</li>
                    <li><strong>Automatic Earning:</strong> Tokens increase as you participate in campaigns and customer interactions</li>
                </ul>
            </div>

            <p>Your tokens are now active and ready to grow! Start accepting campaign assignments and watch your balance increase with every successful customer interaction.</p>

            <p>If you have any questions about your tokens or how to maximize your earnings, our support team is here to help.</p>

            <p style="margin-top: 30px;">
                Welcome to the Daya family!<br>
                <strong>The Daya Team</strong>
            </p>
        </div>

        <div class="footer">
            <p>These tokens represent your earning potential on the Daya platform. Keep engaging with campaigns to grow your balance!</p>
            <p>© {{ date('Y') }} Daya. All rights reserved.</p>
        </div>
    </div>
</body>
</html>