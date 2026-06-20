<?php

namespace App\Models\Tenants;

use Filament\Actions\Imports\Models\Import as ModelsImport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperImport
 */
use App\Models\Traits\HasTenant;
class Import extends ModelsImport
{
    use HasTenant;
    use HasFactory, HasTenant;

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
