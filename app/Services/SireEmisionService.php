<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\SireHistorial;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para emitir comprobantes via SIRE
 * 
 * Orquesta: validación → mapeo → envío → almacenamiento
 */
class SireEmisionService
{
    protected SireApiService $sireApi;
    protected SireJsonMapper $mapper;

    public function __construct()
    {
        $this->sireApi = app(SireApiService::class);
        $this->mapper = new SireJsonMapper();
    }

    /**
     * Emitir comprobante para una cuota
     * 
     * @param Cuota $cuota
     * @return array ['success' => bool, 'historial_id' => int|null, 'error' => string|null, 'data' => array]
     */
    public function emitirComprobanteParaCuota(Cuota $cuota): array
    {
        try {
            // Cargar relaciones
            $cuota->load(['prestamo' => fn($q) => $q->with('cliente')]);
            $prestamo = $cuota->prestamo;
            $cliente = $prestamo->cliente;

            // Validar datos
            $validation = $this->mapper->validateBeforeMapping($cuota, $prestamo, $cliente);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => 'Validación fallida: ' . implode(', ', $validation['errors']),
                ];
            }

            // Mapear a JSON
            $payload = $this->mapper->mapCuotaToSireJson($cuota, $prestamo, $cliente);

            Log::info('Emitiendo comprobante via SIRE', [
                'cuota_id' => $cuota->id,
                'prestamo_id' => $prestamo->id,
                'cliente_id' => $cliente->id,
                'payload' => $payload,
            ]);

            // Enviar a SIRE
            $result = $this->sireApi->enviarJson($payload);

