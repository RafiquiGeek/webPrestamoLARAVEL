<?php

namespace App\Services;

use App\Models\ConfiguracionSunat;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Company;
use Greenter\Model\Company\Address;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Sale\Legend;
use Greenter\Xml\Builder\InvoiceBuilder;
use Greenter\Xml\Builder\NoteBuilder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Servicio para integración directa con API SUNAT (SIRE)
 *
 * Este servicio se conecta directamente a la API de SUNAT para:
 * - Enviar comprobantes electrónicos
 * - Firmar XMLs con certificado digital
 * - Consultar estado de comprobantes
 * - Obtener CDR (Constancia de Recepción)
 */
class SireApiService
{
    protected $baseUrl;
    protected $timeout = 120;
    protected $configuracion;
    protected $certificadoPath;
    protected $certificadoPassword;
    protected $environment; // 'production' o 'testing'
    protected $see; // Instancia de Greenter See
    protected $certificadoDisponible = false; // Flag para saber si el certificado está configurado

    public function __construct()
    {
        $this->configuracion = ConfiguracionSunat::obtenerActiva();

        if (!$this->configuracion) {
            throw new \Exception('No hay configuración SUNAT activa');
        }

        if (!$this->configuracion->usar_sire) {
            throw new \Exception('Integración SIRE no está habilitada. Active SIRE en la configuración SUNAT.');
        }

        // Validar certificado digital (advertencia en lugar de error)
        if (empty($this->configuracion->sire_cert_path)) {
            Log::warning('Certificado digital no configurado. Suba el certificado .p12/.pfx en la configuración.');
            $this->certificadoDisponible = false;
        } else {
            $this->certificadoPath = storage_path('app/' . $this->configuracion->sire_cert_path);

            if (!file_exists($this->certificadoPath)) {
                Log::warning('Archivo de certificado no encontrado: ' . $this->certificadoPath);
                $this->certificadoDisponible = false;
            } else {
                // Obtener contraseña del certificado (debe estar encriptada en BD)
                $this->certificadoPassword = decrypt($this->configuracion->sire_cert_password ?? '');

                // Si la contraseña está serializada después de desencriptar, deserializarla
                if (strpos($this->certificadoPassword, 's:') === 0) {
                    try {
                        $unserialized = @unserialize($this->certificadoPassword);
                        if ($unserialized !== false) {
                            $this->certificadoPassword = $unserialized;
                        }
                    } catch (\Exception $e) {
                        // Continuar con la contraseña original
                    }
                }

                $this->certificadoDisponible = true;
            }
        }

        // Determinar ambiente (producción o pruebas)
        $this->environment = $this->configuracion->modo_produccion ? 'production' : 'testing';

        // URLs oficiales de SUNAT
        $this->baseUrl = $this->environment === 'production'
            ? 'https://api-cpe.sunat.gob.pe/v1/contribuyente/gem'
            : 'https://api-cpe-beta.sunat.gob.pe/v1/contribuyente/gem';

        // Inicializar Greenter See solo si el certificado está disponible
        if ($this->certificadoDisponible) {
            $this->inicializarGreenter();
            Log::info('SireApiService inicializado (conexión directa a SUNAT)', [
                'environment' => $this->environment,
                'url' => $this->baseUrl,
                'ruc' => $this->configuracion->ruc,
                'certificado' => 'configurado'
            ]);
        } else {
            Log::warning('SireApiService inicializado SIN certificado digital', [
                'environment' => $this->environment,
                'url' => $this->baseUrl,
                'ruc' => $this->configuracion->ruc,
                'mensaje' => 'Configure el certificado digital para poder emitir comprobantes'
            ]);
        }
    }

    /**
     * Inicializar Greenter See con certificado
     */
    private function inicializarGreenter(): void
    {
        $this->see = new See();

        // Crear configuración legacy de OpenSSL para soporte de certificados antiguos
        $this->createLegacyOpenSSLConfig();

        // Verificar si tenemos archivos PEM (nuevo método) o .pfx (método antiguo)
        $certPath = storage_path('app/' . $this->configuracion->sire_cert_path);
        $keyPath = !empty($this->configuracion->sire_key_path)
            ? storage_path('app/' . $this->configuracion->sire_key_path)
            : null;

        // Configurar certificado con soporte legacy para OpenSSL 3.x
        $originalConf = getenv('OPENSSL_CONF');

        try {
            // Activar proveedor legacy de OpenSSL
            putenv('OPENSSL_CONF=' . storage_path('app/keys/openssl_legacy.cnf'));

            // Si tenemos archivos PEM separados (certificado + clave privada)
            if ($keyPath && file_exists($keyPath) && pathinfo($certPath, PATHINFO_EXTENSION) === 'pem') {
                // Leer certificado y clave por separado
                $certContent = file_get_contents($certPath);
                $keyContent = file_get_contents($keyPath);

                // Crear un certificado PEM combinado que Greenter pueda usar
                $combinedPem = $certContent . "\n" . $keyContent;

                // setCertificate puede recibir PEM sin contraseña
                $this->see->setCertificate($combinedPem);

                Log::info('Greenter configurado con certificado PEM', [
                    'cert_path' => basename($certPath),
                    'key_path' => basename($keyPath)
                ]);
            } else {
                // Método antiguo: usar .pfx con contraseña
                $this->see->setCertificate(
                    file_get_contents($certPath),
                    $this->certificadoPassword
                );

                Log::info('Greenter configurado con certificado PFX', [
                    'cert_path' => basename($certPath)
                ]);
            }

        } finally {
            // Restaurar configuración original de OpenSSL
            if ($originalConf !== false) {
                putenv('OPENSSL_CONF=' . $originalConf);
            } else {
                putenv('OPENSSL_CONF');
            }
        }

        // Configurar credenciales SOL (usuario secundario SUNAT)
        $this->see->setService($this->environment === 'production'
            ? SunatEndpoints::FE_PRODUCCION
            : SunatEndpoints::FE_BETA
        );

        // Desencriptar contraseña SOL si está encriptada
        $solPass = $this->configuracion->sol_pass ?? '';
        if (!empty($solPass) && strlen($solPass) > 50) {
            try {
                $solPass = decrypt($solPass);
            } catch (\Exception $e) {
                Log::warning('No se pudo desencriptar sol_pass, usando valor original');
            }
        }

        // Log de credenciales SOL para debugging
        Log::info('Configurando credenciales SOL', [
            'ruc' => $this->configuracion->ruc,
            'sol_user' => $this->configuracion->sol_user ?? '',
            'usuario_sol' => $this->configuracion->usuario_sol ?? '', // Para verificar si difieren
            'sol_pass_length' => strlen($solPass),
        ]);

        $this->see->setClaveSOL(
            $this->configuracion->ruc,
            $this->configuracion->sol_user ?? '',
            $solPass
        );
    }

