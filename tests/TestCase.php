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

        // Ensure requests are made against the configured central domain
        $central = config('tenancy.central_domains')[0] ?? 'localhost';
        URL::forceRootUrl("http://{$central}");
        // Make PHPUnit HTTP requests include the correct Host header
        $_SERVER['HTTP_HOST'] = $central;
        $_SERVER['SERVER_NAME'] = $central;
    }
}
