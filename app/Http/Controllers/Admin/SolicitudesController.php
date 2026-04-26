<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asesor;
use App\Models\Cliente;
use App\Models\Cuenta;
use App\Models\Cuota;
use App\Models\Prestamo;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SolicitudesController extends Controller
{
    /**
     * Muestra una lista de los recursos.
     */
    public function index()
    {
        $prestamos = Prestamo::with('cliente', 'analista')->get();

        return view('admin.Solicitudes.index', compact('prestamos'));
    }

    /**
     * Muestra el formulario para crear un nuevo recurso.
     */
    public function create()
    {
        $clientes = Cliente::with('persona')->get();
        $cuentas = Cuenta::all();
        $cuentasCliente = Cuenta::all();

        // Si es necesario inicializar un prestamo
        $prestamo = new Prestamo;

        return view('admin.Solicitudes.create', compact('clientes', 'cuentas', 'cuentasCliente', 'prestamo'));
    }

    /**
     * Almacena un recurso recién creado en el almacenamiento.
     */
    public function store(Request $request)
    {
        // Redirigir al PrestamosController que sí funciona
        $prestamosController = new \App\Http\Controllers\Admin\PrestamosController;

        return $prestamosController->store($request);

        // Generar el número de contrato
        $this->asignarNumeroContrato($solicitud);

        // Si la solicitud está aprobada, crear el préstamo y las cuotas
        if ($solicitud->estado === 'Aprobado') {
            $prestamo = $this->crearPrestamo($solicitud);
            $cuotas = $this->crearCuotas(
                $solicitud->id,
                $prestamo->id,
                $solicitud->mon_sol,
                $solicitud->plazo,
                $solicitud->tas_int,
                $solicitud->fpri_pag
            );
            $this->guardarCuotas($cuotas);
            $this->actualizarCapitalInteres($solicitud, $cuotas);
        }

        // Preparar datos para la vista de cálculo
        $calculo = [
            'montoSolicitado' => $solicitud->mon_sol,
            'plazo' => $solicitud->plazo,
        ];
        $cliente = Cliente::find($solicitud->cliente_id);
        $nombre = $cliente->persona->nombres.' '.$cliente->persona->ape_pat.' '.$cliente->persona->ape_mat;

        $cuotasParaMostrar = $this->crearCuotasParaMostrar($calculo, $solicitud->id);

        return view('admin.Solicitudes.calculo', [
            'calculo' => $calculo,
            'nombre' => $nombre,
            'cuotas' => $cuotasParaMostrar,
            'tipo' => $solicitud->fre_pag,
            'tasa_interes' => $solicitud->tas_int,
            'fecha_pago' => $solicitud->fpri_pag,
            'id' => $solicitud->id,
            'nro_contrato' => $solicitud->nro_contrato,
            'capitalInteres' => $solicitud->cap_int,
            'estado' => $solicitud->estado,
            'prestamo_id' => $solicitud->prestamo->id ?? null,
        ]);
    }

    /**
     * Muestra el recurso especificado.
     */
    public function show($id)
    {
        $solicitud = Solicitud::with('cliente', 'analista', 'cuotas')->findOrFail($id);
        $pdf = Pdf::loadView('admin.PDF.pdf', compact('solicitud'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('solicitud.pdf');
    }

    /**
     * Muestra el formulario para editar el recurso especificado.
     */
    public function edit($id)
    {
        $clientes = Cliente::with('persona')->get();
        $asesores = Asesor::all();
        $solicitud = Solicitud::findOrFail($id);
        $cuentas = Cuenta::all();

        return view('admin.Solicitudes.edit', compact('solicitud', 'clientes', 'asesores', 'cuentas'));
    }

    /**
     * Actualiza el recurso especificado en el almacenamiento.
     */
    public function update(Request $request, $id)
    {
        $solicitud = Solicitud::findOrFail($id);

        // Validar los datos de entrada
        $validatedData = $this->validateSolicitud($request);

        // Actualizar la solicitud
        $this->actualizarSolicitud($solicitud, $validatedData);

        // Si la solicitud ahora está aprobada y no tiene préstamo, crear el préstamo
        if ($solicitud->estado === 'Aprobado' && ! $solicitud->prestamo) {
            $prestamo = $this->crearPrestamo($solicitud);
            $solicitud->prestamo_id = $prestamo->id;
            $solicitud->save();

            // Crear las cuotas
            $cuotas = $this->crearCuotas(
                $solicitud->id,
                $prestamo->id,
                $solicitud->mon_sol,
                $solicitud->plazo,
                $solicitud->tas_int,
                $solicitud->fpri_pag
            );
            $this->guardarCuotas($cuotas);
            $this->actualizarCapitalInteres($solicitud, $cuotas);
        }

        return redirect()->route('admin.Solicitudes.index')->with('success', 'Solicitud actualizada exitosamente.');
    }

    /**
     * Elimina el recurso especificado del almacenamiento.
     */
    public function destroy($id)
    {
        $solicitud = Solicitud::findOrFail($id);

        // Eliminar cuotas asociadas
        Cuota::where('solicitud_id', $id)->delete();

        // Eliminar préstamo asociado si existe
        if ($solicitud->prestamo) {
            $solicitud->prestamo->delete();
        }

        $solicitud->delete();

        return redirect()->back()->with('success', 'Solicitud eliminada exitosamente.');
    }

    /**
     * Función para realizar el cálculo de las cuotas.
     */
    public function crearCuotas($prestamo_id, $montoSolicitado, $plazo, $tasaInteres, $fechaPrimerPago, $frecuenciaPago)
    {
        $cuotas = [];
        $frecuenciaPago = $frecuenciaPago ?? 'semanal';
        $fechaInicio = Carbon::parse($fechaPrimerPago);

        switch ($plazo) {
            case 8:
                $cuotas = $this->calcularCuotas8Semanas($prestamo_id, $montoSolicitado, $fechaInicio);
                break;
            case 12:
            case 15:
            case 18:
            case 20:
                $cuotas = $this->calcularCuotasGenerales($prestamo_id, $montoSolicitado, $plazo, $tasaInteres, $fechaInicio, $frecuenciaPago);
                break;
            default:
                // Manejar otros plazos si es necesario
                break;
        }

        return $cuotas;
    }

    /**
     * Calcula las cuotas para 8 semanas.
     */

    /**
     * Calcula las cuotas para plazos generales (12, 15, 18, 20 semanas).
     */

    /**
     * Guarda las cuotas en la base de datos.
     */
    private function guardarCuotas(array $cuotas)
    {
        foreach ($cuotas as $cuota) {
            Cuota::create($cuota);
        }
    }

    /**
     * Actualiza el capital más intereses en la solicitud.
     */
    private function actualizarCapitalInteres(Solicitud $solicitud, array $cuotas)
    {
        $capitalInteres = array_sum(array_column($cuotas, 'cuota'));
        $solicitud->cap_int = $capitalInteres;
        $solicitud->save();
    }

    /**
     * Crea una nueva solicitud.
     */
    private function crearSolicitud(array $data)
    {
        $cliente = Cliente::findOrFail($data['cliente_id']);
        $nombre = $cliente->persona->nombres.' '.$cliente->persona->ape_pat.' '.$cliente->persona->ape_mat;

        return Solicitud::create([
            'cliente_id' => $data['cliente_id'],
            'estado' => $data['estado'],
            'nombre_cliente' => $nombre,
            'tip_sol' => $data['tSolicitud'],
            'cta_asig' => $data['cuentaAsignada'],
            'fech_ate' => $data['fechaAtencion'],
            'plazo' => $data['plazo'],
            'mon_sol' => $data['montoSolicitado'],
            'tas_int' => $data['tasaInteres'],
            'cap_int' => 0, // Se actualizará después
            'tas_mor' => $data['tasaMora'],
            'fre_pag' => $data['frecuenciaPago'],
            'fpri_pag' => $data['fechaPrimerPago'],
            'analista_id' => $data['asesorCredito'],
            'observ' => $data['observaciones'],
            'fondo_provi' => 0,
        ]);
    }

    /**
     * Asigna el número de contrato a la solicitud.
     */
    private function asignarNumeroContrato(Solicitud $solicitud)
    {
        $fecha = Carbon::now()->format('dmY');
        $nro_contrato = $fecha.'-'.$solicitud->id;
        $solicitud->nro_contrato = $nro_contrato;
        $solicitud->save();
    }

    /**
     * Crea un nuevo préstamo asociado a una solicitud.
     */
    private function crearPrestamo(Solicitud $solicitud)
    {
        return Prestamo::create([
            'solicitud_id' => $solicitud->id,
            'cliente_id' => $solicitud->cliente_id,
            'analista_id' => $solicitud->analista_id,
            'nombre_cliente' => $solicitud->nombre_cliente,
            'estado' => 'Nueva Solicitud',
        ]);
    }

    /**
     * Valida los datos de la solicitud.
     */
    private function validateSolicitud(Request $request)
    {
        return $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'estado' => 'required|string',
            'tSolicitud' => 'required|string',
            'cuentaAsignada' => 'required',
            'fechaAtencion' => 'required|date',
            'plazo' => 'required|integer|in:8,12,15,18,20',
            'montoSolicitado' => 'required|numeric',
            'tasaInteres' => 'required|numeric',
            'tasaMora' => 'required|numeric',
            'frecuenciaPago' => 'required|string',
            'fechaPrimerPago' => 'required|date',
            'asesorCredito' => 'required|exists:asesors,id',
            'observaciones' => 'nullable|string',
        ]);
    }

    /**
     * Actualiza los datos de una solicitud existente.
     */
    private function actualizarSolicitud(Solicitud $solicitud, array $data)
    {
        $cliente = Cliente::findOrFail($data['cliente_id']);
        $nombre = $cliente->persona->nombres.' '.$cliente->persona->ape_pat.' '.$cliente->persona->ape_mat;

        $solicitud->update([
            'cliente_id' => $data['cliente_id'],
            'estado' => $data['estado'],
            'nombre_cliente' => $nombre,
            'tip_sol' => $data['tSolicitud'],
            'cta_asig' => $data['cuentaAsignada'],
            'fech_ate' => $data['fechaAtencion'],
            'plazo' => $data['plazo'],
            'mon_sol' => $data['montoSolicitado'],
            'tas_int' => $data['tasaInteres'],
            'cap_int' => $data['montoSolicitado'], // Puedes ajustar según tus necesidades
            'tas_mor' => $data['tasaMora'],
            'fre_pag' => $data['frecuenciaPago'],
            'fpri_pag' => $data['fechaPrimerPago'],
            'analista_id' => $data['asesorCredito'],
            'observ' => $data['observaciones'],
        ]);
    }

    /**
     * Crea cuotas para mostrar en la vista de cálculo.
     */
    private function crearCuotasParaMostrar(array $calculo, $prestamo_id)
    {
        // Implementa la lógica para crear las cuotas para mostrar
        // Esto podría reutilizar la función crearCuotas pero ajustando según sea necesario
        $cuotas = Cuota::where('prestamo_id', $prestamo_id)->get();

        return $cuotas;
    }
}
