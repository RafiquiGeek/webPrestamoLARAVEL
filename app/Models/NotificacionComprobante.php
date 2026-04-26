<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificacionComprobante extends Model
{
    use HasFactory;

    protected $table = 'notificaciones_comprobantes';

    protected $fillable = [
        'comprobante_id',
        'user_id',
        'tipo',
        'titulo',
        'mensaje',
        'datos_adicionales',
        'leido',
        'leido_at',
    ];

    protected $casts = [
        'datos_adicionales' => 'array',
        'leido' => 'boolean',
        'leido_at' => 'datetime',
    ];

    /**
     * Relación con Comprobante
     */
    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class);
    }

    /**
     * Relación con User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para notificaciones no leídas
     */
    public function scopeNoLeidas($query)
    {
        return $query->where('leido', false);
    }

    /**
     * Marcar como leída
     */
    public function marcarLeida()
    {
        $this->update([
            'leido' => true,
            'leido_at' => now(),
        ]);
    }

    /**
     * Crear notificación de éxito
     */
    public static function crearExito($comprobanteId, $userId, $mensaje, $datosAdicionales = [])
    {
        return self::create([
            'comprobante_id' => $comprobanteId,
            'user_id' => $userId,
            'tipo' => 'exito',
            'titulo' => '✅ Comprobante Enviado',
            'mensaje' => $mensaje,
            'datos_adicionales' => $datosAdicionales,
        ]);
    }

    /**
     * Crear notificación de fallo
     */
    public static function crearFallo($comprobanteId, $userId, $mensaje, $datosAdicionales = [])
    {
        return self::create([
            'comprobante_id' => $comprobanteId,
            'user_id' => $userId,
            'tipo' => 'fallo',
            'titulo' => '❌ Error en Comprobante',
            'mensaje' => $mensaje,
            'datos_adicionales' => $datosAdicionales,
        ]);
    }

    /**
     * Crear notificación de advertencia
     */
    public static function crearAdvertencia($comprobanteId, $userId, $mensaje, $datosAdicionales = [])
    {
        return self::create([
            'comprobante_id' => $comprobanteId,
            'user_id' => $userId,
            'tipo' => 'advertencia',
            'titulo' => '⚠️ Advertencia en Comprobante',
            'mensaje' => $mensaje,
            'datos_adicionales' => $datosAdicionales,
        ]);
    }
}
