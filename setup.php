<?php
/**
 * Laravel Setup Script for cPanel
 * 
 * This script helps you set up your Laravel application on cPanel
 * when you don't have SSH access.
 * 
 * IMPORTANT SECURITY NOTES:
 * 1. Access this file via: https://daya.africa/DDS/setup.php
 * 2. DELETE THIS FILE after successful setup for security!
 * 3. This file should only be used during initial deployment
 */

// Set execution time limit
set_time_limit(300);

// Change to the application directory
chdir(__DIR__);

// Security check - only allow access in specific conditions
$setupKey = 'dds-setup-2025'; // Change this to a random string
$providedKey = $_GET['key'] ?? '';

if ($providedKey !== $setupKey) {
    die('Access denied. Provide the correct setup key as ?key=your-setup-key');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DDS Laravel Setup</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
        }
        .command {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #2980b9;
        }
        button.danger {
            background: #e74c3c;
        }
        button.danger:hover {
            background: #c0392b;
        }
        .checklist {
            list-style: none;
            padding: 0;
        }
        .checklist li {
            padding: 8px;
            margin: 5px 0;
            background: #ecf0f1;
            border-radius: 4px;
        }
        .checklist li:before {
            content: "✓ ";
            color: #27ae60;
            font-weight: bold;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 DDS Laravel Setup for cPanel</h1>
        
        <div class="warning">
            <strong>⚠️ SECURITY WARNING:</strong> Delete this file (setup.php) immediately after completing setup!
        </div>

        <?php
        $action = $_GET['action'] ?? 'home';
        
        switch ($action) {
            case 'check':
                checkEnvironment();
                break;
            case 'cache-clear':
                runCommand('Cache Clear', 'php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan event:clear && php artisan optimize:clear && php artisan queue:restart');
                break;
            case 'cache-build':
                runCommand('Build Cache', 'php artisan config:cache && php artisan route:cache && php artisan view:cache');
                break;
            case 'storage-link':
                runCommand('Storage Link', 'php artisan storage:link');
                break;
            case 'migrate':
                echo '<div class="error"><strong>Migration via web interface is risky!</strong> Consider using SSH or phpMyAdmin instead.</div>';
                if (isset($_GET['confirm'])) {
                    runCommand('Run Migrations', 'php artisan migrate --force');
                } else {
                    echo '<p>Are you sure you want to run migrations?</p>';
                    echo '<a href="?key=' . htmlspecialchars($setupKey) . '&action=migrate&confirm=yes"><button class="danger">Yes, Run Migrations</button></a>';
                    echo '<a href="?key=' . htmlspecialchars($setupKey) . '"><button>Cancel</button></a>';
                }
                break;
            case 'optimize':
                runCommand('Optimize Application', 'php artisan optimize');
                break;
            default:
                showHome($setupKey);
                break;
        }
        ?>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #7f8c8d;">
            <small>DDS - Digital Distribution System | Laravel Setup Assistant</small>
        </div>
    </div>
</body>
</html>

<?php

function showHome($setupKey) {
    ?>
    <div class="info">
        <strong>📋 Setup Instructions:</strong>
        <ol>
            <li>Ensure all files are uploaded to /public_html/DDS/</li>
            <li>Verify .env file has correct database credentials</li>
            <li>Run the environment check below</li>
            <li>Execute setup commands in order</li>
            <li>Test your application</li>
            <li>Delete this setup.php file!</li>
        </ol>
    </div>

    <h2>1. Environment Check</h2>
    <p>Check if your server meets Laravel requirements:</p>
    <a href="?key=<?= htmlspecialchars($setupKey) ?>&action=check">
        <button>Run Environment Check</button>
    </a>

    <h2>2. Setup Commands</h2>
    <p>Run these commands in order:</p>
    
    <h3>Clear All Caches</h3>
    <p>Clear configuration, route, and view caches (run this first if you have issues):</p>
    <a href="?key=<?= htmlspecialchars($setupKey) ?>&action=cache-clear">
        <button>Clear All Caches</button>
    </a>

    <h3>Build Optimized Caches</h3>
    <p>Build configuration, route, and view caches for better performance:</p>
    <a href="?key=<?= htmlspecialchars($setupKey) ?>&action=cache-build">
        <button>Build Caches</button>
    </a>

    <h3>Create Storage Link</h3>
    <p>Create symbolic link from public/storage to storage/app/public:</p>
    <a href="?key=<?= htmlspecialchars($setupKey) ?>&action=storage-link">
        <button>Create Storage Link</button>
    </a>

    <h3>Optimize Application</h3>
    <p>Run Laravel's optimize command:</p>
    <a href="?key=<?= htmlspecialchars($setupKey) ?>&action=optimize">
        <button>Optimize Application</button>
    </a>

    <h3>Run Database Migrations</h3>
    <p><strong>Warning:</strong> Only run if database is properly configured!</p>
    <a href="?key=<?= htmlspecialchars($setupKey) ?>&action=migrate">
        <button class="danger">Run Migrations</button>
    </a>

    <h2>3. Manual Checks</h2>
    <ul class="checklist">
        <li>Verify .env file exists and has correct values</li>
        <li>Check database credentials are correct</li>
        <li>Ensure storage/ and bootstrap/cache/ are writable (755)</li>
        <li>Confirm public/build/ directory exists with assets</li>
        <li>Test application at: https://daya.africa/DDS/</li>
    </ul>

    <div class="warning" style="margin-top: 30px;">
        <strong>🗑️ REMEMBER:</strong> Delete this setup.php file after completing setup!
    </div>
    <?php
}

function checkEnvironment() {
    echo '<h2>Environment Check Results</h2>';
    
    $checks = [
        'PHP Version' => [
            'required' => '8.1.0',
            'current' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '8.1.0', '>=')
        ],
        'PHP Extensions' => []
    ];
    
    $requiredExtensions = [
        'openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 
        'json', 'bcmath', 'fileinfo', 'mysqli'
    ];
    
    foreach ($requiredExtensions as $ext) {
        $checks['PHP Extensions'][$ext] = extension_loaded($ext);
    }
    
    // Check permissions
    $directories = [
        'storage/framework' => is_writable(__DIR__ . '/storage/framework'),
        'storage/logs' => is_writable(__DIR__ . '/storage/logs'),
        'bootstrap/cache' => is_writable(__DIR__ . '/bootstrap/cache')
    ];
    
    // Display PHP Version
    if ($checks['PHP Version']['status']) {
        echo '<div class="success">';
        echo '<strong>✓ PHP Version:</strong> ' . $checks['PHP Version']['current'] . ' (Required: ' . $checks['PHP Version']['required'] . '+)';
        echo '</div>';
    } else {
        echo '<div class="error">';
        echo '<strong>✗ PHP Version:</strong> ' . $checks['PHP Version']['current'] . ' (Required: ' . $checks['PHP Version']['required'] . '+)';
        echo '</div>';
    }
    
    // Display Extensions
    echo '<h3>PHP Extensions</h3>';
    foreach ($checks['PHP Extensions'] as $ext => $loaded) {
        if ($loaded) {
            echo '<div class="success">✓ ' . $ext . ' is loaded</div>';
        } else {
            echo '<div class="error">✗ ' . $ext . ' is NOT loaded</div>';
        }
    }
    
    // Display Directory Permissions
    echo '<h3>Directory Permissions</h3>';
    foreach ($directories as $dir => $writable) {
        if ($writable) {
            echo '<div class="success">✓ ' . $dir . ' is writable</div>';
        } else {
            echo '<div class="error">✗ ' . $dir . ' is NOT writable (needs chmod 755)</div>';
        }
    }
    
    // Check .env file
    if (file_exists(__DIR__ . '/.env')) {
        echo '<div class="success">✓ .env file exists</div>';
    } else {
        echo '<div class="error">✗ .env file is missing! Copy from .env.example</div>';
    }
    
    // Check vendor directory
    if (is_dir(__DIR__ . '/vendor')) {
        echo '<div class="success">✓ Composer dependencies installed</div>';
    } else {
        echo '<div class="error">✗ Vendor directory missing! Run composer install</div>';
    }
    
    echo '<div style="margin-top: 20px;">';
    echo '<a href="?key=' . htmlspecialchars($_GET['key']) . '"><button>Back to Home</button></a>';
    echo '</div>';
}

function runCommand($title, $command) {
    echo '<h2>' . htmlspecialchars($title) . '</h2>';
    echo '<div class="command">$ ' . htmlspecialchars($command) . '</div>';
    
    echo '<h3>Output:</h3>';
    echo '<div class="command">';
    
    $output = [];
    $returnVar = 0;
    exec($command . ' 2>&1', $output, $returnVar);
    
    if ($returnVar === 0) {
        echo '<div class="success" style="margin-bottom: 10px;">✓ Command executed successfully</div>';
    } else {
        echo '<div class="error" style="margin-bottom: 10px;">✗ Command failed with exit code: ' . $returnVar . '</div>';
    }
    
    echo htmlspecialchars(implode("\n", $output));
    echo '</div>';
    
    echo '<div style="margin-top: 20px;">';
    echo '<a href="?key=' . htmlspecialchars($_GET['key']) . '"><button>Back to Home</button></a>';
    echo '</div>';
}