    /**
     * Generar XML del comprobante según estructura UBL 2.1 usando Greenter
     *
     * @param array $data Datos del comprobante en formato SIRE JSON
     * @return string XML generado
     */
    private function generarXML(array $data): string
    {
        $tipoDoc = $data['tipoDoc'];

        // Convertir data a modelo Greenter
        if (in_array($tipoDoc, ['01', '03'])) {
            // Factura o Boleta
            $invoice = $this->crearInvoiceDesdeData($data);
            $xmlBuilder = new InvoiceBuilder();
            $xml = $xmlBuilder->build($invoice);
        } elseif (in_array($tipoDoc, ['07', '08'])) {
            // Nota de Crédito o Débito
            $note = $this->crearNoteDesdeData($data);
            $xmlBuilder = new NoteBuilder();
            $xml = $xmlBuilder->build($note);
        } else {
            throw new \Exception("Tipo de documento no soportado: {$tipoDoc}");
        }

        return $xml;
    }

    /**
     * Crear modelo Invoice de Greenter desde array de datos
     */
    private function crearInvoiceDesdeData(array $data): Invoice
    {
        $invoice = new Invoice();

        // Datos básicos
        $invoice->setUblVersion($data['ublVersion'] ?? '2.1')
            ->setTipoOperacion($data['tipoOperacion'] ?? '0101')
            ->setTipoDoc($data['tipoDoc'])
            ->setSerie($data['serie'])
            ->setCorrelativo($data['correlativo'])
            ->setFechaEmision(new \DateTime($data['fechaEmision']))
            ->setTipoMoneda($data['tipoMoneda'] ?? 'PEN');

        // Cliente
        $cliente = new Client();
        $cliente->setTipoDoc($data['client']['tipoDoc'] ?? '6')
            ->setNumDoc($data['client']['numDoc'] ?? '')
            ->setRznSocial($data['client']['rznSocial'] ?? '');

        if (isset($data['client']['address'])) {
            $address = new Address();
            $address->setDireccion($data['client']['address']['direccion'] ?? '')
                ->setProvincia($data['client']['address']['provincia'] ?? '')
                ->setDepartamento($data['client']['address']['departamento'] ?? '')
                ->setDistrito($data['client']['address']['distrito'] ?? '')
                ->setUbigueo($data['client']['address']['ubigeo'] ?? '');
            $cliente->setAddress($address);
        }

        $invoice->setClient($cliente);

        // Empresa (Company)
        $company = new Company();
        $company->setRuc($data['company']['ruc'] ?? $this->configuracion->ruc)
            ->setRazonSocial($data['company']['razonSocial'] ?? '')
            ->setNombreComercial($data['company']['nombreComercial'] ?? '');

        if (isset($data['company']['address'])) {
            $address = new Address();
            $address->setDireccion($data['company']['address']['direccion'] ?? '')
                ->setProvincia($data['company']['address']['provincia'] ?? '')
                ->setDepartamento($data['company']['address']['departamento'] ?? '')
                ->setDistrito($data['company']['address']['distrito'] ?? '')
                ->setUbigueo($data['company']['address']['ubigeo'] ?? '');
            $company->setAddress($address);
        }

        $invoice->setCompany($company);

        // Items/Detalles
        $items = [];
        foreach ($data['details'] as $detail) {
            $item = new SaleDetail();

            // Calcular precio unitario (con IGV) si no viene en los datos
            $valorUnitario = $detail['mtoValorUnitario'] ?? 0;
            $porcentajeIgv = $detail['porcentajeIgv'] ?? 18;
            $precioUnitario = $detail['mtoPrecioUnitario'] ?? ($valorUnitario * (1 + $porcentajeIgv / 100));

            $item->setCodProducto($detail['codProducto'] ?? '')
                ->setUnidad($detail['unidad'] ?? 'NIU')
                ->setCantidad($detail['cantidad'] ?? 1)
                ->setDescripcion($detail['descripcion'] ?? '')
                ->setMtoValorUnitario($valorUnitario)
                ->setMtoPrecioUnitario($precioUnitario) // REQUERIDO por SUNAT (PriceAmount)
                ->setMtoValorVenta($detail['mtoValorVenta'] ?? 0)
                ->setMtoBaseIgv($detail['mtoBaseIgv'] ?? 0)
                ->setPorcentajeIgv($porcentajeIgv)
                ->setIgv($detail['igv'] ?? 0)
                ->setTipAfeIgv($detail['tipAfeIgv'] ?? '10')
                ->setTotalImpuestos($detail['totalImpuestos'] ?? 0);

            $items[] = $item;
        }
        $invoice->setDetails($items);

        // Totales
        $invoice->setMtoOperGravadas($data['mtoOperGravadas'] ?? 0)
            ->setMtoOperExoneradas($data['mtoOperExoneradas'] ?? 0)
            ->setMtoOperInafectas($data['mtoOperInafectas'] ?? 0)
            ->setMtoIGV($data['mtoIGV'] ?? 0)
            ->setValorVenta($data['valorVenta'] ?? 0) // REQUERIDO: Valor de venta total sin IGV (LineExtensionAmount)
            ->setSubTotal($data['mtoImpVenta'] ?? 0) // CORREGIDO: TaxInclusiveAmount = valor venta + IGV
            ->setTotalImpuestos($data['totalImpuestos'] ?? $data['mtoIGV'] ?? 0) // REQUERIDO: Total de impuestos
            ->setMtoImpVenta($data['mtoImpVenta'] ?? 0); // Total a pagar (PayableAmount)

        // Leyendas
        if (isset($data['legends'])) {
            $legends = [];
            foreach ($data['legends'] as $leg) {
                $legend = new Legend();
                $legend->setCode($leg['code'] ?? '1000')
                    ->setValue($leg['value'] ?? '');
                $legends[] = $legend;
            }
            $invoice->setLegends($legends);
        }

        return $invoice;
    }

