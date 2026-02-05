<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A DCD in Your Network Has a New Campaign 🎉</title>
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
            <p>Dear {{ $da->name }},</p>
        </div>

        <div class="content">
            <div class="section">
                <p>One of the Digital Content Distributors (DCDs) in your network has been approved to participate in a new campaign on the Daya platform.</p>
            </div>

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
                <h2 class="section-title">💰 What This Means for You</h2>
                <div class="highlight-box">
                    <h4>You earn 10% of every verified advert completed by this DCD</h4>
                    <p>No selling or customer acquisition required</p>
                    <p>Your role is to support, coordinate, and help DCDs stay active and consistent</p>
                    <p>The more active your DCD network is, the more your earnings grow.</p>
                </div>
            </div>

            <div class="section">
                <p>If you have any questions or need support, contact us at <a href="mailto:ambassadorsupport@dayadistribution.com" style="color: #4f46e5; text-decoration: none;">ambassadorsupport@dayadistribution.com</a>.</p>
                <p><strong>Keep building 💪🏾</strong></p>
                <p>The Daya Team</p>
            </div>
        </div>

        <div class="footer">
            <p><strong>Daya</strong> - Empowering Digital Content Distribution</p>
        </div>
    </div>
</body>
</html>