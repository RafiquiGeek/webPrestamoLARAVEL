<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ClientesPorUsuarioExport;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReporteClientesController extends Controller
{
    /**
     * Muestra el reporte de clientes por usuario con filtros
     */
    public function index(Request $request)
    {
        // Construir query con filtros
        $query = $this->buildQuery($request);

        // Paginar resultados
        $clientes = $query->paginate(50)->appends($request->query());

        // Cargar préstamo más reciente CON usuarios asignados
        // Esto se hace después de paginar para evitar cargar datos innecesarios
        $clientes->load([
            'prestamos' => fn($q) => $q->select('id', 'cliente_id', 'estado', 'fecha_atencion', 'cantidad_solicitada')
                ->latest('fecha_atencion')
                ->with([
                    'carterasJcc' => fn($cq) => $cq->where('estado', 1)
                        ->with('jcc.persona:id,nombres,ape_pat,ape_mat'),
                    'carterasAsesor' => fn($cq) => $cq->where('estado', 1)
                        ->with('asesor.persona:id,nombres,ape_pat,ape_mat'),
                    'carterasAnalista' => fn($cq) => $cq->where('estado', 1)
                        ->with('analista.persona:id,nombres,ape_pat,ape_mat')
                ])
        ]);

        // Datos para los filtros
        $usuarios = User::whereHas('roles', fn($q) =>
                $q->whereIn('name', ['JCC', 'Asesor', 'Analista'])
            )
            ->where('status', 1)
            ->with([
                'persona:id,nombres,ape_pat,ape_mat',
                'roles:id,name',
                'zonas:id,nombre'
            ])
            ->orderBy('name')
            ->get();

        $zonas = Zona::orderBy('nombre')->get();
        $sucursales = Sucursal::orderBy('sucursal')->get();

        // Estados de préstamos disponibles
        $estados = [
            'Nueva Solicitud',
            'Por Desembolsar',
            'Vigente',
            'Moroso',
            'Con Convenio',
            'Liquidado',
            'Finalizado',
            'Cancelado'
        ];

        return view('admin.reportes-clientes.index', compact(
            'clientes',
            'usuarios',
            'zonas',
            'sucursales',
            'estados'
        ));
    }

    /**
     * Exportar reporte a Excel con los mismos filtros aplicados
     */
    public function exportar(Request $request)
    {
        $query = $this->buildQuery($request);
        $clientes = $query->get();

        // Cargar préstamo más reciente CON usuarios asignados
        $clientes->load([
            'prestamos' => fn($q) => $q->select('id', 'cliente_id', 'estado', 'fecha_atencion', 'cantidad_solicitada')
                ->latest('fecha_atencion')
                ->with([
                    'carterasJcc' => fn($cq) => $cq->where('estado', 1)
                        ->with('jcc.persona:id,nombres,ape_pat,ape_mat'),
                    'carterasAsesor' => fn($cq) => $cq->where('estado', 1)
                        ->with('asesor.persona:id,nombres,ape_pat,ape_mat'),
                    'carterasAnalista' => fn($cq) => $cq->where('estado', 1)
                        ->with('analista.persona:id,nombres,ape_pat,ape_mat')
                ])
        ]);

        $filename = 'clientes_por_usuario_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new ClientesPorUsuarioExport($clientes), $filename);
    }

    /**
     * Construye la query base con todos los filtros aplicados
     */
    private function buildQuery(Request $request)
    {
        $query = Cliente::query()
            ->with([
                'persona:id,nombres,ape_pat,ape_mat,documento',
                'persona.telefonos:id,persona_id,numero,tipo_telefono',
                'persona.direcciones' => fn($q) => $q->select('id', 'persona_id', 'sucursal_id', 'direccion', 'numero', 'referencia', 'estado')
                    ->with([
                        'sucursal:id,sucursal',
                        'sucursal.zonas:id,nombre'
                    ])
            ])
            ->withCount('prestamos')
            ->withSum('prestamos', 'cantidad_solicitada')
            ->whereHas('prestamos'); // Solo clientes con préstamos

        // Filtro 1: Por Usuario específico (JCC/Asesor/Analista)
        if ($request->filled('usuario_id')) {
            $usuarioIds = is_array($request->usuario_id)
                ? $request->usuario_id
                : [$request->usuario_id];

            $query->whereHas('prestamos', function($q) use ($usuarioIds) {
                $q->where(function($subQ) use ($usuarioIds) {
                    $subQ->whereHas('carterasJcc', fn($jccQ) =>
                        $jccQ->whereIn('jcc_id', $usuarioIds)->where('estado', 1))
                    ->orWhereHas('carterasAsesor', fn($asesorQ) =>
                        $asesorQ->whereIn('asesor_id', $usuarioIds)->where('estado', 1))
                    ->orWhereHas('carterasAnalista', fn($analistaQ) =>
                        $analistaQ->whereIn('analista_id', $usuarioIds)->where('estado', 1));
                });
            });
        }

        // Filtro 2: Por Zona del Usuario (zonas en user_zona)
        if ($request->filled('zona_usuario_id')) {
            $zonaUsuarioIds = is_array($request->zona_usuario_id)
                ? $request->zona_usuario_id
                : [$request->zona_usuario_id];

            // Obtener zonas asignadas a estos usuarios
            $zonasAsignadas = DB::table('user_zona')
                ->whereIn('user_id', $zonaUsuarioIds)
                ->pluck('zona_id')
                ->unique()
                ->toArray();

            if (!empty($zonasAsignadas)) {
                $query->whereHas('persona.direcciones.sucursal.zonas', fn($q) =>
                    $q->whereIn('zonas.id', $zonasAsignadas));
            }
        }

        // Filtro 3: Por Zona del Cliente (ubicación física)
        if ($request->filled('zona_cliente_id')) {
            $query->whereHas('persona.direcciones.sucursal.zonas', fn($q) =>
                $q->where('zonas.id', $request->zona_cliente_id));
        }

        // Filtro 4: Por Sucursal del Cliente
        if ($request->filled('sucursal_cliente_id')) {
            $query->whereHas('persona.direcciones', fn($q) =>
                $q->where('sucursal_id', $request->sucursal_cliente_id));
        }

        // Filtro 5: Por Estado de Préstamo
        if ($request->filled('estado_prestamo')) {
            $estados = is_array($request->estado_prestamo)
                ? $request->estado_prestamo
                : [$request->estado_prestamo];

            $query->whereHas('prestamos', fn($q) =>
                $q->whereIn('estado', $estados));
        }

        // Ordenar por ID del cliente (el ordenamiento por nombre se hará en la vista si es necesario)
        $query->orderBy('clientes.id', 'desc');

        return $query;
    }

    /**
     * Obtiene las sucursales asociadas a una zona específica
     */
    public function getSucursalesByZona($zonaId)
    {
        $sucursales = Sucursal::whereHas('zonas', function($q) use ($zonaId) {
            $q->where('zonas.id', $zonaId);
        })
        ->orderBy('sucursal')
        ->get(['id', 'sucursal']);

        return response()->json($sucursales);
    }
}
