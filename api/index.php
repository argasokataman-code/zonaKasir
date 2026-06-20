<?php

declare(strict_types=1);

/**
 * Vercel Laravel Entry Point
 *
 * Bridges Vercel serverless PHP runtime to Laravel's public/index.php.
 * Sets up writable /tmp paths for logs and cache (Vercel is read-only).
 */

// Resolve the project root (one level up from api/)
$projectRoot = dirname(__DIR__);

// Register Composer autoloader
require $projectRoot . '/vendor/autoload.php';

// Suppress PDO deprecation warnings on PHP 8.5+
if (PHP_VERSION_ID >= 80500) {
    error_reporting(error_reporting() & ~E_DEPRECATED);
}

// Vercel: use /tmp for writable storage
if (isset($_ENV['VERCEL']) || getenv('VERCEL')) {
    $tmpDir = '/tmp/storage';
    @mkdir($tmpDir . '/logs', 0777, true);
    @mkdir($tmpDir . '/framework/cache', 0777, true);
    @mkdir($tmpDir . '/framework/views', 0777, true);
    @mkdir($tmpDir . '/framework/sessions', 0777, true);

    $app = require_once $projectRoot . '/bootstrap/app.php';
    $app->useStoragePath($tmpDir);
} else {
    $app = require_once $projectRoot . '/bootstrap/app.php';
}

// Auto-run pending migrations once per deploy (Vercel only)
$flagFile = '/tmp/storage/migrated.flag';
if (! file_exists($flagFile) && (getenv('VERCEL') || isset($_ENV['VERCEL']))) {
    try {
        $artisan = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $artisan->call('migrate', ['--force' => true]);
        $log = $artisan->output();
        @file_put_contents('/tmp/storage/migrate.log', $log);
        @file_put_contents($flagFile, date('c'));
    } catch (\Throwable $e) {
        @file_put_contents('/tmp/storage/migrate.error', $e->getMessage());
    }
}

// Handle Google OAuth discovery (Google Identity Services pings this)
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// TEMP: Diagnostic endpoint
$__check = $_GET['__check'] ?? '';
if ($__check === '1') {
    $lines = [];
    $lines[] = 'VERCEL: ' . (getenv('VERCEL') ?: 'not set');
    $lines[] = 'VERCEL_ENV: ' . (getenv('VERCEL_ENV') ?: 'not set');
    $lines[] = 'MIGRATE_FLAG: ' . (file_exists('/tmp/storage/migrated.flag') ? file_get_contents('/tmp/storage/migrated.flag') : 'NOT FOUND');
    $lines[] = 'MIGRATE_LOG: ' . (file_exists('/tmp/storage/migrate.log') ? substr(file_get_contents('/tmp/storage/migrate.log'), 0, 500) : 'NOT FOUND');
    $lines[] = 'MIGRATE_ERROR: ' . (file_exists('/tmp/storage/migrate.error') ? file_get_contents('/tmp/storage/migrate.error') : 'none');
    echo implode("\n", $lines);
    exit;
}

if (preg_match('#^/api/auth/login#', $requestUri)) {
    http_response_code(200);
    header('Content-Type: application/json');
    echo '{}';
    exit;
}

// Change working directory to Laravel public folder
chdir($projectRoot . '/public');

// Handle the request
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
)->send();
$kernel->terminate($request, $response);
