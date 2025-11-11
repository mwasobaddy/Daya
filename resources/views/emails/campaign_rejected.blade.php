<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ef4444; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 30px; border-radius: 0 0 8px 8px; }
        .campaign-details { background: white; padding: 20px; margin: 20px 0; border-radius: 6px; border-left: 4px solid #ef4444; }
        .next-steps { background: #fef3c7; padding: 20px; margin: 20px 0; border-radius: 6px; border-left: 4px solid #f59e0b; }
        .footer { text-align: center; color: #6b7280; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Campaign Not Approved</h1>
        </div>

        <div class="content">
            <p>Dear {{ $campaign->client_name }},</p>

            <p>We regret to inform you that your campaign submission has not been approved at this time.</p>

            <div class="campaign-details">
                <h3>Campaign Details</h3>
                <p><strong>Title:</strong> {{ $campaign->title }}</p>
                <p><strong>Description:</strong> {{ $campaign->description }}</p>
                <p><strong>Budget:</strong> ${{ number_format($campaign->budget, 2) }}</p>
                <p><strong>Submitted:</strong> {{ $campaign->created_at->format('M j, Y g:i A') }}</p>
            </div>

            <div class="next-steps">
                <h4>What happens next?</h4>
                <ul>
                    <li>You can review and modify your campaign details</li>
                    <li>Re-submit your campaign for approval</li>
                    <li>Contact support if you have questions about the rejection</li>
                </ul>
            </div>

            <p>We appreciate your interest in our platform and encourage you to submit an improved campaign.</p>

            <p>Best regards,<br>The DDS Team</p>
        </div>

        <div class="footer">
            <p>This is an automated message from the DDS system.</p>
        </div>
    </div>
</body>
</html>