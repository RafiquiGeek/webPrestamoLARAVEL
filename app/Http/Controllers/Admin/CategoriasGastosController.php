<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaGasto;
use Illuminate\Http\Request;

class CategoriasGastosController extends Controller
{
    public function index()
    {
        $categorias = CategoriaGasto::withCount('gastos')
            ->orderBy('nombre')
            ->paginate(15);

        return view('admin.Gastos.Categorias.index', compact('categorias'));
    }

    public function create()
    {
        return view('admin.Gastos.Categorias.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias_gastos,nombre',
            'descripcion' => 'nullable|string',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'estado' => 'required|boolean',
        ]);

        try {
            CategoriaGasto::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'color' => $request->color,
                'estado' => $request->estado,
            ]);

            return redirect()->route('admin.categorias-gastos.index')
                ->with('success', 'Categoría creada correctamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear la categoría: '.$e->getMessage());
        }
    }

    public function show($id)
    {
        $categoriaGasto = CategoriaGasto::with('gastos')->findOrFail($id);
        $categoriaGasto->loadCount('gastos');
        $gastosRecientes = $categoriaGasto->gastos()
            ->with('usuario')
            ->orderBy('fecha_gasto', 'desc')
            ->limit(10)
            ->get();

        return view('admin.Gastos.Categorias.show', compact('categoriaGasto', 'gastosRecientes'));
    }

    public function edit($id)
    {
        $categoriaGasto = CategoriaGasto::findOrFail($id);

        return view('admin.Gastos.Categorias.edit', compact('categoriaGasto'));
    }

    public function update(Request $request, $id)
    {
        $categoriaGasto = CategoriaGasto::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias_gastos,nombre,'.$categoriaGasto->id,
            'descripcion' => 'nullable|string',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'estado' => 'required|boolean',
        ]);

        try {
            $categoriaGasto->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'color' => $request->color,
                'estado' => $request->estado,
            ]);

            return redirect()->route('admin.categorias-gastos.index')
                ->with('success', 'Categoría actualizada correctamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar la categoría: '.$e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $categoriaGasto = CategoriaGasto::findOrFail($id);

            // Verificar si tiene gastos asociados
            if ($categoriaGasto->gastos()->count() > 0) {
                return redirect()->route('admin.categorias-gastos.index')
                    ->with('error', 'No se puede eliminar la categoría porque tiene gastos asociados');
            }

            $categoriaGasto->delete();

            return redirect()->route('admin.categorias-gastos.index')
                ->with('success', 'Categoría eliminada correctamente');

        } catch (\Exception $e) {
            return redirect()->route('admin.categorias-gastos.index')
                ->with('error', 'Error al eliminar la categoría: '.$e->getMessage());
        }
    }
}
