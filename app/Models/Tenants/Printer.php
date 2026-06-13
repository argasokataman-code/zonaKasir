<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperPrinter
 */
use App\Models\Traits\HasTenant;
class Printer extends Model
{
    use HasTenant;
    use HasFactory, HasTenant;

    protected $guarded = ['id'];
}
