<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Confirmation</title>
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
            <h1>Campaign Submitted Successfully!</h1>
            <p>Thank you for choosing Daya for your campaign.</p>
        </div>

        <div class="content">
            <h2>Campaign Details</h2>
            <div class="campaign-details">
                <p><strong>Title:</strong> {{ $campaign->title }}</p>

                <p><strong>Budget:</strong> ${{ number_format($campaign->budget, 2) }}</p>
                <p><strong>Status:</strong> {{ ucfirst($campaign->status) }}</p>
                <p><strong>DCD Assigned:</strong> {{ $dcd ? $dcd->name . ' (' . $dcd->email . ')' : 'To be assigned' }}</p>
            </div>

            <h3>What Happens Next?</h3>
            <ul>
                <li>Your campaign will be reviewed by our team</li>
                <li>Once approved, the DCD will begin working on your campaign</li>
                <li>You'll receive progress updates via email</li>
                <li>Campaign completion and final results will be shared with you</li>
            </ul>

            <p>If you have any questions, please contact our support team.</p>

            <p>Best regards,<br>
            The Daya Team</p>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>© 2024 Daya. All rights reserved.</p>
        </div>
    &lt;/div&gt;
&lt;/body&gt;
&lt;/html&gt;