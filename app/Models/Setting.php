<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'group'];

    /**
     * Get a setting value by key.
     */
    public static function get($key, $default = null)
    {
        $cacheKey = 'setting_' . $key;
        $missing = '__setting_cache_missing__';
        $cached = Cache::get($cacheKey, $missing);

        if ($cached !== $missing) {
            return $cached;
        }

        $setting = self::where('key', $key)->first();

        if ($setting) {
            Cache::forever($cacheKey, $setting->value);
            return $setting->value;
        }

        return $default ?? config($key);
    }

    /**
     * Set a setting value.
     */
    public static function set($key, $value, $group = 'general')
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget('setting_' . $key);

        return $setting;
    }
}