            if (!$result['success']) {
                Log::error('Error al enviar a SIRE', [
                    'cuota_id' => $cuota->id,
                    'error' => $result['error'] ?? 'Error desconocido',
                ]);

                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Error desconocido al comunicarse con SIRE',
                ];
            }

            // Guardar en SireHistorial
            $responseData = $result['data'] ?? [];
            $sunatResponse = $responseData['sunat_response'] ?? [];

            // Determinar estado según código de respuesta SUNAT
            $codigoRespuesta = $responseData['cod_respuesta'] ?? '0';
            $estado = $codigoRespuesta === '0' ? 'aceptado' : ($codigoRespuesta >= '4000' ? 'rechazado' : 'observado');

            $historial = SireHistorial::create([
                'tipo_comprobante' => $payload['tipoDoc'],
                'serie' => $payload['serie'],
                'numero' => $payload['correlativo'],
                'fecha_emision' => $payload['fechaEmision'] ?? now()->toDateString(),
                'moneda' => $payload['tipoMoneda'] ?? 'PEN',
                'total' => $payload['mtoImpVenta'] ?? $cuota->monto, // Campo correcto: monto, no monto_cuota
                'cliente_tipo_doc' => $cliente->tipo_documento,
                'cliente_numero_doc' => $cliente->numero_documento,
                'cliente_razon_social' => $cliente->nombre_completo ?? $cliente->nombre,
                'estado' => $estado,
                'fecha_envio' => now(),
                'xml_generado' => $responseData['xml_generado'] ?? null,
                'xml_firmado' => $responseData['xml_firmado'] ?? null,
                'hash_xml' => $responseData['hash'] ?? null,
                'cdr_zip' => $responseData['cdr'] ?? null,
                'cdr_xml' => null, // Se actualiza después si se consulta
                'sunat_codigo' => $codigoRespuesta,
                'sunat_mensaje' => $responseData['mensaje'] ?? null,
                'sunat_response' => json_encode($sunatResponse),
                'digest_value' => $responseData['hash'] ?? null,
                'origen_sistema' => 'grupo_santiago',
                'intentos' => 1,
                'metadata' => json_encode([
                    'cuota_id' => $cuota->id,
                    'prestamo_id' => $prestamo->id,
                    'cliente_id' => $cliente->id,
                    'monto_gravado' => $payload['mtoOperGravadas'] ?? 0,
                    'monto_exonerado' => $payload['mtoOperExoneradas'] ?? 0,
                    'monto_igv' => $payload['mtoIGV'] ?? 0,
                ]),
            ]);

            // Actualizar cuota si es necesario (marcar como con comprobante)
            $cuota->update(['comprobante_emitido' => true]);

            // Incrementar el correlativo en la configuración SUNAT
            $this->incrementarCorrelativo($payload['tipoDoc'], $payload['serie']);

            Log::info('Comprobante emitido exitosamente', [
                'historial_id' => $historial->id,
                'cuota_id' => $cuota->id,
                'serie' => $payload['serie'],
                'correlativo' => $payload['correlativo'],
            ]);

            return [
                'success' => true,
                'historial_id' => $historial->id,
                'data' => $historial,
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al emitir comprobante', [
                'cuota_id' => $cuota->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reenviar un comprobante ya emitido
     *
     * @param SireHistorial $historial
     * @return array
     */
    public function reenviarComprobante(SireHistorial $historial): array
    {
        try {
            // Validar que tenga los datos mínimos necesarios
            if (empty($historial->serie) || empty($historial->numero)) {
                return ['success' => false, 'error' => 'Comprobante no tiene datos completos (serie o número faltantes)'];
            }

            // Si no tiene tipo_comprobante, usar '03' (Boleta) por defecto
            $tipoComprobante = $historial->tipo_comprobante ?? '03';

            Log::info('Reenviando comprobante', [
                'historial_id' => $historial->id,
                'tipo' => $tipoComprobante,
                'serie' => $historial->serie,
                'numero' => $historial->numero,
                'tiene_cuota' => !empty($historial->cuota_id)
            ]);

            // Si tiene cuota_id, regenerar el comprobante desde los datos de la cuota
            // Esto asegura que el XML tenga todos los campos correctos según el código actualizado
            if (!empty($historial->cuota_id)) {
                $cuota = \App\Models\Cuota::find($historial->cuota_id);

                if ($cuota) {
                    Log::info('Regenerando comprobante desde cuota', ['cuota_id' => $cuota->id]);

                    // Cargar relaciones
                    $cuota->load(['prestamo' => fn($q) => $q->with('cliente')]);
                    $prestamo = $cuota->prestamo;
                    $cliente = $prestamo->cliente;

                    // Mapear a JSON con el código actualizado
                    $payload = $this->mapper->mapCuotaToSireJson($cuota, $prestamo, $cliente);

                    // Mantener la serie, correlativo y fecha originales
                    $payload['serie'] = $historial->serie;
                    $payload['correlativo'] = (int) $historial->numero;

                    // Usar la fecha de emisión original del comprobante
                    if ($historial->fecha_emision) {
                        $fechaOriginal = \Carbon\Carbon::parse($historial->fecha_emision);
                        $payload['fechaEmision'] = $fechaOriginal->format('Y-m-d');
                        $payload['horEmision'] = $fechaOriginal->format('H:i:s');
                        $payload['fecVencimiento'] = $fechaOriginal->format('Y-m-d');
                    }

                    // Enviar el payload regenerado (se generará y firmará nuevo XML)
                    $result = $this->sireApi->enviarComprobante($payload);
                } else {
                    // Si no encuentra la cuota, usar el XML guardado
                    Log::warning('Cuota no encontrada, usando XML guardado', ['cuota_id' => $historial->cuota_id]);

                    if (empty($historial->xml_firmado)) {
                        return ['success' => false, 'error' => 'Comprobante no tiene XML firmado y no se pudo regenerar'];
                    }

                    $result = $this->sireApi->enviarComprobante([
                        'xml_firmado' => $historial->xml_firmado,
                        'tipoDoc' => $tipoComprobante,
                        'serie' => $historial->serie,
                        'correlativo' => $historial->numero,
                    ]);
                }
            } else {
                // No tiene cuota_id, usar el XML guardado
                if (empty($historial->xml_firmado)) {
                    return ['success' => false, 'error' => 'Comprobante no tiene XML firmado'];
                }

                Log::info('Usando XML guardado (sin cuota_id)');

                $result = $this->sireApi->enviarComprobante([
                    'xml_firmado' => $historial->xml_firmado,
                    'tipoDoc' => $tipoComprobante,
                    'serie' => $historial->serie,
                    'correlativo' => $historial->numero,
                ]);
            }

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Error al reenviar',
                ];
            }

            // Determinar estado según código de respuesta SUNAT
            $responseData = $result['data'] ?? [];
            $codigoRespuesta = $responseData['cod_respuesta'] ?? '0';
            $estado = $codigoRespuesta === '0' ? 'aceptado' : ($codigoRespuesta >= '4000' ? 'rechazado' : 'observado');

            // Actualizar historial con el nuevo XML si fue regenerado
            $updateData = [
                'estado' => $estado,
                'fecha_envio' => now(),
                'sunat_codigo' => $codigoRespuesta,
                'sunat_mensaje' => $responseData['mensaje'] ?? null,
                'sunat_response' => json_encode($responseData['sunat_response'] ?? []),
                'intentos' => ($historial->intentos ?? 0) + 1,
            ];

            // Si se regeneró el XML, guardarlo
            if (isset($responseData['xml_firmado'])) {
                $updateData['xml_firmado'] = $responseData['xml_firmado'];
                $updateData['xml_generado'] = $responseData['xml_generado'] ?? null;
                $updateData['hash_xml'] = $responseData['hash'] ?? null;
            }

            $historial->update($updateData);

            Log::info('Comprobante reenviado exitosamente', [
                'historial_id' => $historial->id,
                'estado' => $estado,
                'codigo_sunat' => $codigoRespuesta
            ]);

            return [
                'success' => true,
                'historial_id' => $historial->id,
                'estado' => $estado,
            ];

        } catch (\Exception $e) {
            Log::error('Error al reenviar comprobante', [
                'historial_id' => $historial->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Incrementar el correlativo correspondiente después de emitir un comprobante exitosamente
     * IMPORTANTE: Sincroniza con el último número real de la BD antes de incrementar
     *
     * @param string $tipoDoc Tipo de comprobante (01=Factura, 03=Boleta)
     * @param string $serie Serie del comprobante
     * @return void
     */
    private function incrementarCorrelativo(string $tipoDoc, string $serie): void
    {
        $config = ConfiguracionSunat::obtenerActiva();

        if (!$config) {
            return;
        }

        $esProduccion = $config->modo_produccion ?? false;
        $esFactura = ($tipoDoc === '01');

        // Determinar qué campo incrementar
        $campoNumero = null;

        if ($esProduccion) {
            // PRODUCCIÓN
            if ($esFactura) {
                $campoNumero = 'sire_numero_factura_prod';
            } else {
                $campoNumero = 'sire_numero_boleta_prod';
            }
        } else {
            // TESTING
            if ($esFactura) {
                $campoNumero = 'sire_numero_factura_test';
            } else {
                $campoNumero = 'sire_numero_boleta_test';
            }
        }

        if ($campoNumero) {
            // CRÍTICO: Antes de incrementar, sincronizar con el último comprobante real de la BD
            // Esto previene desincronización durante merges o deployments
            $ultimoComprobante = \App\Models\Comprobante::where('serie', $serie)
                ->where('tipo_comprobante', $tipoDoc)
                ->orderBy('numero', 'desc')
                ->first();

            if ($ultimoComprobante) {
                $numeroReal = (int) $ultimoComprobante->numero;
                $numeroConfig = (int) $config->$campoNumero;

                // Si el último real es mayor que el configurado, actualizar primero
                if ($numeroReal >= $numeroConfig) {
                    $config->update([$campoNumero => $numeroReal + 1]);

                    Log::info('Correlativo sincronizado con BD antes de incrementar', [
                        'campo' => $campoNumero,
                        'valor_anterior_config' => $numeroConfig,
                        'ultimo_bd' => $numeroReal,
                        'nuevo_valor' => $numeroReal + 1,
                    ]);
                } else {
                    // El configurado es mayor, solo incrementar
                    $config->increment($campoNumero);
                }
            } else {
                // No hay comprobantes previos, solo incrementar
                $config->increment($campoNumero);
            }

            Log::info('Correlativo incrementado', [
                'campo' => $campoNumero,
                'nuevo_valor' => $config->fresh()->$campoNumero,
                'ambiente' => $esProduccion ? 'PRODUCCIÓN' : 'TEST',
                'tipo' => $esFactura ? 'FACTURA' : 'BOLETA',
                'serie' => $serie,
            ]);
        }
    }
}
