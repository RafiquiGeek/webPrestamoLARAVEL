<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class CarteraAsesorExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $prestamos;

    public function __construct($prestamos)
    {
        $this->prestamos = $prestamos;
    }

    public function collection()
    {
        return $this->prestamos;
    }

    public function headings(): array
    {
        return [
            'PRÉSTAMO',
            'CLIENTE',
            'DOCUMENTO',
            'TIPO',
            'DESEMBOLSO',
            '1° CUOTA',
            'ÚLT. CUOTA',
            'CUOTAS PAGADAS',
            'CUOTAS TOTALES',
            'CUMPLIMIENTO %',
            'MORA PENDIENTE',
            'MONTO SOLICITADO'
        ];
    }

    public function map($prestamo): array
    {
        $cuotasTotal = $prestamo->cuotas->count();
        $cuotasPagadas = $prestamo->cuotas->where('estado', \App\Enums\CuotaEstado::PAGADO)->count();
        $porcentaje = $cuotasTotal > 0 ? ($cuotasPagadas / $cuotasTotal) * 100 : 0;
        $morasPendientes = $prestamo->cuotas->where('estado', '!=', \App\Enums\CuotaEstado::PAGADO)->sum('cantidad_mora');

        $tipo = str_contains(strtolower($prestamo->tipo_solicitud ?? ''), 'nueva') ? 'NUEVO' : 'RENOVACIÓN';
        $fechaDesembolso = $prestamo->operaciones->where('tipo_operacion', 'Desembolso')->first()?->fecha;

        return [
            $prestamo->getNumeroPrestamoAttribute(),
            $prestamo->cliente?->persona?->full_name ?? 'N/A',
            $prestamo->cliente?->persona?->documento ?? 'N/A',
            $tipo,
            $fechaDesembolso ? Carbon::parse($fechaDesembolso)->format('d/m/Y') : '---',
            $prestamo->cuotas->min('fecha_pago') ? Carbon::parse($prestamo->cuotas->min('fecha_pago'))->format('d/m/Y') : '---',
            $prestamo->cuotas->max('fecha_pago') ? Carbon::parse($prestamo->cuotas->max('fecha_pago'))->format('d/m/Y') : '---',
            $cuotasPagadas,
            $cuotasTotal,
            number_format($porcentaje, 2) . '%',
            number_format($morasPendientes, 2),
            number_format($prestamo->cantidad_solicitada, 2)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '28A745'] // Green background
                ],
            ],
        ];
    }
}
