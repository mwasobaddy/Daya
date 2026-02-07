<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Recap - {{ $campaign->title }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 600;
        }
        .header .status {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .content {
            padding: 30px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f0f0f0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .highlight-box {
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .highlight-box .amount {
            font-size: 36px;
            font-weight: 700;
            color: #2d3436;
            margin-bottom: 5px;
        }
        .highlight-box .label {
            font-size: 14px;
            color: #636e72;
            font-weight: 500;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #666;
            font-weight: 500;
        }
        .info-value {
            color: #333;
            font-weight: 600;
        }
        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            margin: 10px 5px;
        }
        @media only screen and (max-width: 600px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Campaign Completed!</h1>
            <div class="status">{{ $campaign->title }}</div>
        </div>

        <div class="content">
            @php
                $currency = '₦';
                $clientCountry = $campaign->client->country ?? null;
                if ($clientCountry && strtoupper($clientCountry->code) === 'KEN') {
                    $currency = 'KSh';
                } else {
                    $currency = '₦';
                }
            @endphp
            <div class="section">
                <p>Dear {{ $recipient->name }},</p>
                
                @if($recipientType === 'client')
                    <p>Your campaign "<strong>{{ $campaign->title }}</strong>" has successfully completed! Here's a comprehensive summary of your campaign performance.</p>
                @elseif($recipientType === 'dcd')
                    <p>Congratulations! The campaign "<strong>{{ $campaign->title }}</strong>" that you distributed has been completed. Here's a summary of the results and your earnings.</p>
                @elseif($recipientType === 'da' || $recipientType === 'admin')
                    <p>The campaign "<strong>{{ $campaign->title }}</strong>" from your network has been completed. Here's the performance summary.</p>
                @endif
            </div>

            @if($stats['recipient_earnings'] > 0)
            <div class="highlight-box">
                <div class="amount">{{ $currency }} {{ number_format($stats['recipient_earnings'], 2) }}</div>
                <div class="label">Your Earnings from This Campaign</div>
            </div>
            @endif

            <div class="section">
                <h2 class="section-title">📊 Campaign Performance</h2>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value">{{ number_format($stats['total_scans']) }}</div>
                        <div class="stat-label">Total Scans</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $stats['budget_utilization'] }}%</div>
                        <div class="stat-label">Budget Used</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $currency }} {{ number_format($stats['spent_amount'], 2) }}</div>
                        <div class="stat-label">Total Spent</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $stats['duration_days'] ?? 'N/A' }} days</div>
                        <div class="stat-label">Campaign Duration</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">💰 Financial Summary</h2>
                
                <div class="info-row">
                    <span class="info-label">Total Budget:</span>
                    <span class="info-value">{{ $currency }} {{ number_format($stats['budget'], 2) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Amount Spent:</span>
                    <span class="info-value">{{ $currency }} {{ number_format($stats['spent_amount'], 2) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Remaining Credit:</span>
                    <span class="info-value">{{ $currency }} {{ number_format($stats['remaining_credit'], 2) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Average Cost Per Scan:</span>
                    <span class="info-value">{{ $currency }} {{ number_format($stats['avg_cost_per_scan'], 2) }}</span>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">🎯 Engagement Metrics</h2>
                
                <div class="info-row">
                    <span class="info-label">Total Scans:</span>
                    <span class="info-value">{{ number_format($stats['total_scans']) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Maximum Scans Available:</span>
                    <span class="info-value">{{ number_format($stats['max_scans']) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Completion Rate:</span>
                    <span class="info-value">{{ $stats['max_scans'] > 0 ? round(($stats['total_scans'] / $stats['max_scans']) * 100, 1) : 0 }}%</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Completed On:</span>
                    <span class="info-value">{{ $stats['completed_at']->format('F d, Y H:i') }}</span>
                </div>
            </div>

            @if($recipientType === 'client')
            <div class="section">
                <div class="success-box">
                    <strong>✅ Thank you for choosing Daya!</strong><br>
                    Your campaign reached {{ number_format($stats['total_scans']) }} verified scans. We hope you achieved your marketing goals!
                </div>
            </div>
            @elseif($recipientType === 'dcd')
            <div class="section">
                <div class="success-box">
                    <strong>🎉 Great Work!</strong><br>
                    You successfully completed this campaign and earned {{ $currency }} {{ number_format($stats['recipient_earnings'], 2) }}. Keep up the excellent work!
                </div>
            </div>
            @endif

            @if($recipientType === 'client')
            <div class="section" style="text-align: center;">
                <p><strong>Want to run another campaign?</strong></p>
                <a href="{{ config('app.url') }}/client/campaigns/create" class="button">Create New Campaign</a>
            </div>
            @endif

            <div class="section">
                <p>If you have any questions about this campaign or need support, please don't hesitate to contact us.</p>
                <p>Best regards,<br>
                <strong>The Daya Team</strong></p>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated campaign completion notification.</p>
            <p>© {{ date('Y') }} Daya. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
