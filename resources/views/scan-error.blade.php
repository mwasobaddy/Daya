<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Error - DDS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .error-container {
            background: white;
            border-radius: 16px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        .error-icon {
            width: 80px;
            height: 80px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 2.5rem;
        }

        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .error-message {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .error-details {
            background: #f9fafb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
            text-align: left;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.875rem;
            color: #374151;
            border: 1px solid #e5e7eb;
        }

        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            border: 1px solid #3b82f6;
        }

        .btn-primary:hover {
            background: #2563eb;
            border-color: #2563eb;
        }

        .btn-secondary {
            background: white;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

        .support-info {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .support-info a {
            color: #3b82f6;
            text-decoration: none;
        }

        .support-info a:hover {
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            .error-container {
                padding: 2rem;
            }

            .error-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        @if($errorType === 'system_error')
            <div class="error-icon">
                ⚠️
            </div>
        @endif

        <h1 class="error-title">
            @if($errorType === 'no_campaigns')
                No Campaigns Available
            @elseif($errorType === 'budget_exhausted')
                Campaign Budget Exhausted
            @elseif($errorType === 'system_error')
                System Error
            @else
                Scan Processing Failed
            @endif
        </h1>

        <p class="error-message">
            @if($errorType === 'no_campaigns')
                There are currently no active campaigns available for this Digital Content Distributor. Please try again later or contact support for more information.
            @elseif($errorType === 'budget_exhausted')
                The selected campaign has reached its budget limit and is no longer accepting new scans. Please try scanning a different QR code.
            @elseif($errorType === 'system_error')
                We encountered a system error while processing your scan. Our technical team has been notified and is working to resolve this issue.
            @else
                We encountered an issue while processing your QR code scan. This could be due to a network error, invalid QR code, or system maintenance.
            @endif
        </p>

        @if($errorType === 'no_campaigns' || $errorType === 'budget_exhausted')
            <div class="error-details">
                <strong>Details:</strong>
                <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                    @if($errorType === 'no_campaigns')
                        <li>No active campaigns are currently assigned to this DCD</li>
                        <li>Campaigns may be scheduled for future dates or have ended</li>
                        <li>Please check back later for new campaign opportunities</li>
                    @elseif($errorType === 'budget_exhausted')
                        <li>The campaign has reached its maximum scan limit</li>
                        <li>All allocated budget has been utilized</li>
                        <li>The campaign has been automatically completed</li>
                    @endif
                </ul>
                <p style="margin-top: 1rem; font-weight: bold;">
                    Timestamp: {{ now()->format('Y-m-d H:i:s T') }}
                </p>
            </div>
        @else
            <div class="error-details">
                <strong>Possible causes:</strong>
                <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                    <li>Network connectivity issues</li>
                    <li>Invalid or expired QR code</li>
                    <li>Campaign no longer active</li>
                    <li>System temporarily unavailable</li>
                </ul>
            </div>
        @endif

        <div class="error-actions">
            {{-- <a href="javascript:window.history.back()" class="btn btn-primary">
                🔄 Try Again
            </a> --}}
            <a href="{{ url('/') }}" class="btn btn-secondary">
                🏠 Go Home
            </a>
        </div>

        <div class="support-info">
            <p>
                @if($errorType === 'no_campaigns')
                    Please check back later for new campaign opportunities. If you believe this is an error, contact our support team.
                @elseif($errorType === 'budget_exhausted')
                    Try scanning a different QR code from another Digital Content Distributor. Contact support if you need assistance.
                @else
                    If this problem persists, please contact our support team at
                @endif
                <a href="mailto:support@daya.com">support@daya.com</a>
            </p>
            @if($errorType !== 'no_campaigns' && $errorType !== 'budget_exhausted')
                <p style="margin-top: 0.5rem;">
                    <small>Error occurred at: {{ now()->format('Y-m-d H:i:s T') }}</small>
                </p>
            @endif
        </div>
    </div>

    <script>
        // Auto-retry logic for transient errors only
        const urlParams = new URLSearchParams(window.location.search);
        const retry = urlParams.get('retry');
        const errorType = urlParams.get('error_type') || 'general_error';

        // Only retry for transient errors (general_error, system_error)
        if (!retry && window.history.length > 1 && (errorType === 'general_error' || errorType === 'system_error')) {
            // Add retry parameter and redirect back after 3 seconds
            setTimeout(() => {
                const currentUrl = new URL(window.location);
                currentUrl.searchParams.set('retry', '1');
                window.location.href = currentUrl.toString();
            }, 3000);
        }
    </script>
</body>
</html>