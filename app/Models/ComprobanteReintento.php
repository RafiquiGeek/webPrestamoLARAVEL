<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComprobanteReintento extends Model
{
    use HasFactory;

    protected $table = 'comprobante_reintentos';

    protected $fillable = [
        'comprobante_id',
        'intentos',
        'max_intentos',
        'ultimo_error_code',
        'ultimo_error_mensaje',
        'proximo_intento',
        'estado',
        'procesado_at',
        'observaciones',
    ];

    protected $casts = [
        'proximo_intento' => 'datetime',
        'procesado_at' => 'datetime',
    ];

    /**
     * Relación con Comprobante
     */
    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class);
    }

    /**
     * Scope para obtener reintentos pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente')
            ->where('proximo_intento', '<=', now())
            ->where('intentos', '<', \DB::raw('max_intentos'));
    }

    /**
     * Incrementar contador de intentos
     */
    public function incrementarIntento($errorCode = null, $errorMensaje = null)
    {
        $this->intentos++;
        $this->ultimo_error_code = $errorCode;
        $this->ultimo_error_mensaje = $errorMensaje;

        // Calcular próximo intento con backoff exponencial
        // 5 min, 15 min, 30 min, 1 hora, 2 horas
        $esperas = [5, 15, 30, 60, 120];
        $indice = min($this->intentos - 1, count($esperas) - 1);
        $minutosEspera = $esperas[$indice];

        $this->proximo_intento = now()->addMinutes($minutosEspera);

        // Si ya alcanzó el máximo de intentos, marcar como fallido
        if ($this->intentos >= $this->max_intentos) {
            $this->estado = 'fallido';
            $this->procesado_at = now();
        }

        $this->save();
    }

    /**
     * Marcar como exitoso
     */
    public function marcarExitoso()
    {
        $this->update([
            'estado' => 'exitoso',
            'procesado_at' => now(),
        ]);
    }

    /**
     * Marcar como procesando
     */
    public function marcarProcesando()
    {
        $this->update([
            'estado' => 'procesando',
        ]);
    }

    /**
     * Marcar como cancelado
     */
    public function marcarCancelado($motivo = null)
    {
        $this->update([
            'estado' => 'cancelado',
            'procesado_at' => now(),
            'observaciones' => $motivo,
        ]);
    }
}
