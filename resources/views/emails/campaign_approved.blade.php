<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Approved</title>
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
            <h1>Campaign Approved!</h1>
            <p>You can now start working on this campaign.</p>
        </div>

        <div class="content">
            <h2>Campaign Details</h2>
            <div class="campaign-details">
                <p><strong>Title:</strong> {{ $campaign->title }}</p>
                <p><strong>Description:</strong> {{ $campaign->description }}</p>
                <p><strong>Budget:</strong> ${{ number_format($campaign->budget, 2) }}</p>
                <p><strong>Client:</strong> {{ $client->name }}</p>
                <p><strong>Status:</strong> {{ ucfirst($campaign->status) }}</p>
            </div>

            <h3>Next Steps</h3>
            <ul>
                <li><strong>Use the attached QR code</strong> to direct potential customers to the client's product</li>
                <li>Begin executing the campaign according to the specifications</li>
            </ul>

            <p><strong>Important:</strong> Your DCD QR code will automatically direct users to the most appropriate active campaign. When customers scan your QR code during this campaign period ({{ $campaign->metadata['start_date'] ?? 'TBD' }} to {{ $campaign->metadata['end_date'] ?? 'TBD' }}), they will be taken to the client's digital product ({{ $campaign->digital_product_link }}) and you'll earn commissions from verified scans.</p>

            <p><strong>Potential Earnings:</strong> You can earn up to 20% commission (${{ number_format($campaign->budget * 0.20, 2) }}) from this campaign.</p>

            <p>Good luck with your campaign execution!</p>

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