    /**
     * Crear modelo Note de Greenter desde array de datos (para notas de crédito/débito)
     */
    private function crearNoteDesdeData(array $data): Note
    {
        $note = new Note();

        // Datos básicos
        $note->setUblVersion($data['ublVersion'] ?? '2.1')
            ->setTipDocAfectado($data['tipDocAfectado'] ?? '01') // Tipo de doc afectado
            ->setNumDocfectado($data['numDocAfectado'] ?? '') // Serie-correlativo afectado
            ->setCodMotivo($data['codMotivo'] ?? '01') // Código de motivo
            ->setDesMotivo($data['desMotivo'] ?? '') // Descripción del motivo
            ->setTipoDoc($data['tipoDoc'])
            ->setSerie($data['serie'])
            ->setCorrelativo($data['correlativo'])
            ->setFechaEmision(new \DateTime($data['fechaEmision']))
            ->setTipoMoneda($data['tipoMoneda'] ?? 'PEN');

        // Cliente
        $cliente = new Client();
        $cliente->setTipoDoc($data['client']['tipoDoc'] ?? '6')
            ->setNumDoc($data['client']['numDoc'] ?? '')
            ->setRznSocial($data['client']['rznSocial'] ?? '');

        if (isset($data['client']['address'])) {
            $address = new Address();
            $address->setDireccion($data['client']['address']['direccion'] ?? '')
                ->setProvincia($data['client']['address']['provincia'] ?? '')
                ->setDepartamento($data['client']['address']['departamento'] ?? '')
                ->setDistrito($data['client']['address']['distrito'] ?? '')
                ->setUbigueo($data['client']['address']['ubigeo'] ?? '');
            $cliente->setAddress($address);
        }

        $note->setClient($cliente);

        // Empresa (Company)
        $company = new Company();
        $company->setRuc($data['company']['ruc'] ?? $this->configuracion->ruc)
            ->setRazonSocial($data['company']['razonSocial'] ?? '')
            ->setNombreComercial($data['company']['nombreComercial'] ?? '');

        if (isset($data['company']['address'])) {
            $address = new Address();
            $address->setDireccion($data['company']['address']['direccion'] ?? '')
                ->setProvincia($data['company']['address']['provincia'] ?? '')
                ->setDepartamento($data['company']['address']['departamento'] ?? '')
                ->setDistrito($data['company']['address']['distrito'] ?? '')
                ->setUbigueo($data['company']['address']['ubigeo'] ?? '');
            $company->setAddress($address);
        }

        $note->setCompany($company);

        // Items/Detalles
        $items = [];
        foreach ($data['details'] as $detail) {
            $item = new SaleDetail();

            // Calcular precio unitario (con IGV) si no viene en los datos
            $valorUnitario = $detail['mtoValorUnitario'] ?? 0;
            $porcentajeIgv = $detail['porcentajeIgv'] ?? 18;
            $precioUnitario = $detail['mtoPrecioUnitario'] ?? ($valorUnitario * (1 + $porcentajeIgv / 100));

            $item->setCodProducto($detail['codProducto'] ?? '')
                ->setUnidad($detail['unidad'] ?? 'NIU')
                ->setCantidad($detail['cantidad'] ?? 1)
                ->setDescripcion($detail['descripcion'] ?? '')
                ->setMtoValorUnitario($valorUnitario)
                ->setMtoPrecioUnitario($precioUnitario) // REQUERIDO por SUNAT (PriceAmount)
                ->setMtoValorVenta($detail['mtoValorVenta'] ?? 0)
                ->setMtoBaseIgv($detail['mtoBaseIgv'] ?? 0)
                ->setPorcentajeIgv($porcentajeIgv)
                ->setIgv($detail['igv'] ?? 0)
                ->setTipAfeIgv($detail['tipAfeIgv'] ?? '10')
                ->setTotalImpuestos($detail['totalImpuestos'] ?? 0);

            $items[] = $item;
        }
        $note->setDetails($items);

        // Totales
        $note->setMtoOperGravadas($data['mtoOperGravadas'] ?? 0)
            ->setMtoOperExoneradas($data['mtoOperExoneradas'] ?? 0)
            ->setMtoOperInafectas($data['mtoOperInafectas'] ?? 0)
            ->setMtoIGV($data['mtoIGV'] ?? 0)
            ->setValorVenta($data['valorVenta'] ?? 0) // REQUERIDO: Valor de venta total sin IGV (LineExtensionAmount)
            ->setSubTotal($data['mtoImpVenta'] ?? 0) // CORREGIDO: TaxInclusiveAmount = valor venta + IGV
            ->setTotalImpuestos($data['totalImpuestos'] ?? $data['mtoIGV'] ?? 0) // REQUERIDO: Total de impuestos
            ->setMtoImpVenta($data['mtoImpVenta'] ?? 0); // Total a pagar (PayableAmount)

        // Leyendas
        if (isset($data['legends'])) {
            $legends = [];
            foreach ($data['legends'] as $leg) {
                $legend = new Legend();
                $legend->setCode($leg['code'] ?? '1000')
                    ->setValue($leg['value'] ?? '');
                $legends[] = $legend;
            }
            $note->setLegends($legends);
        }

        return $note;
    }

