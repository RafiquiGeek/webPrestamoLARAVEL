<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoAutomatico extends Model
{
    use HasFactory;

    protected $table = 'eventos_automaticos';

    protected $fillable = [
        'prestamo_id',
        'cuota_id',
        'operacion_id',
        'evento',
        'categoria',
        'datos_antes',
        'datos_despues',
        'metadatos',
        'resultado',
        'mensaje_humano',
        'usuario_id',
        'ip_address',
        'procesado_en',
        'tiempo_procesamiento',
    ];

    protected $casts = [
        'datos_antes' => 'array',
        'datos_despues' => 'array',
        'metadatos' => 'array',
        'procesado_en' => 'datetime',
        'tiempo_procesamiento' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(Cuota::class);
    }

    public function operacion(): BelongsTo
    {
        return $this->belongsTo(Operacion::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'usuario_id');
    }

    // Scopes para consultas frecuentes
    public function scopeExitosos($query)
    {
        return $query->where('resultado', 'exitoso');
    }

    public function scopeFallidos($query)
    {
        return $query->where('resultado', 'fallido');
    }

    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopeRecientes($query, $dias = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }

    public function scopePorPrestamo($query, $prestamoId)
    {
        return $query->where('prestamo_id', $prestamoId);
    }

    // Métodos de utilidad
    public function getIconoAttribute(): string
    {
        return match ($this->categoria) {
            'pagos' => '💰',
            'moras' => '📊',
            'estados' => '🔄',
            'calculos' => '🔧',
            'notificaciones' => '🔔',
            default => '🤖'
        };
    }

    public function getColorAttribute(): string
    {
        return match ($this->resultado) {
            'exitoso' => 'success',
            'parcial' => 'warning',
            'fallido' => 'danger',
            default => 'info'
        };
    }

    public function getDuracionHumanaAttribute(): string
    {
        if (! $this->tiempo_procesamiento) {
            return 'Instantáneo';
        }

        $ms = $this->tiempo_procesamiento;
        if ($ms < 1) {
            return '<1ms';
        } elseif ($ms < 1000) {
            return round($ms).'ms';
        } else {
            return round($ms / 1000, 2).'s';
        }
    }

    // Método estático para registrar eventos automáticos
    public static function registrar(
        Prestamo $prestamo,
        string $evento,
        string $categoria,
        array $datosDespues,
        ?array $datosAntes = null,
        ?array $metadatos = null,
        string $resultado = 'exitoso',
        ?Cuota $cuota = null,
        ?Operacion $operacion = null
    ): self {
        $inicio = microtime(true);

        $eventoObj = self::create([
            'prestamo_id' => $prestamo->id,
            'cuota_id' => $cuota?->id,
            'operacion_id' => $operacion?->id,
            'evento' => $evento,
            'categoria' => $categoria,
            'datos_antes' => $datosAntes,
            'datos_despues' => $datosDespues,
            'metadatos' => $metadatos,
            'resultado' => $resultado,
            'mensaje_humano' => self::generarMensajeHumano($evento, $datosDespues),
            'usuario_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'procesado_en' => now(),
            'tiempo_procesamiento' => (microtime(true) - $inicio) * 1000,
        ]);

        // Disparar evento de broadcast si es necesario
        event(new \App\Events\PrestamoEventoAutomatico(
            $prestamo,
            $evento,
            $datosDespues,
            auth()->id()
        ));

        return $eventoObj;
    }

    private static function generarMensajeHumano(string $evento, array $datos): string
    {
        return match ($evento) {
            'pago_registrado' => "💰 Pago de S/{$datos['monto']} procesado automáticamente",
            'cuota_vencida' => "⏰ Cuota #{$datos['numero_cuota']} marcada como vencida",
            'mora_generada' => "📊 {$datos['cantidad_moras']} mora(s) generada(s) automáticamente",
            'estado_actualizado' => "🔄 Estado cambiado de '{$datos['estado_anterior']}' a '{$datos['estado_nuevo']}'",
            'prestamo_finalizado' => '✅ Préstamo finalizado automáticamente',
            'recalculo_completo' => "🔧 Recálculo automático: {$datos['cuotas_actualizadas']} cuotas procesadas",
            default => "🤖 Evento automático: {$evento}"
        };
    }
}
