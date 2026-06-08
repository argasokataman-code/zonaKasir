<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\URL;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    protected function setUp(): void
    {
        parent::setUp();

        // Disable rate limiting for tests to avoid 429 responses during repeated requests
        $this->withoutMiddleware(ThrottleRequests::class);

        // If tenancy hasn't been initialized (by RefreshDatabaseWithTenant), default to central domain
        if (! tenant()) {
            $central = config('tenancy.central_domains')[0] ?? 'localhost';
            URL::forceRootUrl("http://{$central}");
            $_SERVER['HTTP_HOST'] = $central;
            $_SERVER['SERVER_NAME'] = $central;
        }
    }
}
