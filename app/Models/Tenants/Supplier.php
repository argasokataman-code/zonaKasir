<?php

namespace App\Models\Tenants;

use App\Traits\Suppliers\HasSupplierForm;
use App\Traits\Suppliers\HasSupplierTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin IdeHelperSupplier
 */
class Supplier extends Model
{
    use HasFactory, HasSupplierForm, HasSupplierTable, SoftDeletes, LogsActivity;

    protected $guarded = ['id'];

    protected static $recordEvents = ['created', 'updated', 'deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
