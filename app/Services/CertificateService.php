<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use App\Models\ConfiguracionSunat;

class CertificateService
{
    /**
     * Leer y validar un archivo .p12 con contraseña
     * Retorna metadatos del certificado (RUC, razón social, vigencia)
     */
    public static function validateAndExtractMetadata(string $filePath, string $password): array
    {
        try {
            // Leer el archivo
            if (!Storage::exists($filePath)) {
                return ['success' => false, 'error' => 'Archivo .p12 no encontrado'];
            }

            $certificateContent = Storage::get($filePath);

            // Si la contraseña está serializada (empieza con "s:"), deserializarla
            // Esto ocurre si la contraseña fue guardada con serialize() por error
            if (strpos($password, 's:') === 0) {
                try {
                    $unserialized = @unserialize($password);
                    if ($unserialized !== false) {
                        $password = $unserialized;
                    }
                } catch (\Exception $e) {
                    // Continuar con la contraseña original si no se puede deserializar
                }
            }

            Log::info('Intentando validar certificado', [
                'filesize' => strlen($certificateContent),
                'has_password' => !empty($password),
                'openssl_version' => OPENSSL_VERSION_TEXT,
                'php_version' => PHP_VERSION
            ]);

            // Crear configuración legacy de OpenSSL si no existe
            self::createLegacyOpenSSLConfig();

            // Parsear el .p12 con la contraseña
            $certs = [];
            $success = false;

            // Método 1: Intentar con configuración legacy para OpenSSL 3.x
            $originalConf = getenv('OPENSSL_CONF');
            $legacyConf = storage_path('app/keys/openssl_legacy.cnf');

            Log::info('Intentando leer certificado con configuración legacy', [
                'legacy_conf_exists' => file_exists($legacyConf),
                'legacy_conf_path' => $legacyConf
            ]);

            putenv('OPENSSL_CONF=' . $legacyConf);

            if (@openssl_pkcs12_read($certificateContent, $certs, $password)) {
                $success = true;
                Log::info('Certificado leído exitosamente con configuración legacy');
            } else {
                $opensslError = openssl_error_string();
                Log::warning('Falló lectura con legacy config', ['error' => $opensslError]);
            }

            // Restaurar configuración original
            if ($originalConf !== false) {
                putenv('OPENSSL_CONF=' . $originalConf);
            } else {
                putenv('OPENSSL_CONF');
            }

            // Método 2: Intentar sin configuración especial
            if (!$success) {
                Log::info('Intentando leer certificado sin configuración legacy');

                if (@openssl_pkcs12_read($certificateContent, $certs, $password)) {
                    $success = true;
                    Log::info('Certificado leído exitosamente sin configuración legacy');
                } else {
                    $opensslError = openssl_error_string();
                    Log::warning('Falló lectura sin legacy config', ['error' => $opensslError]);
                }
            }

            // Método 3: Intentar con contraseña vacía (algunos certificados no tienen contraseña)
            if (!$success && !empty($password)) {
                Log::info('Intentando con contraseña vacía');

                if (@openssl_pkcs12_read($certificateContent, $certs, '')) {
                    $success = true;
                    $password = '';
                    Log::info('Certificado leído exitosamente con contraseña vacía');
                } else {
                    $opensslError = openssl_error_string();
                    Log::warning('Falló lectura con contraseña vacía', ['error' => $opensslError]);
                }
            }

            // Método 4: Si es error de algoritmos legacy, intentar conversión con openssl CLI
            if (!$success) {
                $finalError = openssl_error_string() ?: 'Error desconocido de OpenSSL';

                // Si el error es específicamente sobre algoritmos legacy, intentar conversión
                if (strpos($finalError, 'unsupported') !== false || strpos($finalError, '0308010C') !== false) {
                    Log::info('Detectado certificado legacy, intentando conversión con openssl CLI');

                    $convertedContent = self::convertLegacyCertificate($certificateContent, $password);

                    if ($convertedContent !== false) {
                        // Intentar leer el certificado convertido
                        if (@openssl_pkcs12_read($convertedContent, $certs, $password)) {
                            $success = true;
                            $certificateContent = $convertedContent; // Usar el convertido
                            Log::info('Certificado legacy convertido y leído exitosamente');
                        }
                    }
                }

                if (!$success) {
                    Log::error('No se pudo leer el certificado después de todos los intentos', [
                        'openssl_error' => $finalError,
                        'has_password' => !empty($password)
                    ]);

                    return ['success' => false, 'error' => 'No se pudo leer el certificado. Verifica que la contraseña sea correcta. Error: ' . $finalError];
                }
            }

            // Extraer datos del certificado
            if (!isset($certs['cert'])) {
                return ['success' => false, 'error' => 'No se encontró certificado en el archivo'];
            }

            $certData = openssl_x509_parse($certs['cert']);

            if (!$certData) {
                return ['success' => false, 'error' => 'No se pudo parsear el certificado'];
            }

            // Extraer RUC del subject (normalmente en CN o O)
            $subject = $certData['subject'] ?? [];
            $ruc = $subject['CN'] ?? $subject['O'] ?? 'Desconocido';

            // Extraer razón social (normalmente en O - Organization)
            $razonSocial = $subject['O'] ?? $subject['CN'] ?? 'Desconocida';

            // Fechas de validez
            $validFrom = isset($certData['validFrom_time_t']) 
                ? date('Y-m-d', $certData['validFrom_time_t']) 
                : 'Desconocida';
            $validTo = isset($certData['validTo_time_t']) 
                ? date('Y-m-d', $certData['validTo_time_t']) 
                : 'Desconocida';

            // Calcular días restantes
            $diasRestantes = 0;
            if (isset($certData['validTo_time_t'])) {
                $diasRestantes = ceil(($certData['validTo_time_t'] - time()) / 86400);
            }

            // Verificar si está vigente
            $isValid = $diasRestantes > 0;

            return [
                'success' => true,
                'ruc' => $ruc,
                'razon_social' => $razonSocial,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'dias_restantes' => max(0, $diasRestantes),
                'is_valid' => $isValid,
                'issuer' => $certData['issuer'] ?? [],
                'subject' => $subject,
            ];

        } catch (\Exception $e) {
            Log::error('Error validating certificate', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtener metadatos del certificado configurado
     */
    public static function getCertificateMetadata(): array
    {
        try {
            $config = ConfiguracionSunat::obtenerActiva();

            if (!$config || empty($config->sire_cert_path)) {
                return ['success' => false, 'error' => 'No hay certificado configurado'];
            }

            $password = '';
            if (!empty($config->sire_cert_password)) {
                $password = Crypt::decryptString($config->sire_cert_password);

                // Si la contraseña está serializada después de desencriptar, deserializarla
                if (strpos($password, 's:') === 0) {
                    try {
                        $unserialized = @unserialize($password);
                        if ($unserialized !== false) {
                            $password = $unserialized;
                        }
                    } catch (\Exception $e) {
                        // Continuar con la contraseña original
                    }
                }
            }

            return self::validateAndExtractMetadata($config->sire_cert_path, $password);

        } catch (\Exception $e) {
            Log::error('Error getting certificate metadata', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Extraer certificado .p12/.pfx a archivos PEM separados
     * Esto resuelve problemas de compatibilidad con OpenSSL 3.x
     */
    public static function extractToPem(string $filePath, string $password, ?string $rucPreferido = null): array
    {
        try {
            if (!Storage::exists($filePath)) {
                return ['success' => false, 'error' => 'Archivo no encontrado'];
            }

            $pfxContent = Storage::get($filePath);

            // Si la contraseña está serializada (empieza con "s:"), deserializarla
            // Esto ocurre si la contraseña fue guardada con serialize() por error
            if (strpos($password, 's:') === 0) {
                try {
                    $unserialized = @unserialize($password);
                    if ($unserialized !== false) {
                        $password = $unserialized;
                        \Log::info('Contraseña deserializada correctamente');
                    }
                } catch (\Exception $e) {
                    \Log::warning('No se pudo deserializar la contraseña: ' . $e->getMessage());
                }
            }

            // Crear configuración legacy
            self::createLegacyOpenSSLConfig();

            // Activar soporte legacy
            $originalConf = getenv('OPENSSL_CONF');
            putenv('OPENSSL_CONF=' . storage_path('app/keys/openssl_legacy.cnf'));

            $certs = [];
            $success = @openssl_pkcs12_read($pfxContent, $certs, $password);

            // Restaurar configuración
            if ($originalConf !== false) {
                putenv('OPENSSL_CONF=' . $originalConf);
            } else {
                putenv('OPENSSL_CONF');
            }

            // Si falla, intentar conversión legacy
            if (!$success || !isset($certs['cert']) || !isset($certs['pkey'])) {
                $opensslError = openssl_error_string();

                Log::warning('Falló extracción normal, intentando conversión legacy', [
                    'error' => $opensslError
                ]);

                // Si es error de algoritmos legacy, intentar conversión
                if (strpos($opensslError, 'unsupported') !== false || strpos($opensslError, '0308010C') !== false) {
                    $convertedContent = self::convertLegacyCertificate($pfxContent, $password);

                    if ($convertedContent !== false) {
                        // Intentar leer el certificado convertido
                        if (@openssl_pkcs12_read($convertedContent, $certs, $password)) {
                            $success = true;
                            Log::info('Certificado legacy convertido para extracción PEM');
                        }
                    }
                }

                if (!$success || !isset($certs['cert']) || !isset($certs['pkey'])) {
                    return ['success' => false, 'error' => 'No se pudo extraer certificado y clave privada del PFX'];
                }
            }

            // Obtener RUC del certificado
            $certData = openssl_x509_parse($certs['cert']);
            $rucCert = $certData['subject']['serialNumber'] ?? $certData['subject']['CN'] ?? 'unknown';

            // Preferir RUC de la configuración; si no, sanitizar el valor del certificado
            $rucBase = ! empty($rucPreferido) ? $rucPreferido : $rucCert;
            $rucSlug = self::sanitizarNombreArchivo($rucBase);
            $timestamp = time();

            // Guardar certificado en formato PEM
            $certFilename = "cert_{$rucSlug}_{$timestamp}.pem";
            $certPath = "keys/{$certFilename}";
            Storage::put($certPath, $certs['cert']);

            // Guardar clave privada en formato PEM
            $keyFilename = "key_{$rucSlug}_{$timestamp}.pem";
            $keyPath = "keys/{$keyFilename}";
            Storage::put($keyPath, $certs['pkey']);

            Log::info('Certificado extraído a formato PEM', [
                'cert_path' => $certPath,
                'key_path' => $keyPath,
                'ruc' => $rucSlug
            ]);

            return [
                'success' => true,
                'cert_path' => $certPath,
                'key_path' => $keyPath,
                'ruc' => $rucSlug
            ];

        } catch (\Exception $e) {
            Log::error('Error extrayendo certificado a PEM', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sanitizar un valor (RUC o CN) para usarlo como parte de un nombre de archivo.
     * Elimina espacios y caracteres no alfanuméricos, dejando solo [A-Za-z0-9_-].
     */
    private static function sanitizarNombreArchivo(string $valor): string
    {
        $limpio = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim($valor));
        $limpio = trim($limpio, '_');
        return $limpio !== '' ? $limpio : 'cert';
    }

    /**
     * Convertir certificado legacy usando comandos CLI de OpenSSL
     * Convierte PFX con algoritmos legacy a PFX con algoritmos modernos
     */
    private static function convertLegacyCertificate(string $certificateContent, string $password)
    {
        try {
            // Crear archivos temporales
            $tempPfx = tempnam(sys_get_temp_dir(), 'cert_') . '.pfx';
            $tempPem = tempnam(sys_get_temp_dir(), 'cert_') . '.pem';
            $tempNewPfx = tempnam(sys_get_temp_dir(), 'cert_') . '.pfx';

            // Guardar certificado original
            file_put_contents($tempPfx, $certificateContent);

            Log::info('Iniciando conversión de certificado legacy', [
                'temp_pfx' => $tempPfx,
                'temp_pem' => $tempPem,
                'temp_new_pfx' => $tempNewPfx
            ]);

            // Paso 1: Convertir PFX a PEM usando algoritmos legacy
            $convertToPem = sprintf(
                'openssl pkcs12 -in %s -out %s -nodes -legacy -passin pass:%s 2>&1',
                escapeshellarg($tempPfx),
                escapeshellarg($tempPem),
                escapeshellarg($password)
            );

            exec($convertToPem, $output1, $returnCode1);

            Log::info('Resultado conversión PFX a PEM', [
                'return_code' => $returnCode1,
                'output' => implode("\n", $output1),
                'pem_exists' => file_exists($tempPem)
            ]);

            if ($returnCode1 !== 0 || !file_exists($tempPem)) {
                Log::error('Falló conversión a PEM', ['output' => implode("\n", $output1)]);
                @unlink($tempPfx);
                @unlink($tempPem);
                @unlink($tempNewPfx);
                return false;
            }

            // Paso 2: Convertir PEM de vuelta a PFX con algoritmos modernos
            $convertToPfx = sprintf(
                'openssl pkcs12 -export -in %s -out %s -passout pass:%s 2>&1',
                escapeshellarg($tempPem),
                escapeshellarg($tempNewPfx),
                escapeshellarg($password)
            );

            exec($convertToPfx, $output2, $returnCode2);

            Log::info('Resultado conversión PEM a PFX', [
                'return_code' => $returnCode2,
                'output' => implode("\n", $output2),
                'pfx_exists' => file_exists($tempNewPfx)
            ]);

            if ($returnCode2 !== 0 || !file_exists($tempNewPfx)) {
                Log::error('Falló conversión a PFX moderno', ['output' => implode("\n", $output2)]);
                @unlink($tempPfx);
                @unlink($tempPem);
                @unlink($tempNewPfx);
                return false;
            }

            // Leer certificado convertido
            $convertedContent = file_get_contents($tempNewPfx);

            // Limpiar archivos temporales
            @unlink($tempPfx);
            @unlink($tempPem);
            @unlink($tempNewPfx);

            Log::info('Certificado legacy convertido exitosamente', [
                'original_size' => strlen($certificateContent),
                'converted_size' => strlen($convertedContent)
            ]);

            return $convertedContent;

        } catch (\Exception $e) {
            Log::error('Error en conversión de certificado legacy', [
                'error' => $e->getMessage()
            ]);

            // Limpiar archivos temporales en caso de error
            @unlink($tempPfx ?? null);
            @unlink($tempPem ?? null);
            @unlink($tempNewPfx ?? null);

            return false;
        }
    }

    /**
     * Crear archivo de configuración OpenSSL con soporte legacy
     * Necesario para OpenSSL 3.x que no soporta algoritmos antiguos por defecto
     */
    private static function createLegacyOpenSSLConfig(): void
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
            Log::info('Archivo de configuración OpenSSL legacy creado', ['path' => $configPath]);
        }
    }
}
