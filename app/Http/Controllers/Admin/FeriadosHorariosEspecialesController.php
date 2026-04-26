<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AreaLaboral;
use App\Models\FeriadoHorarioEspecial;
use Illuminate\Http\Request;

class FeriadosHorariosEspecialesController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));

        // Obtener días especiales del mes
        $diasEspeciales = FeriadoHorarioEspecial::obtenerDiasEspecialesDelMes($year, $month);

        // Obtener todas las áreas laborales
        $areas = AreaLaboral::activas()->get();

        return view('admin.asistencia.feriados-horarios-especiales', compact(
            'diasEspeciales', 'areas', 'year', 'month'
        ));
    }

    public function obtenerEventosCalendario(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $areaId = $request->get('area_id');

        $query = FeriadoHorarioEspecial::activos()
            ->whereBetween('fecha', [$start, $end]);

        if ($areaId) {
            $query->paraArea($areaId);
        }

        $eventos = $query->get()->map(function ($dia) {
            return [
                'id' => $dia->id,
                'title' => $dia->nombre,
                'start' => $dia->fecha->format('Y-m-d'),
                'backgroundColor' => $dia->color,
                'borderColor' => $dia->color,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'tipo' => $dia->tipo,
                    'descripcion' => $dia->descripcion,
                    'hora_entrada' => $dia->hora_entrada,
                    'hora_salida' => $dia->hora_salida,
                ],
            ];
        });

        return response()->json($eventos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date|unique:feriados_horarios_especiales,fecha',
            'tipo' => 'required|in:feriado,medio_dia,especial',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'hora_entrada' => 'required_if:tipo,medio_dia,especial|nullable|date_format:H:i',
            'hora_salida' => 'required_if:tipo,medio_dia,especial|nullable|date_format:H:i|after:hora_entrada',
            'inicio_refrigerio' => 'nullable|date_format:H:i',
            'fin_refrigerio' => 'nullable|date_format:H:i|after:inicio_refrigerio',
            'aplicar_todas_areas' => 'boolean',
            'areas_laborales_ids' => 'required_if:aplicar_todas_areas,false|array',
            'areas_laborales_ids.*' => 'exists:areas_laborales,id',
        ]);

        $data = $request->all();

        // Si aplica a todas las áreas, limpiar el array de áreas específicas
        if ($request->boolean('aplicar_todas_areas')) {
            $data['areas_laborales_ids'] = null;
        }

        // Para feriados, limpiar horarios
        if ($request->tipo === 'feriado') {
            $data['hora_entrada'] = null;
            $data['hora_salida'] = null;
            $data['inicio_refrigerio'] = null;
            $data['fin_refrigerio'] = null;
        }

        FeriadoHorarioEspecial::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Día especial creado exitosamente.',
        ]);
    }

    public function show(FeriadoHorarioEspecial $feriadoHorarioEspecial)
    {
        return response()->json($feriadoHorarioEspecial);
    }

    public function update(Request $request, FeriadoHorarioEspecial $feriadoHorarioEspecial)
    {
        $request->validate([
            'fecha' => 'required|date|unique:feriados_horarios_especiales,fecha,'.$feriadoHorarioEspecial->id,
            'tipo' => 'required|in:feriado,medio_dia,especial',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'hora_entrada' => 'required_if:tipo,medio_dia,especial|nullable|date_format:H:i',
            'hora_salida' => 'required_if:tipo,medio_dia,especial|nullable|date_format:H:i|after:hora_entrada',
            'inicio_refrigerio' => 'nullable|date_format:H:i',
            'fin_refrigerio' => 'nullable|date_format:H:i|after:inicio_refrigerio',
            'aplicar_todas_areas' => 'boolean',
            'areas_laborales_ids' => 'required_if:aplicar_todas_areas,false|array',
            'areas_laborales_ids.*' => 'exists:areas_laborales,id',
        ]);

        $data = $request->all();

        // Si aplica a todas las áreas, limpiar el array de áreas específicas
        if ($request->boolean('aplicar_todas_areas')) {
            $data['areas_laborales_ids'] = null;
        }

        // Para feriados, limpiar horarios
        if ($request->tipo === 'feriado') {
            $data['hora_entrada'] = null;
            $data['hora_salida'] = null;
            $data['inicio_refrigerio'] = null;
            $data['fin_refrigerio'] = null;
        }

        $feriadoHorarioEspecial->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Día especial actualizado exitosamente.',
        ]);
    }

    public function destroy(FeriadoHorarioEspecial $feriadoHorarioEspecial)
    {
        $feriadoHorarioEspecial->delete();

        return response()->json([
            'success' => true,
            'message' => 'Día especial eliminado exitosamente.',
        ]);
    }

    public function toggle(FeriadoHorarioEspecial $feriadoHorarioEspecial)
    {
        $feriadoHorarioEspecial->update(['activo' => ! $feriadoHorarioEspecial->activo]);

        $estado = $feriadoHorarioEspecial->activo ? 'activado' : 'desactivado';

        return response()->json([
            'success' => true,
            'message' => "Día especial {$estado} exitosamente.",
        ]);
    }

    // Método para obtener feriados predefinidos del Perú
    public function obtenerFeriadosPredefinfidos($year)
    {
        $feriados = [
            ['fecha' => "{$year}-01-01", 'nombre' => 'Año Nuevo'],
            ['fecha' => "{$year}-05-01", 'nombre' => 'Día del Trabajador'],
            ['fecha' => "{$year}-07-28", 'nombre' => 'Fiestas Patrias - Independencia'],
            ['fecha' => "{$year}-07-29", 'nombre' => 'Fiestas Patrias - Batalla de Ayacucho'],
            ['fecha' => "{$year}-08-30", 'nombre' => 'Santa Rosa de Lima'],
            ['fecha' => "{$year}-10-08", 'nombre' => 'Combate de Angamos'],
            ['fecha' => "{$year}-11-01", 'nombre' => 'Todos los Santos'],
            ['fecha' => "{$year}-12-08", 'nombre' => 'Inmaculada Concepción'],
            ['fecha' => "{$year}-12-25", 'nombre' => 'Navidad'],
        ];

        return response()->json($feriados);
    }

    public function importarFeriadosPredefinfidos(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $feriados = $request->get('feriados', []);

        $creados = 0;
        $existentes = 0;

        foreach ($feriados as $feriado) {
            $existe = FeriadoHorarioEspecial::where('fecha', $feriado['fecha'])->exists();

            if (! $existe) {
                FeriadoHorarioEspecial::create([
                    'fecha' => $feriado['fecha'],
                    'tipo' => 'feriado',
                    'nombre' => $feriado['nombre'],
                    'descripcion' => 'Feriado nacional del Perú',
                    'aplicar_todas_areas' => true,
                    'activo' => true,
                ]);
                $creados++;
            } else {
                $existentes++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Se importaron {$creados} feriados. {$existentes} ya existían.",
            'creados' => $creados,
            'existentes' => $existentes,
        ]);
    }
}
