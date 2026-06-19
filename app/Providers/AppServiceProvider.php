<?php

namespace App\Providers;

use App\Models\Tenants\User;
use App\Services\Tenants\DisbursementProvider;
use App\Services\Tenants\FlipPayoutProvider;
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
        $this->app->bind(DisbursementProvider::class, FlipPayoutProvider::class);

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

        config([
            'livewire.temporary_file_upload.disk' => config('upload.tmp_disk'),
            'livewire.temporary_file_upload.rules' => config('upload.livewire_rules'),
            'livewire.temporary_file_upload.preview_mimes' => config('upload.preview_mimes'),
            'livewire.temporary_file_upload.max_upload_time' => config('upload.max_upload_time'),
            'livewire.temporary_file_upload.directory' => config('upload.directory'),
        ]);
    }
}
