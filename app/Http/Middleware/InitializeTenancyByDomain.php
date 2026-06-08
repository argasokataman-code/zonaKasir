<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException;
use Stancl\Tenancy\Middleware\IdentificationMiddleware;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;
use Stancl\Tenancy\Tenancy;

class InitializeTenancyByDomain extends IdentificationMiddleware
{
    /** @var callable|null */
    public static $onFail;

    /** @var Tenancy */
    protected $tenancy;

    /** @var DomainTenantResolver */
    protected $resolver;

    public function __construct(Tenancy $tenancy, DomainTenantResolver $resolver)
    {
        $this->tenancy = $tenancy;
        $this->resolver = $resolver;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $this->initializeTenancy(
            $request, $next, $request->getHost()
        );
    }

    public function initializeTenancy($request, $next, ...$resolverArguments)
    {
        try {
            $host = $request->getHost();
            $adminDomains = array_filter((array) config('tenancy.admin_domains'));
            $centralDomains = array_filter((array) config('tenancy.central_domains'));

            if (! in_array($host, $adminDomains, true)) {
                if (empty($centralDomains) || ! in_array($host, $centralDomains, true)) {
                    $this->tenancy->initialize(
                        $this->resolver->resolve(...$resolverArguments)
                    );
                }
            }
        } catch (TenantCouldNotBeIdentifiedException $e) {
            $onFail = static::$onFail ?? function ($e) {
                throw $e;
            };

            return $onFail($e, $request, $next);
        }

        return $next($request);
    }
}
