<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compromiso extends Model
{
    use HasFactory;

    // Estados actualizados
    const ESTADO_PENDIENTE = 'pendiente';

    const ESTADO_PAGADO = 'cumplido';     // Anteriormente COMPLETADO

    const ESTADO_POSTERGADO = 'incumplido'; // Anteriormente CANCELADO

    protected $fillable = [
        'prestamo_id',
        'gestion_id',
        'usuario_id',
        'fecha_compromiso_pago',
        'hora',
        'monto',
        'estado',
        'fecha_registro',
        'comentario',
        // Nuevos campos
        'monto_original',
        'monto_pendiente',
        'fecha_original',
        'motivo_postergacion',
        'veces_postergado',
        'compromiso_padre_id',
    ];

    protected $casts = [
        'fecha_compromiso_pago' => 'date',
        'fecha_original' => 'date',
        'fecha_registro' => 'datetime',
        'monto' => 'decimal:2',
        'monto_original' => 'decimal:2',
        'monto_pendiente' => 'decimal:2',
        'veces_postergado' => 'integer',
    ];

    /**
     * Relación con Prestamo
     */
    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    /**
     * Relación con todas las gestiones de este compromiso
     */
    public function gestiones(): HasMany
    {
        return $this->hasMany(Gestion::class);
    }

    /**
     * Relación con el compromiso padre (compromiso original)
     */
    public function compromisoPadre(): BelongsTo
    {
        return $this->belongsTo(Compromiso::class, 'compromiso_padre_id');
    }

    /**
     * Relación con compromisos hijos (postergaciones)
     */
    public function compromisosHijos(): HasMany
    {
        return $this->hasMany(Compromiso::class, 'compromiso_padre_id');
    }

    /**
     * Obtiene el historial completo del compromiso (padre + hijos)
     */
    public function historialCompleto()
    {
        if ($this->compromiso_padre_id) {
            // Si es un compromiso hijo, obtener desde el padre
            return $this->compromisoPadre->compromisosHijos()->with('gestiones')->orderBy('created_at')->get();
        } else {
            // Si es el padre, obtener todos los hijos
            return $this->compromisosHijos()->with('gestiones')->orderBy('created_at')->get();
        }
    }

    /**
     * Obtiene el compromiso raíz (el original)
     */
    public function compromisoRaiz()
    {
        return $this->compromiso_padre_id ? $this->compromisoPadre : $this;
    }

    /**
     * Verifica si el compromiso está vencido
     */
    public function getEstaVencidoAttribute(): bool
    {
        return $this->fecha_compromiso_pago < now()->toDateString() && $this->estado == self::ESTADO_PENDIENTE;
    }

    /**
     * Obtiene los días de vencimiento (negativo = vencido, positivo = por vencer)
     */
    public function getDiasVencimientoAttribute(): int
    {
        return now()->diffInDays($this->fecha_compromiso_pago, false);
    }

    /**
     * Obtiene el texto del estado
     */
    public function getEstadoTextoAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_PAGADO => 'Pagado',
            self::ESTADO_POSTERGADO => 'Postergado',
            default => 'Desconocido'
        };
    }

    /**
     * Scope para compromisos pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    /**
     * Scope para compromisos vencidos
     */
    public function scopeVencidos($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE)
            ->where('fecha_compromiso_pago', '<', now()->toDateString());
    }

    /**
     * Scope para compromisos por vencer en X días
     */
    public function scopePorVencer($query, $dias = 2)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE)
            ->whereBetween('fecha_compromiso_pago', [
                now()->toDateString(),
                now()->addDays($dias)->toDateString(),
            ]);
    }
}
