<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientesPorUsuarioExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $clientes;

    public function __construct($clientes)
    {
        $this->clientes = $clientes;
    }

    /**
     * Retorna la colección de clientes a exportar
     */
    public function collection()
    {
        return $this->clientes;
    }

    /**
     * Define los encabezados de las columnas
     */
    public function headings(): array
    {
        return [
            'DNI',
            'NOMBRE COMPLETO',
            'TELÉFONO',
            'TELÉFONO SECUNDARIO',
            'ZONA',
            'SUCURSAL',
            'DIRECCIÓN',
            'JCC ASIGNADO',
            'ASESOR ASIGNADO',
            'ANALISTA ASIGNADO',
            'TOTAL PRÉSTAMOS',
            'MONTO TOTAL PRESTADO',
            'ÚLTIMO PRÉSTAMO',
            'ESTADO ACTUAL'
        ];
    }

    /**
     * Mapea cada cliente a una fila del Excel
     */
    public function map($cliente): array
    {
        // Obtener préstamo más reciente
        $prestamoReciente = $cliente->prestamos->first(); // Ya viene ordenado por latest

        // Extraer usuarios del préstamo más reciente
        $jcc = $prestamoReciente?->carterasJcc->first()?->jcc;
        $asesor = $prestamoReciente?->carterasAsesor->first()?->asesor;
        $analista = $prestamoReciente?->carterasAnalista->first()?->analista;

        // Dirección principal
        $direccion = $cliente->persona->direcciones->first();

        // Zona (puede tener múltiples, tomamos la primera)
        $zona = $direccion?->sucursal?->zonas->first();

        // Obtener teléfonos
        $telefono1 = $cliente->persona->telefonos->first()?->numero ?? 'N/A';
        $telefono2 = $cliente->persona->telefonos->skip(1)->first()?->numero ?? 'N/A';

        // Construir dirección completa
        $direccionCompleta = 'N/A';
        if ($direccion) {
            $direccionCompleta = trim(
                ($direccion->direccion ?? '') . ' ' .
                ($direccion->numero ? 'N° ' . $direccion->numero : '') . ' ' .
                ($direccion->referencia ? '- ' . $direccion->referencia : '')
            );
        }

        return [
            $cliente->persona->documento ?? 'N/A',
            trim($cliente->persona->nombres . ' ' . $cliente->persona->ape_pat . ' ' . $cliente->persona->ape_mat),
            $telefono1,
            $telefono2,
            $zona?->nombre ?? 'N/A',
            $direccion?->sucursal?->sucursal ?? 'N/A',
            $direccionCompleta,
            $jcc ? trim($jcc->persona->nombres . ' ' . $jcc->persona->ape_pat) : 'Sin asignar',
            $asesor ? trim($asesor->persona->nombres . ' ' . $asesor->persona->ape_pat) : 'Sin asignar',
            $analista ? trim($analista->persona->nombres . ' ' . $analista->persona->ape_pat) : 'Sin asignar',
            $cliente->prestamos_count ?? 0,
            'S/ ' . number_format($cliente->prestamos_sum_cantidad_solicitada ?? 0, 2),
            $prestamoReciente?->fecha_atencion?->format('d/m/Y') ?? 'N/A',
            $prestamoReciente?->estado ?? 'N/A'
        ];
    }

    /**
     * Aplica estilos a la hoja de Excel
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la fila de encabezados
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ]
            ],
        ];
    }
}
