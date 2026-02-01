<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Campaign Matched</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2c3e50;">Campaign Matched Successfully!</h2>

        <p>Hi {{ $client->name }},</p>

        <p>Excellent news! Your campaign has been matched with a Digital Content Distributor (DCD) and is now live.</p>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2c3e50;">{{ $campaign->title }}</h3>
            <p><strong>DCD Assigned:</strong> {{ $dcd->name }}</p>
            <p><strong>DCD Email:</strong> {{ $dcd->email }}</p>
            <p><strong>Budget:</strong> ${{ number_format($campaign->budget, 2) }}</p>
            @if($campaign->metadata)
                @if(isset($campaign->metadata['start_date']) && isset($campaign->metadata['end_date']))
                    <p><strong>Duration:</strong> {{ $campaign->metadata['start_date'] }} to {{ $campaign->metadata['end_date'] }}</p>
                @endif
            @endif
        </div>

        <p>The DCD will now start scanning QR codes to promote your campaign. You can track the progress in your dashboard.</p>

        <p>If you have any questions or need to make changes to the campaign, please contact our support team.</p>

        <p>Thank you for choosing Daya!</p>

        <p>Best regards,<br>The Daya Team</p>
    </div>
</body>
</html>