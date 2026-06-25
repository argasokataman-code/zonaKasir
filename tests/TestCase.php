<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\URL;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        // Reset migration state for SQLite in-memory (each test gets fresh DB)
        RefreshDatabaseState::$migrated = false;

        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        URL::forceRootUrl('http://localhost');
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
    }
}
