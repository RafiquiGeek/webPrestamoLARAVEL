<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConstructorReportesController extends Controller
{
    // Tablas disponibles para reportes con sus relaciones
    private $tablasDisponibles = [
        'clientes' => [
            'label' => 'Clientes',
            'descripcion' => 'Información de clientes y personas',
            'joins' => [
                'personas' => 'clientes.persona_id = personas.id',
                'direcciones' => 'personas.id = direcciones.persona_id',
                'zonas' => 'direcciones.zona_id = zonas.id',
                'sucursales' => 'direcciones.sucursal_id = sucursales.id',
            ],
            'icon' => 'fa-users',
        ],
        'prestamos' => [
            'label' => 'Préstamos',
            'descripcion' => 'Información de préstamos y solicitudes',
            'joins' => [
                'clientes' => 'prestamos.cliente_id = clientes.id',
                'personas' => 'clientes.persona_id = personas.id',
                'direcciones' => 'personas.id = direcciones.persona_id',
                'tasas' => 'prestamos.tasa_id = tasas.id',
                'plazos' => 'prestamos.plazo_id = plazos.id',
                'zonas' => 'direcciones.zona_id = zonas.id',
                'users' => 'prestamos.user_id = users.id',
            ],
            'icon' => 'fa-money-bill',
        ],
        'cuotas' => [
            'label' => 'Cuotas',
            'descripcion' => 'Cuotas de préstamos y pagos',
            'joins' => [
                'prestamos' => 'cuotas.prestamo_id = prestamos.id',
                'clientes' => 'prestamos.cliente_id = clientes.id',
                'personas' => 'clientes.persona_id = personas.id',
            ],
            'icon' => 'fa-calendar-check',
        ],
        'operaciones' => [
            'label' => 'Operaciones',
            'descripcion' => 'Operaciones financieras (pagos, desembolsos)',
            'joins' => [
                'prestamos' => 'operaciones.prestamo_id = prestamos.id',
                'clientes' => 'prestamos.cliente_id = clientes.id',
                'personas' => 'clientes.persona_id = personas.id',
                'metodos_pago' => 'operaciones.metodo_pago_id = metodos_pago.id',
                'users' => 'operaciones.user_id = users.id',
            ],
            'icon' => 'fa-exchange-alt',
        ],
        'moras' => [
            'label' => 'Moras',
            'descripcion' => 'Configuración de moras y penalizaciones',
            'joins' => [
                // La tabla moras no tiene relaciones directas, es una tabla de configuración
            ],
            'icon' => 'fa-exclamation-triangle',
        ],
        'gestiones' => [
            'label' => 'Gestiones',
            'descripcion' => 'Gestiones de cobranza',
            'joins' => [
                'prestamos' => 'gestiones.prestamo_id = prestamos.id',
                'clientes' => 'prestamos.cliente_id = clientes.id',
                'personas' => 'clientes.persona_id = personas.id',
                'users as asesor' => 'gestiones.asesor_id = asesor.id',
                'users as jcc' => 'gestiones.jcc_id = jcc.id',
                'estado_gestions' => 'gestiones.estado_id = estado_gestions.id',
            ],
            'icon' => 'fa-tasks',
        ],
    ];

    public function index()
    {
        // Obtener reportes guardados por el usuario
        $reportesGuardados = $this->obtenerReportesGuardados();

        // Obtener plantillas predefinidas
        $plantillasPredefinidas = $this->obtenerPlantillasPredefinidas();

        return view('admin.constructor-reportes.index', [
            'reportesGuardados' => $reportesGuardados,
            'plantillasPredefinidas' => $plantillasPredefinidas,
            'tablasDisponibles' => $this->tablasDisponibles,
        ]);
    }

    public function constructor(Request $request, $id = null)
    {
        $reporte = null;

        if ($id) {
            $reporte = $this->cargarReporte($id);
        }

        // Obtener esquema de tablas disponibles
        $esquemaTablas = $this->obtenerEsquemaTablas();

        return view('admin.constructor-reportes.constructor', [
            'reporte' => $reporte,
            'tablasDisponibles' => $this->tablasDisponibles,
            'esquemaTablas' => $esquemaTablas,
        ]);
    }

    public function obtenerCampos(Request $request)
    {
        $tabla = $request->get('tabla');

        if (! isset($this->tablasDisponibles[$tabla])) {
            return response()->json(['error' => 'Tabla no válida'], 400);
        }

        $campos = $this->obtenerCamposTabla($tabla);
        $camposRelacionados = $this->obtenerCamposRelacionados($tabla);

        return response()->json([
            'campos' => $campos,
            'camposRelacionados' => $camposRelacionados,
        ]);
    }

    public function previsualizar(Request $request)
    {
        try {
            $configuracion = $request->validate([
                'tabla_principal' => 'required|string',
                'campos' => 'required|array',
                'filtros' => 'array',
                'agrupaciones' => 'array',
                'ordenamiento' => 'array',
                'limite' => 'integer|min:1|max:1000',
            ]);

            $query = $this->construirQuery($configuracion);
            $datos = $query->limit($configuracion['limite'] ?? 100)->get();

            $metadatos = [
                'total_registros' => $datos->count(),
                'campos_seleccionados' => count($configuracion['campos']),
                'filtros_aplicados' => count($configuracion['filtros'] ?? []),
                'tiempo_ejecucion' => 0, // Se calculará en el frontend
            ];

            return response()->json([
                'success' => true,
                'datos' => $datos,
                'metadatos' => $metadatos,
                'sql_generado' => $query->toSql(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function generar(Request $request)
    {
        try {
            $configuracion = $request->validate([
                'tabla_principal' => 'required|string',
                'campos' => 'required|array',
                'filtros' => 'array',
                'agrupaciones' => 'array',
                'ordenamiento' => 'array',
                'formato' => 'required|in:pdf,excel,csv,json',
                'titulo' => 'string|max:255',
                'incluir_graficos' => 'boolean',
                'limite' => 'integer|min:1|max:10000',
            ]);

            $inicio = microtime(true);
            $query = $this->construirQuery($configuracion);
            $datos = $query->limit($configuracion['limite'] ?? 1000)->get();
            $tiempoEjecucion = round((microtime(true) - $inicio) * 1000, 2);

            $metadatos = [
                'titulo' => $configuracion['titulo'] ?? 'Reporte Personalizado',
                'fecha_generacion' => now()->format('d/m/Y H:i:s'),
                'usuario' => auth()->user()->name,
                'total_registros' => $datos->count(),
                'tiempo_ejecucion' => $tiempoEjecucion.'ms',
                'filtros_aplicados' => $configuracion['filtros'] ?? [],
                'sql_generado' => $query->toSql(),
            ];

            switch ($configuracion['formato']) {
                case 'pdf':
                    return $this->generarPDF($datos, $metadatos, $configuracion);
                case 'excel':
                    return $this->generarExcel($datos, $metadatos, $configuracion);
                case 'csv':
                    return $this->generarCSV($datos, $metadatos, $configuracion);
                case 'json':
                    return $this->generarJSON($datos, $metadatos, $configuracion);
                default:
                    throw new \Exception('Formato no soportado');
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function guardar(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'string|max:500',
                'configuracion' => 'required|string',
                'es_publico' => 'boolean',
                'categoria' => 'string|max:100',
            ]);

            // Decodificar la configuración JSON
            $configuracion = json_decode($validatedData['configuracion'], true);
            if (! $configuracion) {
                throw new \Exception('Configuración inválida');
            }

            $reporte = [
                'id' => uniqid(),
                'nombre' => $validatedData['nombre'],
                'descripcion' => $validatedData['descripcion'] ?? '',
                'configuracion' => $configuracion,
                'es_publico' => $validatedData['es_publico'] ?? false,
                'categoria' => $validatedData['categoria'] ?? 'General',
                'usuario_id' => auth()->id(),
                'usuario_nombre' => auth()->user()->name,
                'fecha_creacion' => now()->toISOString(),
                'fecha_modificacion' => now()->toISOString(),
                'veces_usado' => 0,
            ];

            $reportesGuardados = Cache::get('reportes_guardados', []);
            $reportesGuardados[$reporte['id']] = $reporte;
            Cache::put('reportes_guardados', $reportesGuardados, now()->addYear());

            return response()->json([
                'success' => true,
                'mensaje' => 'Reporte guardado correctamente',
                'reporte_id' => $reporte['id'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function eliminarReporte($id)
    {
        try {
            $reportesGuardados = Cache::get('reportes_guardados', []);

            if (! isset($reportesGuardados[$id])) {
                return response()->json(['error' => 'Reporte no encontrado'], 404);
            }

            // Verificar permisos
            if ($reportesGuardados[$id]['usuario_id'] !== auth()->id() && ! auth()->user()->hasRole('Admin')) {
                return response()->json(['error' => 'Sin permisos para eliminar este reporte'], 403);
            }

            unset($reportesGuardados[$id]);
            Cache::put('reportes_guardados', $reportesGuardados, now()->addYear());

            return response()->json([
                'success' => true,
                'mensaje' => 'Reporte eliminado correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function estadisticas()
    {
        $reportesGuardados = Cache::get('reportes_guardados', []);

        $estadisticas = [
            'total_reportes' => count($reportesGuardados),
            'reportes_publicos' => count(array_filter($reportesGuardados, fn ($r) => $r['es_publico'])),
            'reportes_usuario' => count(array_filter($reportesGuardados, fn ($r) => $r['usuario_id'] === auth()->id())),
            'por_categoria' => [],
            'mas_usados' => [],
            'recientes' => [],
        ];

        // Agrupar por categoría
        foreach ($reportesGuardados as $reporte) {
            $categoria = $reporte['categoria'] ?? 'General';
            $estadisticas['por_categoria'][$categoria] = ($estadisticas['por_categoria'][$categoria] ?? 0) + 1;
        }

        // Más usados
        $estadisticas['mas_usados'] = collect($reportesGuardados)
            ->sortByDesc('veces_usado')
            ->take(5)
            ->values()
            ->toArray();

        // Más recientes
        $estadisticas['recientes'] = collect($reportesGuardados)
            ->sortByDesc('fecha_modificacion')
            ->take(5)
            ->values()
            ->toArray();

        return response()->json($estadisticas);
    }

    private function construirQuery($configuracion)
    {
        $tablaPrincipal = $configuracion['tabla_principal'];
        $campos = $configuracion['campos'];
        $filtros = $configuracion['filtros'] ?? [];
        $agrupaciones = $configuracion['agrupaciones'] ?? [];
        $ordenamiento = $configuracion['ordenamiento'] ?? [];

        // Iniciar query builder
        $query = DB::table($tablaPrincipal);

        // Determinar JOINs necesarios
        $tablesUsadas = [$tablaPrincipal];
        $joinsNecesarios = $this->determinarJoins($campos, $tablaPrincipal);

        // Aplicar JOINs
        foreach ($joinsNecesarios as $tabla => $condicion) {
            $query->leftJoin($tabla, DB::raw($condicion));
            $tablesUsadas[] = $tabla;
        }

        // Seleccionar campos
        $camposSelect = [];
        foreach ($campos as $campo) {
            if (strpos($campo, '.') !== false) {
                $camposSelect[] = $campo;
            } else {
                $camposSelect[] = $tablaPrincipal.'.'.$campo;
            }
        }
        $query->select($camposSelect);

        // Aplicar filtros
        foreach ($filtros as $filtro) {
            $this->aplicarFiltro($query, $filtro);
        }

        // Aplicar agrupaciones
        if (! empty($agrupaciones)) {
            $query->groupBy($agrupaciones);
        }

        // Aplicar ordenamiento
        foreach ($ordenamiento as $orden) {
            $query->orderBy($orden['campo'], $orden['direccion'] ?? 'asc');
        }

        return $query;
    }

    private function determinarJoins($campos, $tablaPrincipal)
    {
        $joins = [];
        $configuracionTabla = $this->tablasDisponibles[$tablaPrincipal] ?? [];
        $joinsDisponibles = $configuracionTabla['joins'] ?? [];

        foreach ($campos as $campo) {
            if (strpos($campo, '.') !== false) {
                $tabla = explode('.', $campo)[0];
                if ($tabla !== $tablaPrincipal && isset($joinsDisponibles[$tabla])) {
                    $joins[$tabla] = $joinsDisponibles[$tabla];
                }
            }
        }

        return $joins;
    }

    private function aplicarFiltro($query, $filtro)
    {
        $campo = $filtro['campo'];
        $operador = $filtro['operador'];
        $valor = $filtro['valor'];

        switch ($operador) {
            case 'equals':
                $query->where($campo, '=', $valor);
                break;
            case 'not_equals':
                $query->where($campo, '!=', $valor);
                break;
            case 'contains':
                $query->where($campo, 'LIKE', '%'.$valor.'%');
                break;
            case 'starts_with':
                $query->where($campo, 'LIKE', $valor.'%');
                break;
            case 'ends_with':
                $query->where($campo, 'LIKE', '%'.$valor);
                break;
            case 'greater_than':
                $query->where($campo, '>', $valor);
                break;
            case 'less_than':
                $query->where($campo, '<', $valor);
                break;
            case 'between':
                if (is_array($valor) && count($valor) === 2) {
                    $query->whereBetween($campo, $valor);
                }
                break;
            case 'in':
                if (is_array($valor)) {
                    $query->whereIn($campo, $valor);
                }
                break;
            case 'not_null':
                $query->whereNotNull($campo);
                break;
            case 'null':
                $query->whereNull($campo);
                break;
            case 'date_equals':
                $query->whereDate($campo, $valor);
                break;
            case 'date_between':
                if (is_array($valor) && count($valor) === 2) {
                    $query->whereBetween(DB::raw('DATE('.$campo.')'), $valor);
                }
                break;
        }
    }

    private function obtenerCamposTabla($tabla)
    {
        try {
            $campos = Schema::getColumnListing($tabla);
            $camposConInfo = [];

            foreach ($campos as $campo) {
                $tipo = Schema::getColumnType($tabla, $campo);
                $camposConInfo[] = [
                    'nombre' => $campo,
                    'tabla' => $tabla,
                    'nombre_completo' => $tabla.'.'.$campo,
                    'tipo' => $tipo,
                    'label' => $this->generarLabelCampo($campo),
                    'es_fecha' => in_array($tipo, ['datetime', 'date', 'timestamp']),
                    'es_numerico' => in_array($tipo, ['integer', 'decimal', 'float', 'double']),
                    'es_texto' => in_array($tipo, ['string', 'text']),
                ];
            }

            return $camposConInfo;

        } catch (\Exception $e) {
            return [];
        }
    }

    private function obtenerCamposRelacionados($tablaPrincipal)
    {
        $camposRelacionados = [];
        $configuracion = $this->tablasDisponibles[$tablaPrincipal] ?? [];
        $joins = $configuracion['joins'] ?? [];

        foreach ($joins as $tabla => $condicion) {
            $campos = $this->obtenerCamposTabla($tabla);
            $camposRelacionados[$tabla] = [
                'label' => $this->tablasDisponibles[$tabla]['label'] ?? ucfirst($tabla),
                'campos' => $campos,
            ];
        }

        return $camposRelacionados;
    }

    private function obtenerEsquemaTablas()
    {
        $esquema = [];

        foreach ($this->tablasDisponibles as $tabla => $config) {
            $esquema[$tabla] = [
                'config' => $config,
                'campos' => $this->obtenerCamposTabla($tabla),
                'relaciones' => $config['joins'] ?? [],
            ];
        }

        return $esquema;
    }

    private function generarLabelCampo($campo)
    {
        // Mapeo de campos comunes a etiquetas amigables
        $labels = [
            'id' => 'ID',
            'nombre' => 'Nombre',
            'email' => 'Email',
            'telefono' => 'Teléfono',
            'direccion' => 'Dirección',
            'fecha_nacimiento' => 'Fecha de Nacimiento',
            'created_at' => 'Fecha de Creación',
            'updated_at' => 'Fecha de Actualización',
            'monto' => 'Monto',
            'monto_cuota' => 'Monto de Cuota',
            'fecha_vencimiento' => 'Fecha de Vencimiento',
            'estado' => 'Estado',
            'dni' => 'DNI',
            'tipo_documento' => 'Tipo de Documento',
            'numero_documento' => 'Número de Documento',
        ];

        return $labels[$campo] ?? ucwords(str_replace(['_', '-'], ' ', $campo));
    }

    private function generarPDF($datos, $metadatos, $configuracion)
    {
        $html = view('admin.constructor-reportes.pdf', [
            'datos' => $datos,
            'metadatos' => $metadatos,
            'configuracion' => $configuracion,
        ])->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');

        $nombreArchivo = 'reporte_'.date('Y-m-d_H-i-s').'.pdf';

        return $pdf->download($nombreArchivo);
    }

    private function generarExcel($datos, $metadatos, $configuracion)
    {
        // Implementar generación de Excel (requiere PhpSpreadsheet)
        return $this->generarCSV($datos, $metadatos, $configuracion);
    }

    private function generarCSV($datos, $metadatos, $configuracion)
    {
        $nombreArchivo = 'reporte_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$nombreArchivo}\"",
        ];

        $callback = function () use ($datos) {
            $handle = fopen('php://output', 'w');

            // Escribir BOM para UTF-8
            fwrite($handle, "\xEF\xBB\xBF");

            // Encabezados
            if ($datos->isNotEmpty()) {
                $primeraFila = $datos->first();
                if (is_object($primeraFila)) {
                    $primeraFila = (array) $primeraFila;
                }
                fputcsv($handle, array_keys($primeraFila));

                // Datos
                foreach ($datos as $fila) {
                    if (is_object($fila)) {
                        $fila = (array) $fila;
                    }
                    fputcsv($handle, array_values($fila));
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function generarJSON($datos, $metadatos, $configuracion)
    {
        $nombreArchivo = 'reporte_'.date('Y-m-d_H-i-s').'.json';

        $contenido = [
            'metadatos' => $metadatos,
            'configuracion' => $configuracion,
            'datos' => $datos,
        ];

        return response()->json($contenido)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$nombreArchivo}\"");
    }

    private function obtenerReportesGuardados()
    {
        $reportesGuardados = Cache::get('reportes_guardados', []);

        // Filtrar por permisos del usuario
        return collect($reportesGuardados)
            ->filter(function ($reporte) {
                return $reporte['es_publico'] ||
                       $reporte['usuario_id'] === auth()->id() ||
                       auth()->user()->hasRole('Admin');
            })
            ->sortByDesc('fecha_modificacion')
            ->values()
            ->toArray();
    }

    private function obtenerPlantillasPredefinidas()
    {
        return [
            [
                'id' => 'cartera-vencida',
                'nombre' => 'Cartera Vencida',
                'descripcion' => 'Reporte de préstamos con pagos vencidos',
                'categoria' => 'Cobranza',
                'icono' => 'fa-exclamation-triangle',
            ],
            [
                'id' => 'clientes-activos',
                'nombre' => 'Clientes Activos',
                'descripcion' => 'Lista de clientes con préstamos vigentes',
                'categoria' => 'Clientes',
                'icono' => 'fa-users',
            ],
            [
                'id' => 'resumen-operaciones',
                'nombre' => 'Resumen de Operaciones',
                'descripcion' => 'Operaciones financieras por período',
                'categoria' => 'Financiero',
                'icono' => 'fa-chart-bar',
            ],
            [
                'id' => 'productividad-asesores',
                'nombre' => 'Productividad Asesores',
                'descripcion' => 'Rendimiento de asesores comerciales',
                'categoria' => 'Gestión',
                'icono' => 'fa-user-tie',
            ],
        ];
    }

    private function cargarReporte($id)
    {
        $reportesGuardados = Cache::get('reportes_guardados', []);

        if (isset($reportesGuardados[$id])) {
            // Incrementar contador de uso
            $reportesGuardados[$id]['veces_usado'] = ($reportesGuardados[$id]['veces_usado'] ?? 0) + 1;
            $reportesGuardados[$id]['ultimo_uso'] = now()->toISOString();
            Cache::put('reportes_guardados', $reportesGuardados, now()->addYear());

            return $reportesGuardados[$id];
        }

        return null;
    }
}
