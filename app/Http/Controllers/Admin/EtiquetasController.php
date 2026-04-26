<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Etiqueta;
use App\Models\EtiquetaCliente;
use App\Models\Prestamo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EtiquetasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Etiqueta::query();

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('search')) {
            $query->where('etiqueta', 'like', '%'.$request->search.'%');
        }

        // Si es una petición AJAX, devolver JSON
        if ($request->ajax() || $request->has('ajax')) {
            $etiquetas = $query->where('estado', 1)->latest()->get();

            return response()->json([
                'success' => true,
                'etiquetas' => $etiquetas,
            ]);
        }

        $etiquetas = $query->latest()->paginate(15);

        return view('admin.etiquetas.index', compact('etiquetas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.etiquetas.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'etiqueta' => 'required|string|max:255|unique:etiquetas,etiqueta',
            'color' => 'required|string|max:7',
            'estado' => 'required|boolean',
        ], [
            'etiqueta.required' => 'El nombre de la etiqueta es obligatorio.',
            'etiqueta.unique' => 'Ya existe una etiqueta con este nombre.',
            'color.required' => 'El color es obligatorio.',
            'estado.required' => 'El estado es obligatorio.',
        ]);

        try {
            Etiqueta::create($validatedData);

            return redirect()->route('admin.etiquetas.index')
                ->with('success', 'Etiqueta creada exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error al crear etiqueta:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Error al crear la etiqueta: '.$e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $etiqueta = Etiqueta::with(['etiquetasCliente.cliente.persona', 'etiquetasCliente.prestamo'])
            ->findOrFail($id);

        return view('admin.etiquetas.show', compact('etiqueta'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $etiqueta = Etiqueta::findOrFail($id);

        return view('admin.etiquetas.edit', compact('etiqueta'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $etiqueta = Etiqueta::findOrFail($id);

        $validatedData = $request->validate([
            'etiqueta' => 'required|string|max:255|unique:etiquetas,etiqueta,'.$id,
            'color' => 'required|string|max:7',
            'estado' => 'required|boolean',
        ], [
            'etiqueta.required' => 'El nombre de la etiqueta es obligatorio.',
            'etiqueta.unique' => 'Ya existe una etiqueta con este nombre.',
            'color.required' => 'El color es obligatorio.',
            'estado.required' => 'El estado es obligatorio.',
        ]);

        try {
            $etiqueta->update($validatedData);

            return redirect()->route('admin.etiquetas.index')
                ->with('success', 'Etiqueta actualizada exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error al actualizar etiqueta:', [
                'error' => $e->getMessage(),
                'etiqueta_id' => $id,
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Error al actualizar la etiqueta: '.$e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();

            $etiqueta = Etiqueta::findOrFail($id);

            // Verificar si la etiqueta está en uso
            $enUso = EtiquetaCliente::where('etiqueta_id', $id)->exists();

            if ($enUso) {
                return redirect()->back()
                    ->withErrors(['error' => 'No se puede eliminar la etiqueta porque está asignada a uno o más clientes.']);
            }

            $etiqueta->delete();

            DB::commit();

            return redirect()->route('admin.etiquetas.index')
                ->with('success', 'Etiqueta eliminada exitosamente.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al eliminar etiqueta:', [
                'error' => $e->getMessage(),
                'etiqueta_id' => $id,
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Error al eliminar la etiqueta: '.$e->getMessage()]);
        }
    }

    /**
     * Asignar etiqueta a un cliente/préstamo
     */
    public function asignar(Request $request)
    {
        $validatedData = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'prestamo_id' => 'required|exists:prestamos,id',
            'etiqueta_id' => 'required|exists:etiquetas,id',
            'observacion' => 'nullable|string|max:500',
        ]);

        try {
            // Verificar que el préstamo pertenezca al cliente
            $prestamo = Prestamo::where('id', $validatedData['prestamo_id'])
                ->where('cliente_id', $validatedData['cliente_id'])
                ->first();

            if (! $prestamo) {
                return response()->json([
                    'success' => false,
                    'message' => 'El préstamo no pertenece al cliente seleccionado.',
                ], 400);
            }

            // Verificar que el préstamo esté en un estado válido para asignación de etiquetas
            $estadosPermitidos = ['Vigente', 'Moroso', 'Por Desembolsar'];
            if (! in_array($prestamo->estado, $estadosPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden asignar etiquetas a préstamos Vigentes, Morosos o Por Desembolsar.',
                ], 400);
            }

            // Verificar si ya existe una etiqueta para este préstamo
            $etiquetaExistente = EtiquetaCliente::where('prestamo_id', $validatedData['prestamo_id'])->first();

            if ($etiquetaExistente) {
                // Actualizar la etiqueta existente
                $etiquetaExistente->update([
                    'etiqueta_id' => $validatedData['etiqueta_id'],
                    'observacion' => $validatedData['observacion'],
                ]);
                $mensaje = 'Etiqueta actualizada exitosamente.';
            } else {
                // Crear nueva asignación
                EtiquetaCliente::create($validatedData);
                $mensaje = 'Etiqueta asignada exitosamente.';
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al asignar etiqueta:', [
                'error' => $e->getMessage(),
                'data' => $validatedData,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al asignar la etiqueta: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remover etiqueta de un préstamo
     */
    public function remover(Request $request)
    {
        $validatedData = $request->validate([
            'prestamo_id' => 'required|exists:prestamos,id',
        ]);

        try {
            $etiquetaCliente = EtiquetaCliente::where('prestamo_id', $validatedData['prestamo_id'])->first();

            if (! $etiquetaCliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró ninguna etiqueta asignada a este préstamo.',
                ], 404);
            }

            $etiquetaCliente->delete();

            return response()->json([
                'success' => true,
                'message' => 'Etiqueta removida exitosamente.',
            ]);

        } catch (\Exception $e) {
            Log::error('Error al remover etiqueta:', [
                'error' => $e->getMessage(),
                'prestamo_id' => $validatedData['prestamo_id'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al remover la etiqueta: '.$e->getMessage(),
            ], 500);
        }
    }
}
