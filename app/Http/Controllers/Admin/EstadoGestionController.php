<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EstadoGestion;
use Illuminate\Http\Request;

class EstadoGestionController extends Controller
{
    public function index()
    {
        $estados = EstadoGestion::all();

        return view('admin.EstadosGestion.index', compact('estados'));
    }

    public function create()
    {
        return view('admin.EstadosGestion.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'estado' => 'required|string|unique:estados_gestion',
            'calificacion' => 'nullable|boolean',
        ]);

        EstadoGestion::create($request->all());

        return redirect()->route('admin.estadosgestion.index')->with('success', 'Estado creado correctamente.');
    }

    public function update(Request $request, EstadoGestion $estadoGestion)
    {
        $request->validate([
            'estado' => 'required|string|unique:estados_gestion,estado,'.$estadoGestion->id,
            'calificacion' => 'nullable|boolean',
        ]);

        $estadoGestion->update($request->all());

        return redirect()->route('admin.EstadosGestion.index')->with('success', 'Estado actualizado correctamente.');
    }

    public function edit(EstadoGestion $estadosgestion)
    {
        return view('admin.EstadosGestion.edit', compact('estadosgestion'));
    }

    public function destroy(EstadoGestion $estadoGestion)
    {
        $estadoGestion->delete();

        return redirect()->route('admin.EstadosGestion.index')->with('success', 'Estado eliminado correctamente.');
    }
}
