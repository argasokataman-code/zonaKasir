<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    protected function shouldReturnJson($request, Throwable $e): bool
    {
        if ($request->expectsJson()) {
            return true;
        }

        if ($request->is('api/*')) {
            return true;
        }

        $contentType = $request->header('Content-Type', '');

        return str_contains($contentType, 'application/json');
    }

    /**
     * Render an exception into an HTTP response.
     *
     * Catches RouteNotFoundException for filament.tenant.auth.login on Vercel
     * where route-group loading may silently fail, and redirects to the login
     * page instead of showing an Internal Server Error.
     */
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof RouteNotFoundException
            && str_contains($e->getMessage(), 'filament.tenant.auth.login')) {
            return redirect('/member/login');
        }

        return parent::render($request, $e);
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
