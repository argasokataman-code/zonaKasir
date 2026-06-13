<?php

namespace App\Models\Tenants;

use App\Models\Tenants\Traits\EnsuresLedgerBalance;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @mixin IdeHelperLedgerEntry
 */
use App\Models\Traits\HasTenant;
class LedgerEntry extends Model
{
    use LogsActivity, EnsuresLedgerBalance;

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
