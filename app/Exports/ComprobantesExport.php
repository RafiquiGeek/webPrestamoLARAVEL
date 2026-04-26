<?php

namespace App\Exports;

use App\Models\Comprobante;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class ComprobantesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $request;

    public function __construct($request = null)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Comprobante::with(['cliente.persona', 'prestamo', 'cuota']);

        // Aplicar mismos filtros que en index
        if ($this->request && $this->request->filled('buscar')) {
            $buscar = $this->request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('serie', 'LIKE', "%{$buscar}%")
                  ->orWhere('numero', 'LIKE', "%{$buscar}%")
                  ->orWhereRaw("CONCAT(serie, '-', numero) LIKE ?", ["%{$buscar}%"]);
            });
        }

        if ($this->request && $this->request->filled('tipo')) {
            $query->where('tipo_comprobante', $this->request->tipo);
        }

        if ($this->request && $this->request->filled('estado')) {
            $query->where('estado', $this->request->estado);
        }

        if ($this->request && $this->request->filled('fecha_desde')) {
            $query->whereDate('fecha_emision', '>=', $this->request->fecha_desde);
        }

        if ($this->request && $this->request->filled('fecha_hasta')) {
            $query->whereDate('fecha_emision', '<=', $this->request->fecha_hasta);
        }

        return $query->orderBy('created_at', 'desc')->get();
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
            'Fecha Creación',
            'Fecha Actualización',
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
                    \Log::warning('Failed to decode items JSON in ComprobantesExport', [
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

        return [
            $comprobante->id,
            $comprobante->numero_completo,
            $comprobante->tipo_comprobante == '01' ? 'FACTURA' : 'BOLETA',
            $comprobante->serie,
            $comprobante->numero,
            $comprobante->cliente->persona->documento ?? 'N/A',
            $comprobante->cliente->persona->nombres ?? 'N/A',
            $comprobante->cliente->persona->ape_pat ?? 'N/A',
            $comprobante->cliente->persona->ape_mat ?? 'N/A',
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
            $comprobante->hash ?? 'N/A',
            $comprobante->created_at->format('d/m/Y H:i:s'),
            $comprobante->updated_at->format('d/m/Y H:i:s'),
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
