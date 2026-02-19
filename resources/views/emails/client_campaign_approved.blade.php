<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Approved - Ready to Launch</title>
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
            background: linear-gradient(135deg, #059669, #047857);
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
        .campaign-details {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .detail-label {
            font-weight: 600;
            color: #374151;
        }
        .detail-value {
            color: #6b7280;
        }
        .success-box {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border: 1px solid #059669;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .success-box h4 {
            margin: 0 0 10px 0;
            color: #065f46;
            font-size: 16px;
        }
        .success-box p {
            margin: 0;
            color: #064e3b;
        }
        .next-steps {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .next-steps h4 {
            margin: 0 0 15px 0;
            color: #92400e;
            font-size: 16px;
        }
        .next-steps ul {
            margin: 0;
            padding-left: 20px;
            color: #78350f;
        }
        .next-steps li {
            margin-bottom: 8px;
        }
        .footer {
            background: #f8fafc;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 0 0 10px 0;
            color: #6b7280;
            font-size: 14px;
        }
        .footer strong {
            color: #374151;
        }
        .emoji {
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Campaign Approved!</h1>
            <p>Your campaign is now live and ready to reach your target audience.</p>
        </div>

        <div class="content">
            <div class="section">
                <h2 class="section-title">📋 CAMPAIGN DETAILS</h2>
                <div class="campaign-details">
                    <div class="detail-row">
                        <span class="detail-label">Campaign Name:</span>
                        <span class="detail-value">{{ $campaign->title }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Budget:</span>
                        <span class="detail-value">
                            @php
                                $currency = '₦';
                                if (!empty($campaign->metadata['currency']) && strtoupper($campaign->metadata['currency']) === 'KSH') {
                                    $currency = 'KSh';
                                } else {
                                    $clientCountry = $campaign->client->country ?? null;
                                    if ($clientCountry && strtoupper($clientCountry->code) === 'KEN') {
                                        $currency = 'KSh';
                                    } else {
                                        $currency = '₦';
                                    }
                                }
                            @endphp
                            {{ $currency }}{{ number_format($campaign->budget, 2) }}
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-row">
                        <span class="detail-label">Digital Product:</span>
                        <span class="detail-value">
                            @if($campaign->digital_product_link)
                                <a href="{{ $campaign->digital_product_link }}" style="color: #4f46e5; text-decoration: none;">{{ $campaign->digital_product_link }}</a>
                            @else
                                Not available
                            @endif
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Campaign Goal:</span>
                        <span class="detail-value">{{ $campaign->campaign_objective ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value" style="color: #059669; font-weight: 600;">Approved & Live</span>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="success-box">
                    <h4>✅ What Happens Next</h4>
                    <p>Your campaign has been approved and is now live on the Daya platform. Our network of Digital Content Distributors (DCDs) will start promoting your campaign to their communities.</p>
                </div>
            </div>

            <div class="section">
                <p>If you have any questions about your campaign or need assistance, please don't hesitate to contact our support team.</p>
                <p>Thank you for choosing Daya for your campaign distribution needs.</p>
                <p>Best regards,<br>
                The Daya Team</p>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p><strong>© 2025 Daya. All rights reserved.</strong></p>
        </div>
    </div>
</body>
</html>