<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 30px; border-radius: 0 0 8px 8px; }
        .campaign-details { background: white; padding: 20px; margin: 20px 0; border-radius: 6px; border-left: 4px solid #2563eb; }
        .section-header { margin-bottom: 10px; font-size: 16px; font-weight: bold; }
        .tag { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 12px; margin-right: 4px; }
        .metadata-section { margin: 15px 0; padding: 15px; border-radius: 4px; }
        .metadata-section h4 { margin-top: 0; font-size: 16px; }
        .metadata-section p { margin: 8px 0; line-height: 1.4; }
        .actions { text-align: center; margin: 30px 0; }
        .action-button {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }
        .approve { background: #10b981; color: white; }
        .reject { background: #ef4444; color: white; }
        .footer { text-align: center; color: #6b7280; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Campaign Pending Approval</h1>
        </div>

        <div class="content">
            <p>A new campaign has been submitted and requires your approval:</p>

            <div class="campaign-details">
                <h3>{{ $campaign->title }}</h3>
                
                <!-- Client Information -->
                <div style="background: #f1f5f9; padding: 15px; margin: 15px 0; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #1e40af;">👤 Client Information</h4>
                    <p><strong>Name:</strong> {{ $clientName ?? ($campaign->client->name ?? 'N/A') }}</p>
                    <p><strong>Email:</strong> {{ $clientEmail ?? ($campaign->client->email ?? 'N/A') }}</p>
                    <p><strong>Phone:</strong> {{ $campaign->metadata['phone'] ?? 'N/A' }}</p>
                    <p><strong>Business Name:</strong> {{ $campaign->metadata['business_name'] ?? 'N/A' }}</p>
                    <p><strong>Account Type:</strong> {{ ucfirst($campaign->metadata['account_type'] ?? 'N/A') }}</p>
                    <p><strong>Country:</strong> {{ $campaign->metadata['country'] ?? 'N/A' }}</p>
                    @if(isset($campaign->metadata['referral_code']))
                        <p><strong>Referral Code:</strong> <span style="background: #dcfce7; padding: 2px 6px; border-radius: 3px;">{{ $campaign->metadata['referral_code'] }}</span></p>
                    @endif
                </div>

                <!-- Campaign Details -->
                <div style="background: #fef3c7; padding: 15px; margin: 15px 0; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #92400e;">📊 Campaign Details</h4>
                    <p><strong>Description:</strong> {{ $campaign->description }}</p>
                    <p><strong>Campaign Objective:</strong> {{ ucwords(str_replace('_', ' ', $campaign->campaign_objective)) }}</p>
                    <p><strong>Budget:</strong> <span style="font-size: 18px; color: #059669; font-weight: bold;">{{ $currencySymbol }}{{ number_format($campaign->budget, 0) }}</span></p>
                    <p><strong>Target Audience:</strong> {{ $campaign->target_audience ?? 'N/A' }}</p>
                    <p><strong>Objectives:</strong> {{ $campaign->objectives ?? 'N/A' }}</p>
                    <p><strong>Digital Product Link:</strong> <a href="{{ $campaign->digital_product_link }}" target="_blank" style="color: #2563eb;">{{ $campaign->digital_product_link }}</a></p>
                    @if($campaign->explainer_video_url)
                        <p><strong>Explainer Video:</strong> <a href="{{ $campaign->explainer_video_url }}" target="_blank" style="color: #2563eb;">{{ $campaign->explainer_video_url }}</a></p>
                    @endif
                </div>

                <!-- Targeting & Preferences -->
                <div style="background: #f3e8ff; padding: 15px; margin: 15px 0; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #7c3aed;">🎯 Targeting & Preferences</h4>
                    @if(isset($campaign->metadata['start_date']) && isset($campaign->metadata['end_date']))
                        <p><strong>Campaign Duration:</strong> {{ \Carbon\Carbon::parse($campaign->metadata['start_date'])->format('M j, Y') }} - {{ \Carbon\Carbon::parse($campaign->metadata['end_date'])->format('M j, Y') }} 
                            <span style="background: #ddd6fe; padding: 2px 6px; border-radius: 3px; font-size: 12px;">{{ \Carbon\Carbon::parse($campaign->metadata['start_date'])->diffInDays(\Carbon\Carbon::parse($campaign->metadata['end_date'])) + 1 }} days</span>
                        </p>
                    @endif
                    
                    @if($countryName || $countyName || $subcountyName || $wardName)
                        <p><strong>Geographic Targeting:</strong>
                            @if($countryName)
                                <span style="background: #e0f2fe; padding: 2px 6px; border-radius: 3px; margin-right: 4px; font-size: 12px;">{{ $countryName }}</span>
                            @endif
                            @if($countyName)
                                @if($countryName) > @endif<span style="background: #e8f5e8; padding: 2px 6px; border-radius: 3px; margin-right: 4px; font-size: 12px;">{{ $countyName }}</span>
                            @endif
                            @if($subcountyName)
                                > <span style="background: #fff3cd; padding: 2px 6px; border-radius: 3px; margin-right: 4px; font-size: 12px;">{{ $subcountyName }}</span>
                            @endif
                            @if($wardName)
                                > <span style="background: #f8d7da; padding: 2px 6px; border-radius: 3px; margin-right: 4px; font-size: 12px;">{{ $wardName }}</span>
                            @endif
                        </p>
                    @endif
                    
                    @if(isset($campaign->metadata['business_types']) && is_array($campaign->metadata['business_types']))
                        <p><strong>Target Business Types:</strong> 
                            @foreach($campaign->metadata['business_types'] as $type)
                                <span style="background: #e0e7ff; padding: 2px 6px; border-radius: 3px; margin-right: 4px; font-size: 12px;">{{ ucfirst($type) }}</span>
                            @endforeach
                        </p>
                    @endif
                    
                    @if(isset($campaign->metadata['content_safety_preferences']) && is_array($campaign->metadata['content_safety_preferences']))
                        <p><strong>Content Safety:</strong> 
                            @foreach($campaign->metadata['content_safety_preferences'] as $pref)
                                <span style="background: #dcfce7; padding: 2px 6px; border-radius: 3px; margin-right: 4px; font-size: 12px;">{{ ucwords(str_replace('_', ' ', $pref)) }}</span>
                            @endforeach
                        </p>
                    @endif
                    
                    @if($campaign->campaign_objective === 'music_promotion' && isset($campaign->metadata['music_genres']) && is_array($campaign->metadata['music_genres']))
                        <p><strong>Music Genres:</strong> 
                            @foreach($campaign->metadata['music_genres'] as $genre)
                                <span style="background: #fed7d7; padding: 2px 6px; border-radius: 3px; margin-right: 4px; font-size: 12px;">{{ $genre }}</span>
                            @endforeach

                        </p>
                    @endif
                </div>

                <!-- Submission Info -->
                <div style="background: #f0fdf4; padding: 15px; margin: 15px 0; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #166534;">⏰ Submission Information</h4>
                    <p><strong>Submitted:</strong> {{ $campaign->created_at->format('M j, Y g:i A') }}</p>
                    <p><strong>Status:</strong> <span style="background: #fef3c7; padding: 2px 8px; border-radius: 3px; color: #92400e; font-weight: bold;">{{ ucfirst($campaign->status) }}</span></p>
                    @if(isset($campaign->metadata['other_business_type']))
                        <p><strong>Other Business Type:</strong> {{ $campaign->metadata['other_business_type'] }}</p>
                    @endif
                </div>
            </div>

            <div class="actions">
                <a href="{{ $approveUrl }}" class="action-button approve">Approve Campaign</a>
                <a href="{{ $rejectUrl }}" class="action-button reject">Reject Campaign</a>
            </div>

            <p style="text-align: center; color: #6b7280; font-size: 14px;">
                These links are secure and will expire in 7 days. Each link can only be used once.
            </p>
        </div>

        <div class="footer">
            <p>This is an automated message from the DDS system.</p>
        </div>
    </div>
</body>
</html>