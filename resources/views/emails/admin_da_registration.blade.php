<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 30px; border-radius: 0 0 8px 8px; }
        .user-details { background: white; padding: 20px; margin: 20px 0; border-radius: 6px; border-left: 4px solid #2563eb; }
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
            <h1>🎉 New Digital Ambassador Registration</h1>
        </div>

        <div class="content">
            <p>A new Digital Ambassador has successfully registered on the Daya platform:</p>

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
                    <span class="label">Referral Code:</span>
                    <span class="value">{{ $user->referral_code }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Wallet Type:</span>
                    <span class="value">{{ ucfirst($user->wallet_type) }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Registration Date:</span>
                    <span class="value">{{ $user->created_at->format('M j, Y g:i A') }}</span>
                </div>
            </div>

            @if($referrer)
            <div class="referral-info">
                <h4>📋 Referral Information</h4>
                <p>This DA was referred by <strong>{{ $referrer->name }}</strong> ({{ $referrer->email }}).</p>
                <p><strong>Referrer's Role:</strong> {{ ucfirst($referrer->role) }}</p>
            </div>
            @else
            <div class="referral-info">
                <h4>📋 Referral Information</h4>
                <p>This DA registered without a referral code.</p>
            </div>
            @endif

            <div class="user-details">
                <h4>📍 Location Details</h4>
                <div class="detail-row">
                    <span class="label">Country:</span>
                    <span class="value">{{ $user->profile['country_id'] ? \App\Models\Country::find($user->profile['country_id'])->name : 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">County:</span>
                    <span class="value">{{ $user->profile['county_id'] ? \App\Models\County::find($user->profile['county_id'])->name : 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Address:</span>
                    <span class="value">{{ $user->profile['address'] ?? 'N/A' }}</span>
                </div>
            </div>

            <div class="user-details">
                <h4>📱 Social Media & Communication</h4>
                <div class="detail-row">
                    <span class="label">Platforms:</span>
                    <span class="value">{{ is_array($user->profile['platforms']) ? implode(', ', $user->profile['platforms']) : 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Followers:</span>
                    <span class="value">{{ $user->profile['followers'] ? str_replace('_', ' ', ucfirst($user->profile['followers'])) : 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Communication Channel:</span>
                    <span class="value">{{ $user->profile['communication_channel'] ? ucfirst($user->profile['communication_channel']) : 'N/A' }}</span>
                </div>
            </div>

            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/admin/users') }}" style="display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">
                    View All Users
                </a>
            </p>

            <div class="footer">
                <p>This is an automated notification from the Daya platform.</p>
                <p>You can manage DA registrations from the admin dashboard.</p>
            </div>
        </div>
    </div>
</body>
</html>