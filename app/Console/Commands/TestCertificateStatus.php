<?php

namespace App\Console\Commands;

use App\Models\ConfiguracionSunat;
use App\Services\GreenterService;
use Illuminate\Console\Command;

class TestCertificateStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sunat:test-certificate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test certificate configuration and loading';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Diagnóstico de Certificado SUNAT ===');

        $config = ConfiguracionSunat::obtenerActiva();
        if (! $config) {
            $this->error('No hay configuración SUNAT activa');

            return 1;
        }

        $this->info("RUC: {$config->ruc}");

        // Verificar archivos PEM
        $this->info('--- Verificando archivos PEM ---');
        $this->info('Has separate PEM files: '.($config->hasSeparatePemFiles() ? 'YES' : 'NO'));

        if ($config->hasSeparatePemFiles()) {
            $certPath = $config->getCertificatePemPath();
            $keyPath = $config->getPrivateKeyPemPath();

            $this->info("Certificate path: {$certPath}");
            $this->info("Private key path: {$keyPath}");

            $certContent = $config->getCertificatePemContent();
            $keyContent = $config->getPrivateKeyPemContent();

            $this->info('Certificate size: '.strlen($certContent).' bytes');
            $this->info('Private key size: '.strlen($keyContent).' bytes');

            // Validar contenido
            if (strpos($certContent, '-----BEGIN CERTIFICATE-----') !== false) {
                $this->info('✅ Certificate PEM format is valid');
            } else {
                $this->error('❌ Certificate PEM format is invalid');
            }

            if (strpos($keyContent, '-----BEGIN PRIVATE KEY-----') !== false ||
                strpos($keyContent, '-----BEGIN RSA PRIVATE KEY-----') !== false) {
                $this->info('✅ Private key PEM format is valid');
            } else {
                $this->error('❌ Private key PEM format is invalid');
            }

            // Validar con OpenSSL
            $certResource = openssl_x509_read($certContent);
            if ($certResource) {
                $this->info('✅ Certificate is valid according to OpenSSL');
            } else {
                $this->error('❌ Certificate is invalid: '.openssl_error_string());
            }

            $keyResource = openssl_pkey_get_private($keyContent);
            if ($keyResource) {
                $this->info('✅ Private key is valid according to OpenSSL');
            } else {
                $this->error('❌ Private key is invalid: '.openssl_error_string());
            }
        }

        // Verificar archivo PFX
        $this->info('--- Verificando archivo PFX ---');
        $pfxPath = $config->getCertificateFilePath();
        $this->info('Has PFX file: '.($pfxPath ? 'YES' : 'NO'));
        if ($pfxPath) {
            $this->info("PFX path: {$pfxPath}");
        }

        // Probar GreenterService
        $this->info('--- Probando GreenterService ---');
        try {
            $greenterService = new GreenterService;
            $this->info('✅ GreenterService initialized successfully');

            // Intentar obtener compañía (esto no requiere certificado)
            $company = $greenterService->getCompany();
            $this->info("Company RUC: {$company->getRuc()}");
            $this->info("Company name: {$company->getRazonSocial()}");

            // Probar creación de XML (esto SÍ requiere certificado)
            $this->info('--- Probando generación de XML firmado ---');
            try {
                // Crear una factura de prueba simple
                $clientData = [
                    'tipo_documento' => '1',
                    'numero_documento' => '12345678',
                    'razon_social' => 'Cliente de Prueba',
                ];

                $items = [
                    [
                        'codigo' => 'ITEM001',
                        'descripcion' => 'Item de prueba',
                        'cantidad' => 1,
                        'valor_unitario' => 100.00,
                        'unidad' => 'NIU',
                    ],
                ];

                $invoice = $greenterService->createInvoice($clientData, $items, '001');
                $this->info('✅ Invoice created successfully');

                // Intentar generar XML firmado (aquí es donde falla normalmente)
                $xml = $greenterService->getXmlFromInvoice($invoice);
                if ($xml) {
                    $this->info('✅ XML signed successfully');
                    $this->info('XML length: '.strlen($xml).' bytes');
                } else {
                    $this->error('❌ Failed to generate signed XML');
                }

            } catch (\Exception $e) {
                $this->error('❌ XML signing failed: '.$e->getMessage());
            }

        } catch (\Exception $e) {
            $this->error('❌ GreenterService failed: '.$e->getMessage());
        }

        return 0;
    }
}
