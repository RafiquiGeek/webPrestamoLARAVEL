<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\Prestamo;
use App\Models\Cliente;

/**
 * Mapper para convertir datos de préstamo/cuota a formato JSON que SIRE espera
 * 
 * Convierte cuotas de préstamos y datos del cliente a payload JSON
 * compatible con el endpoint /api/sire/enviar-json de SIRE
 */
class SireJsonMapper
{
    /**
     * Mapear una cuota y sus datos asociados a formato SIRE JSON
     *
     * @param Cuota $cuota
     * @param Prestamo $prestamo
     * @param Cliente $cliente
     * @return array Payload JSON para SIRE
     */
    public static function mapCuotaToSireJson(Cuota $cuota, Prestamo $prestamo, Cliente $cliente): array
    {
        // Calcular montos desglosados - usando los nombres correctos de campos del modelo Cuota
        $capital = $cuota->pago_capital ?? 0; // Campo correcto: pago_capital, no capital
        $interes = $cuota->interes ?? 0;
        $comision = $cuota->comision ?? 0;
        $seguro = $cuota->gas ?? 0; // Campo correcto: gas, no seguro

        // Construir items del comprobante
        $items = [];
        $totalExonerado = 0;
        $totalGravado = 0;
        $totalIgv = 0;

        // Item: Capital (EXONERADO - no paga IGV según Ley del IGV)
        if ($capital > 0) {
            $items[] = [
                'codProducto' => 'CAPITAL',
                'unidad' => 'NIU',
                'cantidad' => 1,
                'descripcion' => "Capital - Cuota No. {$cuota->numero} - Préstamo {$prestamo->numero_prestamo}",
                'mtoValorUnitario' => round($capital, 2),
                'mtoPrecioUnitario' => round($capital, 2), // Igual al valor porque está exonerado
                'mtoValorVenta' => round($capital, 2),
                'mtoBaseIgv' => round($capital, 2), // Base imponible (requerido incluso para exonerado)
                'porcentajeIgv' => 0,
                'igv' => 0,
                'tipAfeIgv' => '20', // 20 = Exonerado - Operación Onerosa (Catálogo 07 SUNAT)
                'totalImpuestos' => 0,
            ];
            $totalExonerado += $capital;
        }

        // Item: Interés (EXONERADO - Los intereses de préstamos NO pagan IGV según Ley del IGV Art. 2)
        if ($interes > 0) {
            $items[] = [
                'codProducto' => 'INTERES',
                'unidad' => 'NIU',
                'cantidad' => 1,
                'descripcion' => "Interés - Cuota No. {$cuota->numero} - Préstamo {$prestamo->numero_prestamo}",
                'mtoValorUnitario' => round($interes, 2),
                'mtoPrecioUnitario' => round($interes, 2), // Igual al valor porque está exonerado
                'mtoValorVenta' => round($interes, 2),
                'mtoBaseIgv' => round($interes, 2), // Base imponible (requerido incluso para exonerado)
                'porcentajeIgv' => 0,
                'igv' => 0,
                'tipAfeIgv' => '20', // 20 = Exonerado - Operación Onerosa - Intereses financieros (Catálogo 07 SUNAT)
                'totalImpuestos' => 0,
            ];
            $totalExonerado += $interes;
        }

        // Item: Comisión (GRAVADO con IGV si es servicio administrativo)
        if ($comision > 0) {
            $igvComision = round($comision * 0.18, 2);
            $precioComision = round($comision * 1.18, 2); // Precio con IGV incluido
            $items[] = [
                'codProducto' => 'COMISION',
                'unidad' => 'NIU',
                'cantidad' => 1,
                'descripcion' => "Comisión por gestión - Cuota No. {$cuota->numero}",
                'mtoValorUnitario' => round($comision, 2),
                'mtoPrecioUnitario' => $precioComision, // Valor + IGV
                'mtoValorVenta' => round($comision, 2),
                'mtoBaseIgv' => round($comision, 2),
                'porcentajeIgv' => 18,
                'igv' => $igvComision,
                'tipAfeIgv' => '10', // 10 = Gravado - Operación Onerosa
                'totalImpuestos' => $igvComision,
            ];
            $totalGravado += $comision;
            $totalIgv += $igvComision;
        }

        // Item: Seguro (EXONERADO típicamente)
        if ($seguro > 0) {
            $items[] = [
                'codProducto' => 'SEGURO',
                'unidad' => 'NIU',
                'cantidad' => 1,
                'descripcion' => "Seguro - Cuota No. {$cuota->numero}",
                'mtoValorUnitario' => round($seguro, 2),
                'mtoPrecioUnitario' => round($seguro, 2), // Igual al valor porque está exonerado
                'mtoValorVenta' => round($seguro, 2),
                'mtoBaseIgv' => round($seguro, 2), // Base imponible (requerido incluso para exonerado)
                'porcentajeIgv' => 0,
                'igv' => 0,
                'tipAfeIgv' => '20', // 20 = Exonerado - Operación Onerosa (Catálogo 07 SUNAT)
                'totalImpuestos' => 0,
            ];
            $totalExonerado += $seguro;
        }

        // Si no hay items, usar monto total de cuota
        if (empty($items)) {
            $items[] = [
                'codProducto' => 'PAGO',
                'unidad' => 'NIU',
                'cantidad' => 1,
                'descripcion' => "Pago de cuota No. {$cuota->numero} - Préstamo {$prestamo->numero_prestamo}",
                'mtoValorUnitario' => round($cuota->monto, 2), // Campo correcto: monto, no monto_cuota
                'mtoPrecioUnitario' => round($cuota->monto, 2), // Igual al valor porque está exonerado
                'mtoValorVenta' => round($cuota->monto, 2), // Campo correcto: monto, no monto_cuota
                'mtoBaseIgv' => round($cuota->monto, 2), // Base imponible (requerido incluso para exonerado)
                'porcentajeIgv' => 0,
                'igv' => 0,
                'tipAfeIgv' => '20', // 20 = Exonerado - Operación Onerosa (Catálogo 07 SUNAT)
                'totalImpuestos' => 0,
            ];
            $totalExonerado += $cuota->monto; // Campo correcto: monto, no monto_cuota
        }

        // Determinar tipo de comprobante según tipo de documento del cliente
        // Catálogo 06 SUNAT: 1=DNI, 6=RUC, 4=Carnet de Extranjería, 7=Pasaporte
        // Tipo Comprobante: 01=Factura (solo RUC), 03=Boleta (DNI u otros)
        $tipoComprobante = ($cliente->tipo_documento === '6') ? '01' : '03';

        // Obtener configuración activa para las series
        $config = \App\Models\ConfiguracionSunat::obtenerActiva();

        // Determinar serie y numeración según ambiente (TEST/PRODUCCIÓN)
        $serie = 'B001'; // Default backup
        $correlativo = 1; // Default backup

        if ($config) {
            $esProduccion = $config->modo_produccion ?? false;
            $esFactura = ($tipoComprobante === '01');

            if ($esProduccion) {
                // PRODUCCIÓN
                if ($esFactura) {
                    $serie = $config->sire_serie_factura_prod ?? 'F001';
                    $correlativo = $config->sire_numero_factura_prod ?? 1;
                } else {
                    $serie = $config->sire_serie_boleta_prod ?? 'B002';
                    $correlativo = $config->sire_numero_boleta_prod ?? 1;
                }
            } else {
                // TESTING
                if ($esFactura) {
                    $serie = $config->sire_serie_factura_test ?? 'T001';
                    $correlativo = $config->sire_numero_factura_test ?? 1;
                } else {
                    $serie = $config->sire_serie_boleta_test ?? 'T001';
                    $correlativo = $config->sire_numero_boleta_test ?? 1;
                }
            }

            // CRÍTICO: Verificar el último comprobante REAL emitido en la BD
            // Esto previene duplicados si la configuración se desincroniza durante merge/deploy
            $ultimoComprobante = \App\Models\Comprobante::where('serie', $serie)
                ->where('tipo_comprobante', $tipoComprobante)
                ->orderBy('numero', 'desc')
                ->first();

            if ($ultimoComprobante) {
                // Usar el siguiente número después del último emitido
                $numeroReal = (int) $ultimoComprobante->numero + 1;

                // Usar el mayor entre el configurado y el real
                $correlativo = max($correlativo, $numeroReal);

                \Log::info('Número de comprobante ajustado según BD', [
                    'serie' => $serie,
                    'numero_config' => $esProduccion
                        ? ($esFactura ? $config->sire_numero_factura_prod : $config->sire_numero_boleta_prod)
                        : ($esFactura ? $config->sire_numero_factura_test : $config->sire_numero_boleta_test),
                    'ultimo_bd' => $ultimoComprobante->numero,
                    'numero_final' => $correlativo,
                ]);
            }
        }

        // Calcular totales
        $subTotal = $totalExonerado + $totalGravado;
        $montoTotal = $subTotal + $totalIgv;

        // Fecha y hora de emisión: SIEMPRE usar la fecha y hora actual (momento de generación del comprobante)
        $fechaEmision = now();

        // Payload final según estructura SUNAT GEM (Gestión Electrónica de Comprobantes)
        return [
            'ublVersion' => '2.1',
            'tipoOperacion' => '0101', // 0101 = Venta Interna
            'tipoDoc' => $tipoComprobante,
            'serie' => $serie,
            'correlativo' => (int) $correlativo, // Numeración secuencial según configuración
            'fechaEmision' => $fechaEmision->format('Y-m-d'),
            'horEmision' => $fechaEmision->format('H:i:s'),
            'fecVencimiento' => $fechaEmision->format('Y-m-d'),
            'tipoMoneda' => 'PEN',

            // Datos del cliente
            'client' => [
                'tipoDoc' => $cliente->tipo_documento,
                'numDoc' => $cliente->numero_documento,
                'rznSocial' => $cliente->nombre_completo ?? $cliente->nombre,
                'address' => [
                    'direccion' => $cliente->direccion ?? '-',
                    'provincia' => $cliente->provincia ?? 'LIMA',
                    'departamento' => $cliente->departamento ?? 'LIMA',
                    'distrito' => $cliente->distrito ?? 'LIMA',
                    'ubigeo' => $cliente->ubigeo ?? '150101',
                ],
            ],

            // Datos de la empresa emisora (se obtienen de config)
            'company' => [
                'ruc' => $config->ruc ?? '',
                'razonSocial' => $config->razon_social ?? '',
                'nombreComercial' => $config->nombre_comercial ?? '',
                'address' => [
                    'direccion' => $config->direccion ?? '',
                    'provincia' => $config->provincia ?? 'LIMA',
                    'departamento' => $config->departamento ?? 'LIMA',
                    'distrito' => $config->distrito ?? 'LIMA',
                    'ubigeo' => $config->ubigeo ?? '150101',
                ],
            ],

            // Items/Detalles
            'details' => $items,

            // Totales
            'mtoOperGravadas' => round($totalGravado, 2),
            'mtoOperExoneradas' => round($totalExonerado, 2),
            'mtoOperInafectas' => 0.00,
            'mtoIGV' => round($totalIgv, 2),
            'totalImpuestos' => round($totalIgv, 2),
            'valorVenta' => round($subTotal, 2),
            'subTotal' => round($subTotal, 2),
            'mtoImpVenta' => round($montoTotal, 2),

            // Leyendas
            'legends' => [
                [
                    'code' => '1000',
                    'value' => 'SON ' . self::numeroALetras($montoTotal) . ' SOLES',
                ],
            ],
        ];
    }

