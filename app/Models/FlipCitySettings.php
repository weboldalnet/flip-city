<?php

namespace Weboldalnet\FlipCity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $key
 * @property string $value
 */
class FlipCitySettings extends Model
{
    protected $table = 'flip_city_settings';
    protected $fillable = ['key', 'value'];

    public static function get($key, $default = null)
    {
        $cacheKey = 'flip_city_setting_v2_' . $key;
        $setting = Cache::remember($cacheKey, 3600, function () use ($key) {
            return self::where('key', $key)->first();
        });

        if ($setting instanceof \__PHP_Incomplete_Class || ($setting && !($setting instanceof self))) {
            Cache::forget($cacheKey);
            $setting = self::where('key', $key)->first();
            if ($setting) {
                Cache::put($cacheKey, $setting, 3600);
            }
        }

        if ($setting) {
            return $setting->value;
        }

        // Fallback to config
        return config('flip-city.' . $key, $default);
    }

    public static function set($key, $value)
    {
        $cacheKey = 'flip_city_setting_v2_' . $key;
        $setting = self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget($cacheKey);
        return $setting;
    }
}
