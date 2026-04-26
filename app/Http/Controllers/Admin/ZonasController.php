<?php

// app/Http/Controllers/Admin/ZonasController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use App\Models\Zona;
use Illuminate\Http\Request;

class ZonasController extends Controller
{
    // Mostrar listado de zonas
    public function index()
    {
        // Obtener todas las zonas con sus sucursales asociadas
        $zonas = Zona::with('sucursales')->paginate(10);

        // Pasar las zonas a la vista
        return view('admin.Zonas.index', compact('zonas'));
    }

    // Mostrar formulario para crear zona
    public function create()
    {
        $sucursales = Sucursal::all(); // Obtener todas las sucursales

        return view('admin.Zonas.create', compact('sucursales'));
    }

    // Guardar nueva zona
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255',
            'sucursales' => 'required|array', // Asegura que sea un array
            'sucursales.*' => 'exists:sucursales,id', // Validar que los ids de las sucursales existan
        ]);

        // Crear la zona
        $zona = Zona::create([
            'nombre' => $validatedData['nombre'],
        ]);

        // Vincular las sucursales seleccionadas con la zona
        $zona->sucursales()->attach($validatedData['sucursales']);

        return redirect()->route('admin.zonas.index')->with('info', 'Zona creada con éxito.');
    }

    // Mostrar formulario para editar zona
    public function edit(Zona $zona)
    {
        $sucursales = Sucursal::all(); // Obtener todas las sucursales

        return view('admin.Zonas.edit', compact('zona', 'sucursales'));
    }

    // Actualizar zona
    public function update(Request $request, Zona $zona)
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255',
            'sucursales' => 'required|array',
            'sucursales.*' => 'exists:sucursales,id',
        ]);

        $zona->update([
            'nombre' => $validatedData['nombre'],
        ]);

        // Actualizar las sucursales vinculadas a la zona
        $zona->sucursales()->sync($validatedData['sucursales']);

        return redirect()->route('admin.zonas.index')->with('info', 'Zona actualizada con éxito.');
    }

    public function validarTipoZona(Request $request)
    {
        // Validación de los datos recibidos
        $request->validate([
            'tipo_zona' => 'required|string|max:255|exists:tipo_zonas,nombre', // Cambia según tus necesidades
        ]);

        // Si no hay errores, puedes retornar una respuesta exitosa
        return response()->json(['message' => 'Tipo de zona válido']);
    }

    // Eliminar zona
    public function destroy(Zona $zona)
    {
        $zona->delete();

        return redirect()->route('admin.zonas.index')->with('info', 'Zona eliminada con éxito.');
    }
}
