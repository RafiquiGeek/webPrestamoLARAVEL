<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use App\Models\Provincia;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class SucursalesController extends Controller
{
    // Mostrar listado de sucursales
    public function index()
    {
        $sucursales = Sucursal::with('provincia')->paginate(10);

        return view('admin.Sucursales.index', compact('sucursales'));
    }

    // Mostrar formulario para crear sucursal
    public function create()
    {
        $departamentos = Departamento::all();

        return view('admin.Sucursales.create', compact('departamentos'));
    }

    // Obtener provincias de un departamento
    public function getProvincias($departamento_id)
    {
        $provincias = Provincia::where('departamento_id', $departamento_id)->get();

        return response()->json($provincias);
    }

    // Guardar una nueva sucursal
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'sucursal' => 'required|string|max:255',
            'provincia_id' => 'required|exists:provincias,id',
        ]);

        Sucursal::create($validatedData);

        return redirect()->route('admin.sucursales.index')->with('info', 'Sucursal creada con éxito.');
    }

    // Mostrar formulario para editar sucursal
    public function edit(Sucursal $sucursal)
    {
        $departamentos = Departamento::all();
        $provincias = Provincia::where('departamento_id', $sucursal->provincia->departamento_id)->get();

        return view('admin.Sucursales.edit', compact('sucursal', 'departamentos', 'provincias'));
    }

    // Actualizar sucursal
    public function update(Request $request, Sucursal $sucursal)
    {
        $validatedData = $request->validate([
            'sucursal' => 'required|string|max:255',
            'provincia_id' => 'required|exists:provincias,id',
        ]);

        $sucursal->update($validatedData);

        // Redirigir correctamente a la lista de sucursales
        return redirect()->route('admin.sucursales.index')->with('info', 'Sucursal actualizada con éxito.');
    }

    // Eliminar sucursal
    public function destroy(Sucursal $sucursal)
    {
        $sucursal->delete();

        return redirect()->route('admin.Sucursales.index')->with('info', 'Sucursal eliminada con éxito.');
    }
}
