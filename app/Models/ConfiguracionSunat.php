<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionSunat extends Model
{
    use HasFactory;

    protected $fillable = [
        'ruc',
        'usuario_sol',
        'clave_sol',
        'ambiente',
        'certificado_nombre',
        'certificado_contenido',
        'certificado_clave',
        'certificado_file_path',
        'certificado_pem_path',
        'clave_privada_pem_path',
        'razon_social',
        'nombre_comercial',
        'direccion',
        'ubigeo',
        'distrito',
        'provincia',
        'departamento',
        'serie_factura',
        'numero_inicial_factura',
        'serie_boleta',
        'numero_inicial_boleta',
        'serie_nota_credito',
        'numero_inicial_nota_credito',
        'serie_nota_debito',
        'numero_inicial_nota_debito',
        'activo',
        // Integración SIRE
        'sire_api_url',
        'sire_api_token',
        'usar_sire',
        'modo_produccion',
        // Series SIRE para Testing
        'sire_serie_boleta_test',
        'sire_numero_boleta_test',
        'sire_serie_factura_test',
        'sire_numero_factura_test',
        // Series SIRE para Producción
        'sire_serie_boleta_prod',
        'sire_numero_boleta_prod',
        'sire_serie_factura_prod',
        'sire_numero_factura_prod',
        // Series para notas
        'sire_serie_nota_credito',
        'sire_numero_nota_credito',
        'sire_serie_nota_debito',
        'sire_numero_nota_debito',
        // Greenter (conexión directa SUNAT)
        'sire_cert_path',
        'sire_key_path',
        'sire_cert_password',
        'sol_user',
        'sol_pass',
        // OAuth2 SUNAT API
        'sire_client_id',
        'sire_client_secret',
        'sire_access_token',
        'sire_token_expires_at',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'usar_sire' => 'boolean',
        'modo_produccion' => 'boolean',
        'sire_numero_boleta_test' => 'integer',
        'sire_numero_factura_test' => 'integer',
        'sire_numero_boleta_prod' => 'integer',
        'sire_numero_factura_prod' => 'integer',
        'sire_numero_nota_credito' => 'integer',
        'sire_numero_nota_debito' => 'integer',
        'sire_token_expires_at' => 'datetime',
    ];

    // Método para obtener la configuración activa
    public static function obtenerActiva()
    {
        return static::where('activo', true)->first();
    }

    // Método para activar esta configuración y desactivar las demás
    public function activar()
    {
        // Desactivar todas las configuraciones
        static::query()->update(['activo' => false]);

        // Activar esta configuración
        $this->update(['activo' => true]);
    }

    // Accesor para obtener el contenido del certificado decodificado
    public function getCertificadoContentAttribute()
    {
        return $this->certificado_contenido ? base64_decode($this->certificado_contenido) : null;
    }

    // Mutador para codificar el contenido del certificado
    public function setCertificadoContentAttribute($value)
    {
        $this->attributes['certificado_contenido'] = $value ? base64_encode($value) : null;
    }

    // Obtener el endpoint de SUNAT según el ambiente
    public function getSunatEndpointAttribute()
    {
        return $this->ambiente === 'produccion'
            ? \Greenter\Ws\Services\SunatEndpoints::FE_PRODUCCION
            : \Greenter\Ws\Services\SunatEndpoints::FE_BETA;
    }

    // Guardar certificado como archivo físico
    public function saveCertificateAsFile($fileContent, $password)
    {
        $filename = 'certificado_'.$this->ruc.'_'.time().'.pfx';
        $filepath = storage_path('app/keys/'.$filename);

        // Asegurar que el directorio existe
        $directory = dirname($filepath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Guardar el archivo
        file_put_contents($filepath, $fileContent);

        // Intentar extraer y guardar por separado certificado y clave privada
        $this->extractAndSaveSeparateFiles($fileContent, $password);

        // Actualizar la configuración con la ruta del archivo
        $this->update([
            'certificado_file_path' => $filename,
            'certificado_clave' => $password,
            'certificado_contenido' => null, // Limpiar contenido base64
        ]);

        return $filepath;
    }

    // Extraer certificado y clave privada por separado del PFX
    private function extractAndSaveSeparateFiles($pfxContent, $password)
    {
        try {
            // Usar openssl_pkcs12_read con configuración legacy para evitar errores de OpenSSL moderno
            $originalCiphers = openssl_get_cipher_methods();

            // Intentar usar configuración de OpenSSL legacy
            $tempConfig = [
                'config' => storage_path('app/keys/openssl_legacy.cnf'),
            ];

            // Crear archivo de configuración legacy si no existe
            $this->createLegacyOpenSSLConfig();

            $certs = [];
            $success = false;

            // Método 1: Intentar con configuración legacy
            putenv('OPENSSL_CONF='.storage_path('app/keys/openssl_legacy.cnf'));
            if (openssl_pkcs12_read($pfxContent, $certs, $password)) {
                $success = true;
            }
            putenv('OPENSSL_CONF'); // Limpiar variable de entorno

            // Método 2: Intentar sin configuración especial
            if (! $success) {
                if (openssl_pkcs12_read($pfxContent, $certs, $password)) {
                    $success = true;
                }
            }

            if ($success && isset($certs['cert']) && isset($certs['pkey'])) {
                // Guardar certificado por separado
                $certPath = storage_path('app/keys/cert_'.$this->ruc.'_'.time().'.pem');
                file_put_contents($certPath, $certs['cert']);

                // Guardar clave privada por separado
                $keyPath = storage_path('app/keys/key_'.$this->ruc.'_'.time().'.pem');
                file_put_contents($keyPath, $certs['pkey']);

                \Log::info('Certificado y clave privada extraídos y guardados por separado', [
                    'cert_path' => basename($certPath),
                    'key_path' => basename($keyPath),
                ]);

                // Actualizar rutas en la configuración
                $this->update([
                    'certificado_pem_path' => basename($certPath),
                    'clave_privada_pem_path' => basename($keyPath),
                ]);

                return true;
            }

            \Log::warning('No se pudo extraer certificado y clave privada del PFX');

            return false;

        } catch (\Exception $e) {
            \Log::error('Error extrayendo certificado y clave: '.$e->getMessage());

            return false;
        }
    }

    // Crear archivo de configuración OpenSSL legacy
    private function createLegacyOpenSSLConfig()
    {
        $configPath = storage_path('app/keys/openssl_legacy.cnf');

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

    // Obtener la ruta completa del certificado
    public function getCertificateFilePath()
    {
        if (! $this->certificado_file_path) {
            return null;
        }

        $filepath = storage_path('app/keys/'.$this->certificado_file_path);

        return file_exists($filepath) ? $filepath : null;
    }

    // Verificar si el certificado existe como archivo
    public function hasCertificateFile()
    {
        return $this->getCertificateFilePath() !== null;
    }

    // Verificar si existen archivos PEM separados
    public function hasSeparatePemFiles()
    {
        return $this->getCertificatePemPath() !== null && $this->getPrivateKeyPemPath() !== null;
    }

    // Obtener la ruta del certificado PEM
    public function getCertificatePemPath()
    {
        if (! $this->certificado_pem_path) {
            return null;
        }

        $filepath = storage_path('app/keys/'.$this->certificado_pem_path);

        return file_exists($filepath) ? $filepath : null;
    }

    // Obtener la ruta de la clave privada PEM
    public function getPrivateKeyPemPath()
    {
        if (! $this->clave_privada_pem_path) {
            return null;
        }

        $filepath = storage_path('app/keys/'.$this->clave_privada_pem_path);

        return file_exists($filepath) ? $filepath : null;
    }

    // Obtener contenido del certificado PEM
    public function getCertificatePemContent()
    {
        $path = $this->getCertificatePemPath();

        return $path ? file_get_contents($path) : null;
    }

    // Obtener contenido de la clave privada PEM
    public function getPrivateKeyPemContent()
    {
        $path = $this->getPrivateKeyPemPath();

        return $path ? file_get_contents($path) : null;
    }
}
