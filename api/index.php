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

    // Session driver: keep 'file' (from .env) — cookie driver would need manual
    // CookieJar flush in the callback handler (AddQueuedCookiesToResponse
    // middleware not in pipeline). File driver works for warm Vercel starts
    // where /tmp persists across requests within the same container.

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

// Google OAuth — bypass Laravel routing (tenant-web.php routes silently 404
// on Vercel/PHP 8.5 regardless of middleware-group position).
$path = parse_url($requestUri, PHP_URL_PATH);
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($path === '/auth/google/redirect') {
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
    if ($path === '/auth/google/callback') {
        $request = \Illuminate\Http\Request::capture();
        $app->instance('request', $request);
        // Start session so Auth::login() + session() helper work
        $session = $app->make(\Illuminate\Session\SessionManager::class)->driver();
        $session->start();
        $app->instance('session.store', $session);
        $request->setLaravelSession($session);
        // Run the callback controller
        $controller = $app->make(\App\Http\Controllers\Auth\GoogleController::class);
        $response = $controller->callback();
        // Save session data — for cookie driver this queues cookies on CookieJar
        $session->save();
        // Get all queued cookies (cookie driver writes data cookie here) and add
        // to response. Without this, queued cookies from CookieSessionHandler::write()
        // are lost because AddQueuedCookiesToResponse middleware is not in the pipeline.
        foreach ($app->make(\Illuminate\Contracts\Cookie\QueueingFactory::class)->getQueuedCookies() as $queuedCookie) {
            if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
                $response->headers->setCookie($queuedCookie);
            }
        }
        if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
            $response->send();
        } else {
            response($response)->send();
        }
        exit;
    }
}

// Safety net: all routes below use $router directly (not Route Facade) because
// the Route Facade silently fails on Vercel/PHP 8.5 during service provider boot
// (RouteServiceProvider::boot(), LivewireServiceProvider::boot(), etc.).
// The closure+require fix also fails — the Facade's internal resolution produces
// a different Router state than direct $app->make('router') during early bootstrap.
$router = $app->make('router');

// Login page (GET)
$router->get('/member/login', \App\Filament\Tenant\Pages\TenantLogin::class)
    ->middleware(['web', 'guest'])
    ->name('filament.tenant.auth.login');

// Login form POST (Livewire uses /livewire/update, but support direct POST too)
$router->post('/member/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store'])
    ->middleware(['web', 'guest'])
    ->name('filament.tenant.auth.login.post');

// Livewire component update — registered here so it exists BEFORE Livewire's
// own boot() runs (which would use the failing Route Facade). Livewire detects
// the existing route and skips re-registration.
$router->post('/livewire/update', [\Livewire\Mechanisms\HandleRequests\HandleRequests::class, 'handleUpdate'])
    ->middleware('web')
    ->name('default.livewire.update');

// Force the UrlGenerator to use the current RouteCollection so subsequent
// calls to route('filament.tenant.auth.login') from middleware find the route.
$app->make('url')->setRoutes($router->getRoutes());

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
