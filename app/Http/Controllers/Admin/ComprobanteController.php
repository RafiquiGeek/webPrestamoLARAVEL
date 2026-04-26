<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionSunat;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ComprobanteController extends Controller
{
    public function generarFactura($prestamo)
    {
        try {
            // Obtener configuración SUNAT activa
            $config = ConfiguracionSunat::obtenerActiva();
            if (!$config) {
                throw new \Exception('No hay configuración SUNAT activa. Por favor, configure SUNAT primero.');
            }

            // Verificar que exista certificado
            if (!$config->hasCertificateFile()) {
                throw new \Exception('No hay certificado configurado. Por favor, cargue un certificado válido.');
            }

            // Configurar Greenter
            $see = new See;
            $see->setService($config->sunat_endpoint);

            // Cargar certificado
            $certPath = $config->getCertificateFilePath();
            $password = $config->certificado_clave ?? '';

            $see->setCertificateFromFile($certPath, $password);

            // Configuración de la empresa desde la configuración
            $company = new Company;
            $company->setRuc($config->ruc)
                ->setRazonSocial($config->razon_social)
                ->setNombreComercial($config->nombre_comercial ?? $config->razon_social)
                ->setAddress((new Address)
                    ->setUbigueo($config->ubigeo)
                    ->setDistrito($config->distrito)
                    ->setProvincia($config->provincia)
                    ->setDepartamento($config->departamento)
                    ->setUrbanizacion('-')
                    ->setCodLocal('0000')
                    ->setDireccion($config->direccion));

            // Configuración del cliente
            $cliente = $prestamo->cliente;
            $client = new Client;
            $client->setTipoDoc('6') // RUC
                ->setNumDoc($cliente->ruc ?? '20000000001')
                ->setRznSocial($cliente->razon_social ?? 'CLIENTE DE PRUEBA S.A.');

            // Configuración de la factura
            $invoice = new Invoice;
            $invoice
                ->setUblVersion('2.1')
                ->setTipoOperacion('0101') // Venta interna
                ->setTipoDoc('01') // Factura
                ->setSerie('F001')
                ->setCorrelativo('123')
                ->setFechaEmision(new \DateTime)
                ->setCompany($company)
                ->setClient($client)
                ->setMtoOperGravadas($prestamo->cuotas->sum('interes') + $prestamo->cuotas->sum('comision')) // Interés + Comisión están gravados
                ->setMtoIGV($prestamo->cuotas->sum(function ($cuota) {
                    return round(($cuota->interes + ($cuota->comision ?? 0)) * 0.18, 2); // IGV = 18% de (interés + comisión)
                }))
                ->setTotalImpuestos($prestamo->cuotas->sum(function ($cuota) {
                    return round(($cuota->interes + ($cuota->comision ?? 0)) * 0.18, 2); // Mismo cálculo que IGV
                }))
                ->setValorVenta($prestamo->cuotas->sum('pago_capital') + $prestamo->cuotas->sum('interes') + $prestamo->cuotas->sum('comision')) // Valor sin IGV
                ->setSubTotal($prestamo->cuotas->sum('monto')) // Total con IGV
                ->setMtoImpVenta($prestamo->cuotas->sum('monto')); // Total con IGV

            // Agregar detalles de cada cuota del préstamo como "items"
            $detalles = [];
            foreach ($prestamo->cuotas as $cuota) {
                // Calcular IGV correcto: 18% de (interés + comisión)
                $baseGravada = $cuota->interes + ($cuota->comision ?? 0);
                $igvCalculado = round($baseGravada * 0.18, 2);
                $valorSinIgv = $cuota->pago_capital + $cuota->interes + ($cuota->comision ?? 0);

                $item = new SaleDetail;
                $item->setCodProducto('C'.$cuota->numero)
                    ->setUnidad('NIU')
                    ->setDescripcion('Cuota #'.$cuota->numero.' del Préstamo')
                    ->setCantidad(1)
                    ->setMtoValorUnitario($valorSinIgv) // Valor sin IGV (capital + interés + comisión)
                    ->setMtoBaseIgv($baseGravada) // Interés + Comisión como base para IGV
                    ->setPorcentajeIgv(18)
                    ->setIgv($igvCalculado) // IGV = 18% de (interés + comisión)
                    ->setTipAfeIgv('10') // Grava IGV
                    ->setTotalImpuestos($igvCalculado)
                    ->setMtoValorVenta($valorSinIgv)
                    ->setMtoPrecioUnitario($cuota->monto); // Precio total de la cuota (con IGV)

                $detalles[] = $item;
            }

            $invoice->setDetails($detalles);

            // Agregar leyenda (con el monto total en palabras)
            $montoTotal = $prestamo->cuotas->sum('monto');
            $legend = new Legend;
            $legend->setCode('1000')
                ->setValue('SON '.strtoupper($this->convertirNumeroALetras($montoTotal)).' SOLES');
            $invoice->setLegends([$legend]);

            // Enviar comprobante a SUNAT en modo prueba
            $result = $see->send($invoice);

            // Manejar resultados de prueba
            if (! $result->isSuccess()) {
                $error = $result->getError();
                Log::error("Error al enviar a SUNAT: {$error->getMessage()}");

                return response()->json(['error' => $error->getMessage()], 500);
            } else {
                // Generar XML para revisión
                $xmlContent = $see->getFactory()->getLastXml();
                File::put(storage_path('app/public/factura_prueba.xml'), $xmlContent);

                Log::info('Factura enviada exitosamente en modo prueba.');

                return response()->json(['message' => 'Envío exitoso (modo prueba)']);
            }
        } catch (\Exception $e) {
            Log::error("Error al generar la factura: {$e->getMessage()}");

            return response()->json(['error' => 'Ocurrió un error al generar la factura.'], 500);
        }
    }

    public function generarComprobanteDesembolso($operacion, $prestamo)
    {
        try {
            // Obtener configuración SUNAT activa
            $config = ConfiguracionSunat::obtenerActiva();
            if (!$config) {
                throw new \Exception('No hay configuración SUNAT activa. Por favor, configure SUNAT primero.');
            }

            // Verificar que exista certificado
            if (!$config->hasCertificateFile()) {
                throw new \Exception('No hay certificado configurado. Por favor, cargue un certificado válido.');
            }

            // Configurar Greenter
            $see = new See;
            $see->setService($config->sunat_endpoint);

            // Cargar certificado
            $certPath = $config->getCertificateFilePath();
            $password = $config->certificado_clave ?? '';

            $see->setCertificateFromFile($certPath, $password);

            // Configuración de la empresa desde la configuración
            $company = new Company;
            $company->setRuc($config->ruc)
                ->setRazonSocial($config->razon_social)
                ->setNombreComercial($config->nombre_comercial ?? $config->razon_social)
                ->setAddress((new Address)
                    ->setUbigueo($config->ubigeo)
                    ->setDistrito($config->distrito)
                    ->setProvincia($config->provincia)
                    ->setDepartamento($config->departamento)
                    ->setUrbanizacion('-')
                    ->setCodLocal('0000')
                    ->setDireccion($config->direccion));

            // Configuración del cliente
            $cliente = $prestamo->cliente;
            $persona = $cliente->persona;
            $client = new Client;
            $client->setTipoDoc('1') // DNI
                ->setNumDoc($persona->documento ?? '12345678')
                ->setRznSocial($persona->nombres.' '.$persona->ape_pat.' '.$persona->ape_mat);

            // Configuración del comprobante de desembolso
            $invoice = new Invoice;
            $invoice
                ->setUblVersion('2.1')
                ->setTipoOperacion('0101') // Venta interna
                ->setTipoDoc('03') // Boleta (para personas naturales)
                ->setSerie('B001')
                ->setCorrelativo(str_pad($operacion->id, 6, '0', STR_PAD_LEFT))
                ->setFechaEmision(new \DateTime($operacion->fecha))
                ->setCompany($company)
                ->setClient($client);

            // Calcular valores para el desembolso
            $montoDesembolso = $operacion->monto;
            $baseGravada = $montoDesembolso / 1.18; // Valor sin IGV
            $igv = $montoDesembolso - $baseGravada; // IGV = 18%

            $invoice
                ->setMtoOperGravadas($baseGravada)
                ->setMtoIGV($igv)
                ->setTotalImpuestos($igv)
                ->setValorVenta($baseGravada)
                ->setSubTotal($montoDesembolso)
                ->setMtoImpVenta($montoDesembolso);

            // Detalle del desembolso
            $item = new SaleDetail;
            $item->setCodProducto('DESEMB-'.$prestamo->id)
                ->setUnidad('ZZ') // Unidad: Mutuo
                ->setDescripcion('Desembolso de Préstamo N° '.$prestamo->id)
                ->setCantidad(1)
                ->setMtoValorUnitario($baseGravada)
                ->setMtoBaseIgv($baseGravada)
                ->setPorcentajeIgv(18)
                ->setIgv($igv)
                ->setTipAfeIgv('10') // Gravado - Operación Onerosa
                ->setTotalImpuestos($igv)
                ->setMtoValorVenta($baseGravada)
                ->setMtoPrecioUnitario($montoDesembolso);

            $invoice->setDetails([$item]);

            // Leyenda con monto en letras
            $legend = new Legend;
            $legend->setCode('1000')
                ->setValue('SON '.strtoupper($this->convertirNumeroALetras($montoDesembolso)).' SOLES');
            $invoice->setLegends([$legend]);

            // Enviar comprobante a SUNAT
            $result = $see->send($invoice);

            if (! $result->isSuccess()) {
                $error = $result->getError();
                Log::error("Error al enviar comprobante de desembolso a SUNAT: {$error->getMessage()}");

                return false;
            } else {
                // Generar y guardar XML
                $xmlContent = $see->getFactory()->getLastXml();
                $xmlPath = storage_path("app/public/comprobantes/desembolso_{$operacion->id}.xml");

                // Crear directorio si no existe
                if (! file_exists(dirname($xmlPath))) {
                    mkdir(dirname($xmlPath), 0755, true);
                }

                File::put($xmlPath, $xmlContent);

                Log::info("Comprobante de desembolso enviado exitosamente a SUNAT para operación {$operacion->id}");

                return [
                    'success' => true,
                    'xml_path' => $xmlPath,
                    'correlativo' => str_pad($operacion->id, 6, '0', STR_PAD_LEFT),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error al generar comprobante de desembolso: '.$e->getMessage());

            return false;
        }
    }

    private function convertirNumeroALetras($monto)
    {
        $formatter = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
        $texto = $formatter->format($monto);

        return strtoupper($texto).' SOLES';
    }
    public function verificarComprobanteCuota($cuotaId)
{
    try {
        $cuota = Cuota::findOrFail($cuotaId);
        
        // Buscar comprobantes asociados a esta cuota a través de operaciones
        $comprobante = Comprobante::whereHas('operacion', function($query) use ($cuotaId) {
            $query->where('cuota_id', $cuotaId);
        })->first();

        $anulado = $comprobante && in_array(strtoupper($comprobante->estado), ['ANULADO', 'ELIMINADO', 'BAJA', 'INACTIVO']);

        return response()->json([
            'tiene_comprobante' => !is_null($comprobante),
            'anulado' => $anulado,
            'estado' => $comprobante ? $comprobante->estado : null,
            'serie' => $comprobante ? $comprobante->serie : null,
            'numero' => $comprobante ? $comprobante->numero : null,
            'observaciones' => $comprobante ? $comprobante->observaciones : null,
            'comprobante_id' => $comprobante ? $comprobante->id : null
        ]);
    } catch (\Exception $e) {
        \Log::error('Error verificando comprobante para cuota ' . $cuotaId . ': ' . $e->getMessage());
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}
// En tu ComprobanteController, agrega este método de diagnóstico
public function diagnosticarComprobanteCuota($cuotaId)
{
    try {
        $cuota = \App\Models\Cuota::findOrFail($cuotaId);
        
        \Log::info("=== DIAGNÓSTICO COMPROBANTE CUOTA {$cuotaId} ===");
        
        // 1. Verificar operaciones de esta cuota
        $operaciones = $cuota->operaciones;
        \Log::info("Operaciones de la cuota:", [
            'total_operaciones' => $operaciones->count(),
            'operaciones_ids' => $operaciones->pluck('id')->toArray()
        ]);

        // 2. Verificar comprobantes en cada operación
        $comprobantesEncontrados = [];
        foreach ($operaciones as $operacion) {
            if ($operacion->comprobante) {
                $comprobantesEncontrados[] = [
                    'operacion_id' => $operacion->id,
                    'comprobante_id' => $operacion->comprobante->id,
                    'comprobante_estado' => $operacion->comprobante->estado,
                    'comprobante_serie' => $operacion->comprobante->serie,
                    'comprobante_numero' => $operacion->comprobante->numero
                ];
            }
        }

        \Log::info("Comprobantes encontrados:", $comprobantesEncontrados);

        // 3. Buscar comprobantes directamente por cuota_id
        $comprobantesDirectos = \App\Models\Comprobante::whereHas('operacion', function($query) use ($cuotaId) {
            $query->where('cuota_id', $cuotaId);
        })->get();

        \Log::info("Comprobantes por búsqueda directa:", [
            'total' => $comprobantesDirectos->count(),
            'comprobantes' => $comprobantesDirectos->map(function($comp) {
                return [
                    'id' => $comp->id,
                    'estado' => $comp->estado,
                    'serie' => $comp->serie,
                    'numero' => $comp->numero,
                    'operacion_id' => $comp->operacion_id
                ];
            })->toArray()
        ]);

        return response()->json([
            'diagnostico' => [
                'cuota_id' => $cuotaId,
                'total_operaciones' => $operaciones->count(),
                'comprobantes_en_operaciones' => $comprobantesEncontrados,
                'comprobantes_directos' => $comprobantesDirectos->count(),
                'detalles_comprobantes' => $comprobantesDirectos->map(function($comp) {
                    return [
                        'id' => $comp->id,
                        'estado' => $comp->estado,
                        'serie' => $comp->serie,
                        'numero' => $comp->numero,
                        'operacion_id' => $comp->operacion_id
                    ];
                })->toArray()
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error("Error en diagnóstico: " . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
}
