<?php

namespace App\Http\Controllers;

use App\Tenant;
use Illuminate\Http\Request;

class TenantExportController extends Controller
{
    public function csv()
    {
        $tenants = Tenant::with('domains')->latest()->get();

        $filename = 'tenants-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($tenants) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Email', 'Domain', 'Active', 'Business Type', 'Registered']);

            foreach ($tenants as $tenant) {
                fputcsv($handle, [
                    $tenant->id,
                    $tenant->data['full_name'] ?? '',
                    $tenant->data['email'] ?? '',
                    $tenant->domains->first()?->domain ?? '',
                    $tenant->is_active ? 'Yes' : 'No',
                    $tenant->data['business_type'] ?? '',
                    $tenant->created_at->format('d M Y H:i'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function destroy($id)
    {
        $tenant = Tenant::findOrFail($id);

        // Log activity BEFORE deleting (model must still exist in DB)
        activity()
            ->causedBy(auth('admin')->user())
            ->performedOn($tenant)
            ->event('deleted')
            ->log('Tenant deleted with full data cleanup');

        // Run inside tenant to clean up user data
        $tenant->run(function () {
            \App\Models\Tenants\User::query()->delete();
        });

        // Delete domain records
        $tenant->domains()->delete();

        // Delete the tenant (stancl/tenancy handles database deletion)
        $tenant->delete();

        return redirect()->back()->with('success', 'Tenant deleted completely.');
    }
}
