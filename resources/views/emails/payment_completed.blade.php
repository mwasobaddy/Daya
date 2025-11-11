<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #10b981; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 30px; border-radius: 0 0 8px 8px; }
        .payment-details { background: white; padding: 20px; margin: 20px 0; border-radius: 6px; border-left: 4px solid #10b981; }
        .celebration { text-align: center; margin: 20px 0; }
        .amount { font-size: 2rem; font-weight: bold; color: #10b981; }
        .footer { text-align: center; color: #6b7280; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Payment Completed!</h1>
        </div>

        <div class="content">
            <p>Dear {{ $earning->user->name }},</p>

            <div class="celebration">
                <div class="amount">${{ number_format($earning->amount, 2) }}</div>
                <p style="color: #10b981; font-weight: bold;">Payment has been processed successfully!</p>
            </div>

            <div class="payment-details">
                <h3>Payment Details</h3>
                <p><strong>Type:</strong> {{ ucwords(str_replace('_', ' ', $earning->type)) }}</p>
                <p><strong>Description:</strong> {{ $earning->description }}</p>
                <p><strong>Processed:</strong> {{ $earning->updated_at->format('M j, Y g:i A') }}</p>
                <p><strong>Status:</strong> <span style="color: #10b981; font-weight: bold;">Completed</span></p>
            </div>

            <p>Thank you for being part of the DDS network. Your earnings have been successfully transferred.</p>

            <p>If you have any questions about this payment, please don't hesitate to contact our support team.</p>

            <p>Best regards,<br>The DDS Team</p>
        </div>

        <div class="footer">
            <p>This is an automated message from the DDS system.</p>
        </div>
    </div>
</body>
</html>