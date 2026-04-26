<?php

namespace App\Services;

use App\Models\Meta;
use App\Models\MetaComision;
use App\Models\MetaCumplimiento;
use App\Models\MetaConfiguracion;
use App\Models\Prestamo;
use App\Models\User;
use App\Enums\NivelCalificacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MetaComisionService
{
    public function calcularCumplimiento($asesorId, $anio, $mes)
    {
        $meta = Meta::where('asesor_id', $asesorId)
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->first();

        if (!$meta) {
            return null;
        }

        // 1. Préstamos originados en el mes
        $originados = Prestamo::whereHas('carterasAsesor', function ($q) use ($asesorId) {
                $q->where('asesor_id', $asesorId)->where('estado', 1);
            })
            ->whereYear('fecha_atencion', $anio)
            ->whereMonth('fecha_atencion', $mes)
            ->whereNotIn('estado', ['Anulado', 'Rechazado'])
            ->count();

        // 2. Cartera Activa (Vigentes + Morosos)
        $carteraActivaIds = DB::table('carteras_asesor')
            ->where('asesor_id', $asesorId)
            ->where('estado', 1)
            ->pluck('prestamo_id');

        $prestamosActivos = Prestamo::whereIn('id', $carteraActivaIds)
            ->whereIn('estado', ['Vigente', 'Moroso'])
            ->get();

        $vigentes = $prestamosActivos->where('estado', 'Vigente')->count();
        $morosos = $prestamosActivos->where('estado', 'Moroso')->count();
        $totalActivos = $prestamosActivos->count();

        // 3. Renovaciones del mes
        $renovaciones = Prestamo::whereHas('carterasAsesor', function ($q) use ($asesorId) {
                $q->where('asesor_id', $asesorId)->where('estado', 1);
            })
            ->whereYear('fecha_atencion', $anio)
            ->whereMonth('fecha_atencion', $mes)
            ->where('tipo_solicitud', 'like', '%Renovación%')
            ->count();

        // 4. Cálculos porcentuales
        $porcentajeCumplimiento = $meta->cantidad_objetivo > 0 
            ? ($originados / $meta->cantidad_objetivo) * 100 
            : 0;

        $porcentajeMorosidad = $totalActivos > 0 
            ? ($morosos / $totalActivos) * 100 
            : 0;

        // 5. Consecutividad y Nivel
        // Si cumple la meta actual (>=100%), sumar 1 por el mes en curso + los anteriores consecutivos
        // Si no cumple, la racha se rompe => 0
        $cumpleMetaActual = $porcentajeCumplimiento >= 100;
        $mesesConsecutivos = $cumpleMetaActual
            ? $this->calcularMesesConsecutivos($asesorId, $anio, $mes) + 1
            : 0;
        $nivel = $this->determinarNivel($mesesConsecutivos);

        // 6. Configuración de Morosidad
        $config = MetaConfiguracion::first() ?: new MetaConfiguracion(['umbral_morosidad' => 20]);
        $umbral = $config->umbral_morosidad;
        $penalizado = $porcentajeMorosidad > $umbral;

        // 7. Cálculo de Comisión
        $comisionBase = $this->calcularComisionBase($porcentajeCumplimiento, $nivel);
        $comisionFinal = $penalizado ? 0 : $comisionBase;

        // 8. Guardar/Actualizar cumplimiento
        return MetaCumplimiento::updateOrCreate(
            ['asesor_id' => $asesorId, 'anio' => $anio, 'mes' => $mes],
            [
                'meta_id' => $meta->id,
                'prestamos_originados' => $originados,
                'prestamos_vigentes' => $vigentes,
                'prestamos_morosos' => $morosos,
                'renovaciones' => $renovaciones,
                'porcentaje_cumplimiento' => $porcentajeCumplimiento,
                'porcentaje_morosidad' => $porcentajeMorosidad,
                'nivel_calificacion' => $nivel,
                'meses_consecutivos' => $mesesConsecutivos,
                'comision_base' => $comisionBase,
                'comision_final' => $comisionFinal,
                'penalizado_morosidad' => $penalizado,
                'porcentaje_morosidad_umbral' => $umbral,
                'fecha_calculo' => now(),
            ]
        );
    }

    public function calcularMesesConsecutivos($asesorId, $anio, $mes)
    {
        $consecutivos = 0;
        $fechaActual = Carbon::createFromDate($anio, $mes, 1)->subMonth();

        while (true) {
            $cumplimientoPrevio = MetaCumplimiento::where('asesor_id', $asesorId)
                ->where('anio', $fechaActual->year)
                ->where('mes', $fechaActual->month)
                ->first();

            if ($cumplimientoPrevio && $cumplimientoPrevio->porcentaje_cumplimiento >= 100) {
                $consecutivos++;
                $fechaActual->subMonth();
            } else {
                break;
            }

            // Seguridad para evitar bucles infinitos
            if ($consecutivos > 120) break; 
        }

        return $consecutivos;
    }

    public function determinarNivel($consecutivos)
    {
        if ($consecutivos >= 3) return NivelCalificacion::ORO;
        if ($consecutivos == 2) return NivelCalificacion::PLATA;
        if ($consecutivos == 1) return NivelCalificacion::BRONCE;
        return NivelCalificacion::NINGUNO;
    }

    public function calcularComisionBase($cumplimiento, $nivel)
    {
        $nivelKey = strtolower($nivel->value == 'Ninguno' ? 'base' : $nivel->value);
        
        $rango = MetaComision::where('nivel', $nivelKey)
            ->where('estado', true)
            ->where('porcentaje_minimo', '<=', $cumplimiento)
            ->where(function($q) use ($cumplimiento) {
                $q->whereNull('porcentaje_maximo')
                  ->orWhere('porcentaje_maximo', '>=', $cumplimiento);
            })
            ->first();

        return $rango ? $rango->monto_comision : 0;
    }

    public function calcularTodosLosCumplimientos($anio, $mes)
    {
        $asesoresConMeta = Meta::where('anio', $anio)
            ->where('mes', $mes)
            ->pluck('asesor_id');

        $resultados = [];
        foreach ($asesoresConMeta as $asesorId) {
            $resultados[] = $this->calcularCumplimiento($asesorId, $anio, $mes);
        }

        return $resultados;
    }
}
