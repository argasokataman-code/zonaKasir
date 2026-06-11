<?php

namespace App\Observers;

use App\Models\Admin;
use App\Tenant;
use Spatie\Activitylog\Models\Activity;

class TenantObserver
{
    public function creating(Tenant $tenant): void
    {
        activity()
            ->causedBy(Admin::find(session('admin_id')))
            ->performedOn($tenant)
            ->event('created')
            ->log('Tenant created');
    }

    public function updating(Tenant $tenant): void
    {
        if ($tenant->isDirty('is_active')) {
            $status = $tenant->is_active ? 'activated' : 'suspended';
            activity()
                ->causedBy(Admin::find(session('admin_id')))
                ->performedOn($tenant)
                ->event($status)
                ->withProperties([
                    'reason' => $tenant->suspension_reason,
                ])
                ->log("Tenant {$status}");
        }
    }

    public function deleted(Tenant $tenant): void
    {
        activity()
            ->causedBy(Admin::find(session('admin_id')))
            ->performedOn($tenant)
            ->event('deleted')
            ->log('Tenant deleted');
    }
}
