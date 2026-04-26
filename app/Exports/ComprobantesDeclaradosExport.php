<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ComprobantesDeclaradosExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $comprobantes;

    public function __construct($comprobantes)
    {
        $this->comprobantes = $comprobantes;
    }

    public function collection()
    {
        return $this->comprobantes;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Número Completo',
            'Tipo',
            'Serie',
            'Número',
            'Cliente - DNI/RUC',
            'Cliente - Nombres',
            'Cliente - Apellido Paterno',
            'Cliente - Apellido Materno',
            'Fecha Emisión',
            'Moneda',
            'GRAVADA',
            'IGV',
            'EXONERADO',
            'TOTAL',
            'Estado',
            'Código Error',
            'Mensaje Error',
            'Préstamo ID',
            'Cuota ID',
            'Items Detalle',
            'Tiene XML',
            'Tiene CDR',
            'Hash',
            'Observaciones',
            'Fecha Creación',
        ];
    }

    public function map($comprobante): array
    {
        // Calcular montos por tipo de afectación IGV
        $exonerado = 0;
        $gravado = 0;
        $igv = 0;
        $itemsDetalle = '';

        // Si el comprobante tiene una cuota asociada, usar los valores calculados de la cuota
        if ($comprobante->cuota_id && $comprobante->cuota) {
            $cuota = $comprobante->cuota;

            // Usar valores directamente de la tabla cuotas (ya calculados correctamente)
            $gravado = ($cuota->interes ?? 0) + ($cuota->comision ?? 0); // Ya sin IGV
            $igv = $cuota->igv ?? 0; // IGV ya calculado
            $exonerado = $cuota->pago_capital ?? 0; // Capital exonerado

            // Crear detalle de items basado en la cuota
            $itemDescriptions = [];
            if ($cuota->pago_capital > 0) {
                $itemDescriptions[] = "Capital - Cuota No. {$cuota->numero} (Cant: 1, Unit: {$cuota->pago_capital}, Tipo: Exonerado)";
            }
            if ($cuota->interes > 0) {
                $itemDescriptions[] = "Interes - Cuota No. {$cuota->numero} (Cant: 1, Unit: {$cuota->interes}, Tipo: Gravado)";
            }
            if ($cuota->comision > 0) {
                $itemDescriptions[] = "Comision - Cuota No. {$cuota->numero} (Cant: 1, Unit: {$cuota->comision}, Tipo: Gravado)";
            }
            $itemsDetalle = implode(' | ', $itemDescriptions);

            \Log::info('Usando valores de cuota para comprobante', [
                'comprobante_id' => $comprobante->id,
                'cuota_id' => $cuota->id,
                'gravado' => $gravado,
                'igv' => $igv,
                'exonerado' => $exonerado,
                'total' => $cuota->monto
            ]);
        } else {
            // Lógica anterior para comprobantes sin cuota asociada
            // Obtener items como array
            $itemsArray = [];
            if ($comprobante->items && is_array($comprobante->items)) {
                $itemsArray = $comprobante->items;
            } elseif ($comprobante->items && is_string($comprobante->items)) {
                // Intentar decodificar JSON si viene como string
                $decoded = json_decode($comprobante->items, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $itemsArray = $decoded;
                } else {
                    \Log::warning('Failed to decode items JSON in ComprobantesDeclaradosExport', [
                        'comprobante_id' => $comprobante->id,
                        'json_error' => json_last_error_msg(),
                        'raw_value' => substr($comprobante->items, 0, 200)
                    ]);
                }
            }

            if (!empty($itemsArray)) {
                $itemDescriptions = [];
                foreach ($itemsArray as $item) {
                    $valorUnitario = $item['valor_unitario'] ?? 0;
                    $cantidad = $item['cantidad'] ?? 1;
                    $tipoAfectacion = $item['tipo_afectacion_igv'] ?? '10'; // Por defecto gravado
                    $descripcion = $item['descripcion'] ?? '';

                    $subtotal = $valorUnitario * $cantidad;

                    // Verificar tipos de afectación más comunes
                    if (in_array($tipoAfectacion, ['20', '21', '30', '31'])) { // Exonerado y similares
                        $exonerado += $subtotal;
                    } elseif (in_array($tipoAfectacion, ['10', '11', '12', '13', '14', '15', '16', '17'])) { // Gravado y similares
                        $gravado += $subtotal;
                        $igv += $subtotal * 0.18; // IGV 18%
                    }

                    $tipoTexto = match($tipoAfectacion) {
                        '10' => 'Gravado',
                        '20' => 'Exonerado',
                        '21' => 'Exonerado - Transferencia',
                        '30' => 'Inafecto',
                        '31' => 'Inafecto - Retiro',
                        default => 'Otro (' . $tipoAfectacion . ')'
                    };

                    $itemDescriptions[] = sprintf(
                        "%s (Cant: %s, Unit: %.2f, Tipo: %s)",
                        $descripcion,
                        $cantidad,
                        $valorUnitario,
                        $tipoTexto
                    );
                }
                $itemsDetalle = implode(' | ', $itemDescriptions);
            } else {
                // Si no hay items, intentar calcular desde el total (asumiendo todo gravado)
                $total = $comprobante->total ?? 0;
                if ($total > 0) {
                    $gravado = $total / 1.18;
                    $igv = $total - $gravado;
                    $itemsDetalle = 'Items no detallados - cálculo aproximado';
                }
            }
        }

        $cliente = $comprobante->cliente && $comprobante->cliente->persona
            ? trim($comprobante->cliente->persona->nombres . ' ' .
                   $comprobante->cliente->persona->ape_pat . ' ' .
                   $comprobante->cliente->persona->ape_mat)
            : 'N/A';

        $documento = $comprobante->cliente && $comprobante->cliente->persona
            ? $comprobante->cliente->persona->documento
            : '';

        $nombres = $comprobante->cliente && $comprobante->cliente->persona
            ? $comprobante->cliente->persona->nombres
            : 'N/A';

        $apePat = $comprobante->cliente && $comprobante->cliente->persona
            ? $comprobante->cliente->persona->ape_pat
            : 'N/A';

        $apeMat = $comprobante->cliente && $comprobante->cliente->persona
            ? $comprobante->cliente->persona->ape_mat
            : 'N/A';

        return [
            $comprobante->id,
            $comprobante->numero_completo,
            $comprobante->tipo_comprobante == '01' ? 'FACTURA' : 'BOLETA',
            $comprobante->serie,
            str_pad($comprobante->numero, 6, '0', STR_PAD_LEFT),
            $documento,
            $nombres,
            $apePat,
            $apeMat,
            $comprobante->fecha_emision->format('d/m/Y H:i:s'),
            $comprobante->moneda,
            number_format($gravado, 2),    // GRAVADA
            number_format($igv, 2),        // IGV
            number_format($exonerado, 2),  // EXONERADO
            $comprobante->total,            // TOTAL
            $comprobante->estado,
            $comprobante->codigo_error ?? 'N/A',
            $comprobante->mensaje_error ?? 'N/A',
            $comprobante->prestamo_id ?? 'N/A',
            $comprobante->cuota_id ?? 'N/A',
            $itemsDetalle,
            $comprobante->xml_content ? 'SÍ' : 'NO',
            $comprobante->cdr_zip ? 'SÍ' : 'NO',
            $comprobante->hash ? substr($comprobante->hash, 0, 32) . '...' : '',
            $comprobante->observaciones ?? '',
            $comprobante->created_at->format('d/m/Y H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la fila de encabezados
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
