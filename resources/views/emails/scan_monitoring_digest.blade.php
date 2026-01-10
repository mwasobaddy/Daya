<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Monitoring Digest</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            overflow: hidden;
        }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            padding: 20px; 
            text-align: center;
        }
        .header h1 { 
            margin: 0; 
            font-size: 24px; 
        }
        .timestamp {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }
        .content { 
            padding: 20px; 
        }
        .section { 
            margin-bottom: 30px; 
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section-title { 
            font-size: 18px; 
            font-weight: 600; 
            margin-bottom: 15px;
            color: #667eea;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 15px 0;
        }
        .summary-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 15px;
            border-radius: 6px;
        }
        .summary-card .label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .summary-card .value {
            font-size: 24px;
            font-weight: 600;
            color: #111827;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0;
            font-size: 14px;
        }
        th { 
            background: #f9fafb; 
            padding: 10px; 
            text-align: left; 
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }
        td { 
            padding: 10px; 
            border-bottom: 1px solid #f3f4f6;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .no-data {
            text-align: center;
            padding: 30px;
            color: #6b7280;
            font-style: italic;
        }
        .amount {
            font-weight: 600;
            color: #059669;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Scan Monitoring Digest</h1>
            <div class="timestamp">{{ $timestamp->format('l, F j, Y - H:i:s') }}</div>
        </div>

        <div class="content">
            <!-- Summary Section -->
            <div class="section">
                <h2 class="section-title">📈 System Overview</h2>
                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="label">Total Campaigns</div>
                        <div class="value">{{ $campaigns_summary['total_campaigns'] }}</div>
                    </div>
                    <div class="summary-card">
                        <div class="label">Total Scans</div>
                        <div class="value">{{ $campaigns_summary['total_scans'] }}</div>
                    </div>
                    <div class="summary-card">
                        <div class="label">Active Campaigns</div>
                        <div class="value">{{ $campaigns_summary['approved'] + $campaigns_summary['live'] }}</div>
                    </div>
                    <div class="summary-card">
                        <div class="label">Total Spent</div>
                        <div class="value">KSh {{ number_format($campaigns_summary['total_spent'], 2) }}</div>
                    </div>
                </div>
            </div>

            <!-- Recent Scans (Last 5 Minutes) -->
            <div class="section">
                <h2 class="section-title">🆕 Recent Scans (Last 5 Minutes)</h2>
                @if($recent_scans->count() > 0)
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Campaign</th>
                                <th>DCD</th>
                                <th>Earnings</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent_scans as $scan)
                            <tr>
                                <td>{{ $scan->created_at->format('H:i:s') }}</td>
                                <td>{{ $scan->campaign->title ?? 'N/A' }}</td>
                                <td>{{ $scan->dcd->name ?? 'Unknown' }}</td>
                                <td class="amount">KSh {{ number_format($scan->earnings ?? 0, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="no-data">No scans in the last 5 minutes</div>
                @endif
            </div>

            <!-- Recent Earnings (Last 5 Minutes) -->
            <div class="section">
                <h2 class="section-title">💰 Recent Earnings (Last 5 Minutes)</h2>
                @if($recent_earnings->count() > 0)
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Campaign</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent_earnings as $earning)
                            <tr>
                                <td>{{ $earning->created_at->format('H:i:s') }}</td>
                                <td>{{ $earning->user->name ?? 'Unknown' }}</td>
                                <td>
                                    @if($earning->type === 'scan')
                                        <span class="badge badge-success">Scan</span>
                                    @elseif($earning->type === 'commission')
                                        <span class="badge badge-info">Commission</span>
                                    @else
                                        <span class="badge badge-warning">{{ ucfirst($earning->type) }}</span>
                                    @endif
                                </td>
                                <td>{{ $earning->campaign->title ?? 'N/A' }}</td>
                                <td class="amount">KSh {{ number_format($earning->amount, 2) }}</td>
                                <td>
                                    @if($earning->status === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif($earning->status === 'paid')
                                        <span class="badge badge-success">Paid</span>
                                    @else
                                        <span class="badge badge-danger">{{ ucfirst($earning->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="no-data">No earnings in the last 5 minutes</div>
                @endif
            </div>

            <!-- Earnings by Type -->
            <div class="section">
                <h2 class="section-title">📊 Earnings by Type (All Time)</h2>
                @if($earnings_by_type->count() > 0)
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Count</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($earnings_by_type as $earning_type)
                            <tr>
                                <td>
                                    @if($earning_type->type === 'scan')
                                        <span class="badge badge-success">Scan</span>
                                    @elseif($earning_type->type === 'commission')
                                        <span class="badge badge-info">Commission</span>
                                    @else
                                        <span class="badge badge-warning">{{ ucfirst($earning_type->type) }}</span>
                                    @endif
                                </td>
                                <td>{{ number_format($earning_type->count) }}</td>
                                <td class="amount">KSh {{ number_format($earning_type->total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="no-data">No earnings data available</div>
                @endif
            </div>

            <!-- Top Campaigns by Scans -->
            <div class="section">
                <h2 class="section-title">🔥 Top Campaigns (by Scans)</h2>
                @if($campaigns_with_scans->count() > 0)
                    <table>
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th>Client</th>
                                <th>DCD</th>
                                <th>Status</th>
                                <th>Scans</th>
                                <th>Spent</th>
                                <th>Budget</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($campaigns_with_scans as $campaign)
                            <tr>
                                <td><strong>{{ $campaign->title }}</strong></td>
                                <td>{{ $campaign->client->name ?? 'N/A' }}</td>
                                <td>{{ $campaign->dcd->name ?? 'N/A' }}</td>
                                <td>
                                    @if($campaign->status === 'approved')
                                        <span class="badge badge-info">Approved</span>
                                    @elseif($campaign->status === 'live')
                                        <span class="badge badge-success">Live</span>
                                    @elseif($campaign->status === 'completed')
                                        <span class="badge badge-warning">Completed</span>
                                    @else
                                        <span class="badge badge-danger">{{ ucfirst($campaign->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $campaign->scans_count }}</td>
                                <td class="amount">KSh {{ number_format($campaign->spent_amount, 2) }}</td>
                                <td>KSh {{ number_format($campaign->budget, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="no-data">No campaigns with scans found</div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
