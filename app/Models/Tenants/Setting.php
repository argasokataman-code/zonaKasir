<?php

namespace App\Models\Tenants;

use App\Models\Traits\HasTenant;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @mixin IdeHelperSetting
 */
class Setting extends Model
{
    use HasFactory;
    use HasTenant;

    protected $fillable = ['key', 'value'];

    public static function get($key, $default = null)
    {
        $tenantId = TenantContext::get();
        if (! $tenantId) {
            return $default;
        }
        $cacheKey = 'setting_'.$tenantId.'_'.$key;

        return Cache::remember($cacheKey, now()->addMinutes(3 * 60), function () use ($key, $default) {
            return self::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set($key, $value)
    {
        $tenantId = TenantContext::get();

        $old = self::where('key', $key)->first();

        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        activity()
            ->performedOn($old ?? new self)
            ->event('updated')
            ->withProperties([
                'attributes' => ['key' => $key, 'value' => $value],
                'old' => $old ? ['key' => $old->key, 'value' => $old->value] : null,
            ])
            ->log("Setting updated: {$key}");

        if ($tenantId) {
            Cache::put('setting_'.$tenantId.'_'.$key, $value, now()->addMinutes(3 * 60));
        }
    }
}
