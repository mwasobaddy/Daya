<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0ea5e9; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 30px; border-radius: 0 0 8px 8px; }
        .user-details { background: white; padding: 20px; margin: 20px 0; border-radius: 6px; border-left: 4px solid #0ea5e9; }
        .referral-info { background: #fef3c7; padding: 15px; margin: 15px 0; border-radius: 6px; border-left: 4px solid #f59e0b; }
        .footer { text-align: center; color: #6b7280; font-size: 12px; margin-top: 30px; }
        .detail-row { margin: 8px 0; }
        .label { font-weight: bold; color: #374151; }
        .value { color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 New Digital Content Distributor (DCD) Registration</h1>
        </div>

        <div class="content">
            <p>A new DCD has registered on the Daya platform. Below are the details:</p>

            <div class="user-details">
                <h3>{{ $user->name }}</h3>

                <div class="detail-row">
                    <span class="label">Email:</span>
                    <span class="value">{{ $user->email }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Phone:</span>
                    <span class="value">{{ $user->phone }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">National ID:</span>
                    <span class="value">{{ $user->national_id }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Business Name:</span>
                    <span class="value">{{ $user->business_name ?? ($user->profile['business_name'] ?? 'N/A') }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Country:</span>
                    <span class="value">{{ ($user->country_id && \App\Models\Country::find($user->country_id)) ? \App\Models\Country::find($user->country_id)->name : ($user->profile['country_id'] ? \App\Models\Country::find($user->profile['country_id'])->name : 'N/A') }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="label">County:</span>
                    <span class="value">{{ ($user->county_id && \App\Models\County::find($user->county_id)) ? \App\Models\County::find($user->county_id)->name : ($user->profile['county_id'] ? \App\Models\County::find($user->profile['county_id'])->name : 'N/A') }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Subcounty:</span>
                    <span class="value">{{ ($user->subcounty_id && \App\Models\Subcounty::find($user->subcounty_id)) ? \App\Models\Subcounty::find($user->subcounty_id)->name : ($user->profile['subcounty_id'] ? \App\Models\Subcounty::find($user->profile['subcounty_id'])->name : 'N/A') }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Ward:</span>
                    <span class="value">{{ ($user->ward_id && \App\Models\Ward::find($user->ward_id)) ? \App\Models\Ward::find($user->ward_id)->name : ($user->profile['ward_id'] ? \App\Models\Ward::find($user->profile['ward_id'])->name : 'N/A') }}</span>
                </div>
            </div>

            @if($referrer)
            <div class="referral-info">
                <h4>📋 Referral Information</h4>
                <p>This DCD was referred by <strong>{{ $referrer->name }}</strong> ({{ $referrer->email }}).</p>
                <p><strong>Referrer's Role:</strong> {{ ucfirst($referrer->role) }}</p>
                <p><strong>Referral Code Used:</strong> {{ $referrer->referral_code }}</p>
            </div>
            @else
            <div class="referral-info">
                <h4>📋 Referral Information</h4>
                <p>This DCD registered without a referral code or the referral code was invalid.</p>
            </div>
            @endif

            <div class="user-details">
                <h4>🏬 Business Details</h4>
                <div class="detail-row">
                    <span class="label">Business Types:</span>
                    <span class="value">{{ is_array($user->profile['business_types'] ?? null) ? implode(', ', $user->profile['business_types']) : ($user->business_types ?? 'N/A') }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Business Address:</span>
                    <span class="value">{{ $user->profile['business_address'] ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Daily Foot Traffic:</span>
                    <span class="value">{{ $user->profile['daily_foot_traffic'] ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Operating Hours:</span>
                    <span class="value">{{ ($user->profile['operating_hours_start'] ?? null) ? ($user->profile['operating_hours_start'] . ' - ' . ($user->profile['operating_hours_end'] ?? '')) : 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Campaign Types:</span>
                    <span class="value">{{ is_array($user->profile['campaign_types'] ?? null) ? implode(', ', $user->profile['campaign_types']) : 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Content Safety (safe for kids):</span>
                    <span class="value">{{ isset($user->profile['safe_for_kids']) ? ($user->profile['safe_for_kids'] ? 'Yes' : 'No') : 'N/A' }}</span>
                </div>
            </div>

            <div class="footer">
                <p>This is an automated notification from the Daya platform.</p>
                <p>Manage DCD registrations via admin email actions or automated workflows.</p>
            </div>
        </div>
    </div>
</body>
</html>
