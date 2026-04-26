<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operacion;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Zona;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CajaController extends Controller
{
    // Definir qué tipos de operaciones son INGRESOS (requieren rendición)
    private $tiposIngresos = [
        'Pago general',
        'Pago de cuota',
        'Pago de mora',
        'Liquidación Total',
    ];

    // Definir qué tipos de operaciones son EGRESOS (NO requieren rendición)
    private $tiposEgresos = [
        'Desembolso',
    ];

    public function index(Request $request)
    {
        // Consulta base SOLO para INGRESOS (operaciones que requieren rendición)
        $query = Operacion::with([
            'user',
            'metodoDePago',
            'user.usersBySucursal.sucursal',
            'user.zonas',
            'user.carteraAsesor',
            'user.carteraJcc',
        ])->whereIn('tipo_operacion', $this->tiposIngresos); // SOLO INGRESOS

        // Filtros directos
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->estado_rendicion !== null && $request->estado_rendicion !== '') {
            $query->where('estado_rendicion', $request->estado_rendicion);
        }

        // Filtro por fecha
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('fecha', [$request->start_date, $request->end_date]);
        }

        // Filtros por relaciones
        if ($request->sucursal_id) {
            $query->whereHas('user.sucursales', function ($q) use ($request) {
                $q->where('sucursal_id', $request->sucursal_id);
            });
        }

        if ($request->zona_id) {
            $query->whereHas('user.zonas', function ($q) use ($request) {
                $q->where('zona_id', $request->zona_id);
            });
        }

        if ($request->asesor_id) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->whereHas('carteraAsesor', function ($aq) use ($request) {
                    $aq->where('asesor_id', $request->asesor_id);
                });
            });
        }

        if ($request->jcc_id) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->whereHas('carteraJcc', function ($jq) use ($request) {
                    $jq->where('jcc_id', $request->jcc_id);
                });
            });
        }

        // Cálculo de KPIs SOLO para INGRESOS
        $totalIngresos = $query->clone()->sum('abono');
        $efectivoPorRendir = $query->clone()->where('metodo_pago_id', 1)->where('estado_rendicion', 0)->sum('abono');
        $efectivoRendido = $query->clone()->where('metodo_pago_id', 1)->where('estado_rendicion', 1)->sum('abono');
        $transferenciasDepositos = $query->clone()->whereIn('metodo_pago_id', [2, 3])->sum('abono');
        $yapePlin = $query->clone()->whereIn('metodo_pago_id', [4, 5])->sum('abono');

        $kpis = [
            'total_ingresos' => $totalIngresos,
            'efectivo_por_rendir' => $efectivoPorRendir,
            'efectivo_rendido' => $efectivoRendido,
            'transferencias_depositos' => $transferenciasDepositos,
            'yape_plin' => $yapePlin,
            'porcentaje_rendido' => ($efectivoRendido + $efectivoPorRendir) > 0 ?
                                         round(($efectivoRendido / ($efectivoRendido + $efectivoPorRendir)) * 100, 1) : 100,
        ];

        // Resumen por usuario SOLO de INGRESOS
        $resumenUsuarios = $this->getResumenUsuarios($query);

        $operaciones = $query->orderBy('fecha', 'desc')->paginate(20);
        $total_abono = $query->clone()->sum('abono');

        if ($request->ajax()) {
            return response()->json([
                'kpis_html' => view('admin.Caja.partials.kpis', compact('kpis'))->render(),
                'charts_html' => view('admin.Caja.partials.charts', compact('resumenUsuarios'))->render(),
                'table_html' => view('admin.Caja.partials.table', compact('operaciones', 'total_abono', 'resumenUsuarios'))->render(),
                'resumen_usuarios' => $resumenUsuarios,
                'operaciones' => $operaciones,
            ]);
        }

        return view('admin.Caja.index', [
            'operaciones' => $operaciones,
            'kpis' => $kpis,
            'resumenUsuarios' => $resumenUsuarios,
            'total_abono' => $total_abono,
            'usuarios' => User::select('id', 'codigo', 'name')->get(),
            'sucursales' => Sucursal::all(),
            'zonas' => Zona::all(),
            'asesores' => User::role('Asesor')->get(),
            'jccs' => User::role('JCC')->get(),
            'estados_rendicion' => [0, 1],
        ]);
    }

    private function getResumenUsuarios($query)
    {
        $usersData = Operacion::selectRaw('
                user_id,
                COALESCE(SUM(CASE WHEN metodo_pago_id = 1 AND estado_rendicion = 1 THEN abono ELSE 0 END), 0) as efectivo_rendido,
                COALESCE(SUM(CASE WHEN metodo_pago_id = 1 AND estado_rendicion = 0 THEN abono ELSE 0 END), 0) as efectivo_por_rendir,
                COALESCE(SUM(CASE WHEN metodo_pago_id != 1 THEN abono ELSE 0 END), 0) as otros_metodos,
                COUNT(*) as total_operaciones
            ')
            ->whereIn('tipo_operacion', $this->tiposIngresos) // SOLO INGRESOS
            ->whereIn('user_id', $query->clone()->pluck('user_id'))
            ->groupBy('user_id')
            ->get();

        return $usersData->map(function ($item) {
            $user = User::find($item->user_id);
            $totalEfectivo = $item->efectivo_rendido + $item->efectivo_por_rendir;

            return [
                'user_id' => $item->user_id,
                'codigo' => $user?->codigo ?? 'Sin Código',
                'nombre' => $user?->name ?? 'Sin Nombre',
                'efectivo_rendido' => (float) $item->efectivo_rendido,
                'efectivo_por_rendir' => (float) $item->efectivo_por_rendir,
                'otros_metodos' => (float) $item->otros_metodos,
                'total_recaudado' => (float) ($totalEfectivo + $item->otros_metodos),
                'total_operaciones' => (int) $item->total_operaciones,
                'porcentaje_rendido' => $totalEfectivo > 0 ? round(($item->efectivo_rendido / $totalEfectivo) * 100, 1) : 100,
            ];
        })->sortByDesc('efectivo_por_rendir')->values();
    }

    public function mostrarRendicionUsuario(Request $request, $user_id)
    {
        $usuario = User::findOrFail($user_id);

        // Obtener SOLO operaciones de INGRESOS en efectivo pendientes de este usuario
        $operacionesPendientes = Operacion::with(['metodoDePago', 'prestamo.cliente.persona'])
            ->where('user_id', $user_id)
            ->where('metodo_pago_id', 1) // Solo efectivo
            ->where('estado_rendicion', 0) // Solo pendientes
            ->whereIn('tipo_operacion', $this->tiposIngresos) // SOLO INGRESOS
            ->orderBy('fecha', 'desc')
            ->get();

        $totalPendiente = $operacionesPendientes->sum('abono');

        return view('admin.Caja.rendicion-usuario', [
            'usuario' => $usuario,
            'operaciones' => $operacionesPendientes,
            'totalPendiente' => $totalPendiente,
        ]);
    }

    public function procesarRendicionParcial(Request $request)
    {
        $request->validate([
            'operaciones_seleccionadas' => 'required|array',
            'operaciones_seleccionadas.*' => 'exists:operaciones,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $operacionesIds = $request->operaciones_seleccionadas;
        $userId = $request->user_id;

        // Verificar que todas las operaciones pertenezcan al usuario y sean de efectivo pendiente
        $operaciones = Operacion::whereIn('id', $operacionesIds)
            ->where('user_id', $userId)
            ->where('metodo_pago_id', 1)
            ->where('estado_rendicion', 0)
            ->whereIn('tipo_operacion', $this->tiposIngresos) // SOLO INGRESOS
            ->get();

        if ($operaciones->count() !== count($operacionesIds)) {
            return redirect()->back()->with('error', 'Algunas operaciones no son válidas para rendición.');
        }

        $totalRendido = $operaciones->sum('abono');

        // Marcar operaciones como rendidas
        foreach ($operaciones as $operacion) {
            $operacion->estado_rendicion = 1;
            $operacion->save();
        }

        // Generar PDF de comprobante
        $usuario = User::findOrFail($userId);
        $numeroRendicion = 'R-'.$userId.'-'.now()->format('YmdHis');

        $data = [
            'numero_rendicion' => $numeroRendicion,
            'usuario' => $usuario,
            'operaciones' => $operaciones,
            'total_rendido' => $totalRendido,
            'fecha_rendicion' => now()->format('d/m/Y H:i:s'),
            'usuario_rendidor' => auth()->user()->codigo,
            'tipo' => 'parcial',
        ];

        $pdf = Pdf::loadView('admin.PDF.comprobante-rendicion', $data);
        $pdfPath = 'rendiciones/rendicion_'.$numeroRendicion.'.pdf';
        $pdf->save(storage_path('app/public/'.$pdfPath));

        // Registrar en historial
        $this->registrarHistorialRendicion($userId, $totalRendido, $pdfPath, 'parcial');

        return redirect()->back()->with('success',
            'Rendición parcial procesada. Total: S/ '.number_format($totalRendido, 2).
            ". <a href='".asset('storage/'.$pdfPath)."' target='_blank' class='btn btn-sm btn-outline-primary ml-2'><i class='fas fa-file-pdf'></i> Ver comprobante</a>"
        );
    }

    public function rendirTodoUsuario(Request $request, $user_id)
    {
        $usuario = User::findOrFail($user_id);

        // Obtener TODAS las operaciones de INGRESOS en efectivo pendientes
        $operaciones = Operacion::where('user_id', $user_id)
            ->where('metodo_pago_id', 1)
            ->where('estado_rendicion', 0)
            ->whereIn('tipo_operacion', $this->tiposIngresos) // SOLO INGRESOS
            ->get();

        if ($operaciones->isEmpty()) {
            return redirect()->back()->with('error', 'No hay operaciones pendientes para rendir de este usuario.');
        }

        $totalRendido = $operaciones->sum('abono');

        // Marcar todas como rendidas
        foreach ($operaciones as $operacion) {
            $operacion->estado_rendicion = 1;
            $operacion->save();
        }

        // Generar PDF
        $numeroRendicion = 'R-'.$user_id.'-'.now()->format('YmdHis');

        $data = [
            'numero_rendicion' => $numeroRendicion,
            'usuario' => $usuario,
            'operaciones' => $operaciones,
            'total_rendido' => $totalRendido,
            'fecha_rendicion' => now()->format('d/m/Y H:i:s'),
            'usuario_rendidor' => auth()->user()->codigo,
            'tipo' => 'completa',
        ];

        $pdf = Pdf::loadView('admin.PDF.comprobante-rendicion', $data);
        $pdfPath = 'rendiciones/rendicion_'.$numeroRendicion.'.pdf';
        $pdf->save(storage_path('app/public/'.$pdfPath));

        // Registrar en historial
        $this->registrarHistorialRendicion($user_id, $totalRendido, $pdfPath, 'completa');

        return redirect()->back()->with('success',
            'Rendición completa procesada. Total: S/ '.number_format($totalRendido, 2).
            ". <a href='".asset('storage/'.$pdfPath)."' target='_blank' class='btn btn-sm btn-outline-primary ml-2'><i class='fas fa-file-pdf'></i> Ver comprobante</a>"
        );
    }

    public function cierreDiario(Request $request)
    {
        // Obtener TODAS las operaciones de INGRESOS en efectivo pendientes
        $operacionesPendientes = Operacion::where('estado_rendicion', 0)
            ->where('metodo_pago_id', 1)
            ->whereIn('tipo_operacion', $this->tiposIngresos) // SOLO INGRESOS
            ->get();

        if ($operacionesPendientes->isEmpty()) {
            return redirect()->back()->with('info', 'No hay operaciones pendientes para el cierre diario.');
        }

        $totalRendido = $operacionesPendientes->sum('abono');

        foreach ($operacionesPendientes as $op) {
            $op->estado_rendicion = 1;
            $op->save();
        }

        // Generar PDF de cierre diario
        $numeroRendicion = 'CD-'.now()->format('YmdHis');

        $data = [
            'numero_rendicion' => $numeroRendicion,
            'operaciones' => $operacionesPendientes,
            'total_rendido' => $totalRendido,
            'fecha_rendicion' => now()->format('d/m/Y H:i:s'),
            'usuario_rendidor' => auth()->user()->codigo,
            'tipo' => 'cierre_diario',
        ];

        $pdf = Pdf::loadView('admin.PDF.comprobante-rendicion', $data);
        $pdfPath = 'rendiciones/cierre_diario_'.$numeroRendicion.'.pdf';
        $pdf->save(storage_path('app/public/'.$pdfPath));

        return redirect()->back()->with('success',
            'Cierre diario realizado. Total: S/ '.number_format($totalRendido, 2).
            ". <a href='".asset('storage/'.$pdfPath)."' target='_blank' class='btn btn-sm btn-outline-primary ml-2'><i class='fas fa-file-pdf'></i> Ver comprobante</a>"
        );
    }

    private function registrarHistorialRendicion($userId, $totalRendido, $pdfPath, $tipo)
    {
        try {
            DB::insert('INSERT INTO rendicion_cuentas (tipo, entidad_id, total_rendido, fecha_rendicion, usuario_rendidor_id, pdf_path) VALUES (?, ?, ?, ?, ?, ?)', [
                $tipo,
                $userId,
                $totalRendido,
                now(),
                auth()->id(),
                $pdfPath,
            ]);
        } catch (\Exception $e) {
            Log::info("Rendición realizada - Tipo: {$tipo}, Usuario: {$userId}, Total: S/ {$totalRendido}");
        }
    }

    // Método legacy mantenido para compatibilidad
    public function updateEstado(Request $request, $operacion_id)
    {
        $request->validate(['estado_rendicion' => 'required|in:0,1']);
        $operacion = Operacion::findOrFail($operacion_id);

        // Solo permitir para efectivo e ingresos
        if ($operacion->metodo_pago_id == 1 && in_array($operacion->tipo_operacion, $this->tiposIngresos)) {
            $operacion->estado_rendicion = $request->estado_rendicion;
            $operacion->save();
        }

        return redirect()->back()->with('success', 'Estado de rendición actualizado.');
    }

    public function historialRendiciones(Request $request)
    {
        try {
            // Intentar obtener datos de la tabla rendicion_cuentas
            $rendiciones = DB::table('rendicion_cuentas')
                ->leftJoin('users', 'rendicion_cuentas.entidad_id', '=', 'users.id')
                ->leftJoin('users as rendidor', 'rendicion_cuentas.usuario_rendidor_id', '=', 'rendidor.id')
                ->select(
                    'rendicion_cuentas.*',
                    'users.codigo as usuario_codigo',
                    'users.name as usuario_nombre',
                    'rendidor.codigo as rendidor_codigo'
                )
                ->orderBy('fecha_rendicion', 'desc')
                ->paginate(20);
        } catch (\Exception $e) {
            // Si la tabla no existe, crear datos dummy basados en archivos PDF existentes
            $rendiciones = collect();

            // Buscar archivos PDF en la carpeta de rendiciones
            $pdfFiles = glob(storage_path('app/public/rendiciones/*.pdf'));

            foreach ($pdfFiles as $file) {
                $filename = basename($file);
                $fileInfo = pathinfo($file);
                $createdAt = date('Y-m-d H:i:s', filemtime($file));

                $tipo = 'desconocido';
                $userId = null;
                $timestamp = null;
                $totalRendido = 0;

                // Patrón para archivos de rendición: rendicion_R-{userId}-{timestamp}.pdf
                if (preg_match('/rendicion_R-(\d+)-(\d{14})\.pdf/', $filename, $matches)) {
                    $tipo = 'R';
                    $userId = $matches[1];
                    $timestamp = $matches[2];
                }
                // Patrón para archivos de cierre diario: cierre_diario_CD-{timestamp}.pdf
                elseif (preg_match('/cierre_diario_CD-(\d{14})\.pdf/', $filename, $matches)) {
                    $tipo = 'CD';
                    $userId = null; // Los cierres diarios no tienen usuario específico
                    $timestamp = $matches[1];
                }
                // Patrón legacy: rendicion_{tipo}-{userId}-{timestamp}.pdf
                elseif (preg_match('/rendicion_(.+?)-(\d+)-(\d{14})\.pdf/', $filename, $matches)) {
                    $tipo = $matches[1];
                    $userId = $matches[2];
                    $timestamp = $matches[3];
                }

                if ($timestamp) {
                    $user = null;
                    if ($userId) {
                        $user = User::find($userId);
                        // Calcular el monto total basado en el timestamp y usuario
                        $fechaRendicion = \Carbon\Carbon::createFromFormat('YmdHis', $timestamp);
                        $totalRendido = $this->calcularMontoRendicion($userId, $fechaRendicion);
                    }

                    $rendiciones->push((object) [
                        'id' => $filename,
                        'tipo' => $tipo,
                        'entidad_id' => $userId,
                        'usuario_codigo' => $user->codigo ?? ($userId ? 'U-'.$userId : 'Sistema'),
                        'usuario_nombre' => $user->name ?? ($userId ? 'Usuario '.$userId : 'Cierre Diario'),
                        'total_rendido' => $totalRendido,
                        'fecha_rendicion' => $createdAt,
                        'rendidor_codigo' => 'Sistema',
                        'pdf_path' => 'rendiciones/'.$filename,
                    ]);
                }
            }

            // Simular paginación
            $page = $request->get('page', 1);
            $perPage = 20;
            $total = $rendiciones->count();
            $items = $rendiciones->slice(($page - 1) * $perPage, $perPage);

            $rendiciones = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'pageName' => 'page']
            );
        }

        return view('admin.Caja.historial-rendiciones', [
            'rendiciones' => $rendiciones,
        ]);
    }

    public function getRecentRendiciones()
    {
        try {
            // Intentar obtener las últimas 5 rendiciones de la tabla
            $rendiciones = DB::table('rendicion_cuentas')
                ->leftJoin('users', 'rendicion_cuentas.entidad_id', '=', 'users.id')
                ->leftJoin('users as rendidor', 'rendicion_cuentas.usuario_rendidor_id', '=', 'rendidor.id')
                ->select(
                    'rendicion_cuentas.*',
                    'users.codigo as usuario_codigo',
                    'users.name as usuario_nombre',
                    'rendidor.codigo as rendidor_codigo'
                )
                ->orderBy('fecha_rendicion', 'desc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            // Si la tabla no existe, crear datos dummy basados en archivos PDF existentes
            $rendiciones = collect();

            // Buscar los últimos 5 archivos PDF en la carpeta de rendiciones
            $pdfFiles = glob(storage_path('app/public/rendiciones/*.pdf'));

            // Ordenar por fecha de modificación descendente y tomar los últimos 5
            usort($pdfFiles, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $pdfFiles = array_slice($pdfFiles, 0, 5);

            foreach ($pdfFiles as $file) {
                $filename = basename($file);
                $createdAt = date('Y-m-d H:i:s', filemtime($file));

                $tipo = 'desconocido';
                $userId = null;
                $timestamp = null;
                $totalRendido = 0;

                // Patrón para archivos de rendición: rendicion_R-{userId}-{timestamp}.pdf
                if (preg_match('/rendicion_R-(\d+)-(\d{14})\.pdf/', $filename, $matches)) {
                    $tipo = 'R';
                    $userId = $matches[1];
                    $timestamp = $matches[2];
                }
                // Patrón para archivos de cierre diario: cierre_diario_CD-{timestamp}.pdf
                elseif (preg_match('/cierre_diario_CD-(\d{14})\.pdf/', $filename, $matches)) {
                    $tipo = 'CD';
                    $userId = null; // Los cierres diarios no tienen usuario específico
                    $timestamp = $matches[1];
                }
                // Patrón legacy: rendicion_{tipo}-{userId}-{timestamp}.pdf
                elseif (preg_match('/rendicion_(.+?)-(\d+)-(\d{14})\.pdf/', $filename, $matches)) {
                    $tipo = $matches[1];
                    $userId = $matches[2];
                    $timestamp = $matches[3];
                }

                if ($timestamp) {
                    $user = null;
                    if ($userId) {
                        $user = User::find($userId);
                        // Calcular el monto total basado en el timestamp y usuario
                        $fechaRendicion = \Carbon\Carbon::createFromFormat('YmdHis', $timestamp);
                        $totalRendido = $this->calcularMontoRendicion($userId, $fechaRendicion);
                    }

                    $rendiciones->push((object) [
                        'id' => $filename,
                        'tipo' => $tipo,
                        'entidad_id' => $userId,
                        'usuario_codigo' => $user->codigo ?? ($userId ? 'U-'.$userId : 'Sistema'),
                        'usuario_nombre' => $user->name ?? ($userId ? 'Usuario '.$userId : 'Cierre Diario'),
                        'total_rendido' => $totalRendido,
                        'fecha_rendicion' => $createdAt,
                        'rendidor_codigo' => 'Sistema',
                        'pdf_path' => 'rendiciones/'.$filename,
                    ]);
                }
            }
        }

        return response()->json([
            'historial_html' => view('admin.Caja.partials.historial-reciente', [
                'rendiciones' => $rendiciones,
            ])->render(),
        ]);
    }

    private function calcularMontoRendicion($userId, $fechaRendicion)
    {
        try {
            // Buscar operaciones de este usuario que fueron rendidas cerca de esta fecha
            // Buscamos en un rango de +/- 1 hora de la fecha de rendición
            $fechaInicio = $fechaRendicion->copy()->subHour();
            $fechaFin = $fechaRendicion->copy()->addHour();

            $totalRendido = Operacion::where('user_id', $userId)
                ->where('metodo_pago_id', 1) // Solo efectivo
                ->where('estado_rendicion', 1) // Solo rendidas
                ->whereIn('tipo_operacion', $this->tiposIngresos) // Solo ingresos
                ->whereBetween('updated_at', [$fechaInicio, $fechaFin])
                ->sum('abono');

            // Si no encontramos nada en updated_at, intentar con created_at
            if ($totalRendido == 0) {
                $totalRendido = Operacion::where('user_id', $userId)
                    ->where('metodo_pago_id', 1) // Solo efectivo
                    ->where('estado_rendicion', 1) // Solo rendidas
                    ->whereIn('tipo_operacion', $this->tiposIngresos) // Solo ingresos
                    ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                    ->sum('abono');
            }

            return (float) $totalRendido;
        } catch (\Exception $e) {
            Log::warning("Error calculando monto de rendición para usuario {$userId}: ".$e->getMessage());

            return 0;
        }
    }
}
