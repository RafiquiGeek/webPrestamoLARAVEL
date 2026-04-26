<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CarteraHelper;
use App\Http\Controllers\Controller;
use App\Models\Prestamo;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Zona;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CarteraController extends Controller
{
    public function index(Request $request)
    {
        // Configurar la consulta base
        $query = Prestamo::with([
            'cliente.persona.direccion.sucursal.zonas',
            'carterasAsesor.asesor.persona',
            'carterasJcc.jcc.persona',
            'carterasAnalista.analista.persona',
            'cuotas' => function ($query) {
                $query->orderBy('fecha_pago', 'desc');
            },
        ]);

        // Aplicar filtros
        if ($request->filled('zona_id')) {
            $query->whereHas('cliente.persona.direccion.sucursal.zonas', function ($q) use ($request) {
                $q->where('zonas.id', $request->zona_id);
            });
        }

        if ($request->filled('sucursal_id')) {
            $query->whereHas('cliente.persona.direccion.sucursal', function ($q) use ($request) {
                $q->where('sucursales.id', $request->sucursal_id);
            });
        }

        if ($request->filled('jcc_id')) {
            $query->whereHas('carterasJcc', function ($q) use ($request) {
                $q->where('jcc_id', $request->jcc_id);
            });
        }

        if ($request->filled('asesor_id')) {
            $query->whereHas('carterasAsesor', function ($q) use ($request) {
                $q->where('asesor_id', $request->asesor_id);
            });
        }

        if ($request->filled('analista_id')) {
            $query->whereHas('carterasAnalista', function ($q) use ($request) {
                $q->where('analista_id', $request->analista_id);
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro para número de cuotas vencidas
        if ($request->filled('nrocuota') && is_numeric($request->nrocuota)) {
            $query->whereHas('cuotas', function ($q) use ($request) {
                $q->where('estado', '!=', 2) // No pagadas
                    ->where('fecha_pago', '<', now())
                    ->havingRaw('COUNT(*) >= ?', [$request->nrocuota]);
            }, '>=', $request->nrocuota);
        }

        // Obtener los resultados
        $carteras = $query->get()->map(function ($prestamo) {
            $ultimaCuota = $prestamo->cuotas->first();
            $cuotasVencidas = $prestamo->cuotas->filter(function ($cuota) {
                return $cuota->estado != 2 && $cuota->fecha_pago < now();
            })->count();

            // Obtener datos del asesor
            $asesor = optional($prestamo->carterasAsesor->first())->asesor;

            // Obtener datos del JCC
            $jcc = optional($prestamo->carterasJcc->first())->jcc;

            // Obtener datos del analista
            $analista = optional($prestamo->carterasAnalista->first())->analista;

            return [
                'id' => $prestamo->id,
                'zona' => $prestamo->cliente->persona->direccion->sucursal->zonas->pluck('nombre')->join(', ') ?? 'Sin zonas',
                'sucursal' => $prestamo->cliente->persona->direccion->sucursal->sucursal ?? 'Sin sucursal',
                'nombre_cliente' => $prestamo->cliente->persona->nombres.' '.$prestamo->cliente->persona->ape_pat.' '.$prestamo->cliente->persona->ape_mat,

                // Código y nombre del asesor
                'codigo_asesor' => optional($asesor)->codigo,
                'nombre_asesor' => optional(optional($asesor)->persona)->nombres.' '.optional(optional($asesor)->persona)->ape_pat ?? 'Sin asesor',

                // Código y nombre del JCC
                'codigo_jcc' => optional($jcc)->codigo,
                'nombre_jcc' => optional(optional($jcc)->persona)->nombres.' '.optional(optional($jcc)->persona)->ape_pat ?? 'Sin JCC',

                // Código y nombre del analista
                'codigo_analista' => optional($analista)->codigo,
                'nombre_analista' => optional(optional($analista)->persona)->nombres.' '.optional(optional($analista)->persona)->ape_pat ?? 'Sin analista',

                'estado_prestamo' => $prestamo->estado,
                'estado_ultima_cuota' => $ultimaCuota ? $this->getEstadoCuota($ultimaCuota->estado) : 'Sin cuotas',
                'cuotas_vencidas' => $cuotasVencidas,
                'monto_prestamo' => $prestamo->cantidad_solicitada,
                'saldo_pendiente' => $prestamo->saldo_restante,
            ];
        });

        // Cargar los datos adicionales para los filtros
        $zonas = Zona::all();
        $sucursales = Sucursal::all();
        $jccs = User::role('JCC')->where('status', 1)->get();
        $asesores = User::role('Asesor')->where('status', 1)->get();
        $analistas = User::role('Analista')->where('status', 1)->get();

        // Definir los helpers para los badges
        $helpers = [
            'estadoBadge' => [CarteraHelper::class, 'estadoBadge'],
            'cuotaBadge' => [CarteraHelper::class, 'cuotaBadge'],
        ];

        // Para peticiones AJAX, devolver solo los datos
        if ($request->ajax()) {
            return response()->json([
                'carteras' => $carteras,
                'count' => $carteras->count(),
            ]);
        }

        return view('admin.Carteras.index', compact('carteras', 'zonas', 'sucursales', 'jccs', 'asesores', 'analistas', 'helpers'));
    }

    private function getEstadoCuota($estado)
    {
        switch ($estado) {
            case 0: return 'Pendiente';
            case 1: return 'Parcial';
            case 2: return 'Pagado';
            default: return 'Desconocido';
        }
    }

    public function generarPDF(Request $request)
    {
        // Configurar la consulta base
        $query = Prestamo::with([
            'cliente.persona.direccion.sucursal.zonas',
            'carterasAsesor.asesor.persona',
            'carterasJcc.jcc.persona',
            'carterasAnalista.analista.persona',
            'cuotas' => function ($query) {
                $query->orderBy('fecha_pago', 'desc');
            },
        ]);

        // Aplicar filtros
        if ($request->filled('zona_id')) {
            $query->whereHas('cliente.persona.direccion.sucursal.zonas', function ($q) use ($request) {
                $q->where('zonas.id', $request->zona_id);
            });
        }

        if ($request->filled('sucursal_id')) {
            $query->whereHas('cliente.persona.direccion.sucursal', function ($q) use ($request) {
                $q->where('sucursales.id', $request->sucursal_id);
            });
        }

        if ($request->filled('jcc_id')) {
            $query->whereHas('carterasJcc', function ($q) use ($request) {
                $q->where('jcc_id', $request->jcc_id);
            });
        }

        if ($request->filled('asesor_id')) {
            $query->whereHas('carterasAsesor', function ($q) use ($request) {
                $q->where('asesor_id', $request->asesor_id);
            });
        }

        if ($request->filled('analista_id')) {
            $query->whereHas('carterasAnalista', function ($q) use ($request) {
                $q->where('analista_id', $request->analista_id);
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro para número de cuotas vencidas
        if ($request->filled('nrocuota') && is_numeric($request->nrocuota)) {
            $query->whereHas('cuotas', function ($q) use ($request) {
                $q->where('estado', '!=', 2) // No pagadas
                    ->where('fecha_pago', '<', now())
                    ->havingRaw('COUNT(*) >= ?', [$request->nrocuota]);
            }, '>=', $request->nrocuota);
        }

        // Obtener los resultados
        $carteras = $query->get()->map(function ($prestamo) {
            $ultimaCuota = $prestamo->cuotas->first();
            $cuotasVencidas = $prestamo->cuotas->filter(function ($cuota) {
                return $cuota->estado != 2 && $cuota->fecha_pago < now();
            })->count();

            // Obtener datos del asesor
            $asesor = optional($prestamo->carterasAsesor->first())->asesor;

            // Obtener datos del JCC
            $jcc = optional($prestamo->carterasJcc->first())->jcc;

            // Obtener datos del analista
            $analista = optional($prestamo->carterasAnalista->first())->analista;

            // Obtener dirección del cliente
            $direccion = optional($prestamo->cliente->persona->direccion);
            $direccionCompleta = trim(
                ($direccion->calle ?? '').' '.
                ($direccion->numero ?? '').' '.
                ($direccion->colonia ?? '')
            );

            return [
                'id' => $prestamo->id,
                'zona' => $prestamo->cliente->persona->direccion->sucursal->zonas->pluck('nombre')->join(', ') ?? 'Sin zonas',
                'sucursal' => $prestamo->cliente->persona->direccion->sucursal->sucursal ?? 'Sin sucursal',
                'direccion' => $direccionCompleta ?: 'Sin dirección registrada',
                'nombre_cliente' => $prestamo->cliente->persona->nombres.' '.$prestamo->cliente->persona->ape_pat.' '.$prestamo->cliente->persona->ape_mat,

                // Código y nombre del asesor
                'codigo_asesor' => optional($asesor)->codigo,
                'nombre_asesor' => optional(optional($asesor)->persona)->nombres.' '.optional(optional($asesor)->persona)->ape_pat ?? 'Sin asesor',

                // Código y nombre del JCC
                'codigo_jcc' => optional($jcc)->codigo,
                'nombre_jcc' => optional(optional($jcc)->persona)->nombres.' '.optional(optional($jcc)->persona)->ape_pat ?? 'Sin JCC',

                // Código y nombre del analista
                'codigo_analista' => optional($analista)->codigo,
                'nombre_analista' => optional(optional($analista)->persona)->nombres.' '.optional(optional($analista)->persona)->ape_pat ?? 'Sin analista',

                'estado_prestamo' => $prestamo->estado,
                'estado_ultima_cuota' => $ultimaCuota ? $this->getEstadoCuota($ultimaCuota->estado) : 'Sin cuotas',
                'cuotas_vencidas' => $cuotasVencidas,
                'monto_prestamo' => $prestamo->cantidad_solicitada,
                'saldo_pendiente' => $prestamo->saldo_restante,
            ];
        });

        // Cargar los datos adicionales para los filtros en la vista PDF
        $zonas = Zona::all();
        $sucursales = Sucursal::all();
        $jccs = User::role('JCC')->where('status', 1)->get();
        $asesores = User::role('Asesor')->where('status', 1)->get();
        $analistas = User::role('Analista')->where('status', 1)->get();

        // Definir los helpers para los badges
        $helpers = [
            'estadoBadge' => [CarteraHelper::class, 'estadoBadge'],
            'cuotaBadge' => [CarteraHelper::class, 'cuotaBadge'],
        ];

        $data = [
            'carteras' => $carteras,
            'fecha' => now()->format('d/m/Y'),
            'filtros' => $request->all(),
            'helpers' => $helpers,
            'zonas' => $zonas,
            'sucursales' => $sucursales,
            'jccs' => $jccs,
            'asesores' => $asesores,
            'analistas' => $analistas,
        ];

        $pdf = PDF::loadView('admin.Carteras.pdf', $data);

        return $pdf->download('carteras_'.now()->format('YmdHis').'.pdf');
    }

    public function estadoCuenta($prestamo_id)
    {
        $prestamo = Prestamo::with([
            'cliente.persona',
            'cuotas.operaciones',
            'operaciones' => function ($query) {
                $query->orderBy('fecha', 'desc');
            },
        ])->findOrFail($prestamo_id);

        $data = [
            'prestamo' => $prestamo,
            'fecha' => now()->format('d/m/Y'),
        ];

        $pdf = PDF::loadView('admin.Carteras.estado_cuenta', $data);

        return $pdf->download('estado_cuenta_'.$prestamo_id.'_'.now()->format('YmdHis').'.pdf');
    }
}
