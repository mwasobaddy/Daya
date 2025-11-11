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
        .actions { text-align: center; margin: 30px 0; }
        .action-button {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }
        .approve { background: #10b981; color: white; }
        .reject { background: #ef4444; color: white; }
        .footer { text-align: center; color: #6b7280; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Campaign Pending Approval</h1>
        </div>

        <div class="content">
            <p>A new campaign has been submitted and requires your approval:</p>

            <div class="campaign-details">
                <h3>{{ $campaign->title }}</h3>
                <p><strong>Client:</strong> {{ $campaign->client_name }}</p>
                <p><strong>Email:</strong> {{ $campaign->client_email }}</p>
                <p><strong>Description:</strong> {{ $campaign->description }}</p>
                <p><strong>Budget:</strong> ${{ number_format($campaign->budget, 2) }}</p>
                <p><strong>Submitted:</strong> {{ $campaign->created_at->format('M j, Y g:i A') }}</p>
            </div>

            <div class="actions">
                <a href="{{ $approveUrl }}" class="action-button approve">Approve Campaign</a>
                <a href="{{ $rejectUrl }}" class="action-button reject">Reject Campaign</a>
            </div>

            <p style="text-align: center; color: #6b7280; font-size: 14px;">
                These links are secure and will expire in 7 days. Each link can only be used once.
            </p>
        </div>

        <div class="footer">
            <p>This is an automated message from the DDS system.</p>
        </div>
    </div>
</body>
</html>