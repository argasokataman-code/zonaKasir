<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperNotification
 */
use App\Models\Traits\HasTenant;
class Notification extends Model
{
    use HasTenant;
    use HasFactory, HasTenant;
}
