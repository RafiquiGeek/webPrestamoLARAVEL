<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AsignacionAreaEmpleado;
use App\Models\HorarioTrabajo;
use App\Models\RegistroAsistencia;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AsistenciaController extends Controller
{
    /**
     * Registrar entrada
     */
    public function registrarEntrada(Request $request): JsonResponse
    {
        try {
            $usuario = auth()->user();
            $fechaHoy = Carbon::now()->format('Y-m-d');

            // Verificar si ya tiene registro de entrada hoy
            $registroExistente = RegistroAsistencia::where('user_id', $usuario->id)
                ->whereDate('fecha', $fechaHoy)
                ->whereNotNull('hora_entrada')
                ->first();

            if ($registroExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tiene registrada la entrada para el día de hoy',
                ], 400);
            }

            // Obtener asignación activa del usuario
            $asignacion = AsignacionAreaEmpleado::where('user_id', $usuario->id)
                ->where('activo', true)
                ->first();

            if (! $asignacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes una asignación de área laboral activa',
                ], 400);
            }

            DB::beginTransaction();

            // Obtener horario desde la asignación (como en Admin)
            $asignacionConHorario = AsignacionAreaEmpleado::with('horarioTrabajo')
                ->where('user_id', $usuario->id)
                ->where('activo', true)
                ->first();

            $horario = $asignacionConHorario?->horarioTrabajo;
            $horaActual = Carbon::now();
            $horaActualStr = $horaActual->format('H:i:s');

            // Determinar estado de entrada
            $estado = 'puntual';
            if ($horario && method_exists($horario, 'esTardanza') && $horario->esTardanza($horaActualStr)) {
                $estado = 'tardanza';
            }

            $registro = RegistroAsistencia::create([
                'user_id' => $usuario->id,
                'asignacion_id' => $asignacion->id,
                'fecha' => $fechaHoy,
                'hora_entrada' => $horaActual,
                'latitud_entrada' => $request->latitud_entrada,
                'longitud_entrada' => $request->longitud_entrada,
                'observaciones' => $request->observaciones,
                'estado_entrada' => $estado,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Entrada registrada exitosamente',
                'data' => [
                    'registro' => $registro->load('asignacion.areaLaboral'),
                    'estado' => $estado,
                    'hora_entrada' => $horaActual->format('H:i:s'),
                    'mensaje_estado' => $this->getMensajeEstado($estado),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error en registrarEntrada: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la entrada',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Registrar salida
     */
    public function registrarSalida(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ubicacion_salida' => 'nullable|string|max:500',
                'latitud_salida' => 'nullable|numeric',
                'longitud_salida' => 'nullable|numeric',
                'observaciones' => 'nullable|string|max:500',
                'foto_salida' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $usuario = auth()->user();
            $fechaHoy = Carbon::now()->format('Y-m-d');

            \Log::info('Intentando marcar salida', [
                'user_id' => $usuario->id,
                'fecha_hoy' => $fechaHoy,
            ]);

            // Buscar registro de entrada del día
            $registro = RegistroAsistencia::where('user_id', $usuario->id)
                ->where('fecha', $fechaHoy)
                ->whereNotNull('hora_entrada')
                ->whereNull('hora_salida')
                ->first();

            if (! $registro) {
                $todosRegistros = RegistroAsistencia::where('user_id', $usuario->id)
                    ->where('fecha', $fechaHoy)
                    ->get();
                \Log::warning('No se encontró registro para salida', [
                    'user_id' => $usuario->id,
                    'fecha_hoy' => $fechaHoy,
                    'total_registros_hoy' => $todosRegistros->count(),
                    'registros' => $todosRegistros->toArray(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró registro de entrada para el día de hoy o ya registró la salida',
                ], 400);
            }

            \Log::info('Registro encontrado para salida', ['registro_id' => $registro->id]);

            DB::beginTransaction();

            $horaActual = Carbon::now();
            $estadoSalida = $this->calcularEstadoSalida($horaActual, $registro);

            // Calcular horas trabajadas
            $fechaStr = $registro->fecha instanceof Carbon ? $registro->fecha->format('Y-m-d') : $registro->fecha;
            $horaEntrada = Carbon::parse($fechaStr . ' ' . $registro->hora_entrada);
            $horasTrabajadas = $horaEntrada->diffInHours($horaActual);
            $minutosTrabajados = $horaEntrada->diffInMinutes($horaActual);

            $updated = $registro->update([
                'hora_salida' => $horaActual->format('H:i:s'),
                'latitud_salida' => $request->latitud_salida,
                'longitud_salida' => $request->longitud_salida,
                'estado_salida' => $estadoSalida,
            ]);

            \Log::info('Update salida completado', ['updated' => $updated, 'registro_id' => $registro->id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Salida registrada exitosamente',
                'data' => [
                    'registro' => $registro->fresh(['asignacion.areaLaboral']),
                    'estado_salida' => $estadoSalida,
                    'hora_salida' => $horaActual->format('H:i:s'),
                    'horas_trabajadas' => $horasTrabajadas,
                    'tiempo_trabajado' => $this->formatearTiempoTrabajado($minutosTrabajados),
                    'mensaje_estado' => $this->getMensajeEstado($estadoSalida),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la salida',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener asistencia del día actual
     */
    public function asistenciaHoy(): JsonResponse
    {
        try {
            $usuario = auth()->user();
            $fechaHoy = Carbon::now()->format('Y-m-d');

            $registro = RegistroAsistencia::with('asignacion.areaLaboral')
                ->where('user_id', $usuario->id)
                ->whereDate('fecha', $fechaHoy)
                ->first();

            if (! $registro) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'tiene_registro' => false,
                        'puede_marcar_entrada' => true,
                        'puede_marcar_salida' => false,
                        'areas_asignadas' => $this->getAreasAsignadas($usuario->id),
                    ],
                ]);
            }

            $puedeMarcarSalida = $registro->hora_entrada && ! $registro->hora_salida;
            $tiempoTrabajadoHoy = null;

            if ($registro->hora_entrada) {
                $tiempoActual = $registro->hora_salida ?? Carbon::now();
                $minutos = $registro->hora_entrada->diffInMinutes($tiempoActual);
                $tiempoTrabajadoHoy = $this->formatearTiempoTrabajado($minutos);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'tiene_registro' => true,
                    'registro' => $registro,
                    'puede_marcar_entrada' => false,
                    'puede_marcar_salida' => $puedeMarcarSalida,
                    'tiempo_trabajado_hoy' => $tiempoTrabajadoHoy,
                    'estado_actual' => $registro->hora_salida ? 'salida_registrada' : 'en_trabajo',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener asistencia de hoy',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Historial de asistencia
     */
    public function historial(Request $request): JsonResponse
    {
        try {
            $usuario = auth()->user();

            $query = RegistroAsistencia::with('asignacion.areaLaboral')
                ->where('user_id', $usuario->id);

            // Filtros
            if ($request->has('fecha_desde')) {
                $query->where('fecha', '>=', $request->fecha_desde);
            }

            if ($request->has('fecha_hasta')) {
                $query->where('fecha', '<=', $request->fecha_hasta);
            }

            if ($request->has('area_laboral_id')) {
                $query->where('area_laboral_id', $request->area_laboral_id);
            }

            // Por defecto, últimos 30 días
            if (! $request->has('fecha_desde') && ! $request->has('fecha_hasta')) {
                $query->where('fecha', '>=', Carbon::now()->subDays(30));
            }

            $perPage = $request->get('per_page', 15);
            $registros = $query->orderBy('fecha', 'desc')->paginate($perPage);

            // Agregar información calculada
            $registros->getCollection()->transform(function ($registro) {
                if ($registro->hora_entrada && $registro->hora_salida) {
                    $minutos = $registro->hora_entrada->diffInMinutes($registro->hora_salida);
                    $registro->tiempo_trabajado_formateado = $this->formatearTiempoTrabajado($minutos);
                }

                return $registro;
            });

            return response()->json([
                'success' => true,
                'data' => $registros->items(),
                'pagination' => [
                    'current_page' => $registros->currentPage(),
                    'last_page' => $registros->lastPage(),
                    'per_page' => $registros->perPage(),
                    'total' => $registros->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial de asistencia',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Estadísticas de asistencia
     */
    public function estadisticas(Request $request): JsonResponse
    {
        try {
            $usuario = auth()->user();
            $fechaInicio = $request->get('fecha_inicio', Carbon::now()->startOfMonth());
            $fechaFin = $request->get('fecha_fin', Carbon::now()->endOfMonth());

            $registros = RegistroAsistencia::where('user_id', $usuario->id)
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->get();

            $estadisticas = [
                'periodo' => [
                    'inicio' => $fechaInicio,
                    'fin' => $fechaFin,
                ],
                'total_dias_registrados' => $registros->count(),
                'dias_completos' => $registros->whereNotNull('hora_salida')->count(),
                'dias_solo_entrada' => $registros->whereNotNull('hora_entrada')->whereNull('hora_salida')->count(),
                'entradas_puntuales' => $registros->where('estado_entrada', 'puntual')->count(),
                'entradas_tardias' => $registros->where('estado_entrada', 'tardanza')->count(),
                'salidas_tempranas' => $registros->where('estado_salida', 'temprano')->count(),
                'horas_totales_trabajadas' => $registros->sum('horas_trabajadas'),
                'minutos_totales_trabajados' => $registros->sum('minutos_trabajados'),
                'promedio_horas_diarias' => $registros->count() > 0 ?
                    round($registros->sum('horas_trabajadas') / $registros->count(), 2) : 0,
            ];

            // Asistencia por día de la semana
            $porDiaSemana = $registros->groupBy(function ($registro) {
                return Carbon::parse($registro->fecha)->format('l');
            })->map->count();

            // Horarios más frecuentes de entrada
            $horariosEntrada = $registros->whereNotNull('hora_entrada')
                ->groupBy(function ($registro) {
                    return Carbon::parse($registro->hora_entrada)->format('H:00');
                })->map->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'estadisticas_generales' => $estadisticas,
                    'asistencia_por_dia_semana' => $porDiaSemana,
                    'horarios_entrada_frecuentes' => $horariosEntrada,
                    'tiempo_total_formateado' => $this->formatearTiempoTrabajado($estadisticas['minutos_totales_trabajados']),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas de asistencia',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Registrar inicio de refrigerio
     */
    public function registrarInicioRefrigerio(Request $request): JsonResponse
    {
        try {
            $usuario = auth()->user();
            $fechaHoy = Carbon::now()->format('Y-m-d');

            $registro = RegistroAsistencia::where('user_id', $usuario->id)
                ->whereDate('fecha', $fechaHoy)
                ->whereNotNull('hora_entrada')
                ->whereNull('hora_salida')
                ->first();

            if (! $registro) {
                return response()->json([
                    'success' => false,
                    'message' => 'Primero debes marcar tu entrada',
                ], 400);
            }

            if ($registro->inicio_refrigerio && ! $registro->fin_refrigerio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tienes un refrigerio en curso',
                ], 400);
            }

            $horaActual = Carbon::now();

            $registro->update([
                'inicio_refrigerio' => $horaActual->format('H:i:s'),
                'latitud_inicio_refrigerio' => $request->latitud,
                'longitud_inicio_refrigerio' => $request->longitud,
                'estado_refrigerio' => 'iniciado',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Refrigerio iniciado',
                'data' => [
                    'hora_inicio' => $horaActual->format('H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar refrigerio',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Registrar fin de refrigerio
     */
    public function registrarFinRefrigerio(Request $request): JsonResponse
    {
        try {
            $usuario = auth()->user();
            $fechaHoy = Carbon::now()->format('Y-m-d');

            $registro = RegistroAsistencia::where('user_id', $usuario->id)
                ->whereDate('fecha', $fechaHoy)
                ->whereNotNull('inicio_refrigerio')
                ->whereNull('fin_refrigerio')
                ->first();

            if (! $registro) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un refrigerio iniciado',
                ], 400);
            }

            $horaActual = Carbon::now();
            $fechaStr = $registro->fecha instanceof Carbon ? $registro->fecha->format('Y-m-d') : $registro->fecha;
            $inicioRefrigerio = Carbon::parse($fechaStr . ' ' . $registro->inicio_refrigerio);
            $minutosRefrigerio = $inicioRefrigerio->diffInMinutes($horaActual);

            $registro->update([
                'fin_refrigerio' => $horaActual->format('H:i:s'),
                'latitud_fin_refrigerio' => $request->latitud,
                'longitud_fin_refrigerio' => $request->longitud,
                'minutos_refrigerio' => $minutosRefrigerio,
                'estado_refrigerio' => 'finalizado',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Refrigerio finalizado',
                'data' => [
                    'hora_fin' => $horaActual->format('H:i:s'),
                    'duracion_minutos' => $minutosRefrigerio,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en finRefrigerio: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar refrigerio',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener estado actual de asistencia
     */
    public function estadoActual(): JsonResponse
    {
        try {
            $usuario = auth()->user();
            $fechaHoy = Carbon::now()->format('Y-m-d');

            // Verificar asignación activa
            $asignacion = AsignacionAreaEmpleado::where('user_id', $usuario->id)
                ->where('activo', true)
                ->with('areaLaboral')
                ->first();

            if (! $asignacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes una asignación activa',
                    'sin_asignacion' => true,
                ], 400);
            }

            $registro = RegistroAsistencia::where('user_id', $usuario->id)
                ->whereDate('fecha', $fechaHoy)
                ->first();

            $estado = [
                'area_laboral' => [
                    'id' => $asignacion->areaLaboral->id,
                    'nombre' => $asignacion->areaLaboral->nombre,
                ],
                'tiene_entrada' => false,
                'tiene_salida' => false,
                'refrigerio_activo' => false,
                'refrigerio_finalizado' => false,
                'hora_entrada' => null,
                'hora_salida' => null,
                'inicio_refrigerio' => null,
                'fin_refrigerio' => null,
                'puede_marcar_entrada' => true,
                'puede_marcar_salida' => false,
                'puede_iniciar_refrigerio' => false,
                'puede_finalizar_refrigerio' => false,
            ];

            if ($registro) {
                $estado['tiene_entrada'] = ! is_null($registro->hora_entrada);
                $estado['tiene_salida'] = ! is_null($registro->hora_salida);
                $estado['refrigerio_activo'] = ! is_null($registro->inicio_refrigerio) && is_null($registro->fin_refrigerio);
                $estado['refrigerio_finalizado'] = ! is_null($registro->fin_refrigerio);
                $estado['hora_entrada'] = $registro->hora_entrada;
                $estado['hora_salida'] = $registro->hora_salida;
                $estado['inicio_refrigerio'] = $registro->inicio_refrigerio;
                $estado['fin_refrigerio'] = $registro->fin_refrigerio;

                $estado['puede_marcar_entrada'] = is_null($registro->hora_entrada);
                $estado['puede_marcar_salida'] = ! is_null($registro->hora_entrada) && is_null($registro->hora_salida) && ! $estado['refrigerio_activo'];
                // Solo puede iniciar refrigerio si no ha tomado uno hoy (inicio_refrigerio es null)
                $estado['puede_iniciar_refrigerio'] = ! is_null($registro->hora_entrada) && is_null($registro->hora_salida) && is_null($registro->inicio_refrigerio);
                $estado['puede_finalizar_refrigerio'] = $estado['refrigerio_activo'];
            }

            return response()->json([
                'success' => true,
                'data' => $estado,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener áreas laborales asignadas
     */
    public function areasAsignadas(): JsonResponse
    {
        try {
            $usuario = auth()->user();

            $asignaciones = AsignacionAreaEmpleado::with(['areaLaboral.horariosTrabajo'])
                ->where('user_id', $usuario->id)
                ->where('activo', true)
                ->get();

            $areas = $asignaciones->map(function ($asignacion) {
                return [
                    'id' => $asignacion->areaLaboral->id,
                    'nombre' => $asignacion->areaLaboral->nombre,
                    'descripcion' => $asignacion->areaLaboral->descripcion,
                    'ubicacion' => $asignacion->areaLaboral->ubicacion,
                    'horario_hoy' => $this->obtenerHorarioDelDia($asignacion->areaLaboral->id),
                    'fecha_asignacion' => $asignacion->fecha_asignacion,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $areas,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener áreas asignadas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener horario del día actual
     */
    private function obtenerHorarioDelDia($areaLaboralId): ?array
    {
        $diaSemana = Carbon::now()->dayOfWeek; // 0 = Domingo, 1 = Lunes, etc.

        $horario = HorarioTrabajo::where('area_laboral_id', $areaLaboralId)
            ->where('dia_semana', $diaSemana)
            ->where('activo', true)
            ->first();

        if (! $horario) {
            return null;
        }

        return [
            'entrada' => $horario->hora_entrada,
            'salida' => $horario->hora_salida,
            'tiene_medio_tiempo' => $horario->tiene_medio_tiempo,
            'hora_inicio_medio_tiempo' => $horario->hora_inicio_medio_tiempo,
            'hora_fin_medio_tiempo' => $horario->hora_fin_medio_tiempo,
        ];
    }

    /**
     * Calcular estado de entrada
     */
    private function calcularEstadoEntrada($horaActual, $horario): string
    {
        if (! $horario || ! $horario['entrada']) {
            return 'sin_horario';
        }

        $horaEntradaProgramada = Carbon::parse($horario['entrada']);
        $tolerancia = 15; // 15 minutos de tolerancia

        if ($horaActual->lte($horaEntradaProgramada->addMinutes($tolerancia))) {
            return 'puntual';
        } else {
            return 'tardanza';
        }
    }

    /**
     * Calcular estado de salida
     * Valores válidos: puntual, temprano, tardio, pendiente
     */
    private function calcularEstadoSalida($horaActual, $registro): string
    {
        // Si no hay horario programado, asumimos puntual
        return 'puntual';
    }

    /**
     * Obtener áreas asignadas de un usuario
     */
    private function getAreasAsignadas($userId): array
    {
        $asignaciones = AsignacionAreaEmpleado::with('areaLaboral')
            ->where('user_id', $userId)
            ->where('activo', true)
            ->get();

        return $asignaciones->map(function ($asignacion) {
            return [
                'id' => $asignacion->areaLaboral->id,
                'nombre' => $asignacion->areaLaboral->nombre,
                'descripcion' => $asignacion->areaLaboral->descripcion,
            ];
        })->toArray();
    }

    /**
     * Formatear tiempo trabajado
     */
    private function formatearTiempoTrabajado(int $minutos): string
    {
        $horas = floor($minutos / 60);
        $minutosRestantes = $minutos % 60;

        return sprintf('%02d:%02d', $horas, $minutosRestantes);
    }

    /**
     * Obtener mensaje de estado
     */
    private function getMensajeEstado(string $estado): string
    {
        return match ($estado) {
            'puntual' => 'Entrada puntual',
            'tardanza' => 'Entrada con tardanza',
            'temprano' => 'Salida temprana',
            'normal' => 'Salida normal',
            'sin_horario' => 'Sin horario definido',
            default => 'Estado desconocido'
        };
    }
}
