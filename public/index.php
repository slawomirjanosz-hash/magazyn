<?php

// ========== ULTRA DEBUG MODE ==========
// This runs BEFORE anything else
echo "<!-- INDEX.PHP LOADED AT " . date('Y-m-d H:i:s') . " -->\n";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Catch FATAL ERRORS (these are NOT caught by set_exception_handler)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo "<pre style='background:#ff5722;color:#fff;padding:20px;font-family:monospace;'>";
        echo "💥 FATAL ERROR DETECTED 💥\n";
        echo "===========================\n\n";
        echo "Type: " . $error['type'] . "\n";
        echo "Message: " . $error['message'] . "\n";
        echo "File: " . $error['file'] . "\n";
        echo "Line: " . $error['line'] . "\n";
        echo "</pre>";
    }
});

// Catch regular errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "<pre style='background:#ff9800;color:#000;padding:10px;'>";
    echo "⚠️ PHP Error [$errno]: $errstr in $errfile on line $errline";
    echo "</pre>";
    return false;
});

// Catch uncaught exceptions
set_exception_handler(function($e) {
    http_response_code(500);
    echo "<pre style='background:#f44336;color:#fff;padding:20px;font-family:monospace;'>";
    echo "🔴 UNCAUGHT EXCEPTION 🔴\n";
    echo "========================\n\n";
    echo "Class: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString();
    echo "</pre>";
    exit(1);
});

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
try {
    echo "<!-- BOOTSTRAP START -->\n";
    $app = require_once __DIR__.'/../bootstrap/app.php';
    echo "<!-- BOOTSTRAP SUCCESS -->\n";
    
    echo "<!-- HANDLING REQUEST -->\n";
    $response = $app->handleRequest(Request::capture());
    echo "<!-- REQUEST HANDLED -->\n";
    
    // Send response
    $response->send();
    echo "<!-- RESPONSE SENT -->\n";
} catch (\Throwable $e) {
    // This catches EVERYTHING
    http_response_code(500);
    echo "<html><head><title>Bootstrap Error</title></head><body>";
    echo "<pre style='background:#e91e63;color:#fff;padding:30px;font-family:monospace;font-size:14px;'>";
    echo "🚨 LARAVEL BOOTSTRAP/HANDLE ERROR 🚨\n";
    echo "=====================================\n\n";
    echo "Exception: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString();
    echo "\n\n";
    echo "Environment Variables:\n";
    echo "APP_ENV: " . (getenv('APP_ENV') ?: 'NOT SET') . "\n";
    echo "APP_DEBUG: " . (getenv('APP_DEBUG') ?: 'NOT SET') . "\n";
    echo "DB_CONNECTION: " . (getenv('DB_CONNECTION') ?: 'NOT SET') . "\n";
    echo "</pre></body></html>";
    exit(1);
}
