<?php

namespace App\Livewire\Prestamos;

use App\Enums\ConvenioEstado;
use App\Enums\CuotaEstado;
use App\Models\Cuota;
use App\Models\MoraCuota;
use App\Models\Operacion;
use App\Models\Prestamo;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Zona;
use App\Services\MoraService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ShowPrestamos extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    protected $listeners = ['cambiarEstadoPrestamo'];

    public $search = '';

    // Asegurar que las propiedades se mantengan entre renders
    protected $queryString = [
        'search' => ['except' => ''],
        'sort' => ['except' => 'prestamos.id'],
        'direction' => ['except' => 'desc'],
        'zona_id' => ['except' => ''],
        'sucursal_id' => ['except' => ''],
        'perPage' => ['except' => 25],
        'soloConFacturacion' => ['except' => false],
        'fecha_pago' => ['except' => ''],
    ];

    public $sort = 'prestamos.id';

    public $direction = 'desc';

    public $selectedOption = '';

    public $usuarios;

    public $zonas;

    public $sucursales;

    public $zona_id = '';

    public $sucursal_id = '';

    public $dia_pago = '';

    public $perPage = 25;

    // Filtro por fecha de pago
    public $fecha_pago = '';

    // Nueva propiedad para controlar si mostrar liquidados/finalizados
    public $mostrarTodos = false;

    // Filtro para préstamos con facturación
    public $soloConFacturacion = false;

    // Visibilidad de columnas
    public $mostrarDni = true;
    public $mostrarSucursal = true;
    public $mostrarTipoSolicitud = true;
    public $mostrarEstado = true;
    public $mostrarEtiquetas = true;
    public $mostrarFechaCreacion = true;
    public $mostrarFechaDesembolso = true;
    public $mostrarFechaFinalizacion = true;

    public function mount()
    {
        $this->usuarios = User::all();
        $this->zonas = Zona::orderBy('nombre')->get();
        $this->cargarSucursales();

        // Cargar preferencias de columnas desde la sesión
        $this->mostrarDni = session('mostrarDni', true);
        $this->mostrarSucursal = session('mostrarSucursal', true);
        $this->mostrarTipoSolicitud = session('mostrarTipoSolicitud', true);
        $this->mostrarEstado = session('mostrarEstado', true);
        $this->mostrarEtiquetas = session('mostrarEtiquetas', true);
        $this->mostrarFechaCreacion = session('mostrarFechaCreacion', true);
        $this->mostrarFechaDesembolso = session('mostrarFechaDesembolso', true);
        $this->mostrarFechaFinalizacion = session('mostrarFechaFinalizacion', true);
    }

    /**
     * Guardar preferencias de columnas en la sesión
     */
    public function guardarPreferenciasColumnas()
    {
        session([
            'mostrarDni' => $this->mostrarDni,
            'mostrarSucursal' => $this->mostrarSucursal,
            'mostrarTipoSolicitud' => $this->mostrarTipoSolicitud,
            'mostrarEstado' => $this->mostrarEstado,
            'mostrarEtiquetas' => $this->mostrarEtiquetas,
            'mostrarFechaCreacion' => $this->mostrarFechaCreacion,
            'mostrarFechaDesembolso' => $this->mostrarFechaDesembolso,
            'mostrarFechaFinalizacion' => $this->mostrarFechaFinalizacion,
        ]);

        $this->dispatch('preferenciasGuardadas', [
            'icon' => 'success',
            'title' => 'Preferencias Guardadas',
            'text' => 'Tus preferencias de columnas se han guardado correctamente.',
        ]);
    }

    /**
     * Búsqueda de clientes para autocompletado
     */
    public function buscarClientes($termino)
    {
        if (strlen($termino) < 2) {
            return [];
        }

        return \App\Models\Cliente::select('clientes.id', 'personas.nombres', 'personas.ape_pat', 'personas.ape_mat', 'personas.documento')
            ->join('personas', 'clientes.persona_id', '=', 'personas.id')
            ->where(function ($query) use ($termino) {
                $terminos = explode(' ', trim($termino));
                foreach ($terminos as $t) {
                    $t = trim($t);
                    if (!empty($t)) {
                        $query->where(function ($subQuery) use ($t) {
                            $subQuery->where('personas.nombres', 'like', '%' . $t . '%')
                                ->orWhere('personas.ape_pat', 'like', '%' . $t . '%')
                                ->orWhere('personas.ape_mat', 'like', '%' . $t . '%')
                                ->orWhere('personas.documento', 'like', '%' . $t . '%');
                        });
                    }
                }
            })
            ->limit(10)
            ->get()
            ->map(function ($cliente) {
                return [
                    'id' => $cliente->id,
                    'nombre_completo' => $cliente->nombres.' '.$cliente->ape_pat.' '.$cliente->ape_mat,
                    'documento' => $cliente->documento,
                    'label' => $cliente->nombres.' '.$cliente->ape_pat.' '.$cliente->ape_mat.' - DNI: '.$cliente->documento,
                ];
            });
    }

    public function updateSearch($value)
    {
        $this->search = $value;
        $this->selectedOption = $value;
        $this->resetPage();
    }

    /**
     * Filtrar préstamos por cliente seleccionado
     */
    public function filtrarPorCliente($clienteId, $nombreCompleto)
    {
        $this->search = $nombreCompleto;
        $this->cliente_seleccionado = $clienteId;
        $this->resetPage();
        // Limpiar caché para forzar recálculo
        \Cache::forget('prestamos_contadores');
    }

    // Nueva propiedad para cliente seleccionado
    public $cliente_seleccionado = null;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedSucursalId()
    {
        $this->resetPage();
    }

    public function updatedDiaPago()
    {
        $this->resetPage();
    }

    public function updatedFechaPago()
    {
        $this->resetPage();
    }

    public function updatedZonaId()
    {
        // Cuando cambia la zona, resetear sucursal y recargar sucursales filtradas
        $this->sucursal_id = '';
        $this->cargarSucursales();
        $this->resetPage();
    }

    public function cargarSucursales()
    {
        if ($this->zona_id) {
            // Cargar solo sucursales de la zona seleccionada
            $zona = Zona::find($this->zona_id);
            $this->sucursales = $zona ? $zona->sucursales()->orderBy('sucursal')->get() : collect();
        } else {
            // Cargar todas las sucursales
            $this->sucursales = Sucursal::orderBy('sucursal')->get();
        }
    }

    public function toggleMostrarTodos()
    {
        $this->mostrarTodos = ! $this->mostrarTodos;
        $this->resetPage();
    }

    public function toggleSoloConFacturacion()
    {
        $this->soloConFacturacion = ! $this->soloConFacturacion;
        $this->resetPage();
        // Limpiar caché para forzar recálculo
        \Cache::forget('prestamos_contadores');
    }

    /**
     * Eliminar préstamo completamente junto con cuotas y moras
     */
    public function eliminarPrestamo($prestamoId)
    {
        try {
            DB::beginTransaction();

            $prestamo = Prestamo::find($prestamoId);
            if (! $prestamo) {
                $this->dispatch('estadoPrestamoCambiado', [
                    'icon' => 'error',
                    'title' => 'Error',
                    'text' => 'Préstamo no encontrado.',
                ]);

                return;
            }

            // Eliminar moras de las cuotas y abonos a favor
            foreach ($prestamo->cuotas as $cuota) {
                MoraCuota::where('cuota_id', $cuota->id)->delete();
                // Eliminar abonos a favor asociados a esta cuota
                \App\Models\AbonoMoraFavor::where('cuota_id', $cuota->id)->delete();
            }

            // Eliminar cuotas
            Cuota::where('prestamo_id', $prestamoId)->delete();

            // Eliminar operaciones relacionadas
            Operacion::where('prestamo_id', $prestamoId)->delete();

            // Eliminar el préstamo
            $prestamo->delete();

            DB::commit();

            $this->dispatch('estadoPrestamoCambiado', [
                'icon' => 'success',
                'title' => 'Préstamo Eliminado',
                'text' => 'El préstamo y todos sus datos relacionados han sido eliminados completamente.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al eliminar préstamo: '.$e->getMessage());

            $this->dispatch('estadoPrestamoCambiado', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se pudo eliminar el préstamo: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Rechazar préstamo con motivo
     */
    public function rechazarPrestamo($prestamoId, $motivo)
    {
        try {
            $prestamo = Prestamo::find($prestamoId);

            if (!$prestamo) {
                $this->dispatch('prestamoRechazado', [
                    'icon' => 'error',
                    'title' => 'Error',
                    'text' => 'Préstamo no encontrado.',
                ]);
                return;
            }

            if ($prestamo->estado !== 'Nueva Solicitud') {
                $this->dispatch('prestamoRechazado', [
                    'icon' => 'error',
                    'title' => 'Error',
                    'text' => 'Solo se pueden rechazar préstamos en estado "Nueva Solicitud".',
                ]);
                return;
            }

            $prestamo->update([
                'estado' => 'Rechazado',
                'observaciones' => $motivo,
            ]);

            $this->dispatch('prestamoRechazado', [
                'icon' => 'success',
                'title' => 'Préstamo Rechazado',
                'text' => 'El préstamo ha sido rechazado exitosamente.',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al rechazar préstamo: '.$e->getMessage());

            $this->dispatch('prestamoRechazado', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se pudo rechazar el préstamo: '.$e->getMessage(),
            ]);
        }
    }

    // Métodos de modal removidos - ahora se usan páginas dedicadas

    public function render()
    {
        // Calcular contadores con caché para mejorar rendimiento
        $contadoresReales = $this->calcularContadoresConCache();

        $cant_todos = $contadoresReales['todos'];
        $cant_nueva_solicitud = $contadoresReales['nueva_solicitud'];
        $cant_aprobado = $contadoresReales['aprobado'];
        $cant_por_desembolsar = $contadoresReales['por_desembolsar'];
        $cant_vigente = $contadoresReales['vigente'];
        $cant_moroso = $contadoresReales['moroso'];
        $cant_con_convenio = $contadoresReales['con_convenio'];
        $cant_liquidado = $contadoresReales['liquidado'];
        $cant_liquidado_sin_prestamo_activo = $contadoresReales['liquidado_sin_prestamo_activo'];
        $cant_rechazado = $contadoresReales['rechazado'];
        $cant_cancelado = $contadoresReales['cancelado'];
        $cant_finalizado = $contadoresReales['finalizado'];

        $query = Prestamo::query()
            ->leftJoin('clientes', 'prestamos.cliente_id', '=', 'clientes.id')
            ->leftJoin('personas', 'clientes.persona_id', '=', 'personas.id')
            ->select('prestamos.*')
            ->with(['cliente.persona.direcciones.sucursal', 'cuotas.moras_pendientes', 'convenios'])
            // FILTRO POR CARTERA: Solo para roles Asesor, Analista y JCC
            ->when($this->debeAplicarFiltroCartera(), function ($queryBuilder) {
                $userId = auth()->id();

                // IMPORTANTE: Si el usuario tiene múltiples roles, buscar en TODAS sus carteras
                // Usamos OR para que muestre préstamos de cualquier cartera donde esté asignado
                $queryBuilder->where(function ($subQuery) use ($userId) {
                    // Verificar cada rol y agregar condición OR para cada cartera
                    $hasConditions = false;

                    if (auth()->user()->hasRole('Asesor')) {
                        $subQuery->orWhereHas('carterasAsesor', function ($q) use ($userId) {
                            $q->where('asesor_id', $userId)->where('estado', 1);
                        });
                        $hasConditions = true;
                    }

                    if (auth()->user()->hasRole('Analista')) {
                        $subQuery->orWhereHas('carterasAnalista', function ($q) use ($userId) {
                            $q->where('analista_id', $userId)->where('estado', 1);
                        });
                        $hasConditions = true;
                    }

                    if (auth()->user()->hasRole('JCC')) {
                        $subQuery->orWhereHas('carterasJcc', function ($q) use ($userId) {
                            $q->where('jcc_id', $userId)->where('estado', 1);
                        });
                        $hasConditions = true;
                    }

                    // Si no tiene ningún rol restringido, no aplicar filtro
                    if (!$hasConditions) {
                        $subQuery->whereRaw('1=1');
                    }
                });
            });

        // Detectar si es búsqueda por texto (nombre, DNI, etc.) vs filtro por estado
        $esBusquedaPorTexto = ! empty($this->search) &&
                              ! in_array($this->search, ['Finalizado', 'Liquidado', 'Liquidado Sin Préstamo Activo', 'Nueva Solicitud', 'Aprobado', 'Vigente', 'Moroso', 'Rechazado', 'Cancelado', 'Con Convenio', 'Por Desembolsar']);

        // IMPORTANTE: Para usuarios con filtro de cartera (Asesor, Analista, JCC):
        // DEBEN ver TODOS los estados (incluidos Liquidados, Cancelados, Con Convenio) para hacer renovaciones
        // Solo filtramos liquidados/finalizados para roles sin restricción (Admin, Oficina, GS)
        // NO aplicar filtro si se está filtrando por día de pago (ya tiene su propia lógica de exclusión)
        if (!$this->debeAplicarFiltroCartera() && ! $this->mostrarTodos && ! $esBusquedaPorTexto && ! $this->dia_pago && ! in_array($this->search, ['Liquidado', 'Liquidado Sin Préstamo Activo', 'Finalizado', 'Cancelado', ''])) {
            $query->whereNotIn('prestamos.estado', ['Liquidado', 'Finalizado']);
        }

        // Filtro por cliente específico (tiene prioridad)
        if ($this->cliente_seleccionado) {
            $query->where('prestamos.cliente_id', $this->cliente_seleccionado);
        }
        // Filtros especiales
        elseif ($this->search === 'Liquidado Sin Préstamo Activo') {
            // Préstamos LIQUIDADOS cuyo cliente NO tiene OTROS préstamos activos
            $query->where('prestamos.estado', 'Liquidado')
                ->whereNotExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('prestamos as p2')
                        ->whereColumn('p2.cliente_id', 'prestamos.cliente_id')
                        ->whereColumn('p2.id', '!=', 'prestamos.id')
                        ->whereIn('p2.estado', ['Vigente', 'Moroso', 'Con Convenio', 'Por Desembolsar', 'Nueva Solicitud']);
                });
        } elseif ($this->search === 'Con Convenio') {
            $query->whereHas('convenios', function ($subquery) {
                $subquery->where('estado', ConvenioEstado::ACTIVO);
            });
        } elseif (in_array($this->search, ['Finalizado', 'Liquidado', 'Nueva Solicitud', 'Aprobado', 'Vigente', 'Moroso', 'Rechazado', 'Cancelado', 'Por Desembolsar'])) {
            // Filtros de estado exactos
            $query->where('prestamos.estado', '=', $this->search);
        } elseif ($esBusquedaPorTexto) {
            // Busca por cada palabra individualmente (en nombres, apellidos, dni, etc.)
            // Esto permite encontrar coincidencias parciales incluso si el nombre no está en orden exacto
            $query->where(function ($mainQuery) {
                $terminos = explode(' ', trim($this->search));
                foreach ($terminos as $termino) {
                    $termino = trim($termino);
                    if (!empty($termino)) {
                        $mainQuery->where(function ($subQuery) use ($termino) {
                            $subQuery->where('personas.nombres', 'like', '%' . $termino . '%')
                                ->orWhere('personas.ape_pat', 'like', '%' . $termino . '%')
                                ->orWhere('personas.ape_mat', 'like', '%' . $termino . '%')
                                ->orWhere('personas.documento', 'like', '%' . $termino . '%')
                                ->orWhere('prestamos.cantidad_solicitada', 'like', '%' . $termino . '%');
                        });
                    }
                }
            });
        }

        // Filtro por zona
        if ($this->zona_id) {
            $query->whereHas('cliente.persona.direcciones.sucursal.zonas', function ($subquery) {
                $subquery->where('zonas.id', $this->zona_id);
            });
        }

        // Filtro por sucursal
        if ($this->sucursal_id) {
            $query->whereHas('cliente.persona.direcciones', function ($subquery) {
                $subquery->where('sucursal_id', $this->sucursal_id);
            });
        }

        // Filtro por día de pago (solo préstamos activos: NO liquidados ni finalizados)
        if ($this->dia_pago) {
            // DAYOFWEEK en MySQL: 1=Domingo, 2=Lunes, 3=Martes, 4=Miércoles, 5=Jueves, 6=Viernes, 7=Sábado
            $diasSemana = [
                'Domingo' => 1,
                'Lunes' => 2,
                'Martes' => 3,
                'Miércoles' => 4,
                'Jueves' => 5,
                'Viernes' => 6,
                'Sábado' => 7
            ];

            $numeroDia = $diasSemana[$this->dia_pago] ?? null;

            if ($numeroDia !== null) {
                $query->whereNotIn('prestamos.estado', ['Liquidado', 'Finalizado'])
                    ->whereNotNull('prestamos.fecha_primer_pago')
                    ->whereRaw('DAYOFWEEK(prestamos.fecha_primer_pago) = ?', [$numeroDia]);
            }
        }

        // Filtro para préstamos con facturación
        if ($this->soloConFacturacion) {
            $query->where('prestamos.tiene_comprobante', 1);
        }

        // Filtro por fecha de pago (día de pago)
        if ($this->fecha_pago) {
            $query->whereDate('prestamos.fecha_primer_pago', $this->fecha_pago);
        }

        $prestamos = $query->orderBy($this->getSortField(), $this->direction)->paginate($this->perPage);

        return view('livewire.prestamos.show-prestamos', compact(
            'cant_todos',
            'prestamos',
            'cant_nueva_solicitud',
            'cant_aprobado',
            'cant_por_desembolsar',
            'cant_vigente',
            'cant_moroso',
            'cant_con_convenio',
            'cant_liquidado',
            'cant_liquidado_sin_prestamo_activo',
            'cant_rechazado',
            'cant_cancelado',
            'cant_finalizado'
        ));
    }

    /**
     * Calcular contadores con caché para mejorar rendimiento
     */
    protected function calcularContadoresConCache()
    {
        $contadores = \Cache::remember('prestamos_contadores', 300, function () { // 5 minutos de caché
            return $this->calcularContadoresReales();
        });

        // Asegurar que todas las claves esperadas existan
        $contadoresDefault = [
            'todos' => 0,
            'nueva_solicitud' => 0,
            'aprobado' => 0,
            'por_desembolsar' => 0,
            'vigente' => 0,
            'moroso' => 0,
            'con_convenio' => 0,
            'liquidado' => 0,
            'liquidado_sin_prestamo_activo' => 0,
            'rechazado' => 0,
            'cancelado' => 0,
            'finalizado' => 0,
        ];

        return array_merge($contadoresDefault, $contadores ?? []);
    }

    protected function getSortField()
    {
        switch ($this->sort) {
            case 'id':
            case 'prestamos.id':
                return 'prestamos.id';
            case 'cliente.persona.nombres':
                return 'personas.nombres';
            case 'cliente.persona.documento':
                return 'personas.documento';
            case 'cantidad_solicitada':
                return 'prestamos.cantidad_solicitada';
            case 'estado':
                return 'prestamos.estado';
            case 'fecha_primer_pago':
                return 'prestamos.fecha_primer_pago';
            case 'created_at':
                return 'prestamos.created_at';
            default:
                return 'prestamos.id';
        }
    }

    public function order($sort)
    {
        if ($this->sort === $sort) {
            $this->direction = $this->direction === 'desc' ? 'asc' : 'desc';
        } else {
            $this->sort = $sort;
            $this->direction = 'asc';
        }
    }

    public function cambiarEstadoPrestamo($prestamoId, $accion)
    {
        $prestamo = Prestamo::find($prestamoId);
        if (! $prestamo) {
            $this->dispatch('estadoPrestamoCambiado', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'Préstamo no encontrado.',
            ]);

            return;
        }

        switch ($accion) {
            case 'aprobar':
                $prestamo->estado = 'Por Desembolsar';
                $prestamo->save();
                $this->dispatch('estadoPrestamoCambiado', [
                    'icon' => 'success',
                    'title' => 'Estado Actualizado',
                    'text' => 'El préstamo ha sido aprobado.',
                ]);
                break;
            case 'anular':
                $prestamo->estado = 'Cancelado';
                $prestamo->save();
                $this->dispatch('estadoPrestamoCambiado', [
                    'icon' => 'success',
                    'title' => 'Estado Actualizado',
                    'text' => 'El préstamo ha sido anulado.',
                ]);
                break;
            case 'liquidar':
                $prestamo->estado = 'Liquidado';
                $prestamo->save();
                $this->dispatch('estadoPrestamoCambiado', [
                    'icon' => 'success',
                    'title' => 'Estado Actualizado',
                    'text' => 'El préstamo ha sido liquidado.',
                ]);
                break;
            case 'desembolsar':
                $this->mostrarModalDesembolso($prestamoId);
                break;
        }
    }

    public function mostrarModalDesembolso($prestamoId)
    {
        $prestamo = Prestamo::find($prestamoId);
        if ($prestamo && $prestamo->estado === 'Por Desembolsar') {
            $this->dispatch('mostrarModalDesembolso', $prestamoId);
        } else {
            $this->dispatch('estadoPrestamoCambiado', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'El préstamo no está en estado "Por Desembolsar".',
            ]);
        }
    }

    #[On('desembolsarPrestamo')]
    public function desembolsarPrestamo($prestamoId)
    {
        // Verificar si se recibió como objeto o como valor directo
        if (isset($prestamoId['prestamoId'])) {
            // Cuando se envía desde el formulario, viene como un objeto
            $id = $prestamoId['prestamoId'];
        } else {
            // Cuando se envía directamente, es el ID mismo
            $id = $prestamoId;
        }

        // Buscar el préstamo con el ID determinado
        $prestamo = Prestamo::find($id);

        // Verificar si el préstamo existe y está en el estado correcto
        if ($prestamo && $prestamo->estado === 'Por Desembolsar') {
            try {
                $prestamo->estado = 'Vigente';
                $prestamo->save();
                $this->dispatch('prestamoDesembolsado', [
                    'icon' => 'success',
                    'title' => 'Préstamo Desembolsado',
                    'text' => 'El préstamo ha sido desembolsado exitosamente.',
                ]);
            } catch (\Exception $e) {
                // Registrar el error pero no enviar notificación al frontend
                \Log::error('Error al desembolsar préstamo: '.$e->getMessage());
            }
        } else {
            // Registrar el problema pero no enviar notificación al frontend
            \Log::info('No se pudo desembolsar: préstamo no encontrado o no está en estado correcto');
        }
    }

    /**
     * Generar moras masivas para cuotas vencidas
     */
    public function generarMorasMasivas()
    {
        try {
            $moraService = new MoraService;
            $resultados = $moraService->generarMorasMasivas();

            $mensaje = "Proceso completado:\n";
            $mensaje .= "• Cuotas procesadas: {$resultados['procesadas']}\n";
            $mensaje .= "• Moras generadas: {$resultados['generadas']}\n";
            $mensaje .= "• Cuotas omitidas: {$resultados['omitidas']}";

            if ($resultados['errores'] > 0) {
                $mensaje .= "\n• Errores: {$resultados['errores']}";
            }

            $this->dispatch('morasMasivasGeneradas', [
                'icon' => $resultados['errores'] > 0 ? 'warning' : 'success',
                'title' => 'Generación de Moras Completada',
                'text' => $mensaje,
                'resultados' => $resultados,
            ]);

            // Refrescar la página para mostrar cambios
            $this->resetPage();

        } catch (\Exception $e) {
            \Log::error('Error en generación masiva de moras: '.$e->getMessage());

            $this->dispatch('morasMasivasGeneradas', [
                'icon' => 'error',
                'title' => 'Error en Generación de Moras',
                'text' => 'Hubo un problema al generar las moras: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Regularizar moras basándose en fechas reales de pago
     * Corrige moras que no deberían existir según las fechas de pago
     */
    public function regularizarMorasPorFechaPago()
    {
        try {
            $moraService = new MoraService;
            $resultados = $moraService->regularizarMorasPorFechaPago();

            $mensaje = "Regularización completada:\n";
            $mensaje .= "• Cuotas procesadas: {$resultados['cuotas_procesadas']}\n";
            $mensaje .= "• Moras regularizadas (pago a tiempo): {$resultados['moras_regularizadas']}\n";
            $mensaje .= "• Moras ajustadas (pago tardío): {$resultados['moras_ajustadas']}";

            if ($resultados['errores'] > 0) {
                $mensaje .= "\n• Errores: {$resultados['errores']}";
            }

            // Mostrar detalles adicionales si hay cambios
            if ($resultados['moras_regularizadas'] > 0 || $resultados['moras_ajustadas'] > 0) {
                $mensaje .= "\n\n📋 Detalles de cambios importantes disponibles en logs.";
            }

            $this->dispatch('morasRegularizadas', [
                'icon' => $resultados['errores'] > 0 ? 'warning' : 'success',
                'title' => 'Regularización de Moras Completada',
                'text' => $mensaje,
                'resultados' => $resultados,
            ]);

            // Refrescar la página para mostrar cambios
            $this->resetPage();

        } catch (\Exception $e) {
            \Log::error('Error en regularización de moras: '.$e->getMessage());

            $this->dispatch('morasRegularizadas', [
                'icon' => 'error',
                'title' => 'Error en Regularización de Moras',
                'text' => 'Hubo un problema al regularizar las moras: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Regularizar estados de préstamos basándose en el estado de cuotas y fechas
     * Actualiza todos los estados de préstamos para reflejar la situación actual
     */
    public function regularizarEstadosPrestamos()
    {
        try {
            // Ejecutar el comando de actualización de estados
            $exitCode = \Artisan::call('prestamos:actualizar-estados', ['--all' => true]);

            if ($exitCode === 0) {
                // Obtener la salida del comando para mostrar estadísticas
                $output = \Artisan::output();

                // Extraer información relevante del output
                preg_match('/(\d+) préstamos actualizados de (\d+) procesados/', $output, $matches);

                $mensaje = "Regularización de estados completada:\n";
                if (isset($matches[1]) && isset($matches[2])) {
                    $mensaje .= "• Préstamos procesados: {$matches[2]}\n";
                    $mensaje .= "• Estados actualizados: {$matches[1]}";
                } else {
                    $mensaje .= '• Todos los estados de préstamos han sido verificados y actualizados';
                }

                $this->dispatch('estadosRegularizados', [
                    'icon' => 'success',
                    'title' => 'Regularización de Estados Completada',
                    'text' => $mensaje,
                ]);

            } else {
                throw new \Exception('El comando de actualización falló con código: '.$exitCode);
            }

            // Refrescar la página para mostrar los cambios
            $this->resetPage();

        } catch (\Exception $e) {
            \Log::error('Error en regularización de estados de préstamos: '.$e->getMessage());

            $this->dispatch('estadosRegularizados', [
                'icon' => 'error',
                'title' => 'Error en Regularización de Estados',
                'text' => 'Hubo un problema al regularizar los estados: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Recalcular cuotas de todos los préstamos usando el nuevo método de cálculo
     * Actualiza interés, comisión e IGV según el método correcto
     */
    public function recalcularCuotasPrestamos()
    {
        try {
            // Ejecutar el comando de recálculo de cuotas
            $exitCode = \Artisan::call('prestamos:recalcular-cuotas', ['--all' => true]);

            if ($exitCode === 0) {
                // Obtener la salida del comando para mostrar estadísticas
                $output = \Artisan::output();

                // Extraer información relevante del output
                preg_match('/(\d+) préstamos recalculados de (\d+) procesados/', $output, $matches);

                $mensaje = "Recálculo de cuotas completado:\n";
                if (isset($matches[1]) && isset($matches[2])) {
                    $mensaje .= "• Préstamos procesados: {$matches[2]}\n";
                    $mensaje .= "• Cuotas recalculadas: {$matches[1]}";
                } else {
                    $mensaje .= '• Todas las cuotas han sido recalculadas con el nuevo método';
                }

                $this->dispatch('cuotasRecalculadas', [
                    'icon' => 'success',
                    'title' => 'Recálculo de Cuotas Completado',
                    'text' => $mensaje,
                ]);

            } else {
                throw new \Exception('El comando de recálculo falló con código: '.$exitCode);
            }

            // Refrescar la página para mostrar los cambios
            $this->resetPage();

        } catch (\Exception $e) {
            \Log::error('Error en recálculo de cuotas de préstamos: '.$e->getMessage());

            $this->dispatch('cuotasRecalculadas', [
                'icon' => 'error',
                'title' => 'Error en Recálculo de Cuotas',
                'text' => 'Hubo un problema al recalcular las cuotas: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Calcula los contadores reales de estados basándose en las condiciones actuales
     */
    private function calcularContadoresReales()
    {
        $prestamos = Prestamo::with(['cuotas.moras_pendientes', 'convenios', 'cliente.prestamos'])->get();

        $contadores = [
            'todos' => 0,
            'nueva_solicitud' => 0,
            'aprobado' => 0,
            'por_desembolsar' => 0,
            'vigente' => 0,
            'moroso' => 0,
            'con_convenio' => 0,
            'liquidado' => 0,
            'liquidado_sin_prestamo_activo' => 0,
            'rechazado' => 0,
            'cancelado' => 0,
            'finalizado' => 0,
        ];

        foreach ($prestamos as $prestamo) {
            $estadoReal = $prestamo->estado; // Ya está actualizado por actualizarTodosLosEstados()

            $contadores['todos']++;

            switch ($estadoReal) {
                case 'Nueva Solicitud':
                    $contadores['nueva_solicitud']++;
                    break;
                case 'Aprobado':
                    $contadores['aprobado']++;
                    break;
                case 'Por Desembolsar':
                    $contadores['por_desembolsar']++;
                    break;
                case 'Vigente':
                    $contadores['vigente']++;
                    break;
                case 'Moroso':
                    $contadores['moroso']++;
                    break;
                case 'Con Convenio':
                    $contadores['con_convenio']++;
                    break;
                case 'Liquidado':
                    $contadores['liquidado']++;
                    break;
                case 'Rechazado':
                    $contadores['rechazado']++;
                    break;
                case 'Cancelado':
                    $contadores['cancelado']++;
                    break;
                case 'Finalizado':
                    $contadores['finalizado']++;
                    break;
            }

            // Contar "Liquidado Sin Préstamo Activo"
            if ($estadoReal === 'Liquidado' && $prestamo->cliente) {
                // Verificar si el cliente NO tiene OTROS préstamos activos (excluyendo el actual)
                $tieneOtroPrestamoActivo = $prestamo->cliente->prestamos
                    ->where('id', '!=', $prestamo->id)
                    ->whereIn('estado', ['Vigente', 'Moroso', 'Con Convenio', 'Por Desembolsar', 'Nueva Solicitud'])
                    ->count() > 0;

                if (!$tieneOtroPrestamoActivo) {
                    $contadores['liquidado_sin_prestamo_activo']++;
                }
            }
        }

        return $contadores;
    }

    /**
     * MÉTODO DEPRECADO: Usar EstadoPrestamoController en su lugar
     * 
     * @deprecated Usar EstadoPrestamoController::calcularYActualizarEstado()
     */
    private function calcularEstadoReal($prestamo)
    {
        // Estados finales que no cambian
        if (in_array($prestamo->estado, ['Cancelado', 'Finalizado'])) {
            return $prestamo->estado;
        }

        // Estados administrativos que se mantienen
        if (in_array($prestamo->estado, ['Nueva Solicitud', 'Por Desembolsar'])) {
            return $prestamo->estado;
        }

        // Verificar convenio activo primero (prioridad alta)
        $tieneConvenioActivo = $prestamo->convenios &&
            $prestamo->convenios->where('estado', ConvenioEstado::ACTIVO)->count() > 0;

        if ($tieneConvenioActivo) {
            return 'Con Convenio';
        }

        // Verificar si todas las cuotas están completamente pagadas
        $totalCuotas = $prestamo->cuotas->count();
        $cuotasPagadas = $prestamo->cuotas->where('estado', CuotaEstado::PAGADO)->count();

        // IMPORTANTE: Solo marcar como Liquidado si NO HAY MORAS PENDIENTES
        $todasCuotasPagadas = ($totalCuotas > 0 && $cuotasPagadas == $totalCuotas);

        // Verificar cuotas vencidas (fecha_pago < hoy) y no pagadas
        $hoy = Carbon::today();
        $cuotasVencidas = $prestamo->cuotas->filter(function ($cuota) use ($hoy) {
            return in_array($cuota->estado, [CuotaEstado::PENDIENTE, CuotaEstado::PARCIAL, CuotaEstado::VENCIDO]) &&
                   Carbon::parse($cuota->fecha_pago)->lt($hoy);
        });

        // Si hay cuotas vencidas O moras pendientes, es Moroso
        if ($cuotasVencidas->count() > 0) {
            return 'Moroso';
        }

        // Verificar si tiene moras pendientes (aunque la cuota no esté vencida aún)
        $tieneMorasPendientes = false;
        foreach ($prestamo->cuotas as $cuota) {
            if ($cuota->moras_pendientes && $cuota->moras_pendientes->count() > 0) {
                $tieneMorasPendientes = true;
                break;
            }
        }

        if ($tieneMorasPendientes) {
            return 'Moroso';
        }

        // Solo aquí podemos marcar como Liquidado si todas las cuotas están pagadas Y no hay moras
        if ($todasCuotasPagadas) {
            return 'Liquidado';
        }

        // Si tiene cuotas pendientes pero ninguna vencida ni con moras
        $cuotasPendientes = $prestamo->cuotas->whereIn('estado', [
            CuotaEstado::PENDIENTE,
            CuotaEstado::PARCIAL,
        ])->count();

        if ($cuotasPendientes > 0) {
            return 'Vigente';
        }

        // Por defecto, mantener el estado actual
        return $prestamo->estado;
    }

    /**
     * Actualiza todos los estados de préstamos en la base de datos
     * Usa el controlador centralizado para evitar duplicación
     */
    private function actualizarTodosLosEstados()
    {
        // Solo actualizar si es necesario, limitar la carga
        $prestamos = Prestamo::with(['cuotas.moras_pendientes', 'convenios'])
            ->whereNotIn('estado', ['Cancelado', 'Finalizado']) // Excluir estados finales
            ->get();

        $estadoController = new \App\Http\Controllers\Admin\EstadoPrestamoController();

        foreach ($prestamos as $prestamo) {
            // Usar el controlador centralizado
            $estadoController->calcularYActualizarEstado(
                $prestamo,
                true, // Sí actualizar en BD
                'livewire_batch' // Origen: actualización masiva desde Livewire
            );
        }
    }

    /**
     * Determina si se debe aplicar filtro de cartera según el rol del usuario
     * DESHABILITADO: Todos los roles pueden ver todos los préstamos
     */
    private function debeAplicarFiltroCartera(): bool
    {
        // Filtro deshabilitado - todos los roles pueden ver todos los préstamos
        return false;
    }
}
