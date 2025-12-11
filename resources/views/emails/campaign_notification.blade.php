<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Campaign Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .campaign-details { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Campaign Assigned!</h1>
            <p>A new campaign has been submitted through your QR code.</p>
        </div>

        <div class="content">
            <h2>Campaign Details</h2>
            <div class="campaign-details">
                <p><strong>Title:</strong> {{ $campaign->title }}</p>

                <p><strong>Budget:</strong> ${{ number_format($campaign->budget, 2) }}</p>
                <p><strong>Client:</strong> {{ $client->name }} ({{ $client->email }})</p>
                <p><strong>Status:</strong> {{ ucfirst($campaign->status) }}</p>
            </div>

            <h3>Next Steps</h3>
            <ul>
                <li>Review the campaign requirements</li>
                <li>Contact the client if you need clarification</li>
                <li>Begin working on the campaign</li>
                <li>Update campaign status as you progress</li>
                <li>Submit final results when complete</li>
            </ul>

            <p><strong>Potential Earnings:</strong> You can earn up to 20% commission on this campaign budget.</p>

            <p>Good luck with your campaign!</p>

            <p>Best regards,<br>
            The Daya Team</p>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>© 2024 Daya. All rights reserved.</p>
        </div>
    </div>
</body>
</html>