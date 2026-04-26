<?php

namespace App\Http\Controllers\Admin;

use App\Exports\GestionesExport;
use App\Http\Controllers\Controller;
use App\Models\Compromiso;
use App\Models\Cuota;
use App\Models\EstadoGestion;
use App\Models\Gestion;
use App\Models\Prestamo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class GestionCobranzaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Obtener todos los estados y asesores para los filtros
        $estados = EstadoGestion::all();
        $asesores = User::whereHas('gestiones')->get();

        // Iniciar la consulta - Solo gestiones principales (no de seguimiento)
        $query = Gestion::principales()
            ->with([
                'prestamo.cliente.persona',
                'estadoGestion',
                'compromiso',
                'asesor',
            ]);

        // Aplicar filtros si existen

        // Filtro por cliente
        if ($request->filled('cliente')) {
            $cliente = $request->cliente;
            $query->whereHas('prestamo.cliente.persona', function ($q) use ($cliente) {
                $q->where(DB::raw("CONCAT(nombres, ' ', ape_pat, ' ', ape_mat)"), 'LIKE', "%{$cliente}%");
            });
        }

        // Filtro por estado
        if ($request->filled('estado_id')) {
            $query->where('estado_id', $request->estado_id);
        }

        // Filtro por asesor
        if ($request->filled('asesor_id')) {
            $query->where('asesor_id', $request->asesor_id);
        }

        // Filtro por compromiso
        if ($request->filled('tiene_compromiso')) {
            if ($request->tiene_compromiso == '1') {
                $query->whereNotNull('compromiso_id');
            } else {
                $query->whereNull('compromiso_id');
            }
        }

        // Filtro por fecha desde
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }

        // Filtro por fecha hasta
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        // Exportar a Excel si se solicita
        if ($request->has('export') && $request->export == 'excel') {
            return Excel::download(new GestionesExport($query), 'gestiones.xlsx');
        }

        // Definir número de elementos por página
        $perPage = $request->per_page ?? 10;

        // Obtener resultados paginados
        $gestiones = $query->latest()->paginate($perPage);

        return view('admin.Gestiones.index', compact('gestiones', 'estados', 'asesores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        Log::debug('GestionCobranzaController@create: Iniciando método create');
        Log::debug('Datos recibidos: '.print_r($request->all(), true));

        // Obtener parámetros
        $prestamo_id = $request->query('prestamo_id');
        $compromiso_id = $request->query('compromiso_id');

        // Caso 1: Crear gestión de seguimiento desde un compromiso
        if ($compromiso_id) {
            $compromiso = Compromiso::with('prestamo.cliente.persona')->find($compromiso_id);

            if (! $compromiso) {
                return redirect()->route('admin.gestiones.index')
                    ->withErrors(['error' => 'Compromiso no encontrado.']);
            }

            $prestamo = $compromiso->prestamo;
            $estados = EstadoGestion::all();

            // Calcular el saldo del préstamo
            $cuotas_pagadas = Cuota::where('prestamo_id', $prestamo->id)
                ->where('estado', 1)
                ->sum('monto');
            $saldo_prestamo = $prestamo->cantidad_solicitada - $cuotas_pagadas;

            return view('admin.Gestiones.create', compact('prestamo', 'compromiso', 'saldo_prestamo', 'estados'));
        }

        // Caso 2: Crear gestión desde un préstamo (lógica original)
        if ($prestamo_id) {
            $prestamo = Prestamo::with('cliente.persona')->find($prestamo_id);

            if (! $prestamo) {
                return redirect()->route('admin.prestamos.index')
                    ->withErrors(['error' => 'Préstamo no encontrado.']);
            }

            $estados = EstadoGestion::all();

            // Calcular el saldo del préstamo
            $cuotas_pagadas = Cuota::where('prestamo_id', $prestamo_id)
                ->where('estado', 1)
                ->sum('monto');
            $saldo_prestamo = $prestamo->cantidad_solicitada - $cuotas_pagadas;

            return view('admin.Gestiones.create', compact('prestamo', 'saldo_prestamo', 'estados'));
        }

        // Caso 3: Sin parámetros válidos
        return redirect()->route('admin.prestamos.index')
            ->withErrors(['error' => 'Debe especificar un préstamo o compromiso para crear la gestión.']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::debug('GestionCobranzaController@store: Iniciando método store');
        Log::debug('Datos recibidos: '.print_r($request->all(), true));

        // Validar los datos básicos de la gestión (siempre requeridos)
        $validatedData = $request->validate([
            'prestamo_id' => 'required|exists:prestamos,id',
            'estado_id' => 'required|exists:estados_gestion,id',
            'fecha' => 'required|date',
            'observaciones' => 'required|string|max:1000',
            'latitud' => 'nullable|string',
            'longitud' => 'nullable|string',
        ], [
            'prestamo_id.required' => 'El campo prestamo id es obligatorio.',
            'estado_id.required' => 'El estado de la gestión es obligatorio.',
            'fecha.required' => 'La fecha de visita es obligatoria.',
            'observaciones.required' => 'Las observaciones de la gestión son obligatorias.',
        ]);

        try {
            // Crear la gestión
            $gestion = new Gestion;
            $gestion->prestamo_id = $request->prestamo_id;
            $gestion->estado_id = $request->estado_id;
            $gestion->fecha = $request->fecha;
            $gestion->observaciones = $request->observaciones;

            // Registrar el asesor (usuario autenticado) que crea la gestión
            $gestion->asesor_id = Auth::id();

            // Si es una gestión de seguimiento de compromiso, guardar la referencia
            if ($request->has('compromiso_seguimiento_id')) {
                $gestion->compromiso_seguimiento_id = $request->compromiso_seguimiento_id;
            }

            // Guardar coordenadas de ubicación
            if ($request->has('latitud') && $request->has('longitud')) {
                $gestion->latitud = $request->latitud;
                $gestion->longitud = $request->longitud;
            }

            $gestion->save();

            // Si se seleccionó "compromisoPago", validar y crear el compromiso
            if ($request->has('compromisoPago') && $request->compromisoPago == '1') {
                Log::debug('Procesando compromiso de pago');

                // Validar datos del compromiso
                $compromisoData = $request->validate([
                    'estado' => 'required|in:pendiente,cumplido,incumplido',
                    'fecha_compromiso' => 'required|date',
                    'hora_compromiso' => 'required|date_format:H:i',
                    'monto' => 'required|numeric|min:0',
                    'observaciones_compromiso' => 'nullable|string|max:1000',
                ], [
                    'estado.required' => 'El estado del compromiso es obligatorio.',
                    'estado.in' => 'El estado del compromiso debe ser pendiente, cumplido o incumplido.',
                    'fecha_compromiso.required' => 'La fecha del compromiso es obligatoria.',
                    'hora_compromiso.required' => 'La hora del compromiso es obligatoria.',
                    'monto.required' => 'El monto del compromiso es obligatorio.',
                    'monto.numeric' => 'El monto debe ser un número válido.',
                    'monto.min' => 'El monto debe ser mayor a 0.',
                ]);

                // Formatear la hora
                $hora = $request->hora_compromiso.':00';

                // Crear el compromiso
                $compromiso = new Compromiso;
                $compromiso->gestion_id = $gestion->id;
                $compromiso->prestamo_id = $request->prestamo_id;
                $compromiso->estado = $request->estado;
                $compromiso->fecha_compromiso_pago = $request->fecha_compromiso;
                $compromiso->hora = $hora;
                $compromiso->monto = $request->monto;
                $compromiso->comentario = $request->observaciones_compromiso;
                $compromiso->fecha_registro = now();
                $compromiso->save();

                // Actualizar el compromiso_id en la gestión
                $gestion->compromiso_id = $compromiso->id;
                $gestion->save();
            }

            // Redirigir con mensaje de éxito
            return redirect()->route('admin.gestiones.index')
                ->with('success', 'Gestión de cobranza registrada exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Error de validación: '.print_r($e->errors(), true));

            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            // Registrar error
            Log::error('Error al crear gestión:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Redirigir con mensaje de error
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Error al registrar la gestión: '.$e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $gestion = Gestion::with([
            'prestamo.cliente.persona',
            'estadoGestion',
            'compromiso',
            'asesor',
            'pago',
            'adjuntos',
        ])
            ->findOrFail($id);

        // Obtener historial de gestiones del mismo préstamo
        $historialPrestamo = Gestion::with([
            'estadoGestion',
            'compromiso',
            'asesor',
            'pago',
        ])
            ->where('prestamo_id', $gestion->prestamo_id)
            ->where('id', '!=', $id) // Excluir la gestión actual
            ->orderBy('fecha', 'desc')
            ->get();

        // Si la gestión tiene compromiso, obtener historial del compromiso
        $historialCompromiso = collect();
        if ($gestion->compromiso) {
            $historialCompromiso = Gestion::with([
                'estadoGestion',
                'asesor',
                'pago',
            ])
                ->where('compromiso_id', $gestion->compromiso_id)
                ->where('id', '!=', $id) // Excluir la gestión actual
                ->orderBy('fecha', 'desc')
                ->get();
        }

        // Obtener compromisos relacionados al préstamo
        $compromisosRelacionados = \App\Models\Compromiso::with(['gestiones.asesor'])
            ->where('prestamo_id', $gestion->prestamo_id)
            ->orderBy('fecha_compromiso_pago', 'desc')
            ->get();

        return view('admin.Gestiones.show', compact(
            'gestion',
            'historialPrestamo',
            'historialCompromiso',
            'compromisosRelacionados'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $gestion = Gestion::with([
            'prestamo.cliente.persona',
            'compromiso',
            'asesor',
        ])
            ->findOrFail($id);

        $estados = EstadoGestion::all();

        return view('admin.Gestiones.edit', compact('gestion', 'estados'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Validar los datos básicos de la gestión (siempre requeridos)
        $validatedData = $request->validate([
            'prestamo_id' => 'required|exists:prestamos,id',
            'estado_id' => 'required|exists:estados_gestion,id',
            'fecha' => 'required|date',
            'observaciones' => 'required|string|max:1000',
            'latitud' => 'nullable|string',
            'longitud' => 'nullable|string',
        ], [
            'estado_id.required' => 'El estado de la gestión es obligatorio.',
            'fecha.required' => 'La fecha de visita es obligatoria.',
            'observaciones.required' => 'Las observaciones de la gestión son obligatorias.',
        ]);

        try {
            $gestion = Gestion::findOrFail($id);

            // Actualizar la gestión
            $gestion->estado_id = $request->estado_id;
            $gestion->fecha = $request->fecha;
            $gestion->observaciones = $request->observaciones;

            // Actualizar coordenadas si están presentes
            if ($request->has('latitud') && $request->has('longitud')) {
                $gestion->latitud = $request->latitud;
                $gestion->longitud = $request->longitud;
            }

            $gestion->save();

            // Manejar el compromiso de pago
            if ($request->has('compromisoPago')) {
                // Validar datos del compromiso
                $compromisoData = $request->validate([
                    'estado' => 'required|in:pendiente,cumplido,incumplido',
                    'fecha_compromiso' => 'required|date',
                    'hora_compromiso' => 'required|date_format:H:i',
                    'monto' => 'required|numeric|min:0',
                    'observaciones_compromiso' => 'nullable|string|max:1000',
                ], [
                    'estado.required' => 'El estado del compromiso es obligatorio.',
                    'estado.in' => 'El estado del compromiso debe ser pendiente, cumplido o incumplido.',
                    'fecha_compromiso.required' => 'La fecha del compromiso es obligatoria.',
                    'hora_compromiso.required' => 'La hora del compromiso es obligatoria.',
                    'monto.required' => 'El monto del compromiso es obligatorio.',
                ]);

                // Formatear la hora
                $hora = $request->hora_compromiso.':00';

                if ($gestion->compromiso) {
                    // Actualizar compromiso existente
                    $gestion->compromiso->estado = $request->estado;
                    $gestion->compromiso->fecha_compromiso_pago = $request->fecha_compromiso;
                    $gestion->compromiso->hora = $hora;
                    $gestion->compromiso->monto = $request->monto;
                    $gestion->compromiso->comentario = $request->observaciones_compromiso;
                    $gestion->compromiso->save();
                } else {
                    // Crear nuevo compromiso
                    $compromiso = new Compromiso;
                    $compromiso->gestion_id = $gestion->id;
                    $compromiso->prestamo_id = $request->prestamo_id;
                    $compromiso->estado = $request->estado;
                    $compromiso->fecha_compromiso_pago = $request->fecha_compromiso;
                    $compromiso->hora = $hora;
                    $compromiso->monto = $request->monto;
                    $compromiso->comentario = $request->observaciones_compromiso;
                    $compromiso->fecha_registro = now();
                    $compromiso->save();

                    // Actualizar la referencia en la gestión
                    $gestion->compromiso_id = $compromiso->id;
                    $gestion->save();
                }
            } else {
                // Si se desmarcó el checkbox y existía un compromiso, eliminar la relación
                if ($gestion->compromiso) {
                    // Opción 1: Eliminar físicamente el compromiso
                    // $gestion->compromiso->delete();

                    // Opción 2: Solo eliminar la referencia en la gestión
                    $gestion->compromiso_id = null;
                    $gestion->save();
                }
            }

            return redirect()->route('admin.gestiones.index')
                ->with('success', 'Gestión y compromiso actualizados correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar la gestión:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Error al actualizar la gestión: '.$e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $gestion = Gestion::findOrFail($id);

            // Si la gestión tiene un compromiso asociado, eliminarlo primero
            if ($gestion->compromiso) {
                $gestion->compromiso->delete();
            }

            $gestion->delete();

            return redirect()->route('admin.gestiones.index')
                ->with('success', 'Gestión eliminada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar la gestión:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Error al eliminar la gestión: '.$e->getMessage()]);
        }
    }
}
