<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/__migrate', function () {
    $token = request()->query('token');
    if ($token !== 'zk-migrate-20260620') {
        abort(403);
    }

    Artisan::call('migrate', ['--force' => true, '--path' => 'database/migrations/tenant']);

    return response()->json([
        'success' => true,
        'output' => Artisan::output(),
    ]);
});
