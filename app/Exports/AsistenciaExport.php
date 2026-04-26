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

class AsistenciaExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
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
            'Fecha',
            'Día de la Semana',
            'Empleado',
            'Código',
            'Área Laboral',
            'Horario Programado',
            'Entrada Registrada',
            'Salida Registrada',
            'Estado Entrada',
            'Estado Salida',
            'Minutos Tardanza',
            'Horas Trabajadas',
            'Inicio Refrigerio',
            'Fin Refrigerio',
            'Duración Refrigerio (min)',
            'Estado Refrigerio',
            'Ubicación Entrada',
            'Ubicación Salida',
            'Observaciones',
            'Fecha de Registro',
        ];
    }

    public function map($registro): array
    {
        // Calcular duración del refrigerio
        $duracionRefrigerio = 0;
        if ($registro->inicio_refrigerio && $registro->fin_refrigerio) {
            $duracionRefrigerio = Carbon::parse($registro->inicio_refrigerio)
                ->diffInMinutes(Carbon::parse($registro->fin_refrigerio));
        }

        // Formatear ubicaciones
        $ubicacionEntrada = ($registro->latitud_entrada && $registro->longitud_entrada)
            ? "{$registro->latitud_entrada}, {$registro->longitud_entrada}"
            : 'No registrada';

        $ubicacionSalida = ($registro->latitud_salida && $registro->longitud_salida)
            ? "{$registro->latitud_salida}, {$registro->longitud_salida}"
            : 'No registrada';

        // Horario programado
        $horarioProgramado = '';
        if ($registro->asignacion && $registro->asignacion->horarioTrabajo) {
            $horarioProgramado = Carbon::parse($registro->asignacion->horarioTrabajo->hora_entrada)->format('H:i').
                ' - '.Carbon::parse($registro->asignacion->horarioTrabajo->hora_salida)->format('H:i');
        }

        return [
            $registro->fecha->format('d/m/Y'),
            $registro->fecha->locale('es')->isoFormat('dddd'),
            $registro->usuario ? $registro->usuario->name : 'No disponible',
            $registro->usuario ? $registro->usuario->codigo : 'No disponible',
            ($registro->asignacion && $registro->asignacion->areaLaboral)
                ? $registro->asignacion->areaLaboral->nombre
                : 'No asignada',
            $horarioProgramado,
            $registro->hora_entrada ? Carbon::parse($registro->hora_entrada)->format('H:i:s') : 'Sin registro',
            $registro->hora_salida ? Carbon::parse($registro->hora_salida)->format('H:i:s') : 'Pendiente',
            ucfirst($registro->estado_entrada),
            $registro->hora_salida ? ucfirst($registro->estado_salida) : 'Pendiente',
            $registro->minutos_tardanza ?? 0,
            $registro->tieneAsistenciaCompleta() ? $registro->calcularHorasTrabajadas() : 0,
            $registro->inicio_refrigerio ? Carbon::parse($registro->inicio_refrigerio)->format('H:i:s') : 'Sin registro',
            $registro->fin_refrigerio ? Carbon::parse($registro->fin_refrigerio)->format('H:i:s') : 'Sin registro',
            $duracionRefrigerio,
            $registro->estado_refrigerio ? ucfirst($registro->estado_refrigerio) : 'Normal',
            $ubicacionEntrada,
            $ubicacionSalida,
            $registro->observaciones ?: 'Sin observaciones',
            $registro->created_at ? $registro->created_at->format('d/m/Y H:i:s') : 'No registrada',
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
            'A1:T'.($this->query->count() + 1) => [
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
