<?php

namespace App\Exports;

use App\Enums\MoraCuotaEstado;
use App\Models\Sucursal;
use App\Models\Zona;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TramosExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected $cuotasAgrupadas;

    protected $totales;

    protected $tramoFiltro;

    protected $exportRows = [];

    protected $filtros = [];

    public function __construct($cuotasAgrupadas, $totales, $tramoFiltro = null, ?Request $request = null)
    {
        $this->cuotasAgrupadas = $cuotasAgrupadas;
        $this->totales = $totales;
        $this->tramoFiltro = $tramoFiltro;
        $this->filtros = $this->extraerFiltros($request);
        $this->prepareExportRows();
    }

    protected function extraerFiltros(?Request $request): array
    {
        $filtros = [];

        // Usuario activo
        $user = auth()->user();
        if ($user) {
            $user->loadMissing('persona');
            $codigo = $user->codigo ?? '';
            $nombre = trim($user->full_name ?? $user->name ?? '');
            $filtros['usuario'] = $codigo ? "{$codigo} - {$nombre}" : $nombre;
        } else {
            $filtros['usuario'] = 'N/A';
        }

        // Fecha y hora
        $filtros['fecha_hora'] = Carbon::now()->format('d/m/Y H:i:s');

        // Tramos (puede ser array o valor único)
        if ($request && $request->filled('tramo')) {
            $tramoInput = $request->input('tramo');
            $tramosArray = is_array($tramoInput) ? $tramoInput : [$tramoInput];
            $tramosTexto = [
                0 => 'Tramo 0 (0-6 días)',
                1 => 'Tramo 1 (7-14 días)',
                2 => 'Tramo 2 (15-21 días)',
                3 => 'Tramo 3 (22-30 días)',
                4 => 'Tramo 4 (31+ días)',
            ];
            $nombres = array_map(function ($t) use ($tramosTexto) {
                return $tramosTexto[(int) $t] ?? "Tramo {$t}";
            }, $tramosArray);
            $filtros['tramos'] = implode(', ', $nombres);
        } else {
            $filtros['tramos'] = 'Todos';
        }

        // Sucursal
        $sucursalIds = [];
        if ($request && $request->filled('sucursal_id')) {
            $sucursalIds = $request->input('sucursal_id');
            $sucursalIds = is_array($sucursalIds) ? $sucursalIds : [$sucursalIds];
            $sucursales = Sucursal::whereIn('id', $sucursalIds)->pluck('sucursal')->toArray();
            $filtros['sucursal'] = !empty($sucursales) ? implode(', ', $sucursales) : 'Todas';
        } else {
            $filtros['sucursal'] = 'Todas';
        }

        // Zona
        if ($request && $request->filled('zona_id')) {
            $zonaIds = $request->input('zona_id');
            $zonaIds = is_array($zonaIds) ? $zonaIds : [$zonaIds];
            $zonas = Zona::whereIn('id', $zonaIds)->pluck('nombre')->toArray();
            $filtros['zona'] = !empty($zonas) ? implode(', ', $zonas) : 'Todas';
        } elseif (!empty($sucursalIds)) {
            $zonas = \DB::table('zona_sucursal')
                ->join('zonas', 'zona_sucursal.zona_id', '=', 'zonas.id')
                ->whereIn('zona_sucursal.sucursal_id', $sucursalIds)
                ->distinct()
                ->pluck('zonas.nombre')
                ->toArray();
            $filtros['zona'] = !empty($zonas) ? implode(', ', $zonas) : 'Todas';
        } else {
            $filtros['zona'] = 'Todas';
        }

        return $filtros;
    }

    /**
     * Prepara las filas para exportar: una fila por préstamo o convenio
     */
    protected function prepareExportRows()
    {
        foreach ($this->cuotasAgrupadas as $clienteId => $datos) {
            // Agrupar cuotas por préstamo
            $cuotasPorPrestamo = $datos['cuotas']->groupBy('prestamo_id');

            foreach ($cuotasPorPrestamo as $prestamoId => $cuotasPrestamo) {
                // Verificar si el préstamo tiene convenio activo
                $prestamo = \App\Models\Prestamo::find($prestamoId);
                $convenioActivo = null;

                if ($prestamo) {
                    $convenioActivo = $prestamo->convenios()
                        ->where('estado', \App\Enums\ConvenioEstado::ACTIVO->value)
                        ->first();
                }

                // Si tiene convenio activo, usar las cuotas del convenio
                if ($convenioActivo) {
                    // Obtener cuotas del convenio
                    $cuotasConvenio = \App\Models\CuotaConvenioModel::where('convenio_id', $convenioActivo->id)
                        ->where('fecha_vencimiento', '<', Carbon::today())
                        ->whereIn('estado', [0, 1, 3]) // Pendiente, Parcial, Vencido
                        ->get();

                    if ($cuotasConvenio->isEmpty()) {
                        continue; // Si no hay cuotas vencidas del convenio, saltar
                    }

                    // Si hay filtro de tramo, verificar que este convenio coincida
                    if ($this->tramoFiltro !== null) {
                        $tramoEstado = $this->calcularTramoEstado($cuotasConvenio);
                        preg_match('/Tramo (\d+)/', $tramoEstado, $matches);
                        $tramoConvenio = isset($matches[1]) ? (int)$matches[1] : null;

                        if ($tramoConvenio !== (int)$this->tramoFiltro) {
                            continue;
                        }
                    }

                    $this->exportRows[] = [
                        'tipo' => 'convenio',
                        'prestamo_id' => $convenioActivo->id,
                        'prestamo_original_id' => $prestamoId,
                        'cliente' => $datos['cliente'],
                        'nombre_completo' => $datos['nombre_completo'],
                        'zona' => $datos['zona'] ?? '',
                        'sucursal' => $datos['sucursal'] ?? '',
                        'cuotas' => $cuotasConvenio,
                    ];
                } else {
                    // Sin convenio, usar el préstamo normal
                    if ($this->tramoFiltro !== null) {
                        $tramoEstado = $this->calcularTramoEstado($cuotasPrestamo);
                        preg_match('/Tramo (\d+)/', $tramoEstado, $matches);
                        $tramoPrestamo = isset($matches[1]) ? (int)$matches[1] : null;

                        if ($tramoPrestamo !== (int)$this->tramoFiltro) {
                            continue;
                        }
                    }

                    $this->exportRows[] = [
                        'tipo' => 'prestamo',
                        'prestamo_id' => $prestamoId,
                        'cliente' => $datos['cliente'],
                        'nombre_completo' => $datos['nombre_completo'],
                        'zona' => $datos['zona'] ?? '',
                        'sucursal' => $datos['sucursal'] ?? '',
                        'cuotas' => $cuotasPrestamo,
                    ];
                }
            }
        }
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->exportRows);
    }

    public function headings(): array
    {
        return [
            'TIPO',
            'PRESTAMO ID',
            'ESTADO',
            'DNI',
            'NOMBRE CLIENTE',
            'ZONA/SUCURSAL',
            'CAPITAL',
            'CUOTA',
            'CUOTAS PAGADAS',
            'CUOTAS VENCIDAS',
            'FECHA INICIO ATRASO',
            'ULT. PAGO CUOTA',
            'ULT. PAGO MORA',
            'TRAMO 0-5 ESTADO CREDITO',
            'MONTO CUOTAS VENCIDAS',
            'MORA ACUMULADA',
        ];
    }

    /**
     * @param  mixed  $row
     */
    public function map($row): array
    {
        // Determinar si es préstamo o convenio
        $tipo = $row['tipo'] ?? 'prestamo';
        $prestamoId = $row['prestamo_id'];
        $cliente = $row['cliente'];
        $nombreCompleto = $row['nombre_completo'];
        $cuotas = $row['cuotas'];

        // Ubicación - ahora son strings
        $ubicacion = '';
        if (!empty($row['zona'])) {
            $ubicacion .= $row['zona'];
        }
        if (!empty($row['sucursal'])) {
            $ubicacion .= ($ubicacion ? ' / ' : '') . $row['sucursal'];
        }
        if (empty($ubicacion)) {
            $ubicacion = 'Sin ubicación';
        }

        // Obtener el objeto principal (préstamo o convenio)
        $prestamo = null;
        $convenio = null;
        $capital = 0;
        $estadoRegistro = 'N/A';
        $montoCuota = 0;
        $cuotasPagadas = 0;
        $totalCuotas = 0;

        if ($tipo === 'convenio') {
            // Es un convenio
            try {
                $convenio = \App\Models\Convenio::find($prestamoId);
                if ($convenio) {
                    // Convertir Enum a string
                    $estado = $convenio->estado;
                    if ($estado instanceof \BackedEnum) {
                        $estadoRegistro = $estado->name; // Usar name o value según lo que prefieras
                    } else {
                        $estadoRegistro = $estado ?? 'N/A';
                    }

                    // Para convenio, el capital es el monto_capital del convenio
                    $capital = $convenio->monto_capital ?? 0;

                    // Obtener monto de cuota del convenio
                    $unaCuotaConvenio = \App\Models\CuotaConvenioModel::where('convenio_id', $convenio->id)->first();
                    $montoCuota = $unaCuotaConvenio ? ($unaCuotaConvenio->monto_cuota ?? 0) : 0;

                    // Contar cuotas pagadas (estado = 2) del convenio
                    $cuotasPagadas = \App\Models\CuotaConvenioModel::where('convenio_id', $convenio->id)
                        ->where('estado', 2)
                        ->count();

                    // Total de cuotas del convenio
                    $totalCuotas = \App\Models\CuotaConvenioModel::where('convenio_id', $convenio->id)->count();
                }
            } catch (\Exception $e) {
                \Log::error('Error obteniendo convenio', ['convenio_id' => $prestamoId, 'error' => $e->getMessage()]);
            }
        } else {
            // Es un préstamo
            try {
                $prestamo = \App\Models\Prestamo::find($prestamoId);
                if ($prestamo) {
                    // Convertir Enum a string si es necesario
                    $estado = $prestamo->estado;
                    if ($estado instanceof \BackedEnum) {
                        $estadoRegistro = $estado->name;
                    } else {
                        $estadoRegistro = $estado ?? 'N/A';
                    }

                    // CAPITAL = cantidad_solicitada del préstamo
                    $capital = $prestamo->cantidad_solicitada ?? 0;

                    // CUOTA = monto de una cuota del préstamo
                    $unaCuota = \App\Models\Cuota::where('prestamo_id', $prestamo->id)->first();
                    $montoCuota = $unaCuota ? ($unaCuota->monto ?? 0) : 0;

                    // Contar cuotas pagadas (estado = 2)
                    $cuotasPagadas = \App\Models\Cuota::where('prestamo_id', $prestamo->id)
                        ->where('estado', 2)
                        ->count();

                    // Obtener el plazo (total de cuotas)
                    $totalCuotas = $prestamo->plazo ?? 0;
                    if ($totalCuotas == 0) {
                        $totalCuotas = \App\Models\Cuota::where('prestamo_id', $prestamo->id)->count();
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error obteniendo préstamo', ['prestamo_id' => $prestamoId, 'error' => $e->getMessage()]);
            }
        }

        // Determinar el campo de fecha según el tipo
        $campoFecha = ($tipo === 'convenio') ? 'fecha_vencimiento' : 'fecha_pago';
        $campoNumero = ($tipo === 'convenio') ? 'numero_cuota' : 'numero';
        $campoMonto = ($tipo === 'convenio') ? 'monto_cuota' : 'monto';

        // Obtener la primera cuota vencida para la fecha de inicio de atraso
        $primeraCuotaVencida = $cuotas->sortBy($campoFecha)->first(function ($cuota) {
            $estado = $cuota->estado;
            $estadoValor = $estado instanceof \BackedEnum ? $estado->value : (is_numeric($estado) ? (int) $estado : null);
            return in_array($estadoValor, [0, 1, 3]); // Pendiente, Parcial o Vencida
        });

        $fechaInicioAtraso = $primeraCuotaVencida ? Carbon::parse($primeraCuotaVencida->$campoFecha)->format('d/m/Y') : 'N/A';

        // Obtener fecha última operación de pago de cuota y mora
        $fechaUltPagoCuota = 'N/A';
        $fechaUltPagoMora = 'N/A';
        if ($tipo === 'prestamo' && $prestamo) {
            $ultPagoCuota = \App\Models\Operacion::where('prestamo_id', $prestamo->id)
                ->where('tipo_operacion', 'Pago de cuota')
                ->where('estado', '!=', 'anulado')
                ->orderByDesc('fecha')
                ->first();
            if ($ultPagoCuota) {
                $fechaUltPagoCuota = Carbon::parse($ultPagoCuota->fecha)->format('d/m/Y');
            }

            $ultPagoMora = \App\Models\Operacion::where('prestamo_id', $prestamo->id)
                ->where('tipo_operacion', 'Pago de mora')
                ->where('estado', '!=', 'anulado')
                ->orderByDesc('fecha')
                ->first();
            if ($ultPagoMora) {
                $fechaUltPagoMora = Carbon::parse($ultPagoMora->fecha)->format('d/m/Y');
            }
        } elseif ($tipo === 'convenio' && isset($row['prestamo_original_id'])) {
            $ultPagoCuota = \App\Models\Operacion::where('prestamo_id', $row['prestamo_original_id'])
                ->whereIn('tipo_operacion', ['Pago de cuota', 'PAGO_CONVENIO'])
                ->where('estado', '!=', 'anulado')
                ->orderByDesc('fecha')
                ->first();
            if ($ultPagoCuota) {
                $fechaUltPagoCuota = Carbon::parse($ultPagoCuota->fecha)->format('d/m/Y');
            }

            $ultPagoMora = \App\Models\Operacion::where('prestamo_id', $row['prestamo_original_id'])
                ->where('tipo_operacion', 'Pago de mora')
                ->where('estado', '!=', 'anulado')
                ->orderByDesc('fecha')
                ->first();
            if ($ultPagoMora) {
                $fechaUltPagoMora = Carbon::parse($ultPagoMora->fecha)->format('d/m/Y');
            }
        }

        // Recolectar números de cuotas vencidas y sumar monto de cuotas vencidas
        $cuotasVencidasNumeros = [];
        $montoCuotasVencidas = 0;
        foreach ($cuotas as $cuota) {
            $estado = $cuota->estado;
            $estadoValor = $estado instanceof \BackedEnum ? $estado->value : (is_numeric($estado) ? (int) $estado : null);

            // Cuotas vencidas (estados: 0, 1, 3)
            if (in_array($estadoValor, [0, 1, 3])) {
                $cuotasVencidasNumeros[] = $cuota->$campoNumero; // Guardar el número de cuota
                // Sumar el monto pendiente de la cuota vencida
                $montoPagadoCuota = $cuota->monto_pagado ?? 0;
                $saldoCuota = $cuota->$campoMonto - $montoPagadoCuota;
                $montoCuotasVencidas += max(0, $saldoCuota);
            }
        }

        // Formatear como lista separada por comas
        $cuotasVencidasFormato = !empty($cuotasVencidasNumeros) ? implode(',', $cuotasVencidasNumeros) : '';

        // MORA ACUMULADA - Solo para préstamos (los convenios no tienen moras en el mismo esquema)
        // SOLO contar moras de cuotas VENCIDAS (fecha_pago <= hoy)
        $moraAcumulada = 0;
        if ($tipo === 'prestamo' && $prestamo && isset($prestamo->id)) {
            try {
                // Obtener SOLO las cuotas VENCIDAS del préstamo con sus moras
                $cuotasVencidas = \App\Models\Cuota::where('prestamo_id', $prestamo->id)
                    ->where('fecha_pago', '<=', Carbon::today())
                    ->with('moras')
                    ->get();

                foreach ($cuotasVencidas as $cuota) {
                    if (isset($cuota->moras) && $cuota->moras->count() > 0) {
                        foreach ($cuota->moras as $mora) {
                            // Solo contar moras PENDIENTES (0) o PARCIALES (1), NO pagadas ni regularizadas
                            $estadoMora = $mora->estado instanceof \BackedEnum ? $mora->estado->value : (int)$mora->estado;
                            if (in_array($estadoMora, [0, 1])) {
                                $montoPagadoMora = $mora->monto_pagado ?? 0;
                                $saldoMora = $mora->monto - $montoPagadoMora;
                                $moraAcumulada += max(0, $saldoMora);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error calculando mora acumulada', [
                    'prestamo_id' => $prestamo->id,
                    'error' => $e->getMessage()
                ]);
                $moraAcumulada = 0;
            }
        }

        // Formato "cuotas pagadas / total de cuotas"
        $cuotasPagadasFormato = $totalCuotas > 0
            ? $cuotasPagadas . '/' . $totalCuotas
            : ($cuotasPagadas > 0 ? $cuotasPagadas : '0');

        // Calcular TRAMO y estado del crédito
        $tramoEstado = $this->calcularTramoEstado($cuotas);

        return [
            strtoupper($tipo),  // TIPO (PRESTAMO o CONVENIO)
            $prestamoId ?? 'N/A',  // PRESTAMO ID o CONVENIO ID
            $estadoRegistro,  // ESTADO
            $cliente->persona->documento ?? 'N/A',  // DNI
            $nombreCompleto,  // NOMBRE CLIENTE
            $ubicacion,  // ZONA/SUCURSAL
            $capital,  // CAPITAL
            $montoCuota,  // CUOTA
            $cuotasPagadasFormato,  // CUOTAS PAGADAS
            $cuotasVencidasFormato,  // CUOTAS VENCIDAS (lista de números: 1,2,4,5)
            $fechaInicioAtraso,  // FECHA INICIO ATRASO
            $fechaUltPagoCuota,  // ULT. PAGO CUOTA
            $fechaUltPagoMora,  // ULT. PAGO MORA
            $tramoEstado,  // TRAMO 0-5 ESTADO CREDITO
            $montoCuotasVencidas,  // MONTO CUOTAS VENCIDAS
            $moraAcumulada,  // MORA ACUMULADA
        ];
    }

    /**
     * Calcula el tramo de atraso y estado del crédito basado en la primera cuota no pagada
     * Tramo 0 = 0-6 días
     * Tramo 1 = 7-14 días
     * Tramo 2 = 15-21 días
     * Tramo 3 = 22-30 días
     * Tramo 4 = 31+ días
     */
    protected function calcularTramoEstado($cuotas): string
    {
        // Detectar si es cuota de convenio o préstamo
        $primeraCuota = $cuotas->first();
        $campoFecha = isset($primeraCuota->fecha_vencimiento) ? 'fecha_vencimiento' : 'fecha_pago';

        // Ordenar cuotas por fecha (más antigua primero)
        $cuotasOrdenadas = $cuotas->sortBy($campoFecha);

        // Buscar la primera cuota no pagada o pagada parcialmente
        $primeraCuotaNoPagada = $cuotasOrdenadas->first(function ($cuota) {
            $estado = $cuota->estado;

            // Obtener el valor numérico del estado
            if ($estado instanceof \BackedEnum) {
                $estadoValor = $estado->value;
            } else {
                $estadoValor = is_numeric($estado) ? (int) $estado : null;
            }

            // Pendiente (0), Parcial (1), Vencido (3) - Excluimos Pagado (2)
            return in_array($estadoValor, [0, 1, 3]);
        });

        if (!$primeraCuotaNoPagada) {
            return 'Tramo 0';
        }

        // Calcular días de atraso desde la fecha de vencimiento
        $fechaVencimiento = Carbon::parse($primeraCuotaNoPagada->$campoFecha);
        $hoy = Carbon::today();
        $diasAtraso = $fechaVencimiento->diffInDays($hoy, false);

        // Si la fecha de vencimiento es futura, no hay atraso
        if ($diasAtraso < 0) {
            return 'Tramo 0';
        }

        // Determinar el tramo según los días de atraso
        if ($diasAtraso <= 6) {
            return 'Tramo 0';
        } elseif ($diasAtraso <= 14) {
            return 'Tramo 1';
        } elseif ($diasAtraso <= 21) {
            return 'Tramo 2';
        } elseif ($diasAtraso <= 30) {
            return 'Tramo 3';
        } else {
            return 'Tramo 4';
        }
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Insertar filas para cabecera con información de filtros
                $sheet->insertNewRowBefore(1, 7);

                // Título principal
                $sheet->setCellValue('A1', 'REPORTE DE TRAMOS Y ESTADO DE CRÉDITO');
                $sheet->mergeCells('A1:P1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Fila 3: Usuario
                $sheet->setCellValue('A3', 'Usuario:');
                $sheet->getStyle('A3')->getFont()->setBold(true);
                $sheet->setCellValue('B3', $this->filtros['usuario'] ?? 'N/A');
                $sheet->mergeCells('B3:E3');

                // Fila 4: Fecha y hora
                $sheet->setCellValue('A4', 'Fecha y Hora:');
                $sheet->getStyle('A4')->getFont()->setBold(true);
                $sheet->setCellValue('B4', $this->filtros['fecha_hora'] ?? 'N/A');
                $sheet->mergeCells('B4:E4');

                // Fila 5: Tramos
                $sheet->setCellValue('A5', 'Tramos:');
                $sheet->getStyle('A5')->getFont()->setBold(true);
                $sheet->setCellValue('B5', $this->filtros['tramos'] ?? 'Todos');
                $sheet->mergeCells('B5:E5');

                // Fila 6: Zona y Sucursal
                $sheet->setCellValue('A6', 'Zona:');
                $sheet->getStyle('A6')->getFont()->setBold(true);
                $sheet->setCellValue('B6', $this->filtros['zona'] ?? 'Todas');
                $sheet->mergeCells('B6:C6');
                $sheet->setCellValue('D6', 'Sucursal:');
                $sheet->getStyle('D6')->getFont()->setBold(true);
                $sheet->setCellValue('E6', $this->filtros['sucursal'] ?? 'Todas');
                $sheet->mergeCells('E6:G6');

                // Ajustar índice debido a las filas insertadas
                $headerRow = 8;
                $dataStartRow = $headerRow + 1;

                // Formatear encabezados
                $headerRange = 'A'.$headerRow.':P'.$headerRow;
                $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle($headerRange)->getFill()->getStartColor()->setARGB('FF4A90E2');
                $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');

                // Aplicar formato a las filas de datos
                $lastRow = count($this->exportRows) + $dataStartRow - 1;

                // Aplicar formato para importes (columnas G: CAPITAL, H: CUOTA, O: MONTO CUOTAS VENCIDAS, P: MORA ACUMULADA)
                $montosCols = ['G', 'H', 'O', 'P'];
                foreach ($montosCols as $col) {
                    $montoRange = $col.$dataStartRow.':'.$col.$lastRow;
                    $sheet->getStyle($montoRange)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                // Aplicar formato condicional para la columna TRAMO (columna N)
                foreach ($this->exportRows as $index => $row) {
                    $currentRow = $dataStartRow + $index;
                    $tramoEstado = $sheet->getCell('N'.$currentRow)->getValue();

                    $color = null;
                    if (strpos($tramoEstado, 'Tramo 4') !== false) {
                        $color = 'FFD32F2F'; // Rojo oscuro - Pérdida
                    } elseif (strpos($tramoEstado, 'Tramo 3') !== false) {
                        $color = 'FFF44336'; // Rojo - Dudoso
                    } elseif (strpos($tramoEstado, 'Tramo 2') !== false) {
                        $color = 'FFFF9800'; // Naranja - Deficiente
                    } elseif (strpos($tramoEstado, 'Tramo 1') !== false) {
                        $color = 'FFFFEB3B'; // Amarillo - CPP
                    } elseif (strpos($tramoEstado, 'Tramo 0') !== false) {
                        $color = 'FF8BC34A'; // Verde claro - Normal
                    }

                    if ($color) {
                        $sheet->getStyle('N'.$currentRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $sheet->getStyle('N'.$currentRow)->getFill()->getStartColor()->setARGB($color);
                    }
                }

                // Agregar fila de totales
                $totalRow = $lastRow + 2;
                $sheet->setCellValue('A'.$totalRow, 'TOTALES:');
                $sheet->mergeCells('A'.$totalRow.':F'.$totalRow);
                $sheet->getStyle('A'.$totalRow.':P'.$totalRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle('A'.$totalRow.':P'.$totalRow)->getFill()->getStartColor()->setARGB('FF4A90E2');
                $sheet->getStyle('A'.$totalRow.':P'.$totalRow)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A'.$totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                // Calcular totales
                $totalCapital = 0;
                $totalCuota = 0;
                $totalCuotasVencidas = 0;
                $totalMontoCuotasVencidas = 0;
                $totalMora = 0;

                foreach ($this->exportRows as $row) {
                    $tipo = $row['tipo'] ?? 'prestamo';
                    $prestamoId = $row['prestamo_id'];
                    $cuotasPrestamo = $row['cuotas'];

                    if ($tipo === 'convenio') {
                        // Es un convenio
                        try {
                            $convenio = \App\Models\Convenio::find($prestamoId);
                            if ($convenio) {
                                // CAPITAL = monto_capital del convenio
                                $totalCapital += $convenio->monto_capital ?? 0;

                                // CUOTA = monto de una cuota del convenio
                                $unaCuotaConvenio = \App\Models\CuotaConvenioModel::where('convenio_id', $convenio->id)->first();
                                $totalCuota += $unaCuotaConvenio ? ($unaCuotaConvenio->monto_cuota ?? 0) : 0;
                            }
                        } catch (\Exception $e) {
                            // Skip on error
                        }
                    } else {
                        // Es un préstamo
                        try {
                            $prestamo = \App\Models\Prestamo::find($prestamoId);
                            if ($prestamo) {
                                // CAPITAL = cantidad_solicitada
                                $totalCapital += $prestamo->cantidad_solicitada ?? 0;

                                // CUOTA = monto de una cuota
                                $unaCuota = \App\Models\Cuota::where('prestamo_id', $prestamo->id)->first();
                                $totalCuota += $unaCuota ? ($unaCuota->monto ?? 0) : 0;
                            }
                        } catch (\Exception $e) {
                            // Skip on error
                        }
                    }

                    // Contar cuotas vencidas y sumar montos
                    $campoMonto = ($tipo === 'convenio') ? 'monto_cuota' : 'monto';
                    foreach ($cuotasPrestamo as $cuota) {
                        $estado = $cuota->estado;
                        $estadoValor = $estado instanceof \BackedEnum ? $estado->value : (is_numeric($estado) ? (int) $estado : null);

                        if (in_array($estadoValor, [0, 1, 3])) {
                            $totalCuotasVencidas++;
                            // Sumar el monto pendiente de la cuota vencida
                            $montoPagadoCuota = $cuota->monto_pagado ?? 0;
                            $saldoCuota = $cuota->$campoMonto - $montoPagadoCuota;
                            $totalMontoCuotasVencidas += max(0, $saldoCuota);
                        }
                    }

                    // Sumar moras SOLO de cuotas VENCIDAS del préstamo (solo para préstamos, no convenios)
                    if ($tipo === 'prestamo') {
                        try {
                            $prestamo = \App\Models\Prestamo::find($prestamoId);
                            if ($prestamo && isset($prestamo->id)) {
                                $cuotasVencidas = \App\Models\Cuota::where('prestamo_id', $prestamo->id)
                                    ->where('fecha_pago', '<=', Carbon::today())
                                    ->with('moras')
                                    ->get();

                                foreach ($cuotasVencidas as $cuota) {
                                    if (isset($cuota->moras) && $cuota->moras->count() > 0) {
                                        foreach ($cuota->moras as $mora) {
                                            // Solo contar moras PENDIENTES (0) o PARCIALES (1), NO pagadas ni regularizadas
                                            $estadoMora = $mora->estado instanceof \BackedEnum ? $mora->estado->value : (int)$mora->estado;
                                            if (in_array($estadoMora, [0, 1])) {
                                                $montoPagadoMora = $mora->monto_pagado ?? 0;
                                                $saldoMora = $mora->monto - $montoPagadoMora;
                                                $totalMora += max(0, $saldoMora);
                                            }
                                        }
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            // Skip on error
                        }
                    }
                }

                // Establecer valores de totales (columnas G, H, J, O, P)
                $sheet->setCellValue('G'.$totalRow, $totalCapital);
                $sheet->setCellValue('H'.$totalRow, $totalCuota);
                $sheet->setCellValue('J'.$totalRow, $totalCuotasVencidas);
                $sheet->setCellValue('O'.$totalRow, $totalMontoCuotasVencidas);
                $sheet->setCellValue('P'.$totalRow, $totalMora);

                // Aplicar formato a los totales
                $sheet->getStyle('G'.$totalRow.':H'.$totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('O'.$totalRow.':P'.$totalRow)->getNumberFormat()->setFormatCode('#,##0.00');

                // Aplicar bordes a toda la tabla
                $tableRange = 'A'.$headerRow.':P'.$lastRow;
                $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Auto-fit para todas las columnas
                foreach (range('A', 'P') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Agregar pie de página con información adicional
                $footerRow = $totalRow + 2;
                $totalRegistros = count($this->exportRows);

                $sheet->setCellValue('A'.$footerRow, 'Total de registros: '.$totalRegistros.' | Total cuotas vencidas: '.$totalCuotasVencidas.' | Total monto cuotas vencidas: S/ '.number_format($totalMontoCuotasVencidas, 2));
                $sheet->mergeCells('A'.$footerRow.':P'.$footerRow);
                $sheet->getStyle('A'.$footerRow)->getFont()->setItalic(true)->setSize(10);
            },
        ];
    }


}
