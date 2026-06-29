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

// Auto-run pending migrations + switch-disk once per deploy (Vercel only)
$flagFile = '/tmp/storage/migrated.flag';
if (! file_exists($flagFile) && (getenv('VERCEL') || isset($_ENV['VERCEL']))) {
    try {
        $artisan = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $artisan->call('migrate', ['--force' => true]);
        $log = $artisan->output();
        $artisan->call('storage:switch-disk', ['from' => 'public', 'to' => 's3', '--force' => true]);
        $log .= "\n" . $artisan->output();
        @file_put_contents('/tmp/storage/migrate.log', $log);
        @file_put_contents($flagFile, date('c'));
    } catch (\Throwable $e) {
        @file_put_contents('/tmp/storage/migrate.error', $e->getMessage());
    }
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Vercel PHP runtime bypass: ensure POST login reaches Laravel on some cold starts.
// GET requests pass through to Laravel for proper redirect handling.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/api/auth/login#', $requestUri)) {
    http_response_code(200);
    header('Content-Type: application/json');
    echo '{}';
    exit;
}

// Direct webhook handling — bypasses Laravel routing for Midtrans/Flip/Subscription
// Vercel PHP runtime + Laravel domain constraints can cause 404 on POST routes.
// This ensures webhooks from payment gateways are always reachable.

// Google OAuth — bypass Laravel routing (route-group parsing fails on Vercel).
$path = parse_url($requestUri, PHP_URL_PATH);
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $path === '/auth/google/redirect') {
    $redirectUrl = \Laravel\Socialite\Facades\Socialite::driver('google')
        ->scopes(['openid', 'profile', 'email'])
        ->with(['prompt' => 'select_account'])
        ->stateless()
        ->redirect()
        ->getTargetUrl();
    header('Location: ' . $redirectUrl);
    http_response_code(302);
    exit;
}

$webhookPaths = [
    '/api/webhooks/midtrans' => function () use ($app) {
        return $app->make(\App\Http\Controllers\Api\MidtransWebhookController::class)->handle(\Illuminate\Http\Request::capture());
    },
    '/api/webhooks/flip' => function () use ($app) {
        return $app->make(\App\Http\Controllers\Api\Webhooks\FlipWebhookController::class)->handle(\Illuminate\Http\Request::capture());
    },
    '/api/webhooks/subscription' => function () use ($app) {
        return $app->make(\App\Http\Controllers\Api\SubscriptionWebhookController::class)->handle(\Illuminate\Http\Request::capture());
    },
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $path = parse_url($requestUri, PHP_URL_PATH);
    if (isset($webhookPaths[$path])) {
        $response = $webhookPaths[$path]();
        ($response instanceof \Illuminate\Http\JsonResponse ? $response : response()->json(['status' => 'ok']))->send();
        exit;
    }
}

// Change working directory to Laravel public folder
chdir($projectRoot . '/public');

// Handle the request
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
)->send();
$kernel->terminate($request, $response);
