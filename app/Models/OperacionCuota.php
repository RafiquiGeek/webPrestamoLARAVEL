<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperacionCuota extends Model
{
    use HasFactory;

    protected $table = 'operaciones_cuota';

    protected $fillable = [
        'cuota_id',
        'operacion_id',
        'monto_aplicado',
        'concepto',
        'observaciones',
        'aplicado_en',
    ];

    protected $casts = [
        'monto_aplicado' => 'decimal:2',
        'aplicado_en' => 'datetime',
    ];

    // Constantes para conceptos de pago
    const CONCEPTO_CAPITAL = 'capital';

    const CONCEPTO_INTERES = 'interes';

    const CONCEPTO_COMISION = 'comision';

    const CONCEPTO_IGV = 'igv';

    const CONCEPTO_PAGO_CUOTA = 'pago_cuota';

    const CONCEPTO_PAGO_MORA = 'pago_mora';

    const CONCEPTO_PAGO_GENERAL = 'pago_general';

    /**
     * Obtiene los conceptos válidos
     */
    public static function getConceptosValidos(): array
    {
        return [
            self::CONCEPTO_CAPITAL => 'Capital',
            self::CONCEPTO_INTERES => 'Interés',
            self::CONCEPTO_COMISION => 'Comisión',
            self::CONCEPTO_IGV => 'IGV',
            self::CONCEPTO_PAGO_CUOTA => 'Pago de Cuota',
            self::CONCEPTO_PAGO_MORA => 'Pago de Mora',
            self::CONCEPTO_PAGO_GENERAL => 'Pago General',
        ];
    }

    /**
     * Accesor para obtener el nombre legible del concepto
     */
    public function getConceptoNombreAttribute(): string
    {
        $conceptos = self::getConceptosValidos();

        return isset($conceptos[$this->concepto]) ? $conceptos[$this->concepto] : 'Desconocido';
    }

    public function cuota()
    {
        return $this->belongsTo(Cuota::class);
    }

    public function operacion()
    {
        return $this->belongsTo(Operacion::class);
    }

    /**
     * Scope para filtrar por concepto
     */
    public function scopePorConcepto($query, string $concepto)
    {
        return $query->where('concepto', $concepto);
    }

    /**
     * Scope para obtener operaciones no anuladas
     */
    public function scopeActivas($query)
    {
        return $query->whereHas('operacion', function ($q) {
            $q->where('estado', '!=', 'anulado');
        });
    }
}
