<?php

namespace App\Logging;

use Monolog\LogRecord;

class TenantLogProcessor
{
    private static ?string $lastTenant = null;

    private static ?string $lastDomain = null;

    public function __invoke(LogRecord $record): LogRecord
    {
        try {
            if (tenancy()->initialized) {
                $tenant = tenancy()->tenant;

                if ($tenant) {
                    $record->extra['tenant'] = $tenant->id;

                    if (self::$lastDomain === null && $tenant->relationLoaded('domains')) {
                        $domain = $tenant->domains->first()?->domain;
                    } else {
                        $domain = self::$lastDomain;
                    }

                    if ($domain === null) {
                        $domain = $tenant->domains()->first()?->domain;
                        self::$lastDomain = $domain;
                    }

                    if ($domain) {
                        $record->extra['domain'] = $domain;
                    }

                    self::$lastTenant = $tenant->id;
                }
            } elseif (auth('admin')->check()) {
                $record->extra['admin'] = auth('admin')->user()?->email ?? 'admin';
            }
        } catch (\Throwable) {
            // Silently skip if tenancy not available
        }

        return $record;
    }
}
