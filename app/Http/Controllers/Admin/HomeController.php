<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Compromiso;
use App\Models\Cuota;
use App\Models\Gestion;
use App\Models\Persona;
use App\Models\Prestamo; // Importar el modelo Alert
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        // Últimas personas registradas
        $personas = Persona::latest()->take(5)->get();

        // Préstamos nuevos
        $prestamos = Prestamo::where('estado', 'Nuevo')->latest()->take(5)->get();

        // Cuotas vencidas
        $cuotasVencidas = Cuota::where('estado', 0) // Estado 0: Pendiente
            ->where('fecha_pago', '<', now()) // Fecha de pago vencida
            ->with('prestamo.cliente.persona')
            ->get();

        // Compromisos por estado
        $compromisos = Compromiso::with('prestamo.cliente.persona')
            ->latest()
            ->take(50)
            ->get();

        $compromisosPendientes = $compromisos->where('estado', 0);
        $compromisosCompletados = $compromisos->where('estado', 1);
        $compromisosCancelados = $compromisos->where('estado', 2);

        // Últimas gestiones realizadas
        $gestiones = Gestion::with('cliente.persona')
            ->latest()
            ->take(5)
            ->get();

        // Gestiones recientes para el dashboard
        $gestionesRecientes = Gestion::with([
            'prestamo.cliente.persona',
            'asesor',
        ])
            ->latest()
            ->take(30)
            ->get();

        // Para el gráfico de estados de préstamos
        $prestamosActivos = Prestamo::where('estado', 'Activo')->count();
        $prestamosFinalizados = Prestamo::where('estado', 'Finalizado')->count();
        $prestamosNuevos = Prestamo::where('estado', 'Nuevo')->count();

        // Datos para el gráfico de tendencia mensual (últimos 6 meses)
        $meses = collect();
        $cuotasPorMes = collect();
        $compromisosPorMes = collect();

        for ($i = 5; $i >= 0; $i--) {
            $fecha = Carbon::now()->subMonths($i);
            $meses->push($fecha->format('M Y'));

            $cuotasPorMes->push(Cuota::whereMonth('fecha_pago', $fecha->month)
                ->whereYear('fecha_pago', $fecha->year)
                ->where('estado', 0) // Cuotas pendientes
                ->count());

            $compromisosPorMes->push(Compromiso::whereMonth('fecha_compromiso_pago', $fecha->month)
                ->whereYear('fecha_compromiso_pago', $fecha->year)
                ->count());
        }

        // Pasar los datos a la vista
        return view('admin.index', compact(
            'personas',
            'prestamos',
            'cuotasVencidas',
            'compromisos',
            'compromisosPendientes',
            'compromisosCompletados',
            'compromisosCancelados',
            'gestiones',
            'gestionesRecientes',
            'prestamosActivos',
            'prestamosFinalizados',
            'prestamosNuevos',
            'meses',
            'cuotasPorMes',
            'compromisosPorMes',
        ));
    }

    public function filter(Request $request)
    {
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Préstamos y cuotas filtrados
        $prestamos = Prestamo::where('estado', 'Nuevo')->whereBetween('created_at', [$startDate, $endDate])->count();
        $cuotasVencidas = Cuota::where('estado', 0)
            ->where('fecha_pago', '<', now())
            ->whereBetween('fecha_pago', [$startDate, $endDate])
            ->count();
        $compromisos = Compromiso::whereBetween('fecha_compromiso_pago', [$startDate, $endDate])->count();

        // Estados de préstamos
        $prestamosActivos = Prestamo::where('estado', 'Activo')->whereBetween('created_at', [$startDate, $endDate])->count();
        $prestamosNuevos = Prestamo::where('estado', 'Nuevo')->whereBetween('created_at', [$startDate, $endDate])->count();
        $prestamosFinalizados = Prestamo::where('estado', 'Finalizado')->whereBetween('created_at', [$startDate, $endDate])->count();

        // Tendencia mensual filtrada
        $meses = collect();
        $cuotasPorMes = collect();
        $compromisosPorMes = collect();
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $meses->push($currentDate->format('M Y'));
            $cuotasPorMes->push(Cuota::whereMonth('fecha_pago', $currentDate->month)
                ->whereYear('fecha_pago', $currentDate->year)
                ->where('estado', 0)
                ->count());
            $compromisosPorMes->push(Compromiso::whereMonth('fecha_compromiso_pago', $currentDate->month)
                ->whereYear('fecha_compromiso_pago', $currentDate->year)
                ->count());
            $currentDate->addMonth();
        }

        return response()->json([
            'prestamos' => $prestamos,
            'cuotasVencidas' => $cuotasVencidas,
            'compromisos' => $compromisos,
            'prestamosActivos' => $prestamosActivos,
            'prestamosNuevos' => $prestamosNuevos,
            'prestamosFinalizados' => $prestamosFinalizados,
            'meses' => $meses->toArray(),
            'cuotasPorMes' => $cuotasPorMes->toArray(),
            'compromisosPorMes' => $compromisosPorMes->toArray(),
        ]);
    }
}
