<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GestionesExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Cliente',
            'Préstamo',
            'Estado de Gestión',
            'Fecha',
            'Observaciones',
            'Tiene Compromiso',
            'Estado Compromiso',
            'Monto Compromiso',
            'Fecha Compromiso',
            'Registrado por',
            'Ubicación',
            'Fecha de Registro',
        ];
    }

    public function map($gestion): array
    {
        // Obtener nombres del cliente si es posible
        $cliente = $gestion->prestamo && $gestion->prestamo->cliente && $gestion->prestamo->cliente->persona
            ? $gestion->prestamo->cliente->persona->nombres.' '.$gestion->prestamo->cliente->persona->ape_pat.' '.$gestion->prestamo->cliente->persona->ape_mat
            : 'No disponible';

        // Obtener información del compromiso
        $tieneCompromiso = $gestion->compromiso ? 'Sí' : 'No';
        $estadoCompromiso = 'N/A';
        $montoCompromiso = 'N/A';
        $fechaCompromiso = 'N/A';

        if ($gestion->compromiso) {
            if ($gestion->compromiso->estado == \App\Models\Compromiso::ESTADO_PENDIENTE) {
                $estadoCompromiso = 'Pendiente';
            } elseif ($gestion->compromiso->estado == \App\Models\Compromiso::ESTADO_PAGADO) {
                $estadoCompromiso = 'Pagado';
            } elseif ($gestion->compromiso->estado == \App\Models\Compromiso::ESTADO_POSTERGADO) {
                $estadoCompromiso = 'Postergado';
            }

            $montoCompromiso = $gestion->compromiso->monto;

            if ($gestion->compromiso->fecha_compromiso_pago) {
                $fechaCompromiso = Carbon::parse($gestion->compromiso->fecha_compromiso_pago)->format('d/m/Y');

                if ($gestion->compromiso->hora) {
                    $fechaCompromiso .= ' '.Carbon::parse($gestion->compromiso->hora)->format('H:i');
                }
            }
        }

        // Obtener ubicación formateada
        $ubicacion = $gestion->latitud && $gestion->longitud
            ? "{$gestion->latitud}, {$gestion->longitud}"
            : 'No registrada';

        // Obtener nombre del asesor
        $asesor = $gestion->asesor ? $gestion->asesor->name : 'No registrado';

        return [
            $gestion->id,
            $cliente,
            $gestion->prestamo ? $gestion->prestamo->id : 'No disponible',
            $gestion->estadoGestion ? $gestion->estadoGestion->estado : 'Sin estado',
            $gestion->fecha ? $gestion->fecha->format('d/m/Y H:i') : 'No registrada',
            $gestion->observaciones,
            $tieneCompromiso,
            $estadoCompromiso,
            $montoCompromiso,
            $fechaCompromiso,
            $asesor,
            $ubicacion,
            $gestion->created_at ? $gestion->created_at->format('d/m/Y H:i:s') : 'No registrada',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para el encabezado
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0056B3']],
                'alignment' => ['horizontal' => 'center'],
            ],
            // Borde para todas las celdas
            'A1:M'.($this->query->count() + 1) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD'],
                    ],
                ],
            ],
        ];
    }
}
