<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Action Failed - DDS</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #fef2f2;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 500px;
            text-align: center;
        }
        .error-icon {
            color: #ef4444;
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .title {
            color: #1f2937;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .message {
            color: #6b7280;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .help-text {
            color: #9ca3af;
            font-size: 0.875rem;
            border-top: 1px solid #e5e7eb;
            padding-top: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">✕</div>
        <h1 class="title">Action Failed</h1>
        <p class="message">{{ $message }}</p>
        <div class="help-text">
            <p>If you believe this is an error, please contact support or try again later.</p>
            <p>Possible reasons: link expired, already used, or invalid action.</p>
        </div>
    </div>
</body>
</html>