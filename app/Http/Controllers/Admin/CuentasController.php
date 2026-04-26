<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cuenta;
use App\Models\EntidadBancaria;
use Illuminate\Http\Request;

class CuentasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cuentas = Cuenta::with('entidadBancaria')->get();

        return view('admin.Cuentas.index', compact('cuentas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $entBancarias = EntidadBancaria::all();

        return view('admin.Cuentas.create', compact('entBancarias'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Cuenta::create([
            'entidad_bancaria_id' => $request->entidadFinanciera,
            'nro_cuenta' => $request->nro_cuenta,
            'codigo' => $request->codigo,
        ]);

        return redirect()->route('admin.cuentas.index')->with('info', 'Cuenta creada con éxito.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $entBancarias = EntidadBancaria::all();
        $cuenta = Cuenta::findOrFail($id);

        return view('admin.Cuentas.edit', compact('cuenta', 'entBancarias'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $cuenta = Cuenta::findOrFail($id);
        $cuenta->update([
            'entidad_bancaria_id' => $request->entidadFinanciera,
            'nro_cuenta' => $request->nro_cuenta,
            'codigo' => $request->codigo,
        ]);

        return redirect()->route('admin.cuentas.index')->with('info', 'Cuenta actualizada con éxito.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cuenta = Cuenta::findOrFail($id);
        $cuenta->delete();

        return redirect()->route('admin.cuentas.index')->with('info', 'Cuenta eliminada con éxito.');
    }

    public function obtenerCuentasPorCliente($clienteId)
    {
        $cuentasCliente = Cuenta::where('cliente_id', $clienteId)->get(); // Asumiendo que tienes una relación entre clientes y cuentas

        return response()->json($cuentasCliente);
    }
}
