<?php

namespace App\Console\Commands;

use App\Models\ConfiguracionSunat;
use Illuminate\Console\Command;

class ExtractPemFromPfx extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sunat:extract-pem {--force : Force extraction even if PEM files already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract PEM certificate and private key from PFX file to solve OpenSSL compatibility issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $config = ConfiguracionSunat::obtenerActiva();

        if (! $config) {
            $this->error('No hay configuración SUNAT activa');

            return 1;
        }

        $this->info("RUC: {$config->ruc}");

        // Verificar si ya existen archivos PEM
        if ($config->hasSeparatePemFiles() && ! $this->option('force')) {
            $this->info('Los archivos PEM ya existen:');
            $this->info('Certificado: '.$config->getCertificatePemPath());
            $this->info('Clave privada: '.$config->getPrivateKeyPemPath());
            $this->info('Use --force para forzar la extracción nuevamente');

            return 0;
        }

        // Verificar si existe archivo PFX
        $pfxPath = $config->getCertificateFilePath();
        if (! $pfxPath) {
            $this->error('No se encontró archivo PFX');

            return 1;
        }

        $this->info("Archivo PFX: {$pfxPath}");

        // Intentar extraer con configuración legacy de OpenSSL
        $this->info('Intentando extraer certificado y clave privada...');

        if ($this->extractWithLegacyOpenSSL($config, $pfxPath)) {
            $this->info('✅ Extracción exitosa con configuración legacy de OpenSSL');

            return 0;
        }

        // Método alternativo usando tempnam y opciones diferentes
        if ($this->extractWithAlternativeMethod($config, $pfxPath)) {
            $this->info('✅ Extracción exitosa con método alternativo');

            return 0;
        }

        $this->error('❌ No se pudo extraer el certificado y clave privada');

        return 1;
    }

    private function extractWithLegacyOpenSSL($config, $pfxPath)
    {
        try {
            $pfxContent = file_get_contents($pfxPath);
            $password = $config->certificado_clave;

            // Crear configuración OpenSSL legacy
            $legacyConfigPath = storage_path('app/keys/openssl_legacy.cnf');
            $this->createLegacyOpenSSLConfig($legacyConfigPath);

            // Intentar con configuración legacy
            $originalConf = getenv('OPENSSL_CONF');
            putenv('OPENSSL_CONF='.$legacyConfigPath);

            $certs = [];
            $success = openssl_pkcs12_read($pfxContent, $certs, $password);

            // Restaurar configuración original
            if ($originalConf !== false) {
                putenv('OPENSSL_CONF='.$originalConf);
            } else {
                putenv('OPENSSL_CONF');
            }

            if ($success && isset($certs['cert']) && isset($certs['pkey'])) {
                // Guardar archivos PEM
                $certPath = storage_path('app/keys/cert_'.$config->ruc.'_'.time().'.pem');
                $keyPath = storage_path('app/keys/key_'.$config->ruc.'_'.time().'.pem');

                file_put_contents($certPath, $certs['cert']);
                file_put_contents($keyPath, $certs['pkey']);

                // Actualizar configuración
                $config->update([
                    'certificado_pem_path' => basename($certPath),
                    'clave_privada_pem_path' => basename($keyPath),
                ]);

                $this->info("Certificado guardado: {$certPath}");
                $this->info("Clave privada guardada: {$keyPath}");

                return true;
            }

            return false;

        } catch (\Exception $e) {
            $this->error('Error con método legacy: '.$e->getMessage());

            return false;
        }
    }

    private function extractWithAlternativeMethod($config, $pfxPath)
    {
        try {
            // Usar comandos openssl con configuración específica para Windows
            $password = $config->certificado_clave;
            $escapedPassword = escapeshellarg($password);
            $escapedPath = escapeshellarg($pfxPath);

            // Crear archivos temporales
            $tempCert = tempnam(sys_get_temp_dir(), 'cert_').'.pem';
            $tempKey = tempnam(sys_get_temp_dir(), 'key_').'.pem';

            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

            // Extraer certificado
            if ($isWindows) {
                $certCmd = "openssl pkcs12 -in $escapedPath -clcerts -nokeys -out ".escapeshellarg($tempCert)." -passin pass:$escapedPassword -provider legacy -provider default 2>nul";
                $keyCmd = "openssl pkcs12 -in $escapedPath -nocerts -nodes -out ".escapeshellarg($tempKey)." -passin pass:$escapedPassword -provider legacy -provider default 2>nul";
            } else {
                $certCmd = "openssl pkcs12 -in $escapedPath -clcerts -nokeys -out ".escapeshellarg($tempCert)." -passin pass:$escapedPassword 2>/dev/null";
                $keyCmd = "openssl pkcs12 -in $escapedPath -nocerts -nodes -out ".escapeshellarg($tempKey)." -passin pass:$escapedPassword 2>/dev/null";
            }

            $this->info('Ejecutando comando de extracción de certificado...');
            exec($certCmd, $certOutput, $certReturnCode);

            $this->info('Ejecutando comando de extracción de clave privada...');
            exec($keyCmd, $keyOutput, $keyReturnCode);

            if ($certReturnCode === 0 && $keyReturnCode === 0 &&
                file_exists($tempCert) && file_exists($tempKey) &&
                filesize($tempCert) > 0 && filesize($tempKey) > 0) {

                $certContent = file_get_contents($tempCert);
                $keyContent = file_get_contents($tempKey);

                // Verificar que realmente contengan datos válidos
                if (strpos($certContent, '-----BEGIN CERTIFICATE-----') !== false &&
                    (strpos($keyContent, '-----BEGIN PRIVATE KEY-----') !== false ||
                     strpos($keyContent, '-----BEGIN RSA PRIVATE KEY-----') !== false)) {

                    // Guardar archivos finales
                    $finalCertPath = storage_path('app/keys/cert_'.$config->ruc.'_'.time().'.pem');
                    $finalKeyPath = storage_path('app/keys/key_'.$config->ruc.'_'.time().'.pem');

                    file_put_contents($finalCertPath, $certContent);
                    file_put_contents($finalKeyPath, $keyContent);

                    // Actualizar configuración
                    $config->update([
                        'certificado_pem_path' => basename($finalCertPath),
                        'clave_privada_pem_path' => basename($finalKeyPath),
                    ]);

                    $this->info("Certificado guardado: {$finalCertPath}");
                    $this->info("Clave privada guardada: {$finalKeyPath}");

                    // Limpiar archivos temporales
                    unlink($tempCert);
                    unlink($tempKey);

                    return true;
                }
            }

            // Limpiar archivos temporales en caso de error
            if (file_exists($tempCert)) {
                unlink($tempCert);
            }
            if (file_exists($tempKey)) {
                unlink($tempKey);
            }

            return false;

        } catch (\Exception $e) {
            $this->error('Error con método alternativo: '.$e->getMessage());

            return false;
        }
    }

    private function createLegacyOpenSSLConfig($configPath)
    {
        if (! file_exists($configPath)) {
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
        }
    }
}
