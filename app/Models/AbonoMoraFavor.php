<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbonoMoraFavor extends Model
{
    use HasFactory;

    protected $table = 'abonos_mora_favor';

    protected $fillable = [
        'cuota_id',
        'operacion_id',
        'monto_abonado',
        'monto_utilizado',
        'saldo_favor',
        'comentario',
        'estado',
        'fecha_abono',
    ];

    protected $casts = [
        'monto_abonado' => 'decimal:2',
        'monto_utilizado' => 'decimal:2',
        'saldo_favor' => 'decimal:2',
        'fecha_abono' => 'datetime',
    ];

    // Estados posibles
    const ESTADO_ACTIVO = 'activo';

    const ESTADO_UTILIZADO = 'utilizado';

    const ESTADO_ANULADO = 'anulado';

    const ESTADO_RESERVADO_CAJA = 'reservado_caja'; // Para saldos no aplicados en liquidación

    /**
     * Relación con la cuota
     */
    public function cuota(): BelongsTo
    {
        return $this->belongsTo(Cuota::class);
    }

    /**
     * Relación con la operación
     */
    public function operacion(): BelongsTo
    {
        return $this->belongsTo(Operacion::class);
    }

    /**
     * Utilizar saldo a favor para pagar moras reales
     *
     * @param  float  $montoMora  Monto de la mora real generada
     * @return float Monto utilizado del saldo a favor
     */
    public function utilizarSaldoFavor(float $montoMora): float
    {
        if ($this->estado !== self::ESTADO_ACTIVO || $this->saldo_favor <= 0) {
            return 0;
        }

        $montoAUtilizar = min($this->saldo_favor, $montoMora);

        $this->monto_utilizado += $montoAUtilizar;
        $this->saldo_favor -= $montoAUtilizar;

        // Si se agotó el saldo, cambiar estado
        if ($this->saldo_favor <= 0) {
            $this->estado = self::ESTADO_UTILIZADO;
        }

        $this->save();

        return $montoAUtilizar;
    }

    /**
     * Anular el abono a favor
     */
    public function anular(): bool
    {
        if ($this->monto_utilizado > 0) {
            throw new \Exception('No se puede anular un abono que ya ha sido utilizado parcialmente');
        }

        $this->estado = self::ESTADO_ANULADO;
        $this->save();

        return true;
    }

    /**
     * Scope para abonos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    /**
     * Scope para abonos con saldo disponible
     */
    public function scopeConSaldo($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO)
            ->where('saldo_favor', '>', 0);
    }

    /**
     * Scope para abonos reservados para caja
     */
    public function scopeReservadosCaja($query)
    {
        return $query->where('estado', self::ESTADO_RESERVADO_CAJA);
    }

    /**
     * Accessor para obtener el porcentaje utilizado
     */
    public function getPorcentajeUtilizadoAttribute(): float
    {
        if ($this->monto_abonado <= 0) {
            return 0;
        }

        return ($this->monto_utilizado / $this->monto_abonado) * 100;
    }
}
