<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CompromisosExport;
use App\Http\Controllers\Controller;
use App\Models\Compromiso;
use App\Models\Prestamo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class CompromisosController extends Controller
{
    public function index(Request $request)
    {
        $query = Compromiso::with([
            'prestamo.cliente.persona',
            'prestamo.carterasJcc.jcc.persona',
            'prestamo.carterasAsesor.asesor.persona',
            'prestamo.carterasAnalista.analista.persona',
        ]);

        // Filtro por búsqueda
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('prestamo.cliente.persona', function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                    ->orWhere('ape_pat', 'like', "%{$search}%")
                    ->orWhere('ape_mat', 'like', "%{$search}%");
            });
        }

        // Filtro por fecha desde
        if ($request->filled('fecha_desde')) {
            $fechaDesde = Carbon::parse($request->input('fecha_desde'))->startOfDay();
            $query->whereDate('fecha_compromiso_pago', '>=', $fechaDesde);
        }

        // Filtro por fecha hasta
        if ($request->filled('fecha_hasta')) {
            $fechaHasta = Carbon::parse($request->input('fecha_hasta'))->endOfDay();
            $query->whereDate('fecha_compromiso_pago', '<=', $fechaHasta);
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        // Filtro por JCC
        if ($request->filled('jcc_id')) {
            $jccId = $request->input('jcc_id');
            $query->whereHas('prestamo.carterasJcc', function ($q) use ($jccId) {
                $q->where('jcc_id', $jccId)->where('estado', 1);
            });
        }

        // Filtro por Asesor
        if ($request->filled('asesor_id')) {
            $asesorId = $request->input('asesor_id');
            $query->whereHas('prestamo.carterasAsesor', function ($q) use ($asesorId) {
                $q->where('asesor_id', $asesorId)->where('estado', 1);
            });
        }

        // Filtro por Analista
        if ($request->filled('analista_id')) {
            $analistaId = $request->input('analista_id');
            $query->whereHas('prestamo.carterasAnalista', function ($q) use ($analistaId) {
                $q->where('analista_id', $analistaId)->where('estado', 1);
            });
        }

        // Filtro por días vencidos
        if ($request->filled('dias_vencidos') && is_numeric($request->input('dias_vencidos'))) {
            $diasVencidos = (int) $request->input('dias_vencidos');
            $fechaLimite = Carbon::now()->subDays($diasVencidos)->toDateString();
            $query->where('fecha_compromiso_pago', '<=', $fechaLimite);
        }

        // Filtro por días por vencer
        if ($request->filled('dias_por_vencer') && is_numeric($request->input('dias_por_vencer'))) {
            $diasPorVencer = (int) $request->input('dias_por_vencer');
            $fechaInicio = Carbon::now()->toDateString();
            $fechaLimite = Carbon::now()->addDays($diasPorVencer)->toDateString();
            $query->whereBetween('fecha_compromiso_pago', [$fechaInicio, $fechaLimite]);
        }

        $compromisos = $query->get();

        // Agregar información de carteras a cada compromiso
        $compromisos->each(function ($compromiso) {
            $prestamo = $compromiso->prestamo;

            // JCC activo
            $carteraJccActiva = $prestamo->carterasJcc()->where('estado', 1)->first();
            $compromiso->jcc_activo = $carteraJccActiva ? $carteraJccActiva->jcc : null;

            // Asesor activo
            $carteraAsesorActiva = $prestamo->carterasAsesor()->where('estado', 1)->first();
            $compromiso->asesor_activo = $carteraAsesorActiva ? $carteraAsesorActiva->asesor : null;

            // Analista activo
            $carteraAnalistaActiva = $prestamo->carterasAnalista()->where('estado', 1)->first();
            $compromiso->analista_activo = $carteraAnalistaActiva ? $carteraAnalistaActiva->analista : null;
        });

        // Calcular el estado de vencimiento
        $diasPorVencer = $request->input('dias_por_vencer', 2); // Valor por defecto
        $compromisos->each(function ($compromiso) use ($diasPorVencer) {
            $fechaCompromiso = Carbon::parse($compromiso->fecha_compromiso_pago);
            $hoy = Carbon::now();
            $diferencia = $hoy->diffInDays($fechaCompromiso, false);

            if ($diferencia < 0) {
                $compromiso->vencimiento_status = 'vencido';
            } elseif ($diferencia == 0) {
                $compromiso->vencimiento_status = 'hoy';
            } elseif ($diferencia <= $diasPorVencer) {
                $compromiso->vencimiento_status = 'por_vencer';
            } else {
                $compromiso->vencimiento_status = 'en_plazo';
            }
        });

        // Para la exportación
        if ($request->has('export')) {
            return $this->exportData($compromisos, $request->input('export'));
        }

        // Para carga de filtros
        $jccs = User::role('JCC')->where('status', 1)->whereHas('persona')->with('persona')->get();
        $asesores = User::role('Asesor')->where('status', 1)->whereHas('persona')->with('persona')->get();
        $analistas = User::role('Analista')->where('status', 1)->whereHas('persona')->with('persona')->get();

        if ($request->ajax()) {
            return view('admin.Compromisos.partials.table', compact('compromisos'));
        }

        return view('admin.Compromisos.index', compact('compromisos', 'jccs', 'asesores', 'analistas'));
    }

    /**
     * Exporta los datos filtrados a Excel o PDF
     */
    public function exportData($compromisos, $format)
    {
        if ($format == 'excel') {
            return Excel::download(new CompromisosExport($compromisos), 'compromisos.xlsx');
        } elseif ($format == 'pdf') {
            return PDF::loadView('admin.Compromisos.pdf', compact('compromisos'))
                ->download('compromisos.pdf');
        }

        return redirect()->back()->with('error', 'Formato no soportado');
    }

    /**
     * Muestra el formulario para crear un compromiso.
     */
    public function create(Request $request)
    {
        \Log::debug('CompromisosController@create: Iniciando método create');
        \Log::debug('Datos recibidos: '.print_r($request->all(), true));

        try {
            // Verificar si se proporciona prestamo_id en la URL o en el request
            $prestamo_id = $request->query('prestamo_id') ?? $request->prestamo_id;

            if ($prestamo_id) {
                // Caso 1: Crear compromiso para un préstamo específico
                $validator = \Validator::make(['prestamo_id' => $prestamo_id], [
                    'prestamo_id' => 'required|exists:prestamos,id',
                ]);

                if ($validator->fails()) {
                    throw new \Illuminate\Validation\ValidationException($validator);
                }

                // Cargar el préstamo junto con el cliente y la persona
                $prestamo = Prestamo::with('cliente.persona')->findOrFail($prestamo_id);
                \Log::debug('Préstamo encontrado: '.$prestamo->id);

                return view('admin.Compromisos.create', compact('prestamo'));
            } else {
                // Caso 2: Crear compromiso desde listado general - permitir seleccionar préstamo
                $prestamos = Prestamo::with('cliente.persona')
                    ->whereHas('cliente.persona')
                    ->orderBy('id', 'desc')
                    ->get();

                \Log::debug('Modo selección de préstamo, total préstamos: '.$prestamos->count());

                return view('admin.Compromisos.create-select-prestamo', compact('prestamos'));
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Error de validación: '.print_r($e->errors(), true));

            return redirect()->route('admin.compromisos.index')
                ->with('error', 'No se ha especificado un préstamo válido');
        } catch (\Exception $e) {
            \Log::error('Error en create: '.$e->getMessage());

            return redirect()->route('admin.compromisos.index')
                ->with('error', 'Error al crear el compromiso: '.$e->getMessage());
        }
    }

    /**
     * Almacena un nuevo compromiso en la base de datos.
     */
    public function store(Request $request)
    {
        \Log::debug('CompromisosController@store: Iniciando método store');
        \Log::debug('Datos del formulario: '.print_r($request->all(), true));

        try {
            $validatedData = $request->validate([
                'prestamo_id' => 'required|exists:prestamos,id',
                'fecha' => 'required|date',
                'hora_hh' => 'required|integer|min:8|max:19',
                'hora_mm' => 'required|in:00,15,30,45',
                'monto' => 'required|numeric|min:0',
                'estado' => 'required|in:'.implode(',', [
                    Compromiso::ESTADO_PENDIENTE,
                    Compromiso::ESTADO_PAGADO,
                    Compromiso::ESTADO_POSTERGADO,
                ]),
                'comentario' => 'nullable|string',
            ]);

            \Log::debug('Datos validados: '.print_r($validatedData, true));

            // Combinar horas y minutos en un solo campo
            $hora = $validatedData['hora_hh'].':'.$validatedData['hora_mm'].':00';
            \Log::debug('Hora formateada: '.$hora);

            // Crear un nuevo compromiso
            $compromiso = Compromiso::create([
                'prestamo_id' => $validatedData['prestamo_id'],
                'fecha_compromiso_pago' => $validatedData['fecha'],
                'hora' => $hora,
                'monto' => $validatedData['monto'],
                'estado' => $validatedData['estado'],
                'fecha_registro' => now(),
                'comentario' => $request->comentario,
            ]);

            \Log::debug('Compromiso creado con éxito: '.$compromiso->id);

            return redirect()->route('admin.prestamos.index')->with('success', 'Compromiso creado correctamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Error de validación en store: '.print_r($e->errors(), true));

            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Error al crear el compromiso:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return redirect()->back()->withErrors(['error' => 'Error al crear el compromiso: '.$e->getMessage()])->withInput();
        }
    }

    public function edit($id)
    {
        // Encuentra el compromiso por su ID
        $compromiso = Compromiso::findOrFail($id);

        // Obtén datos adicionales si es necesario
        $prestamo = $compromiso->prestamo;

        // Retorna la vista de edición
        return view('admin.Compromisos.edit', compact('compromiso', 'prestamo'));
    }

    /**
     * Actualiza un compromiso existente en la base de datos.
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'fecha' => 'required|date',
            'hora_hh' => 'required|integer|min:0|max:23',
            'hora_mm' => 'required|in:00,15,30,45',
            'monto' => 'required|numeric|min:0',
            'estado' => 'required|in:'.implode(',', [
                Compromiso::ESTADO_PENDIENTE,
                Compromiso::ESTADO_PAGADO,
                Compromiso::ESTADO_POSTERGADO,
            ]),
            'comentario' => 'nullable|string',
        ]);

        try {
            // Buscar el compromiso existente
            $compromiso = Compromiso::findOrFail($id);

            // Combinar horas y minutos en un solo campo
            $hora = $validatedData['hora_hh'].':'.$validatedData['hora_mm'].':00';

            // Actualizar el compromiso
            $compromiso->update([
                'fecha_compromiso_pago' => $validatedData['fecha'],
                'hora' => $hora,
                'monto' => $validatedData['monto'],
                'estado' => $validatedData['estado'],
                'comentario' => $request->comentario,
            ]);

            return redirect()->route('admin.prestamos.index')->with('success', 'Compromiso actualizado correctamente.');
        } catch (\Exception $e) {
            \Log::error('Error al actualizar el compromiso:', ['error' => $e->getMessage()]);

            return redirect()->back()->withErrors(['error' => 'Error al actualizar el compromiso. Inténtalo de nuevo.']);
        }
    }

    /**
     * Elimina un compromiso de la base de datos.
     */
    public function destroy($id)
    {
        try {
            $compromiso = Compromiso::findOrFail($id);
            $compromiso->delete();

            return redirect()->route('admin.prestamos.index')->with('success', 'Compromiso eliminado correctamente.');
        } catch (\Exception $e) {
            \Log::error('Error al eliminar el compromiso:', ['error' => $e->getMessage()]);

            return redirect()->back()->withErrors(['error' => 'Error al eliminar el compromiso. Inténtalo de nuevo.']);
        }
    }
}
