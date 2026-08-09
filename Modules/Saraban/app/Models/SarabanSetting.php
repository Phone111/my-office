<?php

namespace Modules\Saraban\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ตั้งค่างานสารบรรณ (key-value) — เช่น active_year (ปีสารบรรณปัจจุบัน)
 */
class SarabanSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function put(string $key, $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
