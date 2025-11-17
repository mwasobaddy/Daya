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
        .footer { text-align: center; color: #6b7280; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Campaign Submission</h1>
        </div>
        <div class="content">
            <p>A client has submitted a new campaign. See details below:</p>

            <div class="campaign-details">
                <h3>{{ $campaign->title }}</h3>
                <p><strong>Client:</strong> {{ $client->name ?? ($campaign->client->name ?? 'N/A') }}</p>
                <p><strong>Email:</strong> {{ $client->email ?? ($campaign->client->email ?? 'N/A') }}</p>
                <p><strong>Budget:</strong> ${{ number_format($campaign->budget, 2) }}</p>
                <p><strong>Description:</strong> {{ $campaign->description }}</p>
                <p><strong>Submitted:</strong> {{ $campaign->created_at->format('M j, Y g:i A') }}</p>
            </div>

            <div class="footer">
                <p>This is an automated notification from DDS.</p>
            </div>
        </div>
    </div>
</body>
</html>
