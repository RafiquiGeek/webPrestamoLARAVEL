<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'value',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Obtener el valor de una configuración por su clave
     */
    public static function getValue(string $key, $default = null)
    {
        $config = self::where('key', $key)->where('is_active', true)->first();

        return $config ? $config->value : $default;
    }

    /**
     * Establecer o actualizar una configuración
     */
    public static function setValue(string $key, $value, ?string $name = null, ?string $description = null)
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'name' => $name ?: $key,
                'description' => $description,
                'is_active' => true,
            ]
        );
    }
}
