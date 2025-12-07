<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No Active Campaigns</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            margin: 0; 
            padding: 0; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container { 
            text-align: center; 
            background: white; 
            padding: 3rem 2rem; 
            border-radius: 16px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
            max-width: 400px; 
            margin: 1rem;
        }
        .icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }
        h1 { 
            color: #374151; 
            font-size: 1.75rem; 
            margin-bottom: 1rem; 
            font-weight: 600;
        }
        p { 
            color: #6b7280; 
            font-size: 1.1rem; 
            line-height: 1.6; 
            margin-bottom: 1.5rem;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            display: inline-block;
            font-weight: 500;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .footer {
            margin-top: 2rem;
            font-size: 0.875rem;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📅</div>
        <h1>No Active Campaigns</h1>
        <p>{{ $message ?? 'No active campaigns right now, try again later' }}</p>
        <a href="/" class="btn">Visit Daya</a>
        <div class="footer">
            <p>Thank you for using Daya</p>
        </div>
    </div>
</body>
</html>