<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FondoProvisional extends Model
{
    use HasFactory;

    protected $table = 'fondo_provisional';

    // Estados del fondo provisional
    const ESTADO_PENDIENTE = 'pendiente';

    const ESTADO_ENTREGADO = 'entregado';

    const ESTADO_RENDIDO = 'rendido';

    const ESTADO_EXONERADO = 'exonerado';

    protected $fillable = [
        'prestamo_id',
        'asesor_id',
        'operacion_id',
        'monto_capital',
        'porcentaje',
        'monto_fondo',
        'fecha_entrega',
        'estado',
        'observaciones',
        'fecha_rendicion',
        'rendido_por',
    ];

    protected $casts = [
        'monto_capital' => 'decimal:2',
        'porcentaje' => 'decimal:2',
        'monto_fondo' => 'decimal:2',
        'fecha_entrega' => 'date',
        'fecha_rendicion' => 'date',
    ];

    /**
     * Relación con el préstamo
     */
    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    /**
     * Relación con el asesor que recibió el fondo
     */
    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    /**
     * Relación con la operación registrada
     */
    public function operacion(): BelongsTo
    {
        return $this->belongsTo(Operacion::class);
    }

    /**
     * Relación con el usuario que rindió el fondo
     */
    public function rendidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rendido_por');
    }

    /**
     * Calcular automáticamente el monto del fondo (5% del capital)
     */
    public static function calcularMontoFondo($montoCapital, $porcentaje = 5.00)
    {
        return round(($montoCapital * $porcentaje) / 100, 2);
    }

    /**
     * Obtener el estado formateado
     */
    public function getEstadoTextoAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_ENTREGADO => 'Entregado',
            self::ESTADO_RENDIDO => 'Rendido',
            self::ESTADO_EXONERADO => 'Exonerado',
            default => 'Desconocido'
        };
    }

    /**
     * Obtener el color del badge según el estado
     */
    public function getEstadoBadgeClassAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE => 'badge-warning',
            self::ESTADO_ENTREGADO => 'badge-info',
            self::ESTADO_RENDIDO => 'badge-success',
            self::ESTADO_EXONERADO => 'badge-secondary',
            default => 'badge-secondary'
        };
    }

    /**
     * Scope para fondos pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    /**
     * Scope para fondos entregados
     */
    public function scopeEntregados($query)
    {
        return $query->where('estado', self::ESTADO_ENTREGADO);
    }

    /**
     * Scope para fondos rendidos
     */
    public function scopeRendidos($query)
    {
        return $query->where('estado', self::ESTADO_RENDIDO);
    }

    /**
     * Scope para fondos por asesor
     */
    public function scopePorAsesor($query, $asesorId)
    {
        return $query->where('asesor_id', $asesorId);
    }

    /**
     * Verificar si el fondo puede ser rendido
     */
    public function puedeSerRendido(): bool
    {
        return $this->estado === self::ESTADO_ENTREGADO;
    }

    /**
     * Marcar como rendido
     */
    public function marcarComoRendido($rendidoPor)
    {
        $this->update([
            'estado' => self::ESTADO_RENDIDO,
            'fecha_rendicion' => now()->toDateString(),
            'rendido_por' => $rendidoPor,
        ]);
    }
}
