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

// Auto-run pending migrations + clear cache once per deploy (Vercel only)
$flagFile = '/tmp/storage/migrated.flag';
if (! file_exists($flagFile) && (getenv('VERCEL') || isset($_ENV['VERCEL']))) {
    try {
        $artisan = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $artisan->call('route:clear');
        $log = 'route:clear OK' . "\n";
        $artisan->call('migrate', ['--force' => true]);
        $log .= $artisan->output();
        $artisan->call('filament:assets');
        $log .= "\n" . $artisan->output();
        @file_put_contents('/tmp/storage/migrate.log', $log);
        @file_put_contents($flagFile, date('c'));
    } catch (\Throwable $e) {
        @file_put_contents('/tmp/storage/migrate.error', $e->getMessage());
    }
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// TEMP: Show request URI + route list for debugging
if (strpos($requestUri, '__debug') !== false) {
    header('Content-Type: text/plain');
    echo 'REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
    echo 'SCRIPT_NAME: ' . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "\n";
    echo 'PHP_SELF: ' . ($_SERVER['PHP_SELF'] ?? 'NOT SET') . "\n";
    echo 'VERCEL: ' . (getenv('VERCEL') ?: 'NOT SET') . "\n";
    echo 'APP_URL: ' . config('app.url') . "\n";
    echo "\n=== ROUTES ===\n";
    $routes = app()->router->getRoutes()->getRoutes();
    foreach ($routes as $route) {
        $methods = implode('|', $route->methods());
        echo $methods . ' ' . $route->uri() . ' domain=' . ($route->domain() ?? '*') . "\n";
    }
    exit;
}

// TEMP: Diagnostic endpoint — list all tables for comparison
if (strpos($requestUri, '__tables') !== false) {

    try {
        $artisan = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $artisan->call('db:show');
        echo $artisan->output();
    } catch (\Throwable $e) {
        echo 'ERROR: ' . $e->getMessage();
    }
    exit(0);
}

if (preg_match('#^/api/auth/login#', $requestUri)) {
    http_response_code(200);
    header('Content-Type: application/json');
    echo '{}';
    exit;
}

// Direct webhook handling — bypasses Laravel routing for Midtrans/Flip/Subscription
// Vercel PHP runtime + Laravel domain constraints can cause 404 on POST routes.
// This ensures webhooks from payment gateways are always reachable.
$webhookHandlers = [
    '#^/api/webhooks/midtrans$#' => function () use ($app) {
        $controller = $app->make(\App\Http\Controllers\Api\MidtransWebhookController::class);
        $request = \Illuminate\Http\Request::capture();
        return $controller->handle($request);
    },
    '#^/api/webhooks/flip$#' => function () use ($app) {
        $controller = $app->make(\App\Http\Controllers\Api\Webhooks\FlipWebhookController::class);
        $request = \Illuminate\Http\Request::capture();
        return $controller->handle($request);
    },
    '#^/api/webhooks/subscription$#' => function () use ($app) {
        $controller = $app->make(\App\Http\Controllers\Api\SubscriptionWebhookController::class);
        $request = \Illuminate\Http\Request::capture();
        return $controller->handle($request);
    },
];
foreach ($webhookHandlers as $pattern => $handler) {
    if (preg_match($pattern, $requestUri) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $response = $handler();
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $response->send();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'ok']);
        }
        exit;
    }
}

// TEMP: Debug env
if (strpos($requestUri, '__env') !== false) {
    header('Content-Type: application/json');
    echo json_encode([
        'app_url' => config('app.url'),
        'app_env' => config('app.env'),
        'db_host' => config('database.connections.pgsql.host'),
        'routes_count' => count(app()->router->getRoutes()->getRoutes()),
    ]);
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
