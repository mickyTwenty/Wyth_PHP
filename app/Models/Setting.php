<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\DBExtendedOperations;
use Cache;

class Setting extends Model
{
    use DBExtendedOperations;

    const CACHE_EXPIRE_TIME = 10; // in Minutes

    static $settings = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'config_key', 'config_value', 'is_encoded'
    ];

    public static function extract($key, $default=null) {
        $settings = self::extractAll();

        return array_key_exists( $key, $settings ) ? $settings[$key] : $default;
    }

    public static function extracts($key) {
        $settings = collect(self::extractAll());

        return $settings->only( $key );
    }

    public static function extractAll() {
        if ( null === self::$settings && null === self::$settings = Cache::get('app.setting') ) {
            $configs = self::all();

            foreach ($configs as $config) {
                self::$settings[ $config['config_key'] ] = $config['is_encoded'] == '1' ? json_decode($config['config_value'], true) : $config['config_value'];
            }

            Cache::put('app.setting', self::$settings, self::CACHE_EXPIRE_TIME);

        }

        return self::$settings;
    }

    public static function updateSetting($key, $value, $resetCache=true) {
        $update = Setting::where(['config_key' => $key])->update([
            'config_value' => $value,
        ]);

        if ( $resetCache )
            self::resetCache();

        return $update;
    }

    public static function updateSettingArray(array $data, $key='config_key') {
        self::batchUpdate( $data, $key ); // Method from DBExtendedOperations trait

        self::resetCache();

        return true;
    }

    private static function resetCache() {
        self::$settings = null;
        Cache::forget('app.setting');

        return new static;
    }
}
