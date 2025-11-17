<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Action Completed - DDS</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
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
        .success-icon {
            color: #10b981;
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
        .action-type {
            background: #f0fdf4;
            color: #166534;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✓</div>
        <h1 class="title">Action Completed Successfully</h1>
        <div class="action-type">{{ ucwords(str_replace('_', ' ', $action)) }}</div>
        <p class="message">{{ $message }}</p>
        @if(isset($result['dcd']) && $result['dcd'])
            <p><strong>DCD Assigned:</strong> {{ $result['dcd']['name'] }} ({{ $result['dcd']['email'] }})</p>
        @else
            <p><strong>Assignment:</strong> No DCD was automatically matched. Please assign a DCD manually from the admin dashboard.</p>
        @endif
        <p style="color: #9ca3af; font-size: 0.875rem;">
            This action has been processed and recorded in the system.
        </p>
    </div>
</body>
</html>