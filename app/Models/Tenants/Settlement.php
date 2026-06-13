<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @mixin IdeHelperSettlement
 */
use App\Models\Traits\HasTenant;
class Settlement extends Model
{
    use HasTenant;
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    protected static $recordEvents = ['created', 'updated'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
