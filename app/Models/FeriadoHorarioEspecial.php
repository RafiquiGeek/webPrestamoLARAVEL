<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeriadoHorarioEspecial extends Model
{
    use HasFactory;

    protected $table = 'feriados_horarios_especiales';

    protected $fillable = [
        'fecha',
        'tipo',
        'nombre',
        'descripcion',
        'hora_entrada',
        'hora_salida',
        'inicio_refrigerio',
        'fin_refrigerio',
        'aplicar_todas_areas',
        'areas_laborales_ids',
        'activo',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_entrada' => 'datetime:H:i',
        'hora_salida' => 'datetime:H:i',
        'inicio_refrigerio' => 'datetime:H:i',
        'fin_refrigerio' => 'datetime:H:i',
        'aplicar_todas_areas' => 'boolean',
        'areas_laborales_ids' => 'array',
        'activo' => 'boolean',
    ];

    // Constantes para tipos
    const TIPO_FERIADO = 'feriado';

    const TIPO_MEDIO_DIA = 'medio_dia';

    const TIPO_ESPECIAL = 'especial';

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeFeriados($query)
    {
        return $query->where('tipo', self::TIPO_FERIADO);
    }

    public function scopeMedioDia($query)
    {
        return $query->where('tipo', self::TIPO_MEDIO_DIA);
    }

    public function scopeEspeciales($query)
    {
        return $query->where('tipo', self::TIPO_ESPECIAL);
    }

    public function scopeParaFecha($query, $fecha)
    {
        return $query->where('fecha', $fecha);
    }

    public function scopeParaArea($query, $areaId)
    {
        return $query->where(function ($q) use ($areaId) {
            $q->where('aplicar_todas_areas', true)
                ->orWhereJsonContains('areas_laborales_ids', $areaId);
        });
    }

    // Métodos de utilidad
    public function esFeriado()
    {
        return $this->tipo === self::TIPO_FERIADO;
    }

    public function esMedioDia()
    {
        return $this->tipo === self::TIPO_MEDIO_DIA;
    }

    public function esEspecial()
    {
        return $this->tipo === self::TIPO_ESPECIAL;
    }

    public function aplicaParaArea($areaId)
    {
        return $this->aplicar_todas_areas || in_array($areaId, $this->areas_laborales_ids ?? []);
    }

    public function getTipoTextoAttribute()
    {
        return match ($this->tipo) {
            self::TIPO_FERIADO => 'Feriado',
            self::TIPO_MEDIO_DIA => 'Medio Día',
            self::TIPO_ESPECIAL => 'Horario Especial',
            default => 'Desconocido'
        };
    }

    public function getColorAttribute()
    {
        return match ($this->tipo) {
            self::TIPO_FERIADO => '#dc3545',     // Rojo
            self::TIPO_MEDIO_DIA => '#ffc107',   // Amarillo
            self::TIPO_ESPECIAL => '#17a2b8',    // Azul
            default => '#6c757d'                 // Gris
        };
    }

    // Método estático para verificar si una fecha es especial
    public static function obtenerDiaEspecial($fecha, $areaId = null)
    {
        $query = static::activos()->paraFecha($fecha);

        if ($areaId) {
            $query->paraArea($areaId);
        }

        return $query->first();
    }

    // Método para obtener todos los días especiales de un mes
    public static function obtenerDiasEspecialesDelMes($year, $month, $areaId = null)
    {
        $fechaInicio = Carbon::create($year, $month, 1)->startOfMonth();
        $fechaFin = $fechaInicio->copy()->endOfMonth();

        $query = static::activos()
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($areaId) {
            $query->paraArea($areaId);
        }

        return $query->get();
    }
}
