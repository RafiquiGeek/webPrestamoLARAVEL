<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class HuerfanasController extends Controller
{
    public function index()
    {
        // Cuotas huérfanas de préstamos (sin prestamo padre)
        $cuotasPrestamo = DB::table('cuotas')
            ->leftJoin('prestamos', 'cuotas.prestamo_id', '=', 'prestamos.id')
            ->whereNull('prestamos.id')
            ->select(
                'cuotas.id',
                'cuotas.prestamo_id',
                'cuotas.numero',
                'cuotas.monto',
                'cuotas.estado',
                'cuotas.fecha_pago'
            )
            ->orderBy('cuotas.id', 'desc')
            ->get();

        // Cuotas huérfanas de convenios (sin convenio padre)
        $cuotasConvenio = DB::table('cuotas_convenio')
            ->leftJoin('convenios', 'cuotas_convenio.convenio_id', '=', 'convenios.id')
            ->whereNull('convenios.id')
            ->select(
                'cuotas_convenio.id',
                'cuotas_convenio.convenio_id',
                'cuotas_convenio.numero_cuota',
                'cuotas_convenio.monto_cuota',
                'cuotas_convenio.estado',
                'cuotas_convenio.fecha_vencimiento'
            )
            ->orderBy('cuotas_convenio.id', 'desc')
            ->get();

        // Moras huérfanas de préstamos (sin cuota padre)
        $morasPrestamo = DB::table('mora_cuota')
            ->leftJoin('cuotas', 'mora_cuota.cuota_id', '=', 'cuotas.id')
            ->whereNull('cuotas.id')
            ->select(
                'mora_cuota.id',
                'mora_cuota.cuota_id',
                'mora_cuota.fecha',
                'mora_cuota.monto',
                'mora_cuota.estado',
                'mora_cuota.dias_mora'
            )
            ->orderBy('mora_cuota.id', 'desc')
            ->get();

        // Moras huérfanas de convenios (sin cuota_convenio padre)
        $morasConvenio = DB::table('moras_convenio')
            ->leftJoin('cuotas_convenio', 'moras_convenio.cuota_convenio_id', '=', 'cuotas_convenio.id')
            ->whereNull('cuotas_convenio.id')
            ->select(
                'moras_convenio.id',
                'moras_convenio.cuota_convenio_id',
                'moras_convenio.fecha',
                'moras_convenio.monto',
                'moras_convenio.estado',
                'moras_convenio.dias_mora'
            )
            ->orderBy('moras_convenio.id', 'desc')
            ->get();

        return view('admin.huerfanas.index', compact(
            'cuotasPrestamo',
            'cuotasConvenio',
            'morasPrestamo',
            'morasConvenio'
        ));
    }

    public function eliminar(Request $request)
    {
        $cuotasPrestamoIds = $request->input('cuotas_prestamo', []);
        $cuotasConvenioIds = $request->input('cuotas_convenio', []);
        $morasPrestamoIds = $request->input('moras_prestamo', []);
        $morasConvenioIds = $request->input('moras_convenio', []);

        $totalEliminados = 0;

        // Eliminar cuotas de préstamos huérfanas
        if (!empty($cuotasPrestamoIds)) {
            $count = DB::table('cuotas')
                ->whereIn('id', $cuotasPrestamoIds)
                ->delete();
            $totalEliminados += $count;
        }

        // Eliminar cuotas de convenios huérfanas
        if (!empty($cuotasConvenioIds)) {
            $count = DB::table('cuotas_convenio')
                ->whereIn('id', $cuotasConvenioIds)
                ->delete();
            $totalEliminados += $count;
        }

        // Eliminar moras de préstamos huérfanas
        if (!empty($morasPrestamoIds)) {
            $count = DB::table('mora_cuota')
                ->whereIn('id', $morasPrestamoIds)
                ->delete();
            $totalEliminados += $count;
        }

        // Eliminar moras de convenios huérfanas
        if (!empty($morasConvenioIds)) {
            $count = DB::table('moras_convenio')
                ->whereIn('id', $morasConvenioIds)
                ->delete();
            $totalEliminados += $count;
        }

        return redirect()->route('admin.huerfanas.index')
            ->with('success', "Se eliminaron {$totalEliminados} registros huérfanos correctamente.");
    }
}
