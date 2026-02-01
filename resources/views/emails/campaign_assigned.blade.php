<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Campaign Assigned</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2c3e50;">New Campaign Assigned!</h2>

        <p>Hi {{ $dcd->name }},</p>

        <p>Great news! You've been assigned to a new campaign. Here are the details:</p>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2c3e50;">{{ $campaign->title }}</h3>
            <p><strong>Client:</strong> {{ $client->name }}</p>
            <p><strong>Budget:</strong> ${{ number_format($campaign->budget, 2) }}</p>
            <p><strong>Campaign Credit:</strong> ${{ number_format($campaign->campaign_credit, 2) }}</p>
            @if($campaign->metadata)
                @if(isset($campaign->metadata['start_date']) && isset($campaign->metadata['end_date']))
                    <p><strong>Duration:</strong> {{ $campaign->metadata['start_date'] }} to {{ $campaign->metadata['end_date'] }}</p>
                @endif
            @endif
        </div>

        <p>You can now start scanning QR codes for this campaign. Make sure to download your QR code from the dashboard.</p>

        <p>If you have any questions, feel free to reach out to our support team.</p>

        <p>Happy scanning!</p>

        <p>Best regards,<br>The Daya Team</p>
    </div>
</body>
</html>