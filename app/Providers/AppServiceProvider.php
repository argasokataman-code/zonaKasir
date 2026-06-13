<?php

namespace App\Providers;

use App\Models\Tenants\User;
use App\Services\Tenants\DisbursementProvider;
use App\Services\Tenants\MidtransPayoutProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

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
        $this->app->bind(DisbursementProvider::class, MidtransPayoutProvider::class);

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
        // Enforce runtime DB driver to be MySQL outside of automated tests.
        if (! $this->app->runningUnitTests()) {
            $default = config('database.default');
            $driver = config("database.connections.{$default}.driver");
            if ($driver !== 'mysql') {
                throw new \RuntimeException("Runtime database driver must be MySQL; current: {$driver} (connection: {$default}).");
            }
        }
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
        if (! config('tenancy.central_domains')[0]) {
            $mainPath = database_path('migrations');
            $directories = glob($mainPath.'/*', GLOB_ONLYDIR);

            // Exclude tenant migrations — they run via tenancy package, not globally
            $directories = array_filter($directories, fn ($dir) => basename($dir) !== 'tenant');

            $this->loadMigrationsFrom($directories);
        }

        Feature::resolveScopeUsing(fn ($driver) => null);
        Feature::discover();

        config([
            'livewire.temporary_file_upload.disk' => config('upload.tmp_disk'),
            'livewire.temporary_file_upload.rules' => config('upload.livewire_rules'),
            'livewire.temporary_file_upload.preview_mimes' => config('upload.preview_mimes'),
            'livewire.temporary_file_upload.max_upload_time' => config('upload.max_upload_time'),
            'livewire.temporary_file_upload.directory' => config('upload.directory'),
        ]);
    }
}
