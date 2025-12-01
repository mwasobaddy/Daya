<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Completed</title>
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
            <h1>Campaign Completed Successfully!</h1>
            <p>Congratulations on completing this campaign.</p>
        </div>

        <div class="content">
            <h2>Campaign Summary</h2>
            <div class="campaign-details">
                <p><strong>Title:</strong> {{ $campaign->title }}</p>
                <p><strong>Description:</strong> {{ $campaign->description }}</p>
                <p><strong>Budget:</strong> ${{ number_format($campaign->budget, 2) }}</p>
                <p><strong>{{ $otherUser->role === 'client' ? 'DCD' : 'Client' }}:</strong> {{ $otherUser->name }} ({{ $otherUser->email }})</p>
                <p><strong>Status:</strong> {{ ucfirst($campaign->status) }}</p>
                <p><strong>Completed At:</strong> {{ $campaign->completed_at?->format('M j, Y g:i A') }}</p>
            </div>

            @if($otherUser->role === 'client')
                <!-- Email to DCD -->
                <h3>Your Earnings</h3>
                <p>Congratulations! You have earned ${{ number_format($campaign->budget * 0.20, 2) }} KeDDS tokens from this campaign completion.</p>
                <p>Your venture shares have been allocated and will be reflected in your monthly report.</p>
            @else
                <!-- Email to Client -->
                <h3>Campaign Complete</h3>
                <p>Your campaign has been successfully completed by our Digital Content Distributor.</p>
                <p>Thank you for choosing Daya for your campaign needs.</p>
            @endif

            <p>If you have any questions or need further assistance, please contact our support team.</p>

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