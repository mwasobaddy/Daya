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
                    throw new Error('Failed to record scan')
                }

                const result = await response.json()

                // Redirect to campaign URL
                window.location.href = result.redirect_url

            } catch (error) {
                console.error('Scan processing failed:', error)
                // Fallback: redirect to a default page or show error
                window.location.href = '/scan-error'
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