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

    public static function getMultiple(array $keys): array
    {
        $tenantId = TenantContext::get();
        if (! $tenantId) {
            return array_fill_keys($keys, null);
        }

        $cached = [];
        $missing = [];
        foreach ($keys as $key) {
            $cacheKey = 'setting_'.$tenantId.'_'.$key;
            $val = Cache::get($cacheKey);
            if ($val !== null) {
                $cached[$key] = $val;
            } else {
                $missing[] = $key;
            }
        }

        if ($missing) {
            $rows = self::whereIn('key', $missing)->pluck('value', 'key')->toArray();
            foreach ($missing as $key) {
                $value = $rows[$key] ?? null;
                Cache::put('setting_'.$tenantId.'_'.$key, $value, now()->addMinutes(3 * 60));
                $cached[$key] = $value;
            }
        }

        return $cached;
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
