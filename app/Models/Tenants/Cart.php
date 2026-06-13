<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperCart
 */
use App\Models\Traits\HasTenant;
class Cart extends Model
{
    use HasFactory, HasTenant;
}
