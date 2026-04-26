<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BilleteraDigital;
use Illuminate\Http\Request;

class BilleterasDigitalesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $billeteras = BilleteraDigital::all();

        return view('admin.billeteras-digitales.index', compact('billeteras'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.billeteras-digitales.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:billeteras_digitales,nombre',
            'status' => 'required|boolean',
        ]);

        BilleteraDigital::create([
            'nombre' => $request->nombre,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.billeteras-digitales.index')
            ->with('success', 'Billetera digital creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(BilleteraDigital $billeterasDigitale)
    {
        return view('admin.billeteras-digitales.show', compact('billeterasDigitale'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BilleteraDigital $billeterasDigitale)
    {
        return view('admin.billeteras-digitales.edit', compact('billeterasDigitale'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BilleteraDigital $billeterasDigitale)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:billeteras_digitales,nombre,'.$billeterasDigitale->id,
            'status' => 'required|boolean',
        ]);

        $billeterasDigitale->update([
            'nombre' => $request->nombre,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.billeteras-digitales.index')
            ->with('success', 'Billetera digital actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BilleteraDigital $billeterasDigitale)
    {
        try {
            $billeterasDigitale->delete();

            return redirect()->route('admin.billeteras-digitales.index')
                ->with('success', 'Billetera digital eliminada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('admin.billeteras-digitales.index')
                ->with('error', 'No se puede eliminar la billetera digital porque tiene cuentas asociadas.');
        }
    }
}
