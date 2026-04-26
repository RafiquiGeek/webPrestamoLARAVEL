<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EntidadBancaria;
use Illuminate\Http\Request;

class BancoController extends Controller
{
    public function index()
    {
        $bancos = EntidadBancaria::paginate(10);

        return view('admin.Bancos.index', compact('bancos'));
    }

    public function create()
    {
        return view('admin.Bancos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'banco' => 'required|string|max:255',
            'status' => 'required|boolean',
        ]);

        EntidadBancaria::create($request->only('banco', 'status'));

        return redirect()->route('admin.bancos.index')->with('success', 'Banco creado con éxito.');

    }

    public function edit(EntidadBancaria $banco)
    {
        return view('admin.Bancos.edit', compact('banco'));
    }

    public function update(Request $request, EntidadBancaria $banco)
    {
        $request->validate([
            'banco' => 'required|string|max:255',
            'status' => 'required|boolean',
        ]);

        $banco->update($request->only('banco', 'status'));

        return redirect()->route('admin.bancos.index')->with('success', 'Banco actualizado con éxito.');

    }

    public function destroy(EntidadBancaria $banco)
    {
        $banco->delete();

        return redirect()->route('admin.bancos.index')->with('success', 'Banco eliminado con éxito.');
    }
}
