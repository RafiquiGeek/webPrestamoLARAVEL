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

class DeudasExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected $cuotasAgrupadas;

    protected $totales;

    protected $exportRows = [];

    protected $filtros = [];

    public function __construct($cuotasAgrupadas, $totales, ?Request $request = null)
    {
        $this->cuotasAgrupadas = $cuotasAgrupadas;
        $this->totales = $totales;
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

        // Zona: si se seleccionó explícitamente, usarla; si no, deducirla de las sucursales seleccionadas
        if ($request && $request->filled('zona_id')) {
            $zonaIds = $request->input('zona_id');
            $zonaIds = is_array($zonaIds) ? $zonaIds : [$zonaIds];
            $zonas = Zona::whereIn('id', $zonaIds)->pluck('nombre')->toArray();
            $filtros['zona'] = !empty($zonas) ? implode(', ', $zonas) : 'Todas';
        } elseif (!empty($sucursalIds)) {
            // Deducir zonas a partir de las sucursales seleccionadas
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
     * Prepara las filas para exportar: una fila por cliente (sin detalles de cuotas)
     */
    protected function prepareExportRows()
    {
        foreach ($this->cuotasAgrupadas as $clienteId => $datos) {
            // Solo fila de resumen del cliente
            $this->exportRows[] = [
                'tipo' => 'cliente',
                'datos' => $datos,
            ];
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
            'Cliente',
            'DNI',
            'Código Cliente',
            'Zona / Sucursal',
            'Dirección',
            'Referencia',
            'JCC',
            'Asesor',
            'Analista',
            'Nro Cuota',
            'ID Préstamo',
            'Fecha Vencimiento',
            'Días Mora',
            'Monto Cuota',
            'Monto Mora',
            'Total Deuda',
            'Tramo',
            'Última Gestión',
            'Último Compromiso',
        ];
    }

    /**
     * @param  mixed  $row
     */
    public function map($row): array
    {
        // Solo manejamos filas de cliente (una por cliente)
        $datos = $row['datos'];
        $cliente = $datos['cliente'];

        // Ubicación - ahora son strings
        $ubicacion = '';
        if (!empty($datos['zona'])) {
            $ubicacion .= $datos['zona'];
        }
        if (!empty($datos['sucursal'])) {
            $ubicacion .= ($ubicacion ? ', ' : '') . $datos['sucursal'];
        }
        if (empty($ubicacion)) {
            $ubicacion = 'Sin ubicación';
        }

        // Dirección y Referencia
        $direccion = $datos['direccion'] ?? 'N/A';
        $referencia = $datos['referencia'] ?? 'N/A';

        // Carteras
        $jcc = $datos['jcc_codigo'] ?? 'N/A';
        $asesor = $datos['asesor_codigo'] ?? 'N/A';
        $analista = $datos['analista_codigo'] ?? 'N/A';

        // Última gestión y compromiso
        $ultimaGestion = $datos['ultima_gestion'] ? Carbon::parse($datos['ultima_gestion']->fecha)->format('d/m/Y') : 'N/A';
        $ultimoCompromiso = $datos['ultimo_compromiso'] ? Carbon::parse($datos['ultimo_compromiso']->fecha_compromiso_pago)->format('d/m/Y') : 'N/A';

        // Obtener datos de la primera cuota con mora (o la más antigua)
        $primeraCuota = $datos['cuotas']->sortBy('fecha_pago')->first();
        $idPrestamo = $primeraCuota ? $primeraCuota->prestamo_id : '';
        $fechaVencimiento = $primeraCuota ? Carbon::parse($primeraCuota->fecha_pago)->format('d/m/Y') : '';

        // Calcular TRAMO basado en días de atraso desde la primera cuota no pagada
        $tramo = $this->calcularTramo($datos['cuotas']);

        // Calcular saldos pendientes de cuotas no pagadas
        $montoCuotaPendiente = 0;
        $montoMoraPendiente = 0;

        foreach ($datos['cuotas'] as $cuota) {
            // Obtener el valor del estado
            $estado = $cuota->estado;
            $estadoValor = $estado instanceof \BackedEnum ? $estado->value : (is_numeric($estado) ? (int) $estado : null);
            
            // Solo considerar cuotas pendientes (0), parciales (1) o vencidas (3)
            if (in_array($estadoValor, [0, 1, 3])) {
                // Saldo pendiente de la cuota = monto total - lo que ya pagó
                $montoPagadoCuota = $cuota->monto_pagado ?? 0;
                $saldoCuota = $cuota->monto - $montoPagadoCuota;
                $montoCuotaPendiente += max(0, $saldoCuota);
                
                // Saldo pendiente de moras de esta cuota
                if (isset($cuota->moras) && $cuota->moras->count() > 0) {
                    foreach ($cuota->moras as $mora) {
                        $montoPagadoMora = $mora->monto_pagado ?? 0;
                        $saldoMora = $mora->monto - $montoPagadoMora;
                        $montoMoraPendiente += max(0, $saldoMora);
                    }
                }
            }
        }

        // Deuda total = saldo cuotas + saldo moras
        $deudaTotalPendiente = $montoCuotaPendiente + $montoMoraPendiente;

        return [
            $datos['nombre_completo'],
            $cliente->persona->documento ?? 'N/A',
            $cliente->codigo ?? 'N/A',
            $ubicacion,
            $direccion,
            $referencia,
            $jcc,
            $asesor,
            $analista,
            $datos['total_cuotas'],
            $idPrestamo,
            $fechaVencimiento,
            $datos['dias_mora_max'],
            round($montoCuotaPendiente),      // Saldo pendiente de cuotas
            round($montoMoraPendiente),       // Saldo pendiente de moras
            round($deudaTotalPendiente),      // Total deuda pendiente
            $tramo,
            $ultimaGestion,
            $ultimoCompromiso,
        ];
    }

    /**
     * Calcula el tramo de atraso basado en la primera cuota no pagada o pagada parcialmente
     * Tramo 0 = 0 - 6 días de atraso
     * Tramo 1 = 7 - 14 días de atraso
     * Tramo 2 = 15 - 21 días de atraso
     * Tramo 3 = 22 - 30 días de atraso
     * Tramo 4 = 31+ días de atraso
     */
    protected function calcularTramo($cuotas): string
    {
        // Ordenar cuotas por fecha de pago (más antigua primero)
        $cuotasOrdenadas = $cuotas->sortBy('fecha_pago');

        // Buscar la primera cuota no pagada o pagada parcialmente
        // El campo estado puede ser CuotaEstado, CuotaConvenio o un entero
        $primeraCuotaNoPagada = $cuotasOrdenadas->first(function ($cuota) {
            $estado = $cuota->estado;
            
            // Obtener el valor numérico del estado
            // BackedEnum es la interfaz base de todos los enums backed (con valor)
            if ($estado instanceof \BackedEnum) {
                $estadoValor = $estado->value;
            } else {
                $estadoValor = is_numeric($estado) ? (int) $estado : null;
            }
            
            // Pendiente (0), Parcial (1), Vencido (3) - Excluimos Pagado (2)
            return in_array($estadoValor, [0, 1, 3]);
        });

        if (!$primeraCuotaNoPagada) {
            return 'Sin atraso';
        }

        // Calcular días de atraso desde la fecha de vencimiento
        $fechaVencimiento = Carbon::parse($primeraCuotaNoPagada->fecha_pago);
        $hoy = Carbon::today();
        $diasAtraso = $fechaVencimiento->diffInDays($hoy, false);

        // Si la fecha de vencimiento es futura, no hay atraso
        if ($diasAtraso < 0) {
            return 'Sin atraso';
        }

        // Determinar el tramo según los días de atraso
        if ($diasAtraso <= 6) {
            $numeroTramo = 0;
        } elseif ($diasAtraso <= 14) {
            $numeroTramo = 1;
        } elseif ($diasAtraso <= 21) {
            $numeroTramo = 2;
        } elseif ($diasAtraso <= 30) {
            $numeroTramo = 3;
        } else {
            $numeroTramo = 4;
        }

        return "Tramo {$numeroTramo} ({$diasAtraso} días)";
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
                $sheet->setCellValue('A1', 'REPORTE DE DEUDAS Y MORAS - CONSOLIDADO POR CLIENTE');
                $sheet->mergeCells('A1:S1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Fila 2: vacía (separador)

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

                // Fila 7: vacía (separador)

                // Ajustar índice debido a las filas insertadas
                $headerRow = 8;
                $dataStartRow = $headerRow + 1;

                // Formatear encabezados
                $headerRange = 'A'.$headerRow.':S'.$headerRow;
                $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle($headerRange)->getFill()->getStartColor()->setARGB('FF4A90E2');
                $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');

                // Aplicar formato a las filas de datos
                $lastRow = count($this->exportRows) + $dataStartRow - 1;

                // Aplicar formato para importes sin decimales (columnas N, O, P)
                $montosCols = ['N', 'O', 'P'];
                foreach ($montosCols as $col) {
                    $montoRange = $col.$dataStartRow.':'.$col.$lastRow;
                    $sheet->getStyle($montoRange)->getNumberFormat()->setFormatCode('#,##0');
                }

                // Aplicar formato condicional para días de mora (columna K)
                foreach ($this->exportRows as $index => $row) {
                    $currentRow = $dataStartRow + $index;
                    $datos = $row['datos'];
                    $diasMora = $datos['dias_mora_max'];

                    $color = null;
                    if ($diasMora > 60) {
                        $color = 'FFD32F2F'; // Rojo oscuro
                    } elseif ($diasMora >= 31) {
                        $color = 'FFF44336'; // Rojo
                    } elseif ($diasMora >= 16) {
                        $color = 'FFFF9800'; // Naranja
                    } elseif ($diasMora >= 8) {
                        $color = 'FFFFEB3B'; // Amarillo
                    } elseif ($diasMora >= 1) {
                        $color = 'FF8BC34A'; // Verde claro
                    }

                    if ($color) {
                        $sheet->getStyle('M'.$currentRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $sheet->getStyle('M'.$currentRow)->getFill()->getStartColor()->setARGB($color);
                    }
                }

                // Agregar fila de totales
                $totalRow = $lastRow + 2;
                $sheet->setCellValue('A'.$totalRow, 'TOTALES:');
                $sheet->mergeCells('A'.$totalRow.':N'.$totalRow);
                $sheet->getStyle('A'.$totalRow.':S'.$totalRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle('A'.$totalRow.':S'.$totalRow)->getFill()->getStartColor()->setARGB('FF4A90E2');
                $sheet->getStyle('A'.$totalRow.':S'.$totalRow)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A'.$totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                // Establecer valores de totales
                $sheet->setCellValue('O'.$totalRow, $this->totales['totalMonto']);
                $sheet->setCellValue('P'.$totalRow, $this->totales['totalMora']);
                $sheet->setCellValue('Q'.$totalRow, $this->totales['totalDeuda']);

                // Aplicar formato a los totales sin decimales
                $sheet->getStyle('O'.$totalRow.':Q'.$totalRow)->getNumberFormat()->setFormatCode('#,##0');

                // Aplicar bordes a toda la tabla
                $tableRange = 'A'.$headerRow.':S'.$lastRow;
                $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Auto-fit para todas las columnas
                foreach (range('A', 'S') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Agregar pie de página con información adicional
                $footerRow = $totalRow + 2;
                $totalClientes = count($this->cuotasAgrupadas);
                $totalCuotasMora = collect($this->cuotasAgrupadas)->sum(function($datos) {
                    return $datos['total_cuotas'];
                });

                $sheet->setCellValue('A'.$footerRow, 'Total de clientes con mora: '.$totalClientes.' | Total de cuotas vencidas: '.$totalCuotasMora);
                $sheet->mergeCells('A'.$footerRow.':S'.$footerRow);
                $sheet->getStyle('A'.$footerRow)->getFont()->setItalic(true)->setSize(10);
            },
        ];
    }

}
