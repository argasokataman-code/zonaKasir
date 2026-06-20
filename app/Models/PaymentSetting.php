<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PaymentSetting extends Model
{
    protected $table = 'payment_settings';

    protected $guarded = ['id'];

    protected static array $encryptedKeys = [
        'server_key',
        'client_key',
        'secret_key',
        'webhook_token',
        'webhook_secret',
        'snapbi_client_secret',
        'snapbi_private_key',
        'snapbi_public_key',
    ];

    public static function encryptedKeys(): array
    {
        return static::$encryptedKeys;
    }

    public static function set(string $group, string $key, ?string $value): void
    {
        if (in_array($key, static::$encryptedKeys) && $value !== null) {
            $value = Crypt::encryptString($value);
        }

        static::updateOrCreate(
            ['group_name' => $group, 'key' => $key],
            ['value' => $value],
        );
    }

    public static function get(string $group, string $key, $default = null): ?string
    {
        $record = static::where('group_name', $group)->where('key', $key)->first();

        if (! $record) {
            return $default;
        }

        $value = $record->value;

        if ($value === null) {
            return $default;
        }

        if (in_array($key, static::$encryptedKeys)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }

        return $value;
    }

    public static function getGroup(string $group): array
    {
        return static::where('group_name', $group)
            ->get()
            ->mapWithKeys(fn ($item) => [$item->key => static::get($group, $item->key)])
            ->toArray();
    }

    public static function saveGroup(string $group, array $data): void
    {
        foreach ($data as $key => $value) {
            static::set($group, $key, $value);
        }
    }
}
