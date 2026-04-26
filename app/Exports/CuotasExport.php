<?php

namespace App\Exports;

use App\Models\Cuota;
use App\Models\Prestamo;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CuotasExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $prestamoIds;

    public function __construct(array $prestamoIds)
    {
        $this->prestamoIds = $prestamoIds;
    }

    public function collection()
    {
        $query = Cuota::with([
                'prestamo.cliente.persona',
                'comprobantes',
                'operaciones' => function($q) {
                    $q->where('estado', '!=', 'anulado')
                      ->orderBy('fecha', 'desc');
                }
            ])
            ->where('estado', 2) // Solo cuotas pagadas
            ->whereHas('prestamo', function($q) {
                $q->where('tiene_comprobante', 1); // Solo préstamos con factura habilitada
            });

        if (!empty($this->prestamoIds)) {
            $query->whereIn('prestamo_id', $this->prestamoIds);
        }

        return $query->orderBy('prestamo_id')
            ->orderBy('numero')
            ->get();
    }

    public function headings(): array
    {
        return [
            'NR. Préstamo',
            'Cliente',
            'DNI/RUC',
            'Fecha Cuota',
            'Fecha pago Cuota',
            'Monto Cuota',
            'IGV',
            'Interés',
            'Comisión',
            'Exonerado',
            'Fecha Emisión',
            'Estado Comprobante',
            'Serie Comprobante',
            'Número Comprobante',
            'Tiene Nota Crédito',
            'Serie Nota Crédito',
            'Número Nota Crédito',
        ];
    }

    public function map($cuota): array
    {
        // Obtener datos del comprobante principal (factura/boleta)
        $estadoComprobante = 'Pendiente';
        $serieComprobante = '';
        $numeroComprobante = '';
        $fechaEmision = '';
        $tieneNotaCredito = 'No';
        $serieNotaCredito = '';
        $numeroNotaCredito = '';

        if ($cuota->comprobantes && $cuota->comprobantes->count() > 0) {
            // Buscar el comprobante principal (factura o boleta - tipo '01' o '03')
            $comprobantePrincipal = $cuota->comprobantes->whereIn('tipo_comprobante', ['01', '03'])->first();

            if ($comprobantePrincipal) {
                $estadoComprobante = $comprobantePrincipal->estado === 'ACEPTADO' ? 'Generado' : $comprobantePrincipal->estado;
                $serieComprobante = $comprobantePrincipal->serie;
                $numeroComprobante = $comprobantePrincipal->numero;
                $fechaEmision = $comprobantePrincipal->fecha_emision ? $comprobantePrincipal->fecha_emision->format('d/m/Y') : '';
            }

            // Buscar si existe una nota de crédito (tipo '07')
            $notaCredito = $cuota->comprobantes->where('tipo_comprobante', '07')->first();
            if ($notaCredito) {
                $tieneNotaCredito = 'Sí';
                $serieNotaCredito = $notaCredito->serie;
                $numeroNotaCredito = $notaCredito->numero;
            }
        }

        // Nombre completo del cliente
        $clienteNombre = '';
        if ($cuota->prestamo && $cuota->prestamo->cliente && $cuota->prestamo->cliente->persona) {
            $persona = $cuota->prestamo->cliente->persona;
            $clienteNombre = trim($persona->nombres . ' ' . $persona->ape_pat . ' ' . $persona->ape_mat);
        }

        // DNI/RUC del cliente
        $documento = '';
        if ($cuota->prestamo && $cuota->prestamo->cliente && $cuota->prestamo->cliente->persona) {
            $documento = $cuota->prestamo->cliente->persona->documento ?? '';
        }

        // Obtener la fecha real de pago (última operación válida - ya pre-cargada y ordenada)
        $fechaPagoReal = '';
        if ($cuota->operaciones && $cuota->operaciones->count() > 0) {
            $ultimaOperacion = $cuota->operaciones->first(); // Ya viene ordenada por fecha desc

            if ($ultimaOperacion && $ultimaOperacion->fecha) {
                $fechaPagoReal = $ultimaOperacion->fecha->format('d/m/Y');
            }
        }

        return [
            $cuota->prestamo->numero_prestamo ?? '',
            $clienteNombre,
            $documento,
            $cuota->fecha_pago ? $cuota->fecha_pago->format('d/m/Y') : '',
            $fechaPagoReal,
            number_format($cuota->monto ?? 0, 2),
            number_format($cuota->igv ?? 0, 2),
            number_format($cuota->interes ?? 0, 2),
            number_format($cuota->comision ?? 0, 2),
            number_format($cuota->pago_capital ?? 0, 2),
            $fechaEmision,
            $estadoComprobante,
            $serieComprobante,
            $numeroComprobante,
            $tieneNotaCredito,
            $serieNotaCredito,
            $numeroNotaCredito,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
    }
}