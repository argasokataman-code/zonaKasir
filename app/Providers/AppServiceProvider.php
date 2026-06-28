<?php

namespace App\Providers;

use App\Filesystem\SupabaseStorageAdapter;
use App\Models\Tenants\User;
use App\Services\PaymentSettingService;
use App\Services\Tenants\DisbursementProvider;
use App\Services\Tenants\FlipPayoutProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Filesystem;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Authenticatable::class, User::class);
        $this->app->bind(DisbursementProvider::class, FlipPayoutProvider::class);

        // Force HTTPS on Vercel (must be in register, before boot)
        if (isset($_ENV['VERCEL']) || getenv('VERCEL')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        if ($this->app->environment('local', 'development') && class_exists(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class)) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('supabase', function ($app, $config) {
            $adapter = new SupabaseStorageAdapter($config);

            return new FilesystemAdapter(
                new Filesystem($adapter),
                $adapter,
                $config,
            );
        });

        Builder::macro('filter', function (Request $request) {
            /* WIP:  <07-08-22, sheenazien8> */
            $columns = $request->filters;
            $query = $this;
            if ($columns) {
                foreach ($columns as $filterColumn) {
                    $column = $filterColumn['column'];

                    if ($filterColumn['condition'] == 'equals') {
                        $condition = '=';
                    } else {
                        $condition = $filterColumn['condition'];
                    }
                    if ($filterColumn['condition'] == 'like') {
                        $value = '%'.$filterColumn['value'].'%';
                    } else {
                        $value = $filterColumn['value'];
                    }
                    if (! $value) {
                        return $this;
                    }
                    $query = optional($this)->where($column, $condition, $value);
                }
            }

            return $columns ? $query : $this;
        });
        $mainPath = database_path('migrations');

        // Load all migrations from main path (old ones are disabled via empty up())
        $this->loadMigrationsFrom($mainPath);

        // Also load tenant migrations
        if (is_dir(database_path('migrations/tenant'))) {
            $this->loadMigrationsFrom(database_path('migrations/tenant'));
        }

        Feature::resolveScopeUsing(fn ($driver) => auth()->user()?->tenant_id);
        Feature::discover();

        app(PaymentSettingService::class)->boot();

        config([
            'livewire.temporary_file_upload.disk' => config('upload.tmp_disk'),
            'livewire.temporary_file_upload.rules' => config('upload.livewire_rules'),
            'livewire.temporary_file_upload.preview_mimes' => config('upload.preview_mimes'),
            'livewire.temporary_file_upload.max_upload_time' => config('upload.max_upload_time'),
            'livewire.temporary_file_upload.directory' => config('upload.directory'),
        ]);
    }
}
