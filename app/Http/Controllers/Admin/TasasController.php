<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tasa;
use App\Models\TasaHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TasasController extends Controller
{
    public function index()
    {
        $tasas = Tasa::all();

        return view('admin.Tasas.index', compact('tasas'));
    }

    public function create()
    {
        return view('admin.Tasas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo_tasa' => 'required|string|max:255',
            'valor' => 'required|numeric',
            'status' => 'required|boolean',
        ]);

        $tasa = Tasa::create([
            'tipo_tasa' => $request->tipo_tasa,
            'valor' => $request->valor,
            'status' => $request->status,
        ]);

        // Registrar en el historial
        TasaHistory::create([
            'tasa_id' => $tasa->id,
            'tipo_tasa_nuevo' => $tasa->tipo_tasa,
            'valor_nuevo' => $tasa->valor,
            'status_nuevo' => $tasa->status,
            'user_id' => Auth::id(), // Opcional
            'accion' => 'creado',
        ]);

        return redirect()->route('admin.tasas.index')->with('info', 'Tasa creada con éxito');
    }

    public function edit(string $id)
    {
        $tasa = Tasa::find($id);

        return view('admin.Tasas.edit', compact('tasa'));
    }

    public function update(Request $request, Tasa $tasa)
    {
        $request->validate([
            'valor' => 'required|numeric',
            'status' => 'required|boolean',
        ]);

        // Guardar valores anteriores para el historial
        $valoresAnteriores = [
            'tipo_tasa' => $tasa->tipo_tasa,
            'valor' => $tasa->valor,
            'status' => $tasa->status,
        ];

        $tasa->update([
            'valor' => $request->valor,
            'status' => $request->status,
        ]);

        // Registrar en el historial
        TasaHistory::create([
            'tasa_id' => $tasa->id,
            'tipo_tasa_anterior' => $valoresAnteriores['tipo_tasa'],
            'valor_anterior' => $valoresAnteriores['valor'],
            'status_anterior' => $valoresAnteriores['status'],
            'tipo_tasa_nuevo' => $tasa->tipo_tasa,
            'valor_nuevo' => $tasa->valor,
            'status_nuevo' => $tasa->status,
            'user_id' => Auth::id(), // Opcional
            'accion' => 'actualizado',
        ]);

        return redirect()->route('admin.tasas.index')->with('info', 'Tasa actualizada con éxito');
    }

    public function destroy(Tasa $tasa)
    {
        // Registrar en el historial antes de eliminar
        TasaHistory::create([
            'tasa_id' => $tasa->id,
            'tipo_tasa_anterior' => $tasa->tipo_tasa,
            'valor_anterior' => $tasa->valor,
            'status_anterior' => $tasa->status,
            'user_id' => Auth::id(), // Opcional
            'accion' => 'eliminado',
        ]);

        $tasa->delete();

        return redirect()->route('admin.tasas.index')->with('info', 'Tasa eliminada con éxito');
    }

    // Método opcional para ver el historial
    public function history($id)
    {
        $tasa = Tasa::findOrFail($id);
        $history = $tasa->history()->latest()->get();

        return view('admin.tasas.history', compact('tasa', 'history'));
    }
}
