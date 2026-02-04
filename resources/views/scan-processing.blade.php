<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Processing Scan...</title>
    <!-- Load FingerprintJS from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@3/dist/fp.min.js"></script>
    <script>
        // Fingerprint generation function
        async function generateFingerprint() {
            try {
                // Initialize the agent
                const fp = await window.FingerprintJS.load()

                // Get the visitor identifier
                const result = await fp.get()

                return result.visitorId
            } catch (error) {
                console.error('Failed to generate device fingerprint:', error)
                return null
            }
        }

        async function processScan() {
            try {
                // Generate device fingerprint
                const fingerprint = await generateFingerprint()

                // Get scan parameters from URL
                const urlParams = new URLSearchParams(window.location.search)
                const dcdId = {{ $dcdId }}
                const campaignId = {{ $campaignId ?: 'null' }}
                const signature = '{{ $signature }}'

                // Record scan with fingerprint
                const scanData = {
                    dcd_id: dcdId,
                    campaign_id: campaignId,
                    fingerprint: fingerprint,
                    signature: signature
                }

                // Send scan data to API
                const response = await fetch('/api/scan/record-with-fingerprint', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(scanData)
                })

                if (!response.ok) {
                    // Try to get error details from response
                    let errorType = 'general_error';
                    try {
                        const errorData = await response.json();
                        errorType = errorData.error_type || 'general_error';
                    } catch (e) {
                        // If we can't parse JSON, keep default error type
                    }
                    throw new Error(`Failed to record scan: ${errorType}`);
                }

                const result = await response.json()

                // Redirect to campaign URL
                window.location.href = result.redirect_url

            } catch (error) {
                console.error('Scan processing failed:', error)
                // Extract error type from error message if available
                const errorMatch = error.message.match(/Failed to record scan: (\w+)/);
                const errorType = errorMatch ? errorMatch[1] : 'general_error';
                // Redirect to error page with error type
                window.location.href = `/scan-error?error_type=${errorType}`
            }
        }

        // Start processing when page loads
        document.addEventListener('DOMContentLoaded', processScan)
    </script>
</head>
<body>
    <div style="display: flex; justify-content: center; align-items: center; height: 100vh; font-family: Arial, sans-serif;">
        <div style="text-align: center;">
            <div style="border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 2s linear infinite; margin: 0 auto 20px;"></div>
            <p>Processing your scan...</p>
        </div>
    </div>
    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</body>
</html>