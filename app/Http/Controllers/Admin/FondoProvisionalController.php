<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FondoProvisional;
use App\Models\Operacion;
use App\Models\Prestamo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FondoProvisionalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = FondoProvisional::with(['prestamo.cliente.persona', 'asesor', 'operacion', 'rendidoPor']);

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('asesor_id')) {
            $query->where('asesor_id', $request->asesor_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_entrega', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_entrega', '<=', $request->fecha_hasta);
        }

        $fondos = $query->latest()->paginate(15);

        return view('admin.fondo-provisional.index', compact('fondos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $prestamo_id = $request->query('prestamo_id');

        if (! $prestamo_id) {
            return redirect()->route('admin.prestamos.index')
                ->withErrors(['error' => 'Debe especificar un préstamo para crear el fondo provisional.']);
        }

        $prestamo = Prestamo::with('cliente.persona')->find($prestamo_id);

        if (! $prestamo) {
            return redirect()->route('admin.prestamos.index')
                ->withErrors(['error' => 'Préstamo no encontrado.']);
        }

        // Verificar si ya existe un fondo provisional para este préstamo
        $fondoExistente = FondoProvisional::where('prestamo_id', $prestamo_id)->first();
        if ($fondoExistente) {
            return redirect()->route('admin.prestamos.show', $prestamo_id)
                ->withErrors(['error' => 'Ya existe un fondo provisional para este préstamo.']);
        }

        // Calcular el monto del fondo provisional (5% del capital)
        $montoCapital = $prestamo->cantidad_solicitada;
        $montoFondo = FondoProvisional::calcularMontoFondo($montoCapital);

        return view('admin.fondo-provisional.create', compact('prestamo', 'montoCapital', 'montoFondo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Obtener el préstamo para calcular el máximo permitido
        $prestamo = Prestamo::findOrFail($request->prestamo_id);
        $montoMaximo = FondoProvisional::calcularMontoFondo($prestamo->cantidad_solicitada);

        // Verificar si está exonerado
        $exonerado = $request->has('exonerar_fondo') && $request->exonerar_fondo == '1';

        // Reglas de validación base
        $rules = [
            'prestamo_id' => 'required|exists:prestamos,id|unique:fondo_provisional,prestamo_id',
            'monto_capital' => 'required|numeric|min:0',
            'monto_fondo' => 'required|numeric|min:0',
        ];

        // Mensajes de validación
        $messages = [
            'prestamo_id.required' => 'El préstamo es obligatorio.',
            'prestamo_id.unique' => 'Ya existe un fondo provisional para este préstamo.',
            'monto_capital.required' => 'El monto del capital es obligatorio.',
            'monto_fondo.required' => 'El monto del fondo provisional es obligatorio.',
            'observaciones.required' => 'Las observaciones son obligatorias cuando se exonera el fondo.',
        ];

        // Si está exonerado, permitir monto 0 y requerir observaciones
        if ($exonerado) {
            $rules['monto_personalizado'] = 'nullable|numeric|min:0|max:0';
            $rules['observaciones'] = 'required|string|max:1000';
            $rules['fecha_entrega'] = 'nullable|date';
        } else {
            // Si no está exonerado, validar normalmente
            $rules['monto_personalizado'] = 'required|numeric|min:0.01|max:'.$montoMaximo;
            $rules['fecha_entrega'] = 'required|date';
            $rules['metodo_pago'] = 'required|in:efectivo,yape';
            $rules['observaciones'] = 'nullable|string|max:1000';

            // Validación condicional para Yape
            if ($request->metodo_pago === 'yape') {
                $rules['nro_operacion_yape'] = 'nullable|string|max:255';
                $rules['imagen_yape'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048';
            }

            $messages['monto_personalizado.required'] = 'El monto personalizado es obligatorio.';
            $messages['monto_personalizado.min'] = 'El monto debe ser mayor a S/ 0.01.';
            $messages['monto_personalizado.max'] = 'El monto no puede exceder S/ '.number_format($montoMaximo, 2);
            $messages['fecha_entrega.required'] = 'La fecha de entrega es obligatoria.';
            $messages['metodo_pago.required'] = 'El método de pago es obligatorio.';
            $messages['metodo_pago.in'] = 'El método de pago debe ser efectivo o yape.';
        }

        $validatedData = $request->validate($rules, $messages);

        try {
            DB::beginTransaction();

            // Si está exonerado, establecer monto en 0
            $montoFinal = $exonerado ? 0 : $validatedData['monto_personalizado'];
            
            // Calcular el porcentaje real basado en el monto personalizado
            $porcentajeReal = $montoFinal > 0 ? ($montoFinal / $validatedData['monto_capital']) * 100 : 0;

            // Crear el fondo provisional
            $fondoProvisional = FondoProvisional::create([
                'prestamo_id' => $validatedData['prestamo_id'],
                'asesor_id' => Auth::id(),
                'monto_capital' => $validatedData['monto_capital'],
                'porcentaje' => round($porcentajeReal, 2),
                'monto_fondo' => $montoFinal,
                'fecha_entrega' => $validatedData['fecha_entrega'] ?? now(),
                'estado' => $exonerado ? FondoProvisional::ESTADO_EXONERADO : FondoProvisional::ESTADO_ENTREGADO,
                'observaciones' => $validatedData['observaciones'],
            ]);

            // Solo registrar operación si NO está exonerado
            if (!$exonerado && $montoFinal > 0) {
                // Mapear método de pago: efectivo = 1, yape = 3
                $metodoPagoId = $validatedData['metodo_pago'] === 'yape' ? 3 : 1;

                // Procesar imagen de Yape si existe
                $voucherPath = null;
                if ($request->hasFile('imagen_yape')) {
                    $voucherPath = $request->file('imagen_yape')->store('fondos_provisionales', 'public');
                }

                $operacion = Operacion::create([
                    'prestamo_id' => $validatedData['prestamo_id'],
                    'cliente_id' => $prestamo->cliente_id,
                    'fecha' => $validatedData['fecha_entrega'],
                    'metodo_pago_id' => $metodoPagoId,
                    'abono' => $montoFinal,
                    'tipo_operacion' => 'Fondo Provisional',
                    'estado_rendicion' => 'pendiente',
                    'user_id' => Auth::id(),
                    'codigo' => $request->input('nro_operacion_yape'),
                    'voucher_path' => $voucherPath,
                    'comentario' => 'Fondo provisional entregado por el cliente ('.round($porcentajeReal, 2)."% del capital: S/ {$validatedData['monto_capital']})",
                ]);

                // Actualizar fondo provisional con la operación creada
                $fondoProvisional->update(['operacion_id' => $operacion->id]);
            }

            DB::commit();

            $mensaje = $exonerado
                ? 'Fondo provisional exonerado exitosamente.'
                : 'Fondo provisional registrado exitosamente.';

            // Si está exonerado O viene del proceso de préstamo, redirigir al proceso
            if ($exonerado || $request->has('desde_proceso') || ($request->header('referer') && strpos($request->header('referer'), 'proceso-prestamo') !== false)) {
                return redirect()->route('admin.proceso-prestamo.index', $validatedData['prestamo_id'])
                    ->with('success', $mensaje);
            }

            return redirect()->route('admin.prestamos.show', $validatedData['prestamo_id'])
                ->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al crear fondo provisional:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Error al registrar el fondo provisional: '.$e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $fondo = FondoProvisional::with([
            'prestamo.cliente.persona',
            'asesor',
            'operacion.metodoDePago',
            'rendidoPor',
        ])->findOrFail($id);

        $metodosPago = \App\Models\MetodoDePago::activos()->get();

        return view('admin.fondo-provisional.show', compact('fondo', 'metodosPago'));
    }

    /**
     * Actualizar fondo provisional (solo Admin)
     */
    public function update(Request $request, string $id)
    {
        if (!Auth::user()->hasRole('Admin')) {
            return redirect()->back()
                ->withErrors(['error' => 'No tiene permisos para editar este fondo provisional.']);
        }

        $fondo = FondoProvisional::with('operacion')->findOrFail($id);

        $rules = [
            'monto_fondo' => 'required|numeric|min:0',
            'metodo_pago_id' => 'required|exists:metodos_de_pago,id',
            'codigo' => 'nullable|string|max:255',
            'voucher_imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        $validatedData = $request->validate($rules);

        try {
            DB::beginTransaction();

            // Actualizar monto del fondo
            $fondo->update([
                'monto_fondo' => $validatedData['monto_fondo'],
            ]);

            // Actualizar la operación asociada si existe
            if ($fondo->operacion) {
                $updateData = [
                    'metodo_pago_id' => $validatedData['metodo_pago_id'],
                    'abono' => $validatedData['monto_fondo'],
                    'codigo' => $validatedData['codigo'],
                ];

                // Procesar nueva imagen si se subió
                if ($request->hasFile('voucher_imagen')) {
                    // Eliminar imagen anterior si existe
                    if ($fondo->operacion->voucher_path) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($fondo->operacion->voucher_path);
                    }
                    $updateData['voucher_path'] = $request->file('voucher_imagen')->store('fondos_provisionales', 'public');
                }

                $fondo->operacion->update($updateData);
            }

            DB::commit();

            return redirect()->route('admin.fondo-provisional.show', $fondo->id)
                ->with('success', 'Fondo provisional actualizado exitosamente.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al actualizar fondo provisional:', [
                'error' => $e->getMessage(),
                'fondo_id' => $id,
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Error al actualizar el fondo provisional: ' . $e->getMessage()]);
        }
    }

    /**
     * Marcar fondo como rendido
     */
    public function marcarRendido(Request $request, $id)
    {
        $fondo = FondoProvisional::findOrFail($id);

        if (! $fondo->puedeSerRendido()) {
            return redirect()->back()
                ->withErrors(['error' => 'Este fondo provisional no puede ser marcado como rendido.']);
        }

        $request->validate([
            'fecha_rendicion' => 'required|date',
        ]);

        try {
            $fondo->update([
                'estado' => FondoProvisional::ESTADO_RENDIDO,
                'fecha_rendicion' => $request->fecha_rendicion,
                'rendido_por' => Auth::id(),
            ]);

            return redirect()->back()
                ->with('success', 'Fondo provisional marcado como rendido exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error al marcar fondo como rendido:', [
                'error' => $e->getMessage(),
                'fondo_id' => $id,
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Error al marcar como rendido: '.$e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();

            $fondo = FondoProvisional::findOrFail($id);

            // Eliminar la operación asociada si existe
            if ($fondo->operacion) {
                $fondo->operacion->delete();
            }

            // Eliminar el fondo provisional
            $fondo->delete();

            DB::commit();

            return redirect()->route('admin.fondo-provisional.index')
                ->with('success', 'Fondo provisional eliminado exitosamente.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al eliminar fondo provisional:', [
                'error' => $e->getMessage(),
                'fondo_id' => $id,
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Error al eliminar el fondo provisional: '.$e->getMessage()]);
        }
    }
}
