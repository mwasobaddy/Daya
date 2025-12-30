<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Approved - New Campaign Available</title>
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
        .highlight-box {
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .highlight-box h4 {
            margin: 0 0 10px 0;
            color: #92400e;
            font-size: 16px;
        }
        .highlight-box p {
            margin: 0;
            color: #78350f;
        }
        .earnings-breakdown {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .earnings-breakdown h4 {
            margin: 0 0 15px 0;
            color: #0c4a6e;
            font-size: 16px;
        }
        .earnings-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .earnings-item:last-child {
            margin-bottom: 0;
        }
        .earnings-percentage {
            font-weight: 600;
            color: #0369a1;
        }
        .important-box {
            background: #fef2f2;
            border: 1px solid #dc2626;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .important-box h4 {
            margin: 0 0 10px 0;
            color: #991b1b;
            font-size: 16px;
        }
        .important-box ul {
            margin: 0;
            padding-left: 20px;
            color: #7f1d1d;
        }
        .important-box li {
            margin-bottom: 5px;
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
            <h1>🎉 Good news!</h1>
            <p>A new campaign you are eligible to distribute has been approved and is now live.</p>
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
                        <span class="detail-label">Digital content:</span>
                        <span class="detail-value">
                            @if($campaign->digital_product_link)
                                <a href="{{ $campaign->digital_product_link }}" style="color: #4f46e5; text-decoration: none;">{{ $campaign->digital_product_link }}</a>
                            @else
                                Not available
                            @endif
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Explainer video:</span>
                        <span class="detail-value">
                            @if($campaign->explainer_video_url)
                                <a href="{{ $campaign->explainer_video_url }}" style="color: #4f46e5; text-decoration: none;">{{ $campaign->explainer_video_url }}</a>
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
                        <span class="detail-label">Campaign Type:</span>
                        <span class="detail-value">{{ $campaign->metadata['campaign_type'] ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value" style="color: #059669; font-weight: 600;">Live and Active</span>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">🎯 YOUR ROLE AS A DCD</h2>
                <p>As a Digital Content Distributor (DCD), you help creators grow by sharing campaigns within your community using your existing Daya QR code (issued at signup).</p>
                <p>Each time someone scans your QR code and engages with the campaign, that activity is verified and counted toward your earnings.</p>
            </div>

            <div class="section">
                <h2 class="section-title">💰 HOW YOU EARN</h2>
                <p>For every verified scan or click on this campaign:</p>
                <div class="earnings-breakdown">
                    <h4>Earnings Breakdown</h4>
                    <div class="earnings-item">
                        <span>You (DCD):</span>
                        <span class="earnings-percentage">60%</span>
                    </div>
                    <div class="earnings-item">
                        <span>Your Digital Ambassador (DA):</span>
                        <span class="earnings-percentage">10%</span>
                    </div>
                    <div class="earnings-item">
                        <span>Daya Platform:</span>
                        <span class="earnings-percentage">30%</span>
                    </div>
                </div>
                <p>This means you earn the largest share for the work you do on the ground.</p>
            </div>

            <div class="section">
                <div class="important-box">
                    <h4>⚠️ IMPORTANT</h4>
                    <ul>
                        <li>Only scans made using your registered Daya QR code are counted</li>
                        <li>Earnings are based on verified engagement only</li>
                        <li>You can increase your earnings by consistently sharing the campaign with real people in your community</li>
                    </ul>
                </div>
            </div>

            <div class="section">
                <p>Thank you for being part of Daya's community-led distribution network, where everyday interactions create real value for creators and communities.</p>
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