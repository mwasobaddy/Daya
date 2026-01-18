<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daya Daily Report</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
        }
        .container {
            max-width: 680px;
            margin: 20px auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header p {
            margin: 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .snapshot {
            background: #f8f9fc;
            border: 2px solid #e3e8ef;
            border-radius: 8px;
            margin: 20px;
            padding: 20px;
        }
        .snapshot h2 {
            margin: 0 0 15px 0;
            font-size: 18px;
            color: #667eea;
            font-weight: 700;
        }
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .metric {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }
        .metric-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .metric-value {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }
        .metric-change {
            font-size: 12px;
            margin-top: 5px;
        }
        .metric-change.positive {
            color: #10b981;
        }
        .metric-change.negative {
            color: #ef4444;
        }
        .section {
            margin: 20px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .section-title::before {
            content: '';
            width: 4px;
            height: 20px;
            background: #667eea;
            margin-right: 10px;
            border-radius: 2px;
        }
        .highlight-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .highlight-box h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #92400e;
        }
        .highlight-item {
            margin: 8px 0;
            font-size: 14px;
            color: #78350f;
        }
        .alert-box {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .alert-box h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #991b1b;
        }
        .alert-item {
            margin: 8px 0;
            font-size: 14px;
            color: #7f1d1d;
        }
        .list-item {
            background: #f9fafb;
            padding: 12px;
            margin: 8px 0;
            border-radius: 6px;
            border-left: 3px solid #e5e7eb;
        }
        .list-item-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }
        .list-item-details {
            font-size: 13px;
            color: #6b7280;
        }
        .performer-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .performer-card h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .performer-card .name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .performer-card .stats {
            font-size: 14px;
            opacity: 0.9;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 5px;
        }
        .footer {
            background: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📊 Daya Daily Report</h1>
            <p>{{ $data['day_name'] }}, {{ $data['date'] }}</p>
        </div>

        <!-- Daily Snapshot -->
        <div class="snapshot">
            <h2>📈 DAILY SNAPSHOT</h2>
            <div class="metric-grid">
                <div class="metric">
                    <div class="metric-label">Total Scans</div>
                    <div class="metric-value">{{ number_format($data['scans']['total']) }}</div>
                    @if($data['comparison']['scans']['change_percent'] != 0)
                        <div class="metric-change {{ $data['comparison']['scans']['change_percent'] > 0 ? 'positive' : 'negative' }}">
                            {{ $data['comparison']['scans']['change_percent'] > 0 ? '↑' : '↓' }} 
                            {{ abs($data['comparison']['scans']['change_percent']) }}% vs. avg
                        </div>
                    @endif
                </div>
                <div class="metric">
                    <div class="metric-label">New Campaigns</div>
                    <div class="metric-value">{{ $data['campaigns']['new_count'] }}</div>
                    <div class="metric-change">KSh {{ number_format($data['campaigns']['total_budget_new'], 0) }}</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Campaigns Completed</div>
                    <div class="metric-value">{{ $data['campaigns']['completed_count'] }}</div>
                    @if($data['campaigns']['recap_emails_sent'] > 0)
                        <div class="metric-change">
                            {{ $data['campaigns']['recap_emails_sent'] }} recap emails sent
                        </div>
                    @endif
                </div>
                <div class="metric">
                    <div class="metric-label">Users Notified (Recap)</div>
                    <div class="metric-value">{{ $data['campaigns']['users_notified_recap'] ?? 0 }}</div>
                    <div class="metric-change">From completed campaigns</div>
                </div>
                <div class="metric">
                    <div class="metric-label">New Users</div>
                    <div class="metric-value">{{ $data['users']['new_das_count'] + $data['users']['new_dcds_count'] + $data['users']['new_clients_count'] }}</div>
                    <div class="metric-change">
                        {{ $data['users']['new_das_count'] }} DAs, {{ $data['users']['new_dcds_count'] }} DCDs, {{ $data['users']['new_clients_count'] }} Clients
                    </div>
                </div>
                <div class="metric">
                    <div class="metric-label">Total Earnings</div>
                    <div class="metric-value">KSh {{ number_format($data['scans']['total_earnings'], 2) }}</div>
                    @if($data['comparison']['earnings']['change_percent'] != 0)
                        <div class="metric-change {{ $data['comparison']['earnings']['change_percent'] > 0 ? 'positive' : 'negative' }}">
                            {{ $data['comparison']['earnings']['change_percent'] > 0 ? '↑' : '↓' }} 
                            {{ abs($data['comparison']['earnings']['change_percent']) }}% vs. avg
                        </div>
                    @endif
                </div>
                <div class="metric">
                    <div class="metric-label">Active Campaigns</div>
                    <div class="metric-value">{{ $data['campaigns']['active'] }}</div>
                </div>
            </div>
        </div>

        <!-- Highlights -->
        @if($data['campaigns']['completed_count'] > 0 || $data['top_performers']['top_dcd'] || $data['campaigns']['nearing_completion']->count() > 0)
        <div class="section">
            <div class="section-title">🚀 HIGHLIGHTS</div>
            <div class="highlight-box">
                <h3>✅ Key Achievements</h3>
                @if($data['campaigns']['completed_count'] > 0)
                    <p style="margin-bottom: 10px;"><strong>Completed Campaigns ({{ $data['campaigns']['completed_count'] }}):</strong></p>
                    @foreach($data['campaigns']['completed_campaigns'] as $campaign)
                        <div class="highlight-item">
                            • Campaign #{{ $campaign['id'] }} "{{ $campaign['title'] }}" completed ({{ $campaign['total_scans'] }} scans, KSh {{ number_format($campaign['spent'], 2) }})
                        </div>
                    @endforeach
                    @if($data['campaigns']['recap_emails_sent'] > 0)
                        <p style="margin-top: 10px; color: #059669; font-weight: 500;">
                            ✉️ Recap emails sent for {{ $data['campaigns']['recap_emails_sent'] }} campaign(s) to {{ $data['campaigns']['users_notified_recap'] ?? 0 }} user(s)
                        </p>
                    @endif
                @endif
                @if($data['top_performers']['top_dcd'])
                    <div class="highlight-item">
                        • DCD {{ $data['top_performers']['top_dcd']['name'] }} earned KSh {{ number_format($data['top_performers']['top_dcd']['earnings'], 2) }} ({{ $data['top_performers']['top_dcd']['scans'] }} scans)
                    </div>
                @endif
                @if($data['campaigns']['pending_review'] > 0)
                    <div class="highlight-item">
                        • {{ $data['campaigns']['pending_review'] }} campaigns need review
                    </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Attention Needed -->
        @if($data['alerts']['budget_warnings'] > 0 || $data['alerts']['pending_approvals'] > 0 || $data['campaigns']['nearing_completion']->count() > 0)
        <div class="section">
            <div class="section-title">⚠️ ATTENTION NEEDED</div>
            <div class="alert-box">
                <h3>🔔 Action Required</h3>
                @if($data['campaigns']['pending_review'] > 0)
                    <div class="alert-item">
                        • <strong>{{ $data['campaigns']['pending_review'] }} pending campaign approvals</strong>
                    </div>
                @endif
                @if($data['alerts']['budget_warnings'] > 0)
                    <div class="alert-item">
                        • <strong>{{ $data['alerts']['budget_warnings'] }} campaigns at 90%+ budget</strong>
                    </div>
                @endif
                @if($data['campaigns']['nearing_completion']->count() > 0)
                    @foreach($data['campaigns']['nearing_completion'] as $campaign)
                        <div class="alert-item">
                            • Campaign #{{ $campaign['id'] }} "{{ $campaign['title'] }}" at {{ $campaign['progress'] }}% ({{ $campaign['remaining_scans'] }} scans left)
                        </div>
                    @endforeach
                @endif
                @if($data['alerts']['inactive_dcds'] > 0)
                    <div class="alert-item">
                        • {{ $data['alerts']['inactive_dcds'] }} DCDs inactive for 3+ days
                    </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Top Performers -->
        @if($data['top_performers']['top_dcd'] || $data['top_performers']['top_campaign'] || $data['top_performers']['top_referrer'])
        <div class="section">
            <div class="section-title">🏆 TOP PERFORMERS</div>
            
            @if($data['top_performers']['top_dcd'])
            <div class="performer-card">
                <h4>🥇 Top DCD</h4>
                <div class="name">{{ $data['top_performers']['top_dcd']['name'] }}</div>
                <div class="stats">
                    {{ $data['top_performers']['top_dcd']['scans'] }} scans • 
                    KSh {{ number_format($data['top_performers']['top_dcd']['earnings'], 2) }} earnings
                </div>
            </div>
            @endif

            @if($data['top_performers']['top_campaign'])
            <div class="performer-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <h4>🥇 Top Campaign</h4>
                <div class="name">{{ $data['top_performers']['top_campaign']['title'] }}</div>
                <div class="stats">{{ $data['top_performers']['top_campaign']['scans'] }} scans</div>
            </div>
            @endif

            @if($data['top_performers']['top_referrer'])
            <div class="performer-card" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                <h4>🥇 Top Referrer (DA)</h4>
                <div class="name">{{ $data['top_performers']['top_referrer']['name'] }}</div>
                <div class="stats">{{ $data['top_performers']['top_referrer']['referrals'] }} new referrals</div>
            </div>
            @endif
        </div>
        @endif

        <!-- New Campaigns -->
        @if($data['campaigns']['new_count'] > 0)
        <div class="section">
            <div class="section-title">📋 NEW CAMPAIGNS ({{ $data['campaigns']['new_count'] }})</div>
            @foreach($data['campaigns']['new_campaigns'] as $campaign)
            <div class="list-item">
                <div class="list-item-title">Campaign #{{ $campaign['id'] }}: {{ $campaign['title'] }}</div>
                <div class="list-item-details">
                    Client: {{ $campaign['client'] }} • Budget: KSh {{ number_format($campaign['budget'], 0) }} • 
                    Type: {{ ucwords(str_replace('_', ' ', $campaign['objective'])) }}
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Financial Summary -->
        <div class="section">
            <div class="section-title">💰 FINANCIAL SUMMARY</div>
            <div class="metric-grid">
                <div class="metric">
                    <div class="metric-label">Revenue Today</div>
                    <div class="metric-value">KSh {{ number_format($data['financial']['revenue_today'], 0) }}</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Pending Earnings</div>
                    <div class="metric-value">KSh {{ number_format($data['financial']['pending_earnings'], 2) }}</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Budget Utilization</div>
                    <div class="metric-value">{{ $data['financial']['budget_utilization_rate'] }}%</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Total Budget Active</div>
                    <div class="metric-value">KSh {{ number_format($data['financial']['total_budget_allocated'], 0) }}</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section" style="text-align: center; padding: 20px;">
            <a href="{{ config('app.url') }}/admin/campaigns?status=submitted" class="button">
                Approve Campaigns ({{ $data['campaigns']['pending_review'] }})
            </a>
            <a href="{{ config('app.url') }}/admin/dashboard" class="button">
                View Full Dashboard
            </a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is an automated daily digest from Daya Distribution Platform</p>
            <p>© {{ date('Y') }} Daya. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