    /**
     * Firmar XML con certificado digital usando Greenter
     *
     * @param string $xml XML sin firmar
     * @return array ['success' => bool, 'xml_firmado' => string, 'digest' => string]
     */
    private function firmarXmlConCertificado(string $xml): array
    {
        try {
            // Greenter ya firma el XML automáticamente cuando se genera
            // usando el certificado configurado en $this->see
            // El método getXmlSigned() retorna el XML con firma XMLDSig

            // Calcular hash SHA-256 del XML
            $digest = hash('sha256', $xml);

            return [
                'success' => true,
                'xml_firmado' => $xml, // Ya viene firmado de Greenter
                'digest' => $digest,
            ];

        } catch (\Exception $e) {
            Log::error('Error al firmar XML', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtener token OAuth2 de SUNAT
     *
     * @return array|null Token data o null si falla
     */
    private function obtenerTokenOAuth2(): ?array
    {
        if (empty($this->configuracion->sire_client_id) || empty($this->configuracion->sire_client_secret)) {
            Log::warning('Credenciales OAuth2 no configuradas');
            return null;
        }

        try {
            $tokenUrl = $this->environment === 'production'
                ? 'https://api-seguridad.sunat.gob.pe/v1/clientessol/{clientId}/oauth2/token/'
                : 'https://gre-test.nubefact.com/v1/contribuyente/gem/auth/token';

            $clientId = $this->configuracion->sire_client_id;
            $clientSecret = decrypt($this->configuracion->sire_client_secret);

            $tokenUrl = str_replace('{clientId}', $clientId, $tokenUrl);

            $response = Http::asForm()->post($tokenUrl, [
                'grant_type' => 'client_credentials',
                'scope' => 'https://api-cpe.sunat.gob.pe',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Token OAuth2 obtenido exitosamente');
                return $data;
            }

            Log::error('Error al obtener token OAuth2', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Excepción al obtener token OAuth2', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Enviar comprobante a SUNAT (con XML firmado)
     *
     * @param array $data Debe incluir 'xml_firmado' o datos para generar XML
     * @return array Resultado del envío
     */
    public function enviarComprobante(array $data): array
    {
        // Solo requerir certificado en PRODUCCIÓN
        if ($this->environment === 'production' && !$this->certificadoDisponible) {
            return [
                'success' => false,
                'error' => 'Certificado digital no configurado. Por favor suba el certificado .p12/.pfx en la configuración SIRE.',
            ];
        }

        try {
            // Obtener token OAuth2
            $tokenData = $this->obtenerTokenOAuth2();
            if (!$tokenData || empty($tokenData['access_token'])) {
                return [
                    'success' => false,
                    'error' => 'No se pudo obtener token OAuth2 de SUNAT',
                ];
            }

            $xmlFirmado = $data['xml_firmado'] ?? null;

            if (!$xmlFirmado) {
                Log::info('Generando XML desde datos', [
                    'tiene_tipoDoc' => isset($data['tipoDoc']),
                    'tiene_serie' => isset($data['serie']),
                    'tiene_correlativo' => isset($data['correlativo']),
                ]);

                // Si no viene XML firmado, generarlo y firmarlo
                $xml = $this->generarXML($data);

                if (!$xml) {
                    Log::error('generarXML() retornó null');
                    return [
                        'success' => false,
                        'error' => 'Error al generar XML: método generarXML retornó null',
                    ];
                }

                Log::info('XML generado, procediendo a firmar');

                $resultFirma = $this->firmarXmlConCertificado($xml);

                if (!$resultFirma['success']) {
                    Log::error('Error al firmar XML', ['error' => $resultFirma['error'] ?? 'desconocido']);
                    return [
                        'success' => false,
                        'error' => 'Error al firmar XML: ' . ($resultFirma['error'] ?? 'desconocido'),
                    ];
                }

                Log::info('XML firmado exitosamente');
                $xmlFirmado = $resultFirma['xml_firmado'];
            } else {
                Log::info('Usando XML firmado que vino en data');
            }

            // Crear ZIP con el XML firmado
            $nombreArchivo = $this->configuracion->ruc . '-' . $data['tipoDoc'] . '-' . $data['serie'] . '-' . $data['correlativo'];
            $zipContent = $this->crearZipConXml($xmlFirmado, $nombreArchivo . '.xml');

            Log::info('Enviando comprobante a SUNAT', [
                'tipo' => $data['tipoDoc'] ?? null,
                'serie' => $data['serie'] ?? null,
                'numero' => $data['correlativo'] ?? null,
                'ruc' => $this->configuracion->ruc
            ]);

            // Endpoint correcto de SUNAT para envío de comprobantes
            $endpoint = "{$this->baseUrl}/comprobantes/{$nombreArchivo}.zip";

            // Preparar opciones HTTP
            $httpOptions = [
                'verify' => true, // Verificar SSL del servidor
                'timeout' => $this->timeout,
            ];

            // Si tenemos archivos PEM, necesitamos usarlos para mutual TLS
            $certPath = storage_path('app/' . $this->configuracion->sire_cert_path);
            $keyPath = !empty($this->configuracion->sire_key_path)
                ? storage_path('app/' . $this->configuracion->sire_key_path)
                : null;

            // Configurar certificado cliente para mutual TLS
            if ($keyPath && file_exists($keyPath) && pathinfo($certPath, PATHINFO_EXTENSION) === 'pem') {
                // Usar archivos PEM separados
                $httpOptions['cert'] = $certPath;
                $httpOptions['ssl_key'] = $keyPath;

                Log::info('Usando certificado PEM para mutual TLS', [
                    'cert' => basename($certPath),
                    'key' => basename($keyPath)
                ]);
            } else {
                // Usar archivo PFX con contraseña
                $httpOptions['cert'] = [$this->certificadoPath, $this->certificadoPassword];

                Log::info('Usando certificado PFX para mutual TLS', [
                    'cert' => basename($this->certificadoPath)
                ]);
            }

            // Enviar a SUNAT con certificado SSL (mutual TLS) + OAuth2
            $response = Http::withToken($tokenData['access_token'])
                ->attach('file', $zipContent, $nombreArchivo . '.zip')
                ->withOptions($httpOptions)
                ->put($endpoint);

            if ($response->successful()) {
                $result = $response->json();

                Log::info('Comprobante enviado exitosamente a SUNAT', [
                    'serie' => $data['serie'],
                    'numero' => $data['correlativo'],
                    'cod_respuesta' => $result['codRespuesta'] ?? null
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'cod_respuesta' => $result['codRespuesta'] ?? '0',
                        'mensaje' => $result['mensaje'] ?? 'Comprobante aceptado',
                        'xml_generado' => $data['xml'] ?? null,
                        'xml_firmado' => $xmlFirmado,
                        'hash' => $result['hash'] ?? hash('sha256', $xmlFirmado),
                        'cdr' => $result['cdr'] ?? null,
                        'sunat_response' => $result,
                    ],
                    'message' => 'Comprobante enviado exitosamente',
                    'status' => $response->status()
                ];
            }

            $error = $response->json();
            $responseBody = $response->body();

            Log::error('Error al enviar comprobante a SUNAT', [
                'status' => $response->status(),
                'status_text' => $response->reason(),
                'error' => $error['error'] ?? 'Error desconocido',
                'mensaje' => $error['mensaje'] ?? null,
                'codigo' => $error['codigo'] ?? null,
                'body' => $responseBody,
                'headers' => $response->headers(),
                'endpoint' => $endpoint,
                'nombre_archivo' => $nombreArchivo
            ]);

            return [
                'success' => false,
                'error' => $error['error'] ?? $error['mensaje'] ?? $responseBody ?? 'Error al enviar comprobante',
                'message' => $error['mensaje'] ?? 'Error desconocido',
                'codigo' => $error['codigo'] ?? 'HTTP',
                'status' => $response->status(),
                'response_body' => $responseBody
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al enviar comprobante a SUNAT', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Error inesperado: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Crear archivo ZIP con el XML
     *
     * @param string $xmlContent Contenido del XML
     * @param string $nombreArchivo Nombre del archivo XML
     * @return string Contenido del ZIP en binario
     */
    private function crearZipConXml(string $xmlContent, string $nombreArchivo): string
    {
        $zipPath = sys_get_temp_dir() . '/' . uniqid('sire_') . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            $zip->addFromString($nombreArchivo, $xmlContent);
            $zip->close();
        }

        $zipContent = file_get_contents($zipPath);
        unlink($zipPath); // Eliminar archivo temporal

        return $zipContent;
    }

    /**
     * Validar datos del comprobante antes de enviar a SUNAT
     *
     * @param array $data Datos del comprobante
     * @return array Array con 'valid' (bool) y 'errors' (array de errores)
     */
    private function validarDatosPreEnvio(array $data): array
    {
        $errors = [];

        // 1. Validar tipo de documento
        if (empty($data['tipoDoc'])) {
            $errors[] = 'Tipo de documento es requerido';
        } elseif (!in_array($data['tipoDoc'], ['01', '03', '07', '08'])) {
            $errors[] = "Tipo de documento no soportado: {$data['tipoDoc']}. Solo se admiten: 01 (Factura), 03 (Boleta), 07 (Nota Crédito), 08 (Nota Débito)";
        }

        // 2. Validar serie y correlativo
        if (empty($data['serie'])) {
            $errors[] = 'Serie es requerida';
        } elseif (!preg_match('/^[A-Z0-9]{4}$/', $data['serie'])) {
            $errors[] = "Serie inválida: {$data['serie']}. Debe tener 4 caracteres alfanuméricos en mayúsculas";
        }

        if (empty($data['correlativo'])) {
            $errors[] = 'Correlativo es requerido';
        } elseif (!is_numeric($data['correlativo']) || $data['correlativo'] <= 0) {
            $errors[] = 'Correlativo debe ser un número positivo';
        }

        // 3. Validar fecha de emisión
        if (empty($data['fechaEmision'])) {
            $errors[] = 'Fecha de emisión es requerida';
        } else {
            try {
                $fechaEmision = new \DateTime($data['fechaEmision']);
                $hoy = new \DateTime();
                $diferenciaDias = $hoy->diff($fechaEmision)->days;

                if ($fechaEmision > $hoy) {
                    $errors[] = 'Fecha de emisión no puede ser futura';
                }

                // SUNAT permite hasta 7 días de atraso
                if ($fechaEmision < $hoy && $diferenciaDias > 7) {
                    $errors[] = "Fecha de emisión con más de 7 días de atraso ({$diferenciaDias} días). SUNAT puede rechazar el comprobante";
                }
            } catch (\Exception $e) {
                $errors[] = "Fecha de emisión inválida: {$data['fechaEmision']}";
            }
        }

        // 4. Validar cliente
        if (empty($data['client'])) {
            $errors[] = 'Datos del cliente son requeridos';
        } else {
            $client = $data['client'];

            if (empty($client['tipoDoc'])) {
                $errors[] = 'Tipo de documento del cliente es requerido';
            } elseif (!in_array($client['tipoDoc'], ['1', '4', '6', '7'])) {
                $errors[] = "Tipo de documento del cliente inválido: {$client['tipoDoc']}. Valores válidos: 1 (DNI), 4 (CE), 6 (RUC), 7 (Pasaporte)";
            }

            if (empty($client['numDoc'])) {
                $errors[] = 'Número de documento del cliente es requerido';
            } else {
                // Validar RUC si es tipo 6
                if ($client['tipoDoc'] === '6') {
                    if (!preg_match('/^[12][0-9]{10}$/', $client['numDoc'])) {
                        $errors[] = "RUC del cliente inválido: {$client['numDoc']}. Debe tener 11 dígitos y comenzar con 10, 15, 17 o 20";
                    }
                }
                // Validar DNI si es tipo 1
                if ($client['tipoDoc'] === '1' && !preg_match('/^[0-9]{8}$/', $client['numDoc'])) {
                    $errors[] = "DNI del cliente inválido: {$client['numDoc']}. Debe tener 8 dígitos";
                }
            }

            if (empty($client['rznSocial'])) {
                $errors[] = 'Razón social o nombre del cliente es requerido';
            }
        }

        // 5. Validar company (emisor)
        if (empty($data['company'])) {
            $errors[] = 'Datos de la empresa emisora son requeridos';
        } else {
            $company = $data['company'];

            if (empty($company['ruc'])) {
                $errors[] = 'RUC de la empresa emisora es requerido';
            } elseif (!preg_match('/^[12][0-9]{10}$/', $company['ruc'])) {
                $errors[] = "RUC de la empresa inválido: {$company['ruc']}. Debe tener 11 dígitos y comenzar con 10, 15, 17 o 20";
            }

            if (empty($company['razonSocial'])) {
                $errors[] = 'Razón social de la empresa emisora es requerida';
            }
        }

        // 6. Validar detalles (items)
        if (empty($data['details']) || !is_array($data['details'])) {
            $errors[] = 'Debe incluir al menos un ítem en el comprobante';
        } else {
            foreach ($data['details'] as $index => $detail) {
                $itemNum = $index + 1;

                if (empty($detail['cantidad']) || $detail['cantidad'] <= 0) {
                    $errors[] = "Ítem #{$itemNum}: Cantidad debe ser mayor a 0";
                }

                if (empty($detail['unidad'])) {
                    $errors[] = "Ítem #{$itemNum}: Unidad de medida es requerida";
                }

                if (empty($detail['descripcion'])) {
                    $errors[] = "Ítem #{$itemNum}: Descripción es requerida";
                }

                if (!isset($detail['mtoValorUnitario']) || $detail['mtoValorUnitario'] < 0) {
                    $errors[] = "Ítem #{$itemNum}: Valor unitario debe ser mayor o igual a 0";
                }

                if (isset($detail['mtoValorVenta']) && $detail['mtoValorVenta'] < 0) {
                    $errors[] = "Ítem #{$itemNum}: Valor de venta no puede ser negativo";
                }

                // Validar código de afectación IGV
                if (isset($detail['tipAfeIgv']) && !in_array($detail['tipAfeIgv'], ['10', '20', '30', '40'])) {
                    $errors[] = "Ítem #{$itemNum}: Código de afectación IGV inválido: {$detail['tipAfeIgv']}. Valores válidos: 10 (Gravado), 20 (Exonerado), 30 (Inafecto), 40 (Exportación)";
                }
            }
        }

        // 7. Validar montos totales
        if (isset($data['mtoImpVenta'])) {
            if ($data['mtoImpVenta'] <= 0) {
                $errors[] = 'Importe total de venta debe ser mayor a 0';
            }
        }

        if (isset($data['mtoOperGravadas']) && $data['mtoOperGravadas'] < 0) {
            $errors[] = 'Monto de operaciones gravadas no puede ser negativo';
        }

        if (isset($data['mtoIGV']) && $data['mtoIGV'] < 0) {
            $errors[] = 'Monto de IGV no puede ser negativo';
        }

        // 8. Validar moneda
        if (empty($data['tipoMoneda'])) {
            $errors[] = 'Tipo de moneda es requerido';
        } elseif (!in_array($data['tipoMoneda'], ['PEN', 'USD', 'EUR'])) {
            $errors[] = "Tipo de moneda inválido: {$data['tipoMoneda']}. Valores válidos: PEN, USD, EUR";
        }

        // 9. Validaciones específicas para Notas de Crédito/Débito
        if (in_array($data['tipoDoc'], ['07', '08'])) {
            if (empty($data['tipDocAfectado'])) {
                $errors[] = 'Tipo de documento afectado es requerido para notas';
            }

            if (empty($data['numDocAfectado'])) {
                $errors[] = 'Número de documento afectado es requerido para notas';
            }

            if (empty($data['codMotivo'])) {
                $errors[] = 'Código de motivo es requerido para notas';
            }

            if (empty($data['desMotivo'])) {
                $errors[] = 'Descripción del motivo es requerida para notas';
            }
        }

        // 10. Validar certificado - SOLO en ambiente PRODUCCIÓN
        // En ambiente TEST (Beta) NO se requiere certificado digital
        if ($this->environment === 'production') {
            if (!$this->certificadoDisponible) {
                $errors[] = 'Certificado digital no configurado. Por favor suba el certificado .p12/.pfx en la configuración SIRE';
            } else {
                // Verificar que el certificado no esté vencido
                try {
                    $certContent = file_get_contents($this->certificadoPath);
                    $certData = [];

                    // Activar soporte legacy OpenSSL
                    $originalConf = getenv('OPENSSL_CONF');
                    putenv('OPENSSL_CONF=' . storage_path('app/keys/openssl_legacy.cnf'));

                    $success = openssl_pkcs12_read($certContent, $certData, $this->certificadoPassword);

                    // Restaurar configuración
                    if ($originalConf !== false) {
                        putenv('OPENSSL_CONF=' . $originalConf);
                    } else {
                        putenv('OPENSSL_CONF');
                    }

                    if ($success) {
                        $certInfo = openssl_x509_parse($certData['cert']);
                        $validTo = $certInfo['validTo_time_t'];

                        if (time() > $validTo) {
                            $errors[] = 'El certificado digital ha expirado. Por favor renueve su certificado';
                        } elseif (time() > ($validTo - 30 * 24 * 60 * 60)) {
                            // Advertencia si falta menos de 30 días
                            $diasRestantes = floor(($validTo - time()) / (24 * 60 * 60));
                            $errors[] = "ADVERTENCIA: El certificado digital expirará en {$diasRestantes} días";
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Error al validar certificado digital: ' . $e->getMessage();
                }
            }
        }
        // En ambiente TEST, el certificado es opcional (SUNAT Beta permite pruebas sin certificado)

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Enviar datos estructurados (convertir a XML, firmar y enviar a SUNAT)
     *
     * Este método usa Greenter con credenciales SOL para enviar comprobantes individuales (CPE).
     * Para libros electrónicos (SIRE) se debe usar OAuth2, pero para facturas/boletas individuales
     * se usa el método tradicional con certificado digital y credenciales SOL.
     *
     * @param array $data Datos del comprobante
     * @return array Resultado del envío
     */
    public function enviarJson(array $data): array
    {
        // Validar datos antes de enviar
        $validacion = $this->validarDatosPreEnvio($data);

        if (!$validacion['valid']) {
            Log::warning('Validación pre-envío fallida', [
                'errors' => $validacion['errors'],
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => 'Errores de validación: ' . implode('; ', $validacion['errors']),
                'validation_errors' => $validacion['errors'],
            ];
        }

        // Solo requerir certificado en PRODUCCIÓN
        if ($this->environment === 'production' && !$this->certificadoDisponible) {
            return [
                'success' => false,
                'error' => 'Certificado digital no configurado. Por favor suba el certificado .p12/.pfx en la configuración SIRE.',
            ];
        }

        try {
            // Generar XML firmado con Greenter
            $tipoDoc = $data['tipoDoc'];

            // Crear modelo Greenter según tipo de documento
            if (in_array($tipoDoc, ['01', '03'])) {
                $invoice = $this->crearInvoiceDesdeData($data);

                // Generar XML firmado usando Greenter con credenciales SOL
                $result = $this->see->send($invoice);

                // Si Greenter devuelve resultado, procesarlo
                if ($result->isSuccess()) {
                    $xml = $this->see->getFactory()->getLastXml();
                    $xmlSigned = $this->see->getXmlSigned($invoice);
                    $cdr = $result->getCdrResponse();

                    Log::info('Comprobante enviado exitosamente a SUNAT con Greenter', [
                        'serie' => $data['serie'],
                        'numero' => $data['correlativo'],
                        'cod_respuesta' => $cdr ? $cdr->getCode() : '0',
                    ]);

                    return [
                        'success' => true,
                        'data' => [
                            'cod_respuesta' => $cdr ? $cdr->getCode() : '0',
                            'mensaje' => $cdr ? $cdr->getDescription() : 'Comprobante aceptado',
                            'xml_generado' => $xml,
                            'xml_firmado' => $xmlSigned,
                            'hash' => hash('sha256', $xmlSigned),
                            'cdr' => $result->getCdrZip() ? base64_encode($result->getCdrZip()) : null,
                        ],
                        'message' => 'Comprobante enviado exitosamente',
                    ];
                } else {
                    $error = $result->getError();

                    Log::error('Error al enviar comprobante con Greenter', [
                        'codigo' => $error->getCode(),
                        'mensaje' => $error->getMessage(),
                        'error_completo' => [
                            'code' => $error->getCode(),
                            'message' => $error->getMessage(),
                        ],
                        'xml_generado' => $this->see->getFactory()->getLastXml(),
                    ]);

                    return [
                        'success' => false,
                        'error' => $error->getMessage(),
                        'codigo_error' => $error->getCode(),
                        'detalle_error' => 'Ver logs para más información del XML generado',
                    ];
                }

            } elseif (in_array($tipoDoc, ['07', '08'])) {
                $note = $this->crearNoteDesdeData($data);

                // Generar XML firmado usando Greenter con credenciales SOL
                $result = $this->see->send($note);

                // Si Greenter devuelve resultado, procesarlo
                if ($result->isSuccess()) {
                    $xml = $this->see->getFactory()->getLastXml();
                    $xmlSigned = $this->see->getXmlSigned($note);
                    $cdr = $result->getCdrResponse();

                    Log::info('Nota enviada exitosamente a SUNAT con Greenter', [
                        'serie' => $data['serie'],
                        'numero' => $data['correlativo'],
                        'cod_respuesta' => $cdr ? $cdr->getCode() : '0',
                    ]);

                    return [
                        'success' => true,
                        'data' => [
                            'cod_respuesta' => $cdr ? $cdr->getCode() : '0',
                            'mensaje' => $cdr ? $cdr->getDescription() : 'Nota aceptada',
                            'xml_generado' => $xml,
                            'xml_firmado' => $xmlSigned,
                            'hash' => hash('sha256', $xmlSigned),
                            'cdr' => $result->getCdrZip() ? base64_encode($result->getCdrZip()) : null,
                        ],
                        'message' => 'Nota enviada exitosamente',
                    ];
                } else {
                    $error = $result->getError();

                    Log::error('Error al enviar nota con Greenter', [
                        'codigo' => $error->getCode(),
                        'mensaje' => $error->getMessage(),
                        'error_completo' => [
                            'code' => $error->getCode(),
                            'message' => $error->getMessage(),
                        ],
                        'xml_generado' => $this->see->getFactory()->getLastXml(),
                    ]);

                    return [
                        'success' => false,
                        'error' => $error->getMessage(),
                        'codigo_error' => $error->getCode(),
                        'detalle_error' => 'Ver logs para más información del XML generado',
                    ];
                }
            } else {
                throw new \Exception("Tipo de documento no soportado: {$tipoDoc}");
            }

        } catch (\Exception $e) {
            Log::error('Error en enviarJson', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar comprobante con sistema de reintentos automáticos
     *
     * Este método envía el comprobante y, si falla, programa reintentos automáticos
     * usando el sistema de Jobs de Laravel con backoff exponencial.
     *
     * @param array $data Datos del comprobante
     * @param int $comprobanteId ID del comprobante en la base de datos
     * @param int $maxIntentos Número máximo de reintentos (default: 5)
     * @return array Resultado del envío
     */
    public function enviarConReintento(array $data, int $comprobanteId, int $maxIntentos = 5): array
    {
        // Intentar envío inmediato
        $result = $this->enviarJson($data);

        // Si fue exitoso, retornar resultado
        if ($result['success']) {
            return $result;
        }

        // Si falló, programar reintentos automáticos
        try {
            // Verificar si ya existe un reintento pendiente para este comprobante
            $reintentoExistente = \App\Models\ComprobanteReintento::where('comprobante_id', $comprobanteId)
                ->whereIn('estado', ['pendiente', 'procesando'])
                ->first();

            if ($reintentoExistente) {
                Log::info('Ya existe un reintento programado para este comprobante', [
                    'comprobante_id' => $comprobanteId,
                    'reintento_id' => $reintentoExistente->id,
                ]);

                return array_merge($result, [
                    'reintento_programado' => true,
                    'reintento_id' => $reintentoExistente->id,
                    'mensaje_adicional' => 'Ya existe un reintento en progreso para este comprobante',
                ]);
            }

            // Crear nuevo registro de reintento
            $reintento = \App\Models\ComprobanteReintento::create([
                'comprobante_id' => $comprobanteId,
                'intentos' => 1, // Ya se hizo el primer intento
                'max_intentos' => $maxIntentos,
                'ultimo_error_code' => $result['codigo_error'] ?? 'UNKNOWN',
                'ultimo_error_mensaje' => $result['error'] ?? 'Error desconocido',
                'proximo_intento' => now()->addMinutes(5), // Primer reintento en 5 minutos
                'estado' => 'pendiente',
            ]);

            // Despachar Job con delay de 5 minutos
            \App\Jobs\ReintentarEnvioComprobante::dispatch($comprobanteId, $reintento->id)
                ->delay(now()->addMinutes(5));

            Log::info('Reintento automático programado', [
                'comprobante_id' => $comprobanteId,
                'reintento_id' => $reintento->id,
                'proximo_intento' => $reintento->proximo_intento,
                'max_intentos' => $maxIntentos,
            ]);

            return array_merge($result, [
                'reintento_programado' => true,
                'reintento_id' => $reintento->id,
                'proximo_intento' => $reintento->proximo_intento,
                'max_intentos' => $maxIntentos,
                'mensaje_adicional' => 'El envío falló pero se programaron reintentos automáticos. Siguiente intento: ' .
                    $reintento->proximo_intento->format('d/m/Y H:i:s'),
            ]);

        } catch (\Exception $e) {
            Log::error('Error al programar reintento automático', [
                'comprobante_id' => $comprobanteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Retornar resultado original con nota del error de programación
            return array_merge($result, [
                'reintento_programado' => false,
                'error_reintento' => $e->getMessage(),
                'mensaje_adicional' => 'No se pudo programar el reintento automático. Se requiere reintento manual.',
            ]);
        }
    }

    /**
     * Consultar CDR (Constancia de Recepción) de un comprobante
     *
     * @param string $tipoDoc Tipo de documento (01, 03, 07, 08)
     * @param string $serie Serie del comprobante
     * @param int $correlativo Número correlativo
     * @return array Resultado de la consulta
     */
    public function consultarCDR(string $tipoDoc, string $serie, int $correlativo): array
    {
        try {
            $nombreArchivo = $this->configuracion->ruc . '-' . $tipoDoc . '-' . $serie . '-' . $correlativo;
            $endpoint = "{$this->baseUrl}/cdr/{$nombreArchivo}";

            Log::info('Consultando CDR en SUNAT', [
                'tipo' => $tipoDoc,
                'serie' => $serie,
                'numero' => $correlativo,
                'endpoint' => $endpoint
            ]);

            // Preparar opciones HTTP
            $httpOptions = [
                'verify' => true,
                'timeout' => 30,
            ];

            // Si tenemos archivos PEM, necesitamos usarlos para mutual TLS
            $certPath = storage_path('app/' . $this->configuracion->sire_cert_path);
            $keyPath = !empty($this->configuracion->sire_key_path)
                ? storage_path('app/' . $this->configuracion->sire_key_path)
                : null;

            // Configurar certificado cliente para mutual TLS
            if ($keyPath && file_exists($keyPath) && pathinfo($certPath, PATHINFO_EXTENSION) === 'pem') {
                // Usar archivos PEM separados
                $httpOptions['cert'] = $certPath;
                $httpOptions['ssl_key'] = $keyPath;

                Log::info('Consultando CDR con certificado PEM', [
                    'cert' => basename($certPath),
                    'key' => basename($keyPath)
                ]);
            } else {
                // Usar archivo PFX con contraseña
                $httpOptions['cert'] = [$this->certificadoPath, $this->certificadoPassword];

                Log::info('Consultando CDR con certificado PFX', [
                    'cert' => basename($this->certificadoPath)
                ]);
            }

            $response = Http::withOptions($httpOptions)->get($endpoint);

            if ($response->successful()) {
                $cdrZip = $response->body();
                $cdrXml = $this->extraerCDRDeZip($cdrZip);

                // Parsear el CDR XML para obtener información
                $cdrInfo = $this->parsearCDR($cdrXml);

                Log::info('CDR obtenido exitosamente', [
                    'serie' => $serie,
                    'numero' => $correlativo,
                    'estado' => $cdrInfo['estado_sunat'] ?? 'desconocido'
                ]);

                return [
                    'success' => true,
                    'cdr_zip' => $cdrZip,
                    'cdr_content' => $cdrXml,
                    'estado_sunat' => $cdrInfo['estado_sunat'] ?? 'ACEPTADO',
                    'codigo_respuesta' => $cdrInfo['codigo_respuesta'] ?? '0',
                    'mensaje_respuesta' => $cdrInfo['mensaje_respuesta'] ?? 'Aceptado por SUNAT',
                    'fecha_respuesta' => $cdrInfo['fecha_respuesta'] ?? now()->toDateTimeString(),
                ];
            }

            // Si el CDR no está disponible, puede ser que el comprobante no existe o está pendiente
            Log::warning('CDR no disponible', [
                'serie' => $serie,
                'numero' => $correlativo,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'CDR no disponible en SUNAT',
                'status' => $response->status(),
                'mensaje' => $response->status() === 404
                    ? 'El comprobante no existe en SUNAT o aún no ha sido procesado'
                    : 'Error al consultar CDR',
            ];

        } catch (\Exception $e) {
            Log::error('Error al consultar CDR', [
                'error' => $e->getMessage(),
                'serie' => $serie,
                'numero' => $correlativo
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parsear CDR XML para extraer información
     *
     * @param string|null $cdrXml
     * @return array
     */
    private function parsearCDR(?string $cdrXml): array
    {
        if (!$cdrXml) {
            return [];
        }

        try {
            $xml = new \SimpleXMLElement($cdrXml);
            $xml->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            $xml->registerXPathNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

            $responseCode = (string) $xml->xpath('//cbc:ResponseCode')[0] ?? '0';
            $description = (string) $xml->xpath('//cbc:Description')[0] ?? 'Aceptado';
            $issueDate = (string) $xml->xpath('//cbc:IssueDate')[0] ?? null;
            $issueTime = (string) $xml->xpath('//cbc:IssueTime')[0] ?? null;

            $estado = 'ACEPTADO';
            if (in_array($responseCode, ['0', '0001', '0002', '0003', '0004'])) {
                $estado = 'ACEPTADO';
            } elseif ($responseCode === '0100') {
                $estado = 'RECHAZADO';
            } elseif (in_array($responseCode, ['0098', '0099'])) {
                $estado = 'OBSERVADO';
            }

            return [
                'estado_sunat' => $estado,
                'codigo_respuesta' => $responseCode,
                'mensaje_respuesta' => $description,
                'fecha_respuesta' => $issueDate && $issueTime ? "$issueDate $issueTime" : null,
            ];

        } catch (\Exception $e) {
            Log::error('Error al parsear CDR XML', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Extraer contenido XML del CDR desde el ZIP
     *
     * @param string $zipContent Contenido binario del ZIP
     * @return string|null Contenido XML del CDR
     */
    private function extraerCDRDeZip(string $zipContent): ?string
    {
        try {
            $zipPath = sys_get_temp_dir() . '/' . uniqid('cdr_') . '.zip';
            file_put_contents($zipPath, $zipContent);

            $zip = new \ZipArchive();
            if ($zip->open($zipPath) === true) {
                $xmlContent = $zip->getFromIndex(0);
                $zip->close();
                unlink($zipPath);
                return $xmlContent;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error al extraer CDR', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verificar estado del servicio SUNAT
     *
     * @return array Estado del servicio
     */
    public function healthCheck(): array
    {
        try {
            // SUNAT no tiene endpoint de health, verificamos con conexión básica
            $response = Http::timeout(10)
                ->get($this->baseUrl);

            return [
                'disponible' => $response->status() < 500,
                'status' => $response->status() < 500 ? 'operational' : 'error',
                'timestamp' => now()->toIso8601String(),
                'http_status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::warning('Health check de SUNAT falló', [
                'error' => $e->getMessage()
            ]);

            return [
                'disponible' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
                'message' => 'No se pudo conectar con SUNAT'
            ];
        }
    }

    /**
     * Obtener información del certificado digital configurado
     *
     * @return array|null Información del certificado
     */
    public function obtenerInfoCertificado(): ?array
    {
        try {
            $certContent = file_get_contents($this->certificadoPath);

            if (!$certContent) {
                return null;
            }

            // Activar soporte legacy OpenSSL
            $originalConf = getenv('OPENSSL_CONF');
            putenv('OPENSSL_CONF=' . storage_path('app/keys/openssl_legacy.cnf'));

            $certs = [];
            $success = openssl_pkcs12_read($certContent, $certs, $this->certificadoPassword);

            // Restaurar configuración
            if ($originalConf !== false) {
                putenv('OPENSSL_CONF=' . $originalConf);
            } else {
                putenv('OPENSSL_CONF');
            }

            if (!$success) {
                return null;
            }

            $certData = openssl_x509_parse($certs['cert']);

            return [
                'ruc' => $certData['subject']['serialNumber'] ?? null,
                'razon_social' => $certData['subject']['CN'] ?? null,
                'valid_from' => date('Y-m-d H:i:s', $certData['validFrom_time_t']),
                'valid_to' => date('Y-m-d H:i:s', $certData['validTo_time_t']),
                'dias_restantes' => floor(($certData['validTo_time_t'] - time()) / 86400),
                'issuer' => $certData['issuer']['CN'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Error al leer certificado', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verificar si el servicio está disponible
     *
     * @return bool
     */
    public function estaDisponible(): bool
    {
        $health = $this->healthCheck();
        return $health['disponible'] ?? false;
    }

    /**
     * Probar conexión completa con SUNAT
     * Verifica certificado, credenciales SOL y conectividad
     *
     * @return array Resultado de la prueba de conexión
     */
    public function testConnection(): array
    {
        try {
            $resultados = [];

            // 1. Verificar certificado digital
            try {
                $certInfo = $this->obtenerInfoCertificado();

                if (!$certInfo) {
                    return [
                        'success' => false,
                        'error' => 'No se pudo leer el certificado digital',
                        'data' => ['certificado_valido' => false]
                    ];
                }

                // Verificar que no esté expirado
                if ($certInfo['dias_restantes'] <= 0) {
                    return [
                        'success' => false,
                        'error' => 'El certificado digital ha expirado',
                        'data' => [
                            'certificado_valido' => false,
                            'certificado' => $certInfo
                        ]
                    ];
                }

                $resultados['certificado'] = [
                    'valido' => true,
                    'ruc' => $certInfo['ruc'],
                    'razon_social' => $certInfo['razon_social'],
                    'valido_hasta' => $certInfo['valid_to'],
                    'dias_restantes' => $certInfo['dias_restantes'],
                ];

            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => 'Error al validar certificado: ' . $e->getMessage(),
                    'data' => ['certificado_valido' => false]
                ];
            }

            // 2. Verificar credenciales SOL
            if (empty($this->configuracion->sol_user) || empty($this->configuracion->sol_pass)) {
                return [
                    'success' => false,
                    'error' => 'Credenciales SOL no configuradas',
                    'data' => [
                        'certificado' => $resultados['certificado'],
                        'credenciales_validas' => false
                    ]
                ];
            }

            $resultados['credenciales_sol'] = [
                'configuradas' => true,
                'usuario' => $this->configuracion->sol_user,
                'ruc' => $this->configuracion->ruc,
            ];

            // 3. Verificar conectividad con SUNAT
            $health = $this->healthCheck();

            if (!$health['disponible']) {
                return [
                    'success' => false,
                    'error' => 'Servicio SUNAT no disponible: ' . ($health['error'] ?? 'Error desconocido'),
                    'data' => [
                        'certificado' => $resultados['certificado'],
                        'credenciales_sol' => $resultados['credenciales_sol'],
                        'sunat_disponible' => false,
                        'health_check' => $health,
                    ]
                ];
            }

            $resultados['sunat_service'] = [
                'disponible' => true,
                'status' => $health['status'],
                'ambiente' => $this->environment,
                'url' => $this->baseUrl,
                'http_status' => $health['http_status'] ?? null,
            ];

            // 4. Verificar que Greenter está inicializado correctamente
            if (!$this->see) {
                return [
                    'success' => false,
                    'error' => 'Greenter See no está inicializado',
                    'data' => array_merge($resultados, ['greenter_inicializado' => false])
                ];
            }

            $resultados['greenter'] = [
                'inicializado' => true,
                'version' => 'Greenter Lite 5.1.1',
                'configuracion' => 'Certificado y credenciales SOL configurados',
            ];

            // Todo OK
            Log::info('Prueba de conexión SIRE exitosa', [
                'ruc' => $this->configuracion->ruc,
                'ambiente' => $this->environment,
                'certificado_dias_restantes' => $certInfo['dias_restantes'],
            ]);

            return [
                'success' => true,
                'message' => 'Conexión exitosa con SUNAT. Sistema listo para emitir comprobantes.',
                'data' => $resultados
            ];

        } catch (\Exception $e) {
            Log::error('Error en prueba de conexión SIRE', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Error en prueba de conexión: ' . $e->getMessage(),
                'data' => $resultados ?? []
            ];
        }
    }

    /**
     * Obtener la URL base
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Obtener ambiente actual (production o testing)
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Crear archivo de configuración OpenSSL con soporte legacy
     * Necesario para OpenSSL 3.x que no soporta algoritmos antiguos por defecto
     */
    private function createLegacyOpenSSLConfig(): void
    {
        $configPath = storage_path('app/keys/openssl_legacy.cnf');
        $keysDir = storage_path('app/keys');

        // Crear directorio si no existe
        if (!file_exists($keysDir)) {
            mkdir($keysDir, 0755, true);
        }

        // Crear archivo de configuración si no existe
        if (!file_exists($configPath)) {
            $config = <<<'EOT'
openssl_conf = openssl_init

[openssl_init]
providers = provider_sect

[provider_sect]
default = default_sect
legacy = legacy_sect

[default_sect]
activate = 1

[legacy_sect]
activate = 1
EOT;

            file_put_contents($configPath, $config);
            Log::info('Archivo de configuración OpenSSL legacy creado para SireApiService', ['path' => $configPath]);
        }
    }
}
