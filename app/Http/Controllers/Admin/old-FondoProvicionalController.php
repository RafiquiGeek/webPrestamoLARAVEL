<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cuenta;
use App\Models\Solicitud;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FondoProvicionalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Soporte para ambos parámetros: solicitud_id (antiguo) y prestamo_id (nuevo)
        $prestamo_id = $request->query('prestamo_id') ?? $request->query('solicitud_id');

        if (!$prestamo_id) {
            return redirect()->route('admin.prestamos.index')
                ->withErrors(['error' => 'Debe especificar un préstamo para crear el fondo provisional.']);
        }

        $prestamo = \App\Models\Prestamo::with('cliente.persona')->find($prestamo_id);

        if (!$prestamo) {
            return redirect()->route('admin.prestamos.index')
                ->withErrors(['error' => 'Préstamo no encontrado.']);
        }

        // Verificar si ya existe un fondo provisional para este préstamo
        $fondoExistente = \App\Models\FondoProvisional::where('prestamo_id', $prestamo_id)->first();
        if ($fondoExistente) {
            return redirect()->route('admin.prestamos.show', $prestamo_id)
                ->withErrors(['error' => 'Ya existe un fondo provisional para este préstamo.']);
        }

        // Calcular el monto del fondo provisional (5% del capital)
        $montoCapital = $prestamo->cantidad_solicitada;
        $montoFondo = \App\Models\FondoProvisional::calcularMontoFondo($montoCapital);

        return view('admin.Solicitudes.FondoProvicional.create', compact('prestamo', 'montoCapital', 'montoFondo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $solicitud_id = $request->input('solicitud_id');
        $solicitud = Solicitud::find($solicitud_id);
        $solicitud->fondo_provi = 1;

        $pdf = Pdf::loadView('admin.PDF.pdf', ['id' => $solicitud_id]);
        $pdf->setPaper('A4', 'portrait');

        $fecha_actual = Carbon::now()->format('dmY');
        $nombre_archivo = 'Fondo_Provicional_'.$solicitud_id.'_'.$fecha_actual.'.pdf';
        $solicitud->pdf = $nombre_archivo;
        Storage::put('public/PDF/fondo_provicional/'.$nombre_archivo, $pdf->output());

        $solicitud->save();

        return redirect()->route('admin.solicitudes.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pdf = Solicitud::where('id', $id)->pluck('pdf')->first();

        $pathToFile = storage_path('app/public/PDF/fondo_provicional/'.$pdf);
        $headers = [
            'Content-Type' => 'application/pdf',
        ];

        return response()->file($pathToFile, $headers);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
