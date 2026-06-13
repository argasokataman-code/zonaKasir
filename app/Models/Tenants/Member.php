<?php

namespace App\Models\Tenants;

use App\Models\Tenants\Receivable;
use App\Models\Tenants\Selling;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin IdeHelperMember
 */
class Member extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = ['id'];

    protected static $recordEvents = ['created', 'updated', 'deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(Receivable::class);
    }

    public function sellings(): HasMany
    {
        return $this->hasMany(Selling::class);
    }
}
