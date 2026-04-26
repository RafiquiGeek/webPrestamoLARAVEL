<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class DatabaseConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'driver',
        'host',
        'port',
        'database',
        'username',
        'password',
        'charset',
        'collation',
        'prefix',
        'is_active',
        'is_sync_enabled',
        'sync_tables',
        'last_sync_at',
        'sync_errors',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_sync_enabled' => 'boolean',
        'sync_tables' => 'array',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    // Mutator para encriptar la contraseña
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Crypt::encryptString($value);
    }

    // Accessor para desencriptar la contraseña
    public function getPasswordAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (Exception $e) {
            return null;
        }
    }

    // Obtener configuración de conexión para Laravel
    public function getConnectionConfig()
    {
        return [
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->getPasswordAttribute($this->attributes['password']),
            'charset' => $this->charset,
            'collation' => $this->collation,
            'prefix' => $this->prefix,
        ];
    }

    // Probar la conexión
    public function testConnection()
    {
        try {
            // Configurar conexión temporal
            config(['database.connections.temp_test' => $this->getConnectionConfig()]);

            // Intentar conectar
            DB::connection('temp_test')->getPdo();

            // Limpiar configuración temporal
            config(['database.connections.temp_test' => null]);

            return [
                'success' => true,
                'message' => 'Conexión establecida exitosamente',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error de conexión: '.$e->getMessage(),
            ];
        }
    }

    // Scope para conexiones activas
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope para conexiones de sincronización
    public function scopeSyncEnabled($query)
    {
        return $query->where('is_sync_enabled', true);
    }
}
