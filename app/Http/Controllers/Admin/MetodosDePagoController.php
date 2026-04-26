<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetodoDePago;
use Illuminate\Http\Request;

class MetodosDePagoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $metodos = MetodoDePago::all();

        return view('admin.MetodosDePago.index', compact('metodos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.MetodosDePago.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'metodo_pago' => 'required|string|max:255',
        ]);

        MetodoDePago::create([
            'metodo_pago' => $request->metodo_pago,
            'status' => $request->status ?? 1,  // Por defecto activo
        ]);

        return redirect()->route('admin.metodosdepago.index')->with('success', 'Método de pago creado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MetodoDePago $metodoDePago)
    {
        return view('admin.MetodosDePago.edit', compact('metodoDePago'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MetodoDePago $metodoDePago)
    {
        $request->validate([
            'metodo_pago' => 'required|string|max:255',
        ]);

        $metodoDePago->update([
            'metodo_pago' => $request->metodo_pago,
            'status' => $request->status ?? $metodoDePago->status,
        ]);

        return redirect()->route('admin.metodosdepago.index')->with('success', 'Método de pago actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MetodoDePago $metodoDePago)
    {
        $metodoDePago->delete();

        return redirect()->route('admin.metodosdepago.index')->with('success', 'Método de pago eliminado correctamente.');
    }
}
