<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenants\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin IdeHelperProductImage
 */
use App\Models\Traits\HasTenant;
class ProductImage extends Model
{
    use HasFactory, HasTenant;

    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getPathAttribute()
    {
        $value = $this->attributes['name'];
        $uploadDisk = config('filesystems.upload_disk');
        $driver = config('filesystems.disks.' . $uploadDisk . '.driver');

        if ($driver === 'local' || $driver === 'public') {
            return Storage::disk($uploadDisk)->path($value);
        }

        return UploadedFile::urlFromPath($value, $uploadDisk);
    }
}