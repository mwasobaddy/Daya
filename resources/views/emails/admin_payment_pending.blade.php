<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f59e0b; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 30px; border-radius: 0 0 8px 8px; }
        .payment-details { background: white; padding: 20px; margin: 20px 0; border-radius: 6px; border-left: 4px solid #f59e0b; }
        .actions { text-align: center; margin: 30px 0; }
        .action-button {
            display: inline-block;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
            background: #10b981;
            color: white;
        }
        .footer { text-align: center; color: #6b7280; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Payment Pending Processing</h1>
        </div>

        <div class="content">
            <p>A payment is ready for processing:</p>

            <div class="payment-details">
                <h3>Payment Details</h3>
                <p><strong>User:</strong> {{ $earning->user->name }} ({{ $earning->user->email }})</p>
                <p><strong>Amount:</strong> ${{ number_format($earning->amount, 2) }}</p>
                <p><strong>Type:</strong> {{ ucwords(str_replace('_', ' ', $earning->type)) }}</p>
                <p><strong>Description:</strong> {{ $earning->description }}</p>
                <p><strong>Requested:</strong> {{ $earning->created_at->format('M j, Y g:i A') }}</p>
            </div>

            <div class="actions">
                <a href="{{ $completeUrl }}" class="action-button">Mark as Completed</a>
            </div>

            <p style="text-align: center; color: #6b7280; font-size: 14px;">
                This link is secure and will expire in 7 days. It can only be used once.
            </p>
        </div>

        <div class="footer">
            <p>This is an automated message from the DDS system.</p>
        </div>
    </div>
</body>
</html>