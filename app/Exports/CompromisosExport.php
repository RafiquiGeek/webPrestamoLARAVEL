<?php

namespace App\Exports;

use App\Models\Compromiso;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CompromisosExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected $compromisos;

    public function __construct($compromisos)
    {
        $this->compromisos = $compromisos;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->compromisos;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Cliente',
            'Fecha de Compromiso',
            'Hora',
            'Monto',
            'Estado',
            'JCC',
            'Asesor',
            'Analista',
            'Días Restantes',
            'Comentario',
            'Fecha de Registro',
        ];
    }

    /**
     * @param  mixed  $row
     */
    public function map($compromiso): array
    {
        // Calcular días restantes
        $fechaCompromiso = Carbon::parse($compromiso->fecha_compromiso_pago);
        $hoy = Carbon::now();
        $diasRestantes = $hoy->diffInDays($fechaCompromiso, false);

        // Formatear estado
        $estados = [
            Compromiso::ESTADO_PENDIENTE => 'Pendiente',
            Compromiso::ESTADO_PAGADO => 'Pagado',
            Compromiso::ESTADO_POSTERGADO => 'Postergado',
        ];

        // Obtener JCC, Asesor y Analista activos
        $jccName = $compromiso->jcc_activo ?
            ($compromiso->jcc_activo->persona->nombres ?? 'N/A').' '.
            ($compromiso->jcc_activo->persona->ape_pat ?? '') : 'N/A';

        $asesorName = $compromiso->asesor_activo ?
            ($compromiso->asesor_activo->persona->nombres ?? 'N/A').' '.
            ($compromiso->asesor_activo->persona->ape_pat ?? '') : 'N/A';

        $analistaName = $compromiso->analista_activo ?
            ($compromiso->analista_activo->persona->nombres ?? 'N/A').' '.
            ($compromiso->analista_activo->persona->ape_pat ?? '') : 'N/A';

        return [
            $compromiso->id,
            // Cliente
            ($compromiso->prestamo->cliente->persona->nombres ?? 'N/A').' '.
            ($compromiso->prestamo->cliente->persona->ape_pat ?? '').' '.
            ($compromiso->prestamo->cliente->persona->ape_mat ?? ''),
            // Fecha
            Carbon::parse($compromiso->fecha_compromiso_pago)->format('d/m/Y'),
            // Hora
            Carbon::parse($compromiso->hora)->format('H:i'),
            // Monto
            number_format($compromiso->monto, 2),
            // Estado
            $estados[$compromiso->estado] ?? 'Desconocido',
            // JCC
            $jccName,
            // Asesor
            $asesorName,
            // Analista
            $analistaName,
            // Días restantes
            $diasRestantes,
            // Comentario
            $compromiso->comentario ?? '',
            // Fecha de registro
            Carbon::parse($compromiso->fecha_registro)->format('d/m/Y H:i'),
        ];
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

                // Aplicar formato condicional para días restantes (columna J, skip header)
                $lastRow = count($this->compromisos) + 1;

                // Formato para células vencidas (menos de 0 días) - Rojo
                $sheet->getStyle('J2:J'.$lastRow)->getConditionalStyles()[] = $this->createConditionalFormat('J2:J'.$lastRow, '<', '0', 'FFEB9C9C');

                // Formato para el mismo día (exactamente 0 días) - Amarillo
                $sheet->getStyle('J2:J'.$lastRow)->getConditionalStyles()[] = $this->createConditionalFormat('J2:J'.$lastRow, '=', '0', 'FFFCE699');

                // Formato para los próximos 2 días (entre 1 y 2 días) - Naranja claro
                $sheet->getStyle('J2:J'.$lastRow)->getConditionalStyles()[] = $this->createConditionalFormatRange('J2:J'.$lastRow, '1', '2', 'FFFFD799');

                // Formato para más de 3 días - Verde claro
                $sheet->getStyle('J2:J'.$lastRow)->getConditionalStyles()[] = $this->createConditionalFormat('J2:J'.$lastRow, '>', '2', 'FFC6E0B4');

                // Aplicar bordes a toda la tabla
                $sheet->getStyle('A1:L'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Auto-fit
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(10);
                $event->sheet->getDelegate()->getColumnDimension('E')->setWidth(12);
                $event->sheet->getDelegate()->getColumnDimension('K')->setWidth(40);
            },
        ];
    }

    /**
     * Helper para crear formato condicional (versión compatible)
     */
    private function createConditionalFormat($range, $operator, $value, $bgColor)
    {
        $conditional = new Conditional;
        $conditional->setConditionType(Conditional::CONDITION_CELLIS);
        $conditional->setOperatorType($operator);
        $conditional->addCondition($value);
        $conditional->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
        $conditional->getStyle()->getFill()->getStartColor()->setARGB($bgColor);

        return $conditional;
    }

    /**
     * Helper para crear formato condicional con rango (versión compatible)
     */
    private function createConditionalFormatRange($range, $minValue, $maxValue, $bgColor)
    {
        $conditional = new Conditional;
        $conditional->setConditionType(Conditional::CONDITION_CELLIS);
        $conditional->setOperatorType(Conditional::OPERATOR_BETWEEN);
        $conditional->addCondition($minValue);
        $conditional->addCondition($maxValue);
        $conditional->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
        $conditional->getStyle()->getFill()->getStartColor()->setARGB($bgColor);

        return $conditional;
    }
}
