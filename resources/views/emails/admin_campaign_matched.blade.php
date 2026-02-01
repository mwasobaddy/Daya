<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Campaign Matched - Admin Notification</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2c3e50;">Campaign Matched - Admin Notification</h2>

        <p>A campaign has been successfully matched with a DCD.</p>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2c3e50;">{{ $campaign->title }}</h3>
            <p><strong>Campaign ID:</strong> {{ $campaign->id }}</p>
            <p><strong>Client:</strong> {{ $client->name }} ({{ $client->email }})</p>
            <p><strong>DCD:</strong> {{ $dcd->name }} ({{ $dcd->email }})</p>
            <p><strong>Budget:</strong> ${{ number_format($campaign->budget, 2) }}</p>
            <p><strong>Status:</strong> {{ $campaign->status }}</p>
            @if($campaign->metadata)
                @if(isset($campaign->metadata['start_date']) && isset($campaign->metadata['end_date']))
                    <p><strong>Duration:</strong> {{ $campaign->metadata['start_date'] }} to {{ $campaign->metadata['end_date'] }}</p>
                @endif
            @endif
        </div>

        <p>The campaign is now live and the DCD can start scanning QR codes.</p>

        <p>Best regards,<br>Daya System</p>
    </div>
</body>
</html>