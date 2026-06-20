<?php

namespace App\Models\Tenants;

use App\Models\Traits\HasTenant;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @mixin IdeHelperPermission
 */
class Permission extends SpatiePermission
{
    use HasTenant;

    protected $guarded = ['id'];
}