    /**
     * Mapear múltiples cuotas (para reportes o envíos en lote)
     * 
     * @param array $cuotas Array de Cuota objects
     * @return array Array de payloads JSON
     */
    public static function mapMultipleCuotasToSireJson(array $cuotas): array
    {
        return collect($cuotas)
            ->map(fn($cuota) => self::mapCuotaToSireJson(
                $cuota,
                $cuota->prestamo,
                $cuota->prestamo->cliente
            ))
            ->all();
    }

    /**
     * Validar que los datos requeridos estén presentes antes de mapear
     *
     * @param Cuota $cuota
     * @param Prestamo $prestamo
     * @param Cliente $cliente
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateBeforeMapping(Cuota $cuota, Prestamo $prestamo, Cliente $cliente): array
    {
        $errors = [];

        // Validar tipo de documento (Catálogo 06 SUNAT)
        if (!$cliente->tipo_documento) {
            $errors[] = 'Cliente: tipo_documento requerido (1=DNI, 6=RUC, 4=Carnet Extranjería, 7=Pasaporte)';
        }

        // Validar número de documento según tipo
        if (!$cliente->numero_documento) {
            $errors[] = 'Cliente: numero_documento requerido';
        } else {
            // Validar formato según tipo de documento
            if ($cliente->tipo_documento === '1' && strlen($cliente->numero_documento) !== 8) {
                $errors[] = 'Cliente: DNI debe tener 8 dígitos';
            }
            if ($cliente->tipo_documento === '6' && strlen($cliente->numero_documento) !== 11) {
                $errors[] = 'Cliente: RUC debe tener 11 dígitos';
            }
        }

        if (!$cliente->nombre_completo && !$cliente->nombre) {
            $errors[] = 'Cliente: nombre o nombre_completo requerido';
        }

        if (!$prestamo->numero_prestamo) {
            $errors[] = 'Préstamo: numero_prestamo requerido';
        }

        if (!$cuota->numero) {
            $errors[] = 'Cuota: numero requerido';
        }

        if ($cuota->monto <= 0) {
            $errors[] = 'Cuota: monto debe ser mayor a 0';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Convertir número a letras para leyendas de SUNAT
     *
     * @param float $numero
     * @return string
     */
    private static function numeroALetras(float $numero): string
    {
        $entero = floor($numero);
        $decimales = round(($numero - $entero) * 100);

        $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        if ($entero == 0) {
            return 'CERO Y ' . sprintf('%02d', $decimales) . '/100';
        }

        $letras = '';

        // Millones
        if ($entero >= 1000000) {
            $millones = floor($entero / 1000000);
            if ($millones == 1) {
                $letras .= 'UN MILLON ';
            } else {
                $letras .= self::convertirGrupo($millones) . ' MILLONES ';
            }
            $entero %= 1000000;
        }

        // Miles
        if ($entero >= 1000) {
            $miles = floor($entero / 1000);
            if ($miles == 1) {
                $letras .= 'MIL ';
            } else {
                $letras .= self::convertirGrupo($miles) . ' MIL ';
            }
            $entero %= 1000;
        }

        // Centenas, decenas y unidades
        if ($entero > 0) {
            $letras .= self::convertirGrupo($entero);
        }

        return trim($letras) . ' Y ' . sprintf('%02d', $decimales) . '/100';
    }

    /**
     * Convertir un grupo de hasta 3 dígitos a letras
     *
     * @param int $numero
     * @return string
     */
    private static function convertirGrupo(int $numero): string
    {
        $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        $letras = '';

        // Centenas
        $c = floor($numero / 100);
        if ($c > 0) {
            if ($numero == 100) {
                $letras .= 'CIEN';
            } else {
                $letras .= $centenas[$c] . ' ';
            }
            $numero %= 100;
        }

        // Decenas y unidades
        if ($numero >= 10 && $numero < 20) {
            $letras .= $especiales[$numero - 10];
        } else {
            $d = floor($numero / 10);
            $u = $numero % 10;

            if ($d > 0) {
                $letras .= $decenas[$d];
                if ($u > 0) {
                    $letras .= ' Y ' . $unidades[$u];
                }
            } else if ($u > 0) {
                $letras .= $unidades[$u];
            }
        }

        return trim($letras);
    }
}
