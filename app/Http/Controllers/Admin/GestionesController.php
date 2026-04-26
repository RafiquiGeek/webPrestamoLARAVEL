<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GestionesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.Gestiones.index');
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
     * Display the specified resource.
     */
    public function show(string $id)
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
    public function update(Request $request, $id)
    {
        // Validar los datos básicos de la gestión
        $validatedData = $request->validate([
            'estado_id' => 'required|exists:estados_gestion,id',
            'observaciones' => 'nullable|string',
        ]);

        try {
            $gestion = Gestion::findOrFail($id);

            // Actualizar la gestión
            $gestion->update([
                'estado_id' => $validatedData['estado_id'],
                'observaciones' => $validatedData['observaciones'],
            ]);

            // Procesar el compromiso
            if ($request->has('compromisoPago')) {
                $compromisoRequest = new Request($request->only(['fecha', 'hora_hh', 'hora_mm', 'monto']));
                $compromisoRequest->merge(['gestion_id' => $gestion->id]);

                if ($gestion->compromiso) {
                    app(CompromisosController::class)->update($compromisoRequest, $gestion->compromiso->id);
                } else {
                    app(CompromisosController::class)->store($compromisoRequest);
                }
            } else {
                if ($gestion->compromiso) {
                    app(CompromisosController::class)->destroy($gestion->compromiso->id);
                }
            }

            return redirect()->route('admin.Gestiones.index')->with('success', 'Gestión y compromiso actualizados correctamente.');
        } catch (\Exception $e) {
            \Log::error('Error al actualizar la gestión y compromiso:', ['error' => $e->getMessage()]);

            return redirect()->back()->withErrors(['error' => 'Error al actualizar la gestión. Inténtalo de nuevo.']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
