<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValidacionOperacionesController extends Controller
{
    /**
     * Display a listing of operations for validation.
     */
    public function index(Request $request)
    {
        $query = Operacion::with([
            'prestamo.cliente.persona',
            'metodoDePago',
            'user',
            'anuladoPor',
        ]);

        // Filtrar solo operaciones de pago (no desembolsos)
        $query->whereIn('tipo_operacion', ['Pago de cuota', 'Pago de mora', 'Pago general']);
        $query->where('estado', '!=', 'anulado'); // Excluir anuladas por defecto

        // Filtro por fecha
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        // Filtros rápidos
        if ($request->filled('filtro_rapido')) {
            switch ($request->filtro_rapido) {
                case 'hoy':
                    $query->whereDate('created_at', now()->toDateString());
                    break;
                case 'semana':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
            }
        }

        // Filtro por estado de validación
        if ($request->filled('estado')) {
            $estadoFiltro = $request->estado;
            if ($estadoFiltro === 'por_validar') {
                $query->where(function ($q) {
                    $q->whereNull('estado_validacion')
                        ->orWhere('estado_validacion', 'por_validar');
                });
            } else {
                $query->where('estado_validacion', $estadoFiltro);
            }
        }

        // Búsqueda por número de operación o cliente
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'LIKE', "%{$buscar}%")
                    ->orWhereHas('prestamo.cliente.persona', function ($subq) use ($buscar) {
                        $subq->where('nombres', 'LIKE', "%{$buscar}%")
                            ->orWhere('ape_pat', 'LIKE', "%{$buscar}%")
                            ->orWhere('ape_mat', 'LIKE', "%{$buscar}%")
                            ->orWhere('documento', 'LIKE', "%{$buscar}%");
                    });
            });
        }

        // Ordenar por fecha descendente (más recientes primero)
        $query->orderBy('created_at', 'desc');

        $operaciones = $query->paginate(20)->withQueryString();

        // Obtener estadísticas
        $estadisticas = $this->getEstadisticas();

        return view('admin.validacion-operaciones.index', compact('operaciones', 'estadisticas'));
    }

    /**
     * Validate an operation.
     */
    public function validar(Request $request, $operacionId)
    {
        try {
            DB::beginTransaction();

            $operacion = Operacion::findOrFail($operacionId);

            // Verificar que la operación se pueda validar
            if ($operacion->estado === 'anulado') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede validar una operación anulada.',
                ], 400);
            }

            if ($operacion->estado_validacion === 'validado') {
                return response()->json([
                    'success' => false,
                    'message' => 'La operación ya está validada.',
                ], 400);
            }

            // Actualizar estado de validación
            $operacion->update([
                'estado_validacion' => 'validado',
                'validado_por' => auth()->id(),
                'validado_en' => now(),
                'observaciones_validacion' => null, // Limpiar observaciones previas
            ]);

            Log::info("Operación validada - ID: {$operacionId} por usuario: ".auth()->id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Operación validada correctamente.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al validar operación {$operacionId}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark an operation as observed.
     */
    public function observar(Request $request, $operacionId)
    {
        $request->validate([
            'observaciones' => 'required|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $operacion = Operacion::findOrFail($operacionId);

            // Verificar que la operación se pueda observar
            if ($operacion->estado === 'anulado') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede observar una operación anulada.',
                ], 400);
            }

            // Actualizar estado de observación
            $operacion->update([
                'estado_validacion' => 'observado',
                'observado_por' => auth()->id(),
                'observado_en' => now(),
                'observaciones_validacion' => $request->observaciones,
            ]);

            Log::info("Operación observada - ID: {$operacionId} por usuario: ".auth()->id().' - Motivo: '.$request->observaciones);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Operación observada correctamente.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al observar operación {$operacionId}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get validation statistics.
     */
    public function estadisticas()
    {
        $stats = $this->getEstadisticas();
        return response()->json($stats);
    }

    /**
     * Get statistics for validation operations
     */
    private function getEstadisticas()
    {
        return [
            'por_validar' => Operacion::whereIn('tipo_operacion', ['Pago de cuota', 'Pago de mora', 'Pago general'])
                ->where(function ($q) {
                    $q->whereNull('estado_validacion')->orWhere('estado_validacion', 'por_validar');
                })
                ->where('estado', '!=', 'anulado')
                ->count(),

            'validadas' => Operacion::whereIn('tipo_operacion', ['Pago de cuota', 'Pago de mora', 'Pago general'])
                ->where('estado_validacion', 'validado')
                ->where('estado', '!=', 'anulado')
                ->count(),

            'observadas' => Operacion::whereIn('tipo_operacion', ['Pago de cuota', 'Pago de mora', 'Pago general'])
                ->where('estado_validacion', 'observado')
                ->where('estado', '!=', 'anulado')
                ->count(),

            'anuladas' => Operacion::whereIn('tipo_operacion', ['Pago de cuota', 'Pago de mora', 'Pago general'])
                ->where('estado', 'anulado')
                ->count(),
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Validate all pending operations
     */
    public function validarTodas(Request $request)
    {
        try {
            DB::beginTransaction();

            $operaciones = Operacion::whereIn('tipo_operacion', ['Pago de cuota', 'Pago de mora', 'Pago general'])
                ->where(function ($q) {
                    $q->whereNull('estado_validacion')->orWhere('estado_validacion', 'por_validar');
                })
                ->where('estado', '!=', 'anulado')
                ->get();

            $count = 0;
            foreach ($operaciones as $operacion) {
                $operacion->update([
                    'estado_validacion' => 'validado',
                    'validado_por' => auth()->id(),
                    'validado_en' => now(),
                    'observaciones_validacion' => null,
                ]);
                $count++;
            }

            Log::info("Validación masiva - {$count} operaciones validadas por usuario: ".auth()->id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Se validaron {$count} operaciones correctamente.",
                'count' => $count
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en validación masiva: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel/annul an operation
     */
    public function anular(Request $request, $operacionId)
    {
        $request->validate([
            'justificacion' => 'required|string|max:500',
        ]);

        try {
            $operacion = Operacion::with(['cuotas', 'morasCuota', 'prestamo'])->findOrFail($operacionId);

            // Verificar que la operación se pueda anular
            if ($operacion->estado === 'anulado') {
                return response()->json([
                    'success' => false,
                    'message' => 'La operación ya está anulada.',
                ], 400);
            }

            // Usar servicio centralizado de anulación
            $estadoPrestamoService = new \App\Services\EstadoPrestamoService();
            $resultado = $estadoPrestamoService->anularOperacion(
                $operacion,
                $request->justificacion,
                auth()->id()
            );

            Log::info("Operación anulada - ID: {$operacionId} por usuario: ".auth()->id().' - Motivo: '.$request->justificacion, [
                'cuotas_afectadas' => count($resultado['cuotas_afectadas'] ?? []),
                'moras_afectadas' => count($resultado['moras_afectadas'] ?? []),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Operación anulada correctamente. Relaciones eliminadas y estados recalculados.',
                'data' => [
                    'cuotas_afectadas' => count($resultado['cuotas_afectadas'] ?? []),
                    'moras_afectadas' => count($resultado['moras_afectadas'] ?? []),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("Error al anular operación {$operacionId}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show operation details
     */
    public function show($id)
    {
        $operacion = Operacion::with([
            'prestamo.cliente.persona',
            'metodoDePago',
            'user',
            'validadoPor',
            'observadoPor',
            'anuladoPor',
            'operacionesCuota.cuota',
        ])->findOrFail($id);

        // Verificar si el voucher existe
        $voucherUrl = null;
        if ($operacion->voucher_path) {
            $voucherPath = storage_path('app/public/' . $operacion->voucher_path);
            if (file_exists($voucherPath)) {
                $voucherUrl = asset('storage/' . $operacion->voucher_path);
            } else {
                // Buscar en rutas alternativas comunes
                $alternativePaths = [
                    'vouchers/' . basename($operacion->voucher_path),
                    'depositos/' . basename($operacion->voucher_path),
                    'rendiciones/' . basename($operacion->voucher_path),
                ];

                foreach ($alternativePaths as $altPath) {
                    if (file_exists(storage_path('app/public/' . $altPath))) {
                        $voucherUrl = asset('storage/' . $altPath);
                        break;
                    }
                }
            }
        }

        return response()->json([
            'operacion' => $operacion,
            'voucher_url' => $voucherUrl,
            'voucher_exists' => $voucherUrl !== null,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
