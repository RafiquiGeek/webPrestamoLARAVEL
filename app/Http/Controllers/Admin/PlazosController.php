<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plazo;
use App\Models\Tasa;
use Illuminate\Http\Request;

class PlazosController extends Controller
{
    public function index()
    {
        $plazos = Plazo::with('plazosByTasa.tasa')->get();

        return view('admin.Plazos.index', compact('plazos'));
    }

    public function create()
    {
        $tasas = Tasa::where('status', 1)->get();

        return view('admin.Plazos.create', compact('tasas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tiempo' => 'required|integer|min:1',
            'unidad_tiempo' => 'required|string|in:semanas,meses,días',
            'tasa_ids' => 'required|array',
            'tasa_ids.*' => 'exists:tasas,id',
        ]);

        $plazo = Plazo::create([
            'tiempo' => $request->tiempo,
            'unidad_tiempo' => $request->unidad_tiempo,
        ]);

        $plazo->plazosByTasa()->createMany(
            array_map(fn ($tasaId) => ['tasa_id' => $tasaId, 'estado' => 1], $request->tasa_ids)
        );

        return redirect()->route('admin.plazos.index')->with('info', 'Plazo creado con éxito');
    }

    public function edit($id)
    {
        $plazo = Plazo::with('plazosByTasa')->findOrFail($id);
        $tasas = Tasa::where('status', 1)->get();

        return view('admin.Plazos.edit', compact('plazo', 'tasas'));
    }

    public function update(Request $request, Plazo $plazo)
    {
        $request->validate([
            'tiempo' => 'required|integer|min:1',
            'unidad_tiempo' => 'required|string|in:semanas,meses,días',
            'tasa_ids' => 'required|array',
            'tasa_ids.*' => 'exists:tasas,id',
        ]);

        $plazo->update([
            'tiempo' => $request->tiempo,
            'unidad_tiempo' => $request->unidad_tiempo,
        ]);

        $plazo->plazosByTasa()->delete();
        $plazo->plazosByTasa()->createMany(
            array_map(fn ($tasaId) => ['tasa_id' => $tasaId, 'estado' => 1], $request->tasa_ids)
        );

        return redirect()->route('admin.plazos.index')->with('info', 'Plazo actualizado con éxito');
    }

    public function destroy(Plazo $plazo)
    {
        $plazo->plazosByTasa()->delete();
        $plazo->delete();

        return redirect()->route('admin.plazos.index')->with('info', 'Plazo eliminado con éxito');
    }
}
