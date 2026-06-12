<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @mixin IdeHelperLedgerEntry
 */
class LedgerEntry extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    protected static $recordEvents = ['created'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function ledgerable()
    {
        return $this->morphTo();
    }
}
