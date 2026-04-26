<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AsistenciaExport;
use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use App\Models\AreaLaboral;
use App\Models\AsignacionAreaEmpleado;
use App\Models\FeriadoHorarioEspecial;
use App\Models\HorarioTrabajo;
use App\Models\RegistroAsistencia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AsistenciaController extends Controller
{
    public function index()
    {
        return view('admin.asistencia.index');
    }

    // ============ GESTIÓN DE ÁREAS LABORALES ============
    public function areasLaborales()
    {
        $areas = AreaLaboral::orderBy('nombre')->paginate(10);

        return view('admin.asistencia.areas-laborales', compact('areas'));
    }

    public function crearAreaLaboral()
    {
        return view('admin.asistencia.crear-area-laboral');
    }

    public function storeAreaLaboral(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:areas_laborales,nombre',
            'descripcion' => 'nullable|string',
            'color' => 'required|regex:/^#[0-9A-F]{6}$/i',
        ]);

        AreaLaboral::create($request->all());

        return redirect()->route('admin.asistencia.areas-laborales')
            ->with('success', 'Área laboral creada exitosamente.');
    }

    public function editarAreaLaboral(AreaLaboral $area)
    {
        return view('admin.asistencia.editar-area-laboral', compact('area'));
    }

    public function updateAreaLaboral(Request $request, AreaLaboral $area)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:areas_laborales,nombre,'.$area->id,
            'descripcion' => 'nullable|string',
            'color' => 'required|regex:/^#[0-9A-F]{6}$/i',
        ]);

        $area->update($request->all());

        return redirect()->route('admin.asistencia.areas-laborales')
            ->with('success', 'Área laboral actualizada exitosamente.');
    }

    public function toggleAreaLaboral(AreaLaboral $area)
    {
        $area->update(['activo' => ! $area->activo]);

        $estado = $area->activo ? 'activada' : 'desactivada';

        return redirect()->back()->with('success', "Área laboral {$estado} exitosamente.");
    }

    // ============ GESTIÓN DE HORARIOS ============
    public function horariosTrabajo()
    {
        $horarios = HorarioTrabajo::orderBy('nombre')->paginate(10);

        return view('admin.asistencia.horarios-trabajo', compact('horarios'));
    }

    public function crearHorarioTrabajo()
    {
        return view('admin.asistencia.crear-horario-trabajo');
    }

    public function storeHorarioTrabajo(Request $request)
    {
        // Validación base
        $rules = [
            'nombre' => 'required|string|max:255',
            'duracion_refrigerio_minutos' => 'nullable|integer|min:1|max:120',
            'tolerancia_entrada' => 'required|integer|min:0|max:60',
            'tolerancia_salida' => 'required|integer|min:0|max:60',
            'tipo_configuracion' => 'required|in:simple,personalizado',
        ];

        // Validaciones específicas según el tipo
        if ($request->tipo_configuracion === 'personalizado') {
            $rules['horarios_semanales'] = 'required|array|min:1';
            $rules['horarios_semanales.*.activo'] = 'required|boolean';
            $rules['horarios_semanales.*.hora_entrada'] = 'required_if:horarios_semanales.*.activo,1|date_format:H:i';
            $rules['horarios_semanales.*.hora_salida'] = 'required_if:horarios_semanales.*.activo,1|date_format:H:i';
            $rules['horarios_semanales.*.duracion_refrigerio_minutos'] = 'nullable|integer|min:1|max:120';
            $rules['horarios_semanales.*.inicio_refrigerio'] = 'nullable|date_format:H:i';
            $rules['horarios_semanales.*.fin_refrigerio'] = 'nullable|date_format:H:i';
        } else {
            $rules['hora_entrada'] = 'required|date_format:H:i';
            $rules['hora_salida'] = 'required|date_format:H:i|after:hora_entrada';
            $rules['inicio_refrigerio'] = 'nullable|date_format:H:i';
            $rules['fin_refrigerio'] = 'nullable|date_format:H:i|after:inicio_refrigerio';
            $rules['dias_laborales'] = 'required|array|min:1';
            $rules['dias_laborales.*'] = 'in:0,1,2,3,4,5,6';
        }

        $request->validate($rules);

        // Preparar datos para crear el horario
        $data = $request->only(['nombre', 'duracion_refrigerio_minutos', 'tolerancia_entrada', 'tolerancia_salida']);

        if ($request->tipo_configuracion === 'personalizado') {
            // Horario personalizado
            $data['es_horario_personalizado'] = true;
            $data['horarios_semanales'] = $request->horarios_semanales;

            // Limpiar los datos - solo mantener días activos
            $horariosFiltrados = [];
            $primeraHoraEntrada = null;
            $primeraHoraSalida = null;

            foreach ($request->horarios_semanales as $dia => $horario) {
                if (isset($horario['activo']) && $horario['activo']) {
                    $horariosFiltrados[$dia] = [
                        'activo' => true,
                        'hora_entrada' => $horario['hora_entrada'],
                        'hora_salida' => $horario['hora_salida'],
                        'duracion_refrigerio_minutos' => $horario['duracion_refrigerio_minutos'] ?? null,
                        'inicio_refrigerio' => $horario['inicio_refrigerio'] ?? null,
                        'fin_refrigerio' => $horario['fin_refrigerio'] ?? null,
                    ];

                    // Usar la primera hora como referencia para los campos base
                    if ($primeraHoraEntrada === null) {
                        $primeraHoraEntrada = $horario['hora_entrada'];
                        $primeraHoraSalida = $horario['hora_salida'];
                    }
                }
            }
            $data['horarios_semanales'] = $horariosFiltrados;

            // Establecer valores por defecto para los campos base usando el primer horario activo
            $data['hora_entrada'] = $primeraHoraEntrada;
            $data['hora_salida'] = $primeraHoraSalida;
            $data['inicio_refrigerio'] = null;
            $data['fin_refrigerio'] = null;

            // Generar días laborales a partir de los días activos
            $data['dias_laborales'] = array_keys($horariosFiltrados);
        } else {
            // Horario simple
            $data['es_horario_personalizado'] = false;
            $data['hora_entrada'] = $request->hora_entrada;
            $data['hora_salida'] = $request->hora_salida;
            $data['inicio_refrigerio'] = $request->inicio_refrigerio;
            $data['fin_refrigerio'] = $request->fin_refrigerio;
            $data['dias_laborales'] = $request->dias_laborales;
        }

        $data['activo'] = $request->has('activo');

        $horario = HorarioTrabajo::create($data);

        // Generar descripción automática
        $horario->actualizarDescripcion();

        return redirect()->route('admin.asistencia.horarios-trabajo')
            ->with('success', 'Horario de trabajo creado exitosamente.');
    }

    public function editarHorarioTrabajo(HorarioTrabajo $horario)
    {
        return view('admin.asistencia.editar-horario-trabajo', compact('horario'));
    }

    public function updateHorarioTrabajo(Request $request, HorarioTrabajo $horario)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'hora_entrada' => 'required|date_format:H:i',
            'hora_salida' => 'required|date_format:H:i|after:hora_entrada',
            'inicio_refrigerio' => 'nullable|date_format:H:i',
            'fin_refrigerio' => 'nullable|date_format:H:i|after:inicio_refrigerio',
            'tolerancia_entrada' => 'required|integer|min:0|max:60',
            'tolerancia_salida' => 'required|integer|min:0|max:60',
            'dias_laborales' => 'required|array|min:1',
            'dias_laborales.*' => 'in:0,1,2,3,4,5,6',
        ]);

        $horario->update($request->all());

        return redirect()->route('admin.asistencia.horarios-trabajo')
            ->with('success', 'Horario de trabajo actualizado exitosamente.');
    }

    public function toggleHorarioTrabajo(HorarioTrabajo $horario)
    {
        $horario->update(['activo' => ! $horario->activo]);

        $estado = $horario->activo ? 'activado' : 'desactivado';

        return redirect()->back()->with('success', "Horario de trabajo {$estado} exitosamente.");
    }

    // ============ GESTIÓN DE ASIGNACIONES ============
    public function asignaciones()
    {
        $asignaciones = AsignacionAreaEmpleado::with(['usuario', 'areaLaboral', 'horarioTrabajo'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.asistencia.asignaciones', compact('asignaciones'));
    }

    public function crearAsignacion()
    {
        $usuarios = User::select('id', 'codigo', 'name')->orderBy('name')->get();
        $areas = AreaLaboral::activas()->orderBy('nombre')->get();
        $horarios = HorarioTrabajo::activos()->orderBy('nombre')->get();

        return view('admin.asistencia.crear-asignacion', compact('usuarios', 'areas', 'horarios'));
    }

    public function storeAsignacion(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'area_laboral_id' => 'required|exists:areas_laborales,id',
            'horario_trabajo_id' => 'required|exists:horarios_trabajo,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after:fecha_inicio',
        ]);

        // Verificar que el usuario no tenga otra asignación activa en la misma área
        $existeAsignacion = AsignacionAreaEmpleado::where('user_id', $request->user_id)
            ->where('area_laboral_id', $request->area_laboral_id)
            ->where('activo', true)
            ->exists();

        if ($existeAsignacion) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['user_id' => 'El usuario ya tiene una asignación activa en esta área.']);
        }

        AsignacionAreaEmpleado::create($request->all());

        return redirect()->route('admin.asistencia.asignaciones')
            ->with('success', 'Asignación creada exitosamente.');
    }

    public function editarAsignacion(AsignacionAreaEmpleado $asignacion)
    {
        $usuarios = User::select('id', 'codigo', 'name')->orderBy('name')->get();
        $areas = AreaLaboral::activas()->orderBy('nombre')->get();
        $horarios = HorarioTrabajo::activos()->orderBy('nombre')->get();

        return view('admin.asistencia.editar-asignacion', compact('asignacion', 'usuarios', 'areas', 'horarios'));
    }

    public function updateAsignacion(Request $request, AsignacionAreaEmpleado $asignacion)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'area_laboral_id' => 'required|exists:areas_laborales,id',
            'horario_trabajo_id' => 'required|exists:horarios_trabajo,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after:fecha_inicio',
        ]);

        $asignacion->update($request->all());

        return redirect()->route('admin.asistencia.asignaciones')
            ->with('success', 'Asignación actualizada exitosamente.');
    }

    public function toggleAsignacion(AsignacionAreaEmpleado $asignacion)
    {
        $asignacion->update(['activo' => ! $asignacion->activo]);

        $estado = $asignacion->activo ? 'activada' : 'desactivada';

        return redirect()->back()->with('success', "Asignación {$estado} exitosamente.");
    }

    // ============ REGISTRO DE ASISTENCIA ============
    public function registroAsistencia()
    {
        $usuario = auth()->user();

        // Obtener asignación activa del usuario para hoy
        $asignacionActiva = AsignacionAreaEmpleado::with(['areaLaboral', 'horarioTrabajo'])
            ->where('user_id', $usuario->id)
            ->vigentes()
            ->first();

        if (! $asignacionActiva) {
            return view('admin.asistencia.sin-asignacion');
        }

        // Verificar si es día laboral
        $hoy = Carbon::now();
        if (! in_array($hoy->dayOfWeek, $asignacionActiva->horarioTrabajo->dias_laborales)) {
            return view('admin.asistencia.dia-no-laboral', compact('asignacionActiva'));
        }

        // Obtener o crear registro de asistencia para hoy
        $registroHoy = RegistroAsistencia::where('user_id', $usuario->id)
            ->where('fecha', $hoy->toDateString())
            ->first();

        return view('admin.asistencia.registro-asistencia', compact('asignacionActiva', 'registroHoy'));
    }

    public function marcarEntrada(Request $request)
    {
        $usuario = auth()->user();
        $hoy = Carbon::now();

        $asignacionActiva = AsignacionAreaEmpleado::with('horarioTrabajo')
            ->where('user_id', $usuario->id)
            ->vigentes()
            ->first();

        if (! $asignacionActiva) {
            return response()->json(['error' => 'No tienes una asignación activa.'], 400);
        }

        // Verificar si ya marcó entrada hoy
        $registro = RegistroAsistencia::where('user_id', $usuario->id)
            ->where('fecha', $hoy->toDateString())
            ->first();

        if ($registro && $registro->hora_entrada) {
            return response()->json(['error' => 'Ya has marcado tu entrada hoy.'], 400);
        }

        $horaActual = $hoy->format('H:i:s');
        $horario = $asignacionActiva->horarioTrabajo;

        // Determinar estado de entrada
        $estadoEntrada = 'puntual';
        $minutosTardanza = 0;

        if ($horario->esTardanza($horaActual)) {
            $estadoEntrada = 'tardanza';
            $minutosTardanza = $horario->minutosDeRetraso($horaActual);
        }

        // Crear o actualizar registro
        $datos = [
            'user_id' => $usuario->id,
            'asignacion_id' => $asignacionActiva->id,
            'fecha' => $hoy->toDateString(),
            'hora_entrada' => $horaActual,
            'estado_entrada' => $estadoEntrada,
            'minutos_tardanza' => $minutosTardanza,
            'latitud_entrada' => $request->input('latitud'),
            'longitud_entrada' => $request->input('longitud'),
            'ip_entrada' => $request->ip(),
        ];

        if ($registro) {
            $registro->update($datos);
        } else {
            RegistroAsistencia::create($datos);
        }

        return response()->json([
            'success' => true,
            'mensaje' => 'Entrada registrada exitosamente.',
            'estado' => $estadoEntrada,
            'hora' => $horaActual,
        ]);
    }

    public function marcarSalida(Request $request)
    {
        $usuario = auth()->user();
        $hoy = Carbon::now();

        $registro = RegistroAsistencia::with('asignacion.horarioTrabajo')
            ->where('user_id', $usuario->id)
            ->where('fecha', $hoy->toDateString())
            ->first();

        if (! $registro || ! $registro->hora_entrada) {
            return response()->json(['error' => 'Primero debes marcar tu entrada.'], 400);
        }

        if ($registro->hora_salida) {
            return response()->json(['error' => 'Ya has marcado tu salida hoy.'], 400);
        }

        $horaActual = $hoy->format('H:i:s');
        $horario = $registro->asignacion->horarioTrabajo;

        // Determinar estado de salida
        $horaSalidaEsperada = Carbon::parse($horario->hora_salida);
        $horaSalidaReal = Carbon::parse($horaActual);

        $estadoSalida = 'puntual';
        if ($horaSalidaReal->lt($horaSalidaEsperada->subMinutes($horario->tolerancia_salida))) {
            $estadoSalida = 'temprano';
        } elseif ($horaSalidaReal->gt($horaSalidaEsperada->addMinutes($horario->tolerancia_salida))) {
            $estadoSalida = 'tardio';
        }

        $registro->update([
            'hora_salida' => $horaActual,
            'estado_salida' => $estadoSalida,
            'latitud_salida' => $request->input('latitud'),
            'longitud_salida' => $request->input('longitud'),
            'ip_salida' => $request->ip(),
        ]);

        // NUEVA FUNCIONALIDAD: Cierre de sesión automático al marcar salida
        $this->procesarCierreSesionAlMarcarSalida($usuario);

        return response()->json([
            'success' => true,
            'mensaje' => 'Salida registrada exitosamente. Tu sesión se cerrará automáticamente.',
            'estado' => $estadoSalida,
            'hora' => $horaActual,
            'logout' => true, // Indicar al frontend que debe cerrar sesión
        ]);
    }

    public function marcarInicioRefrigerio(Request $request)
    {
        $usuario = auth()->user();
        $hoy = Carbon::now();

        $registro = RegistroAsistencia::with('asignacion.horarioTrabajo')
            ->where('user_id', $usuario->id)
            ->where('fecha', $hoy->toDateString())
            ->first();

        if (! $registro || ! $registro->hora_entrada) {
            return response()->json(['error' => 'Primero debes marcar tu entrada.'], 400);
        }

        if ($registro->inicio_refrigerio) {
            return response()->json(['error' => 'Ya has marcado el inicio de tu refrigerio.'], 400);
        }

        $horaActual = $hoy->format('H:i:s');
        $horario = $registro->asignacion->horarioTrabajo;

        // Verificar que el horario tenga configurado refrigerio
        $horarioDelDia = $horario->obtenerHorarioParaDia($hoy->dayOfWeek);
        if (! $horarioDelDia || ! $horarioDelDia->duracion_refrigerio_minutos) {
            return response()->json(['error' => 'Tu horario no tiene refrigerio configurado.'], 400);
        }

        $registro->update([
            'inicio_refrigerio' => $horaActual,
            'latitud_inicio_refrigerio' => $request->input('latitud'),
            'longitud_inicio_refrigerio' => $request->input('longitud'),
        ]);

        return response()->json([
            'success' => true,
            'mensaje' => 'Inicio de refrigerio registrado exitosamente.',
            'hora' => $horaActual,
            'duracion_permitida' => $horarioDelDia->duracion_refrigerio_minutos,
        ]);
    }

    public function marcarFinRefrigerio(Request $request)
    {
        $usuario = auth()->user();
        $hoy = Carbon::now();

        $registro = RegistroAsistencia::with('asignacion.horarioTrabajo')
            ->where('user_id', $usuario->id)
            ->where('fecha', $hoy->toDateString())
            ->first();

        if (! $registro || ! $registro->inicio_refrigerio) {
            return response()->json(['error' => 'Primero debes marcar el inicio de tu refrigerio.'], 400);
        }

        if ($registro->fin_refrigerio) {
            return response()->json(['error' => 'Ya has marcado el fin de tu refrigerio.'], 400);
        }

        $horaActual = $hoy->format('H:i:s');
        $horario = $registro->asignacion->horarioTrabajo;

        // Obtener configuración del refrigerio
        $horarioDelDia = $horario->obtenerHorarioParaDia($hoy->dayOfWeek);
        $duracionPermitida = $horarioDelDia->duracion_refrigerio_minutos;

        // Calcular duración real del refrigerio
        $inicioRefrigerio = Carbon::parse($registro->fecha.' '.$registro->inicio_refrigerio);
        $finRefrigerio = Carbon::parse($registro->fecha.' '.$horaActual);
        $minutosReales = $inicioRefrigerio->diffInMinutes($finRefrigerio);

        // Determinar estado del refrigerio
        $estadoRefrigerio = 'normal';
        if ($minutosReales > $duracionPermitida) {
            $estadoRefrigerio = 'excedido';
        }

        $registro->update([
            'fin_refrigerio' => $horaActual,
            'minutos_refrigerio' => $minutosReales,
            'estado_refrigerio' => $estadoRefrigerio,
            'latitud_fin_refrigerio' => $request->input('latitud'),
            'longitud_fin_refrigerio' => $request->input('longitud'),
        ]);

        return response()->json([
            'success' => true,
            'mensaje' => 'Fin de refrigerio registrado exitosamente.',
            'hora' => $horaActual,
            'duracion_real' => $minutosReales,
            'duracion_permitida' => $duracionPermitida,
            'estado' => $estadoRefrigerio,
        ]);
    }

    // ============ REPORTES Y CONSULTAS ============
    public function reporteAsistencia(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio', Carbon::now()->startOfMonth()->toDateString());
        $fechaFin = $request->input('fecha_fin', Carbon::now()->toDateString());
        $areaId = $request->input('area_id');
        $userId = $request->input('user_id');

        $query = RegistroAsistencia::with(['usuario', 'asignacion.areaLaboral', 'asignacion.horarioTrabajo'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($areaId) {
            $query->whereHas('asignacion', function ($q) use ($areaId) {
                $q->where('area_laboral_id', $areaId);
            });
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Exportar a Excel si se solicita
        if ($request->has('export') && $request->export == 'excel') {
            $filename = 'reporte_asistencia_'.Carbon::parse($fechaInicio)->format('d-m-Y').'_al_'.Carbon::parse($fechaFin)->format('d-m-Y').'.xlsx';

            return Excel::download(new AsistenciaExport($query->orderBy('fecha', 'desc')), $filename);
        }

        $registros = $query->orderBy('fecha', 'desc')->paginate(20);

        $areas = AreaLaboral::activas()->orderBy('nombre')->get();
        $usuarios = User::select('id', 'codigo', 'name')->orderBy('name')->get();

        return view('admin.asistencia.reporte-asistencia', compact('registros', 'areas', 'usuarios', 'fechaInicio', 'fechaFin', 'areaId', 'userId'));
    }

    public function dashboardAsistencia()
    {
        $hoy = Carbon::now();

        // Estadísticas del día
        $totalEmpleados = AsignacionAreaEmpleado::vigentes()->count();
        $presentesHoy = RegistroAsistencia::hoy()->whereNotNull('hora_entrada')->count();
        $tardanzasHoy = RegistroAsistencia::hoy()->conTardanzas()->count();
        $faltasHoy = $totalEmpleados - $presentesHoy;

        // Últimos registros
        $ultimosRegistros = RegistroAsistencia::with(['usuario', 'asignacion.areaLaboral'])
            ->hoy()
            ->whereNotNull('hora_entrada')
            ->orderBy('hora_entrada', 'desc')
            ->limit(10)
            ->get();

        return view('admin.asistencia.dashboard', compact(
            'totalEmpleados', 'presentesHoy', 'tardanzasHoy', 'faltasHoy', 'ultimosRegistros'
        ));
    }

    // ============ GESTIÓN DE FERIADOS Y HORARIOS ESPECIALES ============
    public function feriadosEspeciales()
    {
        $feriados = FeriadoHorarioEspecial::orderBy('fecha')->paginate(15);
        $areas = AreaLaboral::orderBy('nombre')->get();

        return view('admin.asistencia.feriados-horarios-especiales', compact('feriados', 'areas'));
    }

    public function storeFeriadoEspecial(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'fecha' => 'required|date',
            'tipo' => 'required|in:feriado,medio_dia,especial',
            'area_laboral_id' => 'nullable|exists:areas_laborales,id',
            'hora_entrada' => 'nullable|date_format:H:i',
            'hora_salida' => 'nullable|date_format:H:i',
            'inicio_refrigerio' => 'nullable|date_format:H:i',
            'fin_refrigerio' => 'nullable|date_format:H:i',
            'descripcion' => 'nullable|string',
        ]);

        // Validaciones adicionales según el tipo
        if ($request->tipo !== 'feriado') {
            $request->validate([
                'hora_entrada' => 'required|date_format:H:i',
                'hora_salida' => 'required|date_format:H:i|after:hora_entrada',
            ]);
        }

        FeriadoHorarioEspecial::create($request->all());

        return redirect()->route('admin.asistencia.feriados-especiales.index')
            ->with('success', 'Día especial creado exitosamente.');
    }

    public function updateFeriadoEspecial(Request $request, FeriadoHorarioEspecial $feriado)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'fecha' => 'required|date',
            'tipo' => 'required|in:feriado,medio_dia,especial',
            'area_laboral_id' => 'nullable|exists:areas_laborales,id',
            'hora_entrada' => 'nullable|date_format:H:i',
            'hora_salida' => 'nullable|date_format:H:i',
            'inicio_refrigerio' => 'nullable|date_format:H:i',
            'fin_refrigerio' => 'nullable|date_format:H:i',
            'descripcion' => 'nullable|string',
        ]);

        // Validaciones adicionales según el tipo
        if ($request->tipo !== 'feriado') {
            $request->validate([
                'hora_entrada' => 'required|date_format:H:i',
                'hora_salida' => 'required|date_format:H:i|after:hora_entrada',
            ]);
        }

        $feriado->update($request->all());

        return redirect()->route('admin.asistencia.feriados-especiales.index')
            ->with('success', 'Día especial actualizado exitosamente.');
    }

    public function destroyFeriadoEspecial(FeriadoHorarioEspecial $feriado)
    {
        $feriado->delete();

        return redirect()->route('admin.asistencia.feriados-especiales.index')
            ->with('success', 'Día especial eliminado exitosamente.');
    }

    public function importarFeriadosNacionales(Request $request)
    {
        $anio = $request->input('anio', date('Y'));

        // Feriados fijos de Perú
        $feriadosFijos = [
            ['nombre' => 'Año Nuevo', 'fecha' => "{$anio}-01-01"],
            ['nombre' => 'Día del Trabajador', 'fecha' => "{$anio}-05-01"],
            ['nombre' => 'Día de la Independencia', 'fecha' => "{$anio}-07-28"],
            ['nombre' => 'Día de la Batalla de Junín', 'fecha' => "{$anio}-07-29"],
            ['nombre' => 'Día de Santa Rosa de Lima', 'fecha' => "{$anio}-08-30"],
            ['nombre' => 'Combate de Angamos', 'fecha' => "{$anio}-10-08"],
            ['nombre' => 'Día de Todos los Santos', 'fecha' => "{$anio}-11-01"],
            ['nombre' => 'Inmaculada Concepción', 'fecha' => "{$anio}-12-08"],
            ['nombre' => 'Navidad', 'fecha' => "{$anio}-12-25"],
        ];

        // Calcular feriados variables (Jueves y Viernes Santo)
        $pascua = $this->calcularPascua($anio);
        $juevesSanto = $pascua->copy()->subDays(3);
        $viernesSanto = $pascua->copy()->subDays(2);

        $feriadosVariables = [
            ['nombre' => 'Jueves Santo', 'fecha' => $juevesSanto->format('Y-m-d')],
            ['nombre' => 'Viernes Santo', 'fecha' => $viernesSanto->format('Y-m-d')],
        ];

        $todosLosFeriados = array_merge($feriadosFijos, $feriadosVariables);
        $importados = 0;

        foreach ($todosLosFeriados as $feriado) {
            $existe = FeriadoHorarioEspecial::where('fecha', $feriado['fecha'])
                ->whereNull('area_laboral_id') // Solo feriados generales
                ->first();

            if (! $existe) {
                FeriadoHorarioEspecial::create([
                    'nombre' => $feriado['nombre'],
                    'fecha' => $feriado['fecha'],
                    'tipo' => 'feriado',
                    'descripcion' => 'Feriado nacional importado automáticamente',
                    'area_laboral_id' => null,
                ]);
                $importados++;
            }
        }

        return redirect()->route('admin.asistencia.feriados-especiales.index')
            ->with('success', "Se importaron {$importados} feriados nacionales para el año {$anio}.");
    }

    private function calcularPascua($anio)
    {
        // Algoritmo para calcular la fecha de Pascua
        $a = $anio % 19;
        $b = intval($anio / 100);
        $c = $anio % 100;
        $d = intval($b / 4);
        $e = $b % 4;
        $f = intval(($b + 8) / 25);
        $g = intval(($b - $f + 1) / 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intval($c / 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intval(($a + 11 * $h + 22 * $l) / 451);
        $n = intval(($h + $l - 7 * $m + 114) / 31);
        $p = ($h + $l - 7 * $m + 114) % 31;

        return Carbon::create($anio, $n, $p + 1);
    }

    // ============ API FLOTANTE DE ASISTENCIA ============

    /**
     * Marcar asistencia desde el sistema flotante
     */
    public function marcarAsistenciaFlotante(Request $request)
    {
        try {
            $request->validate([
                'action' => 'required|in:entrada,refrigerio-inicio,refrigerio-fin,salida',
                'timestamp' => 'required|date',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'user_agent' => 'nullable|string|max:500',
                'timezone' => 'nullable|string|max:50',
            ]);

            $usuario = auth()->user();
            $action = $request->action;
            $fechaHora = Carbon::parse($request->timestamp)->setTimezone('America/Lima');

            // Verificar si el usuario tiene una asignación de área laboral activa
            $asignacion = AsignacionAreaEmpleado::where('user_id', $usuario->id)
                ->where('activo', true)
                ->first();

            if (! $asignacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes una asignación de área laboral activa. Contacta al administrador.',
                ], 400);
            }

            // Verificar si hay horarios habilitados para hoy
            $horarioValidacion = $this->validarHorarioHabilitado($asignacion, $fechaHora, $action);
            if (! $horarioValidacion['valido']) {
                $response = [
                    'success' => false,
                    'message' => $horarioValidacion['mensaje'],
                ];

                // Si es día no laboral, agregar información para redirección
                if (isset($horarioValidacion['redirigir_a_dia_no_laboral'])) {
                    $response['redirect_to'] = route('admin.asistencia.dia-no-laboral');
                    $response['is_non_working_day'] = true;
                }

                return response()->json($response, 400);
            }

            // Obtener el último registro del día
            $ultimoRegistro = RegistroAsistencia::where('user_id', $usuario->id)
                ->whereDate('fecha', $fechaHora->toDateString())
                ->orderBy('created_at', 'desc')
                ->first();

            // Verificar si el día ya está completado (se marcó la salida)
            if ($ultimoRegistro && $ultimoRegistro->hora_salida) {
                $fechaStr = $ultimoRegistro->fecha instanceof Carbon ?
                           $ultimoRegistro->fecha->format('Y-m-d') :
                           Carbon::parse($ultimoRegistro->fecha)->format('Y-m-d');
                $horaSalida = Carbon::parse($fechaStr.' '.$ultimoRegistro->hora_salida)->format('H:i');

                return response()->json([
                    'success' => false,
                    'message' => '✅ Ya completaste tu jornada laboral hoy. Registraste tu salida a las '.$horaSalida,
                    'day_completed' => true,
                ], 400);
            }

            $mensaje = '';
            $currentStatus = null;
            $isBreakActive = false;

            switch ($action) {
                case 'entrada':
                    $resultado = $this->procesarEntradaFlotante($usuario, $asignacion, $fechaHora, $request);
                    break;
                case 'refrigerio-inicio':
                    $resultado = $this->procesarInicioRefrigerioFlotante($ultimoRegistro, $fechaHora, $request);
                    break;
                case 'refrigerio-fin':
                    $resultado = $this->procesarFinRefrigerioFlotante($ultimoRegistro, $fechaHora, $request);
                    break;
                case 'salida':
                    $resultado = $this->procesarSalidaFlotante($ultimoRegistro, $fechaHora, $request);
                    break;
                default:
                    throw new \Exception('Acción no válida');
            }

            return response()->json([
                'success' => true,
                'message' => $resultado['message'],
                'action' => $action,
                'timestamp' => $fechaHora->toISOString(),
                'current_status' => $resultado['current_status'],
                'is_break_active' => $resultado['is_break_active'],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en marcarAsistenciaFlotante: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'action' => $request->action ?? 'undefined',
                'error' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener el estado actual de asistencia del usuario
     */
    public function obtenerEstadoActual()
    {
        try {
            $usuario = auth()->user();
            $hoy = Carbon::today();

            // Verificar asignación activa
            $asignacion = AsignacionAreaEmpleado::where('user_id', $usuario->id)
                ->where('activo', true)
                ->first();

            if (! $asignacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes una asignación de área laboral activa.',
                ], 400);
            }

            // Verificar si hay horarios habilitados para hoy
            $horarioValidacion = $this->validarHorarioHabilitado($asignacion, $hoy, 'entrada');
            if (! $horarioValidacion['valido']) {
                $response = [
                    'success' => false,
                    'message' => $horarioValidacion['mensaje'],
                ];

                // Si es día no laboral, agregar información para manejo especial
                if (isset($horarioValidacion['redirigir_a_dia_no_laboral'])) {
                    $response['is_non_working_day'] = true;
                    $response['redirect_to'] = route('admin.asistencia.dia-no-laboral');
                }

                return response()->json($response, 400);
            }

            // Buscar el último registro del día
            $ultimoRegistro = RegistroAsistencia::where('user_id', $usuario->id)
                ->whereDate('fecha', $hoy)
                ->orderBy('created_at', 'desc')
                ->first();

            if (! $ultimoRegistro) {
                return response()->json([
                    'success' => true,
                    'current_status' => null,
                    'last_action' => null,
                    'last_action_time' => null,
                    'is_break_active' => false,
                    'day_completed' => false,
                ]);
            }

            // Verificar si el día está completado (ya se marcó la salida)
            if ($ultimoRegistro->hora_salida) {
                $fechaStr = $ultimoRegistro->fecha instanceof Carbon ?
                           $ultimoRegistro->fecha->format('Y-m-d') :
                           Carbon::parse($ultimoRegistro->fecha)->format('Y-m-d');
                $lastActionTime = Carbon::parse($fechaStr.' '.$ultimoRegistro->hora_salida);

                return response()->json([
                    'success' => true,
                    'current_status' => 'salida',
                    'last_action' => 'salida',
                    'last_action_time' => $lastActionTime->toISOString(),
                    'is_break_active' => false,
                    'day_completed' => true,
                    'message' => '✅ Jornada laboral completada. Ya registraste tu salida a las '.$lastActionTime->format('H:i'),
                ]);
            }

            // Determinar el estado actual si el día no está completado
            $currentStatus = 'entrada';
            $lastAction = 'entrada';
            $lastActionTime = null;
            $isBreakActive = false;

            // Construir fecha y hora completa para entrada
            if ($ultimoRegistro->fecha && $ultimoRegistro->hora_entrada) {
                $fechaStr = $ultimoRegistro->fecha instanceof Carbon ?
                           $ultimoRegistro->fecha->format('Y-m-d') :
                           Carbon::parse($ultimoRegistro->fecha)->format('Y-m-d');
                $lastActionTime = Carbon::parse($fechaStr.' '.$ultimoRegistro->hora_entrada);
            }

            if ($ultimoRegistro->fin_refrigerio) {
                $lastAction = 'refrigerio-fin';
                if ($ultimoRegistro->fecha) {
                    $fechaStr = $ultimoRegistro->fecha instanceof Carbon ?
                               $ultimoRegistro->fecha->format('Y-m-d') :
                               Carbon::parse($ultimoRegistro->fecha)->format('Y-m-d');
                    $lastActionTime = Carbon::parse($fechaStr.' '.$ultimoRegistro->fin_refrigerio);
                }
            } elseif ($ultimoRegistro->inicio_refrigerio) {
                $lastAction = 'refrigerio-inicio';
                $isBreakActive = true;
                if ($ultimoRegistro->fecha) {
                    $fechaStr = $ultimoRegistro->fecha instanceof Carbon ?
                               $ultimoRegistro->fecha->format('Y-m-d') :
                               Carbon::parse($ultimoRegistro->fecha)->format('Y-m-d');
                    $lastActionTime = Carbon::parse($fechaStr.' '.$ultimoRegistro->inicio_refrigerio);
                }
            }

            return response()->json([
                'success' => true,
                'current_status' => $currentStatus,
                'last_action' => $lastAction,
                'last_action_time' => $lastActionTime ? Carbon::parse($lastActionTime)->toISOString() : null,
                'is_break_active' => $isBreakActive,
                'day_completed' => false,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en obtenerEstadoActual: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'error' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado actual',
            ], 500);
        }
    }

    // ============ MÉTODOS AUXILIARES PARA SISTEMA FLOTANTE ============

    private function procesarEntradaFlotante($usuario, $asignacion, $fechaHora, $request)
    {
        // Verificar si ya hay una entrada para hoy
        $entradaHoy = RegistroAsistencia::where('user_id', $usuario->id)
            ->whereDate('fecha', $fechaHora->toDateString())
            ->first();

        if ($entradaHoy) {
            if ($entradaHoy->hora_salida) {
                throw new \Exception('Ya completaste tu jornada laboral hoy. Registraste tu salida a las '.
                    Carbon::parse($entradaHoy->fecha.' '.$entradaHoy->hora_salida)->format('H:i'));
            } else {
                throw new \Exception('Ya tienes una entrada registrada para hoy a las '.
                    Carbon::parse($entradaHoy->fecha.' '.$entradaHoy->hora_entrada)->format('H:i'));
            }
        }

        // Crear nuevo registro de entrada
        RegistroAsistencia::create([
            'user_id' => $usuario->id,
            'asignacion_id' => $asignacion->id,
            'fecha' => $fechaHora->toDateString(),
            'hora_entrada' => $fechaHora->format('H:i:s'),
            'latitud_entrada' => $request->latitude,
            'longitud_entrada' => $request->longitude,
            'ip_entrada' => $request->ip(),
            'estado_entrada' => 'puntual', // Por defecto, se puede calcular después con horarios
        ]);

        return [
            'message' => '✅ Entrada registrada correctamente a las '.$fechaHora->format('H:i'),
            'current_status' => 'entrada',
            'is_break_active' => false,
        ];
    }

    private function procesarInicioRefrigerioFlotante($ultimoRegistro, $fechaHora, $request)
    {
        if (! $ultimoRegistro) {
            throw new \Exception('Debes marcar tu entrada antes de iniciar el refrigerio.');
        }

        if ($ultimoRegistro->hora_salida) {
            throw new \Exception('No puedes iniciar refrigerio después de haber marcado la salida.');
        }

        if ($ultimoRegistro->inicio_refrigerio && ! $ultimoRegistro->fin_refrigerio) {
            throw new \Exception('Ya tienes un refrigerio en curso. Termínalo antes de iniciar otro.');
        }

        // Actualizar registro con inicio de refrigerio
        $ultimoRegistro->update([
            'inicio_refrigerio' => $fechaHora->format('H:i:s'),
            'latitud_inicio_refrigerio' => $request->latitude,
            'longitud_inicio_refrigerio' => $request->longitude,
            'estado_refrigerio' => 'iniciado',
        ]);

        return [
            'message' => '☕ Refrigerio iniciado a las '.$fechaHora->format('H:i'),
            'current_status' => 'entrada',
            'is_break_active' => true,
        ];
    }

    private function procesarFinRefrigerioFlotante($ultimoRegistro, $fechaHora, $request)
    {
        if (! $ultimoRegistro) {
            throw new \Exception('Debes marcar tu entrada antes de finalizar el refrigerio.');
        }

        if (! $ultimoRegistro->inicio_refrigerio) {
            throw new \Exception('No tienes un refrigerio iniciado para finalizar.');
        }

        if ($ultimoRegistro->fin_refrigerio) {
            throw new \Exception('El refrigerio ya fue finalizado.');
        }

        if ($ultimoRegistro->hora_salida) {
            throw new \Exception('No puedes finalizar refrigerio después de haber marcado la salida.');
        }

        // Calcular minutos de refrigerio
        $fechaStr = $ultimoRegistro->fecha instanceof Carbon ?
                   $ultimoRegistro->fecha->format('Y-m-d') :
                   Carbon::parse($ultimoRegistro->fecha)->format('Y-m-d');
        $inicioRefrigerio = Carbon::parse($fechaStr.' '.$ultimoRegistro->inicio_refrigerio);
        $finRefrigerio = $fechaHora;
        $minutosRefrigerio = $finRefrigerio->diffInMinutes($inicioRefrigerio);

        // Actualizar registro con fin de refrigerio
        $ultimoRegistro->update([
            'fin_refrigerio' => $fechaHora->format('H:i:s'),
            'latitud_fin_refrigerio' => $request->latitude,
            'longitud_fin_refrigerio' => $request->longitude,
            'minutos_refrigerio' => $minutosRefrigerio,
            'estado_refrigerio' => 'finalizado',
        ]);

        return [
            'message' => '🔄 Refrigerio finalizado a las '.$fechaHora->format('H:i'),
            'current_status' => 'entrada',
            'is_break_active' => false,
        ];
    }

    private function procesarSalidaFlotante($ultimoRegistro, $fechaHora, $request)
    {
        if (! $ultimoRegistro) {
            throw new \Exception('Debes marcar tu entrada antes de registrar la salida.');
        }

        if ($ultimoRegistro->hora_salida) {
            throw new \Exception('Ya tienes una salida registrada para hoy.');
        }

        if ($ultimoRegistro->inicio_refrigerio && ! $ultimoRegistro->fin_refrigerio) {
            throw new \Exception('Debes finalizar tu refrigerio antes de marcar la salida.');
        }

        // Actualizar registro con salida
        $ultimoRegistro->update([
            'hora_salida' => $fechaHora->format('H:i:s'),
            'latitud_salida' => $request->latitude,
            'longitud_salida' => $request->longitude,
            'ip_salida' => $request->ip(),
            'estado_salida' => 'puntual', // Se puede calcular después con horarios
        ]);

        // Calcular horas trabajadas usando el método del modelo
        $horasTrabajadas = $ultimoRegistro->fresh()->calcularHorasTrabajadas();

        // NUEVA FUNCIONALIDAD: Cierre de sesión automático al marcar salida
        $usuario = auth()->user();
        $this->procesarCierreSesionAlMarcarSalida($usuario);

        return [
            'message' => '🏁 Salida registrada a las '.$fechaHora->format('H:i').'. Horas trabajadas: '.number_format($horasTrabajadas, 2).'h. Sesión cerrada automáticamente.',
            'current_status' => 'salida',
            'is_break_active' => false,
            'logout' => true, // Indicar al frontend que debe cerrar sesión
        ];
    }

    /**
     * Validar si hay horarios habilitados para la fecha y acción específica
     */
    private function validarHorarioHabilitado($asignacion, $fechaHora, $action)
    {
        $diaSemana = $fechaHora->dayOfWeek; // 0 = Domingo, 1 = Lunes, etc.
        $fecha = $fechaHora->toDateString();

        // Verificar si hay un feriado o día especial
        $diaEspecial = FeriadoHorarioEspecial::obtenerDiaEspecial($fecha, $asignacion->area_laboral_id);

        if ($diaEspecial && $diaEspecial->esFeriado()) {
            return [
                'valido' => false,
                'mensaje' => 'No se puede marcar asistencia en día feriado: '.$diaEspecial->nombre,
            ];
        }

        // Obtener el horario de trabajo de la asignación
        $horario = $asignacion->horarioTrabajo;

        // Verificar si el horario está activo
        if (! $horario || ! $horario->activo) {
            $nombreDia = $this->obtenerNombreDia($diaSemana);

            return [
                'valido' => false,
                'mensaje' => 'Hoy es día de descanso',
                'redirigir_a_dia_no_laboral' => true,
                'nombre_dia' => $nombreDia,
            ];
        }

        // Verificar si el día está habilitado en el horario
        $diasLaborales = $horario->dias_laborales ?? [];
        if (! in_array($diaSemana, $diasLaborales)) {
            $nombreDia = $this->obtenerNombreDia($diaSemana);

            return [
                'valido' => false,
                'mensaje' => 'Hoy es día de descanso',
                'redirigir_a_dia_no_laboral' => true,
                'nombre_dia' => $nombreDia,
            ];
        }

        // Verificar horario específico según la acción (opcional para flexibilidad)
        if ($action === 'entrada' && $horario->hora_entrada) {
            $horaActual = $fechaHora->format('H:i:s');
            $horaEntrada = $horario->hora_entrada;
            $toleranciaEntrada = $horario->tolerancia_entrada ?? 0;

            // Permitir entrada desde 2 horas antes hasta la tolerancia después
            $horaPermitidaDesde = Carbon::parse($fecha.' '.$horaEntrada)->subHours(2)->format('H:i:s');
            $horaPermitidaHasta = Carbon::parse($fecha.' '.$horaEntrada)->addMinutes($toleranciaEntrada)->format('H:i:s');

            if ($horaActual < $horaPermitidaDesde || $horaActual > $horaPermitidaHasta) {
                return [
                    'valido' => true, // Permitir pero con advertencia
                    'mensaje' => "Entrada fuera del horario normal ({$horaEntrada}). Se registrará como tardanza o entrada temprana.",
                    'advertencia' => true,
                ];
            }
        }

        return [
            'valido' => true,
            'mensaje' => 'Horario válido',
            'horario' => $horario,
        ];
    }

    /**
     * Obtener nombre del día en español
     */
    private function obtenerNombreDia($diaSemana)
    {
        $dias = [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
        ];

        return $dias[$diaSemana] ?? 'Día desconocido';
    }

    /**
     * Mostrar vista de día no laboral
     */
    public function mostrarDiaNoLaboral()
    {
        $usuario = auth()->user();

        // Obtener asignación activa
        $asignacionActiva = AsignacionAreaEmpleado::where('user_id', $usuario->id)
            ->where('activo', true)
            ->with(['areaLaboral', 'horarioTrabajo'])
            ->first();

        if (! $asignacionActiva) {
            return redirect()->route('admin.asistencia.index')
                ->with('error', 'No tienes una asignación de área laboral activa.');
        }

        return view('admin.asistencia.dia-no-laboral', compact('asignacionActiva'));
    }

    /**
     * Procesar cierre de sesión automático al marcar salida
     */
    private function procesarCierreSesionAlMarcarSalida($usuario)
    {
        try {
            // 1. INVALIDAR CÓDIGOS DE ACCESO ACTIVOS DEL USUARIO
            // Desactivar todos los códigos de acceso creados por este usuario
            AccessCode::where('created_by', $usuario->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'updated_at' => Carbon::now(),
                ]);

            // 2. LIMPIAR CACHÉ DE SESIONES
            $this->limpiarCacheSesiones($usuario->id);

            // 3. INVALIDAR TODOS LOS TOKENS DE ACCESO (SANCTUM)
            $usuario->tokens()->delete();

            // 4. LIMPIAR SESIONES LARAVEL
            $this->invalidarSesionesUsuario($usuario->id);

            // Log de la acción
            \Log::info('Cierre de sesión automático ejecutado', [
                'user_id' => $usuario->id,
                'user_codigo' => $usuario->codigo,
                'timestamp' => Carbon::now(),
                'action' => 'marcar_salida_auto_logout',
            ]);

        } catch (\Exception $e) {
            // Log del error pero no interrumpir el proceso de marcar salida
            \Log::error('Error en procesarCierreSesionAlMarcarSalida: '.$e->getMessage(), [
                'user_id' => $usuario->id,
                'error' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Limpiar caché de sesiones específicas del usuario
     */
    private function limpiarCacheSesiones($userId)
    {
        try {
            // Limpiar caché de Laravel
            \Cache::forget("user_session_{$userId}");
            \Cache::forget("user_permissions_{$userId}");
            \Cache::forget("user_roles_{$userId}");
            \Cache::forget("user_asignaciones_{$userId}");
            \Cache::forget("user_horarios_{$userId}");

            // Limpiar caché de asistencia específico
            \Cache::forget("asistencia_usuario_{$userId}");
            \Cache::forget("registro_asistencia_hoy_{$userId}");

            // Limpiar caché de configuraciones
            \Cache::flush(); // Limpiar todo el caché (opcional, usar con precaución)

        } catch (\Exception $e) {
            \Log::error('Error limpiando caché de sesiones: '.$e->getMessage());
        }
    }

    /**
     * Invalidar sesiones Laravel del usuario
     */
    private function invalidarSesionesUsuario($userId)
    {
        try {
            // Obtener todas las sesiones activas del usuario de la base de datos
            DB::table('sessions')
                ->where('user_id', $userId)
                ->delete();

            // También limpiar sesiones de archivos si se usa file driver
            if (config('session.driver') === 'file') {
                $sessionPath = storage_path('framework/sessions');
                if (is_dir($sessionPath)) {
                    $files = glob($sessionPath.'/*');
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            $content = file_get_contents($file);
                            // Buscar si la sesión pertenece al usuario
                            if (strpos($content, '"user_id";i:'.$userId.';') !== false) {
                                unlink($file);
                            }
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            \Log::error('Error invalidando sesiones del usuario: '.$e->getMessage());
        }
    }
}
