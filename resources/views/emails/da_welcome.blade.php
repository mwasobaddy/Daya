<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Daya, {{ $user->name }} — Your Ambassador Journey Starts Now!</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            margin: 0; 
            padding: 0; 
            background-color: #f8f9fa;
        }
        .container { 
            max-width: 600px; 
            margin: 20px auto; 
            background: white; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            overflow: hidden;
        }
        .header { 
            background: linear-gradient(135deg, #4f46e5, #7c3aed); 
            color: white; 
            padding: 30px 20px; 
            text-align: center; 
        }
        .header h1 { 
            margin: 0 0 10px 0; 
            font-size: 24px; 
            font-weight: 600; 
        }
        .header p { 
            margin: 0; 
            opacity: 0.9; 
            font-size: 16px; 
        }
        .content { 
            padding: 30px 20px; 
        }
        .section { 
            margin-bottom: 30px; 
            padding-bottom: 25px; 
            border-bottom: 1px solid #e5e7eb; 
        }
        .section:last-child { 
            border-bottom: none; 
            margin-bottom: 0; 
        }
        .section-title { 
            font-size: 18px; 
            font-weight: 600; 
            margin-bottom: 15px; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
        }
        .referral-box { 
            background: #f1f5f9; 
            border: 1px solid #cbd5e1; 
            border-radius: 8px; 
            padding: 20px; 
            text-align: center; 
            margin: 15px 0; 
        }
        .referral-link { 
            font-family: 'Monaco', 'Menlo', monospace; 
            font-size: 14px; 
            word-break: break-all; 
            background: white; 
            padding: 12px; 
            border-radius: 4px; 
            border: 1px solid #d1d5db; 
            margin: 10px 0; 
            color: #4f46e5; 
        }
        .welcome-bonus { 
            background: linear-gradient(135deg, #fef3c7, #fed7aa); 
            border: 1px solid #f59e0b; 
            border-radius: 8px; 
            padding: 20px; 
            margin: 15px 0; 
        }
        .earning-method { 
            background: #f8fafc; 
            border-left: 4px solid #3b82f6; 
            padding: 20px; 
            margin: 15px 0; 
            border-radius: 0 8px 8px 0; 
        }
        .earning-method h4 { 
            margin: 0 0 10px 0; 
            color: #1e40af; 
            font-size: 16px; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
        }
        .reward-highlight { 
            background: #ecfdf5; 
            border: 1px solid #10b981; 
            padding: 10px 15px; 
            border-radius: 6px; 
            margin: 10px 0; 
            font-weight: 500; 
        }
        .note { 
            background: #fef2f2; 
            border: 1px solid #f87171; 
            padding: 10px 15px; 
            border-radius: 6px; 
            margin: 10px 0; 
            font-size: 14px; 
        }
        .progress-list { 
            list-style: none; 
            padding: 0; 
        }
        .progress-list li { 
            padding: 8px 0; 
            border-bottom: 1px solid #f3f4f6; 
        }
        .progress-list li:last-child { 
            border-bottom: none; 
        }
        .footer { 
            background: #f9fafb; 
            padding: 20px; 
            text-align: center; 
            font-size: 12px; 
            color: #6b7280; 
            border-top: 1px solid #e5e7eb; 
        }
        .emoji { 
            font-size: 18px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Daya, {{ $user->name }}!</h1>
            <p>Your journey toward ownership, rewards, and community-led impact starts today.</p>
        </div>

        <div class="content">
            <div class="section">
                <h2 class="section-title">🔗 Your Referral Link</h2>
                <p>Share your unique link to onboard new Ambassadors (DAs) and Digital Content Distributors (DCDs):</p>
                
                <div class="referral-box">
                    <strong>Referral Link:</strong>
                    <div class="referral-link">{{ url('/?ref=' . $user->referral_code) }}</div>
                </div>
            </div>

            @if($referrer)
            <div class="section">
                <h2 class="section-title">🎉 Your Welcome Bonus</h2>
                <div class="welcome-bonus">
                    <p><strong>You were referred by {{ $referrer->name }}.</strong></p>
                    <p>Welcome to the Daya family — where every member earns and owns through participation.</p>
                </div>
            </div>
            @endif

            <div class="section">
                <h2 class="section-title">🚀 How You Earn as a Digital Ambassador</h2>
                
                <div class="earning-method">
                    <h4>1️⃣ Ambassador Referrals</h4>
                    <p>Refer new Ambassadors using your link.</p>
                    <p><strong>You earn:</strong></p>
                    <div class="reward-highlight">
                        ➡️ 200 Venture Shares for every DA you refer<br>
                        <small>(awarded when the DA completes onboarding)</small>
                    </div>
                    <div class="note">
                        <strong>Note:</strong> Venture shares for DA referrals are limited to the <strong>first 3,000 Ambassadors</strong> program-wide.
                    </div>
                </div>

                <div class="earning-method">
                    <h4>2️⃣ Distributor Referrals</h4>
                    <p>Refer Digital Content Distributors — shops, agents, businesses, kiosks, salons, mobile money agents, etc.</p>
                    <p><strong>You earn:</strong></p>
                    <div class="reward-highlight">
                        ➡️ 500 Venture Shares for every DCD you refer<br>
                        <small>(awarded when the DCD completes their first 2 QR scans)</small>
                    </div>
                    <div class="note">
                        <strong>Note:</strong> Venture shares for DCD referrals apply only to the <strong>first 30,000 activated DCDs</strong> program-wide.
                    </div>
                </div>

                <div class="earning-method">
                    <h4>3️⃣ Flat DCD Activity Rewards</h4>
                    <p>Every DCD that completes their 2 scans also unlocks a <strong>1000 venture share reward</strong>.</p>
                    <div class="note">
                        (Also capped within the <strong>first 30,000 DCDs</strong>.)
                    </div>
                </div>

                <div class="earning-method">
                    <h4>4️⃣ Earn 5% Commission When You Bring Clients</h4>
                    <p>If you introduce a business, brand, or organization that runs a campaign on the Daya network:</p>
                    <div class="reward-highlight">
                        ➡️ You earn 5% of the client's total campaign budget.
                    </div>
                    <p>This commission is credited directly to your wallet once the campaign launches.</p>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">📈 Track Your Progress</h2>
                <p>You'll receive monthly reports covering:</p>
                <ul class="progress-list">
                    <li>• Total DA referrals</li>
                    <li>• Total DCD referrals</li>
                    <li>• Confirmation of onboarding/activation</li>
                    <li>• Venture shares earned from each activity</li>
                    <li>• 5% client commissions earned</li>
                    <li>• Eligibility based on the 3,000 DA & 30,000 DCD caps</li>
                </ul>
            </div>

            <div class="section">
                <h2 class="section-title">🙌 Start Building Your Network</h2>
                <p>Share your referral link, activate DCD locations, and introduce clients.</p>
                <p>Your contributions help grow the Daya ecosystem — while earning you ownership, commissions, and long-term benefits.</p>
                <p>If you need support, reach us through the Daya support portal.</p>
                
                <p style="margin-top: 30px;">
                    Best regards,<br>
                    <strong>The Daya Team</strong>
                </p>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply.</p>
            <p>© {{ date('Y') }} Daya. All rights reserved.</p>
        </div>
    </div>
</body>
</html>