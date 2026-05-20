<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ClinicSetting extends Model
{
    protected $fillable = ['key', 'value', 'description'];

    /**
     * Lee un setting. Si no existe, devuelve $default.
     * El value se guarda como JSON serializado para soportar strings, ints, bools, arrays.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = "clinic_setting:{$key}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($key, $default) {
            $row = static::where('key', $key)->first();
            if (! $row) {
                return $default;
            }
            $decoded = json_decode($row->value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $row->value;
        });
    }

    /**
     * Escribe un setting (upsert). Invalida la cache.
     */
    public static function setValue(string $key, mixed $value, ?string $description = null): self
    {
        $row = static::updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($value), 'description' => $description],
        );
        Cache::forget("clinic_setting:{$key}");

        return $row;
    }
}
