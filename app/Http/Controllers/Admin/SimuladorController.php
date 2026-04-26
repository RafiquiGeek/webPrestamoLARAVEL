<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Analista;
use App\Models\Asesor;
use App\Models\EntidadBancaria;
use App\Models\JCC;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SimuladorController extends Controller
{
    public function index()
    {
        $entBancarias = EntidadBancaria::all();
        $analistas = Analista::all();
        $jccs = JCC::all();
        $asesores = Asesor::all();

        return view('admin.Simulador.index', compact('entBancarias', 'analistas', 'jccs', 'asesores'));
    }

    public function create()
    {
        $clientes = Cliente::all();
        $asesores = Asesor::all();
        $cuentas = EntidadBancaria::all();

        return view('admin.Simulador.create', compact('clientes', 'asesores', 'cuentas'));
    }

    public function store(Request $request)
    {
        // Validar y procesar la solicitud
        $data = $request->validate([
            'cliente' => 'required|exists:clientes,id',
            'asesorCredito' => 'required|exists:asesores,id',
            'cuentaAsignada' => 'required|exists:entidad_bancarias,id',
            'fechaAtencion' => 'required|date',
            'fechaPrimerPago' => 'required|date',
            'plazo' => 'required|integer',
            'montoSolicitado' => 'required|numeric',
            'tasaMora' => 'required|numeric',
            'observaciones' => 'nullable|string',
        ]);

        // Lógica para calcular las cuotas y otros datos dinámicos
        $cuotas = $this->crearCuotasParaMostrar($data);

        // Calcular capital + interés
        $capitalInteres = $this->calcularCapitalInteres($data['montoSolicitado'], $data['plazo']);

        // Retornar la vista de cálculo
        return view('admin.solicitudes.calculo', [
            'nombre' => 'Nombre del Cliente', // Actualiza con el nombre correcto
            'nro_contrato' => 'Número de Contrato', // Actualiza con el número correcto
            'tipo' => 'Semanal',
            'calculo' => $data,
            'cuotas' => $cuotas,
            'capitalInteres' => $capitalInteres,
        ]);
    }

    public function calcularCuotas(Request $request)
    {
        $semanas = $request->input('semanas');
        $monto = $request->input('monto');
        $incluirIgv = $request->input('incluirIgv') === 'true'; // Asegúrate de que es booleano

        // Lógica de cálculo aquí
        $cuotas = $this->crearCuotasParaAjax($semanas, $monto, $incluirIgv);

        return response()->json(['cuotas' => $cuotas, 'totalCuotas' => array_sum(array_column($cuotas, 'monto'))]);
    }

    private function crearCuotasParaAjax($semanas, $monto, $incluirIgv)
    {
        $cuotas = [];
        $tasaInteres = 0.04; // Ejemplo de tasa de interés semanal
        $fecha_pago = Carbon::now();
        $saldo_capital = $monto;
        $igv = $incluirIgv ? 0.18 : 0;

        for ($i = 1; $i <= $semanas; $i++) {
            $interes = round($saldo_capital * $tasaInteres, 2);
            $igvMonto = round($interes * $igv, 2);
            $monto_cuota = round(($monto / $semanas) + $interes + $igvMonto, 2);
            $saldo_capital -= round($monto / $semanas, 2);

            $cuotas[] = [
                'numero' => $i,
                'fecha' => $fecha_pago->format('d-m-Y'),
                'monto' => number_format($monto_cuota, 2),
                'igv' => number_format($igvMonto, 2),
            ];

            $fecha_pago->addWeek();
        }

        return $cuotas;
    }

    private function crearCuotasParaMostrar($calculo)
    {
        $cuotas = [];
        $fecha_pago = Carbon::parse($calculo['fechaPrimerPago']);
        $saldo_capital = $calculo['montoSolicitado'];
        $tasaInteres = 0.04; // Ejemplo de tasa de interés semanal
        $igv = 0.18; // Ejemplo de IGV

        for ($i = 1; $i <= $calculo['plazo']; $i++) {
            $interes = round($saldo_capital * $tasaInteres, 2);
            $igvMonto = round($interes * $igv, 2);
            $pagoCapital = round($calculo['montoSolicitado'] / $calculo['plazo'], 2);
            $cuotaTotal = $pagoCapital + $interes + $igvMonto;
            $saldo_capital -= $pagoCapital;

            $cuotas[] = [
                'numero' => $i,
                'fecha' => $fecha_pago->format('d-m-Y'),
                'cuota' => number_format($cuotaTotal, 2),
                'pagoCapital' => number_format($pagoCapital, 2),
                'interes' => number_format($interes, 2),
                'comision' => number_format($interes, 2), // Ejemplo, podrías tener una lógica diferente
                'igv' => number_format($igvMonto, 2),
                'saldoCapital' => number_format($saldo_capital, 2),
            ];

            $fecha_pago->addWeek();
        }

        return $cuotas;
    }

    private function calcularCapitalInteres($monto, $plazo)
    {
        // Lógica para calcular el capital + interés
        $tasaInteres = 0.04; // Ejemplo de tasa de interés semanal
        $capitalInteres = $monto * pow(1 + $tasaInteres, $plazo);

        return number_format($capitalInteres, 2);
    }
}
