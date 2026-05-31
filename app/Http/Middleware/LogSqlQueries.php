<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogSqlQueries
{
    /**
     * Handle an incoming request and log SQL queries when enabled.
     */
    public function handle(Request $request, Closure $next)
    {
        $enabled = env('ENABLE_QUERY_LOG', app()->environment('local'));

        if ($enabled) {
            DB::listen(function ($query) {
                try {
                    $sql = $query->sql;
                    // Replace ? with bindings for readability
                    foreach ($query->bindings as $binding) {
                        $value = is_numeric($binding) ? $binding : "'".str_replace("'", "\\'", $binding)."'";
                        $sql = preg_replace('/\?/', $value, $sql, 1);
                    }

                    $entry = sprintf("[%s] %s (%.2f ms)\n", now()->toDateTimeString(), $sql, $query->time);
                    file_put_contents(storage_path('logs/sql-queries.log'), $entry, FILE_APPEND);
                } catch (\Throwable $e) {
                    // ignore logging errors to avoid breaking requests
                }
            });
        }

        return $next($request);
    }
}
