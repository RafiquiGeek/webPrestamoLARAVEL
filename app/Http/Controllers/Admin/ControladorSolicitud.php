<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ControladorSolicitud;
use App\Http\Controllers\Controller;
use App\Models\Asesor;
use App\Models\Cliente;
use App\Models\Cuenta;
use App\Models\Cuota;
use App\Models\Prestamo;
use App\Models\Solicitud;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SolicitudesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.Solicitudes.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clientes = Cliente::all();
        $asesores = Asesor::all();
        $cuentas = Cuenta::all();

        return view('admin.Solicitudes.create', compact('clientes', 'asesores', 'cuentas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $calculo = [
            'montoSolicitado' => $request->montoSolicitado,
            'plazo' => $request->plazo,
        ];

        $cliente = Cliente::find($request->cliente);
        $nombre = $cliente->nombres.' '.$cliente->ape_pat.' '.$cliente->ape_mat;
        $tipo = $request->frecuenciaPago;
        $tasa_interes = $request->tasaInteres;
        $fecha_pago = $request->fechaPrimerPago;
        $capitalInteres = $request->capitalInteres;

        $solicitud = Solicitud::create([
            'cliente_id' => $request->cliente,
            'estado' => $request->estado,
            'nombre_cliente' => $nombre,
            'tip_sol' => $request->tSolicitud,
            'cta_asig' => $request->cuentaAsignada,
            'fech_ate' => $request->fechaAtencion,
            'plazo' => $request->plazo,
            'mon_sol' => $request->montoSolicitado,
            'tas_int' => $request->tasaInteres,
            'cap_int' => $request->capitalInteres,
            'tas_mor' => $request->tasaMora,
            'fre_pag' => $request->frecuenciaPago,
            'fpri_pag' => $request->fechaPrimerPago,
            'analista_id' => $request->asesorCredito,
            'observ' => $request->observaciones,
            'fondo_provi' => 0,
        ]);

        $id = $solicitud->id;
        $fecha = Carbon::now()->format('dmY');
        $nro_contrato = $fecha.'-'.$id;

        $solicitud->nro_contrato = $nro_contrato;
        $solicitud->save();

        $estado = $request->estado;
        $prestamo_id = '';
        if ($estado === 'Aprobado') {
            $prestamo = Prestamo::create([
                'solicitud_id' => $id,
                'cliente_id' => $request->cliente,
                'analista_id' => $request->asesorCredito,
                'nombre_cliente' => $nombre,
                'estado' => 'Por Desembolsar',
            ]);
            $prestamo_id = $prestamo->id;

            // Llamada a la creación de cuotas
            $controladorSolicitud = new ControladorSolicitud;
            $controladorSolicitud->crearCuotas($id, $prestamo_id, $request->montoSolicitado, $request->plazo, $request->tasaInteres, $request->fechaPrimerPago, $request->capitalInteres);
        }

        $cuotas = (new ControladorSolicitud)->crearCuotasParaMostrar($calculo, $id);

        return view('admin.Solicitudes.calculo', compact('calculo', 'nombre', 'cuotas', 'tipo', 'tasa_interes', 'fecha_pago', 'id', 'nro_contrato', 'capitalInteres', 'estado', 'prestamo_id'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //return view('admin.PDF.pdf');

        $pdf = Pdf::loadView('admin.PDF.pdf', ['id' => $id]);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $clientes = Cliente::all();
        $asesores = Asesor::all();
        $solicitud = Solicitud::find($id);
        $cuentas = Cuenta::all();

        return view('admin.Solicitudes.edit', compact('solicitud', 'clientes', 'asesores', 'cuentas'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Solicitud $solicitude)
    {
        $cliente = Cliente::find($request->cliente);
        $nombre = $cliente->nombres.' '.$cliente->ape_pat.' '.$cliente->ape_mat;

        $solicitude->update([
            'cliente_id' => $request->cliente,
            'estado' => $request->estado,
            'cliente' => $nombre,
            'tip_sol' => $request->tSolicitud,
            'cta_asig' => $request->cuentaAsignada,
            'fech_ate' => $request->fechaAtencion,
            'plazo' => $request->plazo,
            'mon_sol' => $request->montoSolicitado,
            'tas_int' => $request->tasaInteres,
            'cap_int' => $request->capitalInteres,
            'tas_mor' => $request->tasaMora,
            'fre_pag' => $request->frecuenciaPago,
            'fpri_pag' => $request->fechaPrimerPago,
            'ana_cre' => $request->asesorCredito,
            'observ' => $request->observaciones,
        ]);

        $id = $solicitude->id;

        if ($request->estado === 'Aprobado') {
            $prestamo = Prestamo::create([
                'solicitud_id' => $solicitude->id,
                'cliente_id' => $request->cliente,
                'analista_id' => $request->asesorCredito,
                'nombre_cliente' => $nombre,
                'estado' => 'Por Desembolsar',
            ]);

            Cuota::where('solicitud_id', $id)->update(['prestamo_id' => $prestamo->id]);

        }

        return redirect()->route('admin.solicitudes.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Encuentra el registro por su ID
        $registro = Solicitud::find($id);

        // Verifica si el registro existe
        if ($registro) {
            // Elimina el registro
            $registro->delete();

            // Redirige a la página anterior con un mensaje de éxito
            return redirect()->back()->with('success', 'Registro eliminado con éxito.');
        } else {
            // Redirige a la página anterior con un mensaje de error
            return redirect()->back()->with('error', 'Registro no encontrado.');
        }
    }
}
