<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $guarded = [];

    public static function getVal($key, $default = null)
    {
        $setting = static::first();
        if ($setting && isset($setting->{$key})) {
            return $setting->{$key};
        }
        return $default;
    }
}
