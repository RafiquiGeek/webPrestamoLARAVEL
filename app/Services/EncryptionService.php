<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class EncryptionService
{
    /**
     * Campos sensibles que deben ser encriptados antes de sincronización
     */
    private const SENSITIVE_FIELDS = [
        'dni',
        'ruc',
        'telefono',
        'email',
        'direccion',
        'cuenta_bancaria',
        'numero_tarjeta',
        'observaciones',
        'notas',
        'comentarios',
    ];

    /**
     * Campos que contienen información financiera crítica
     */
    private const FINANCIAL_FIELDS = [
        'monto',
        'saldo',
        'capital',
        'interes',
        'mora',
        'importe',
        'total',
    ];

    /**
     * Encripta datos sensibles antes de la sincronización
     */
    public function encryptSensitiveData(array $data, string $table): array
    {
        $encryptedData = $data;

        foreach (self::SENSITIVE_FIELDS as $field) {
            if (isset($data[$field]) && ! empty($data[$field])) {
                try {
                    $encryptedData[$field] = Crypt::encryptString($data[$field]);
                    $encryptedData[$field.'_encrypted'] = true;
                } catch (\Exception $e) {
                    Log::channel('database-sync')->error("Error encriptando campo {$field}", [
                        'table' => $table,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Crear hash de verificación para datos financieros
        $encryptedData['financial_hash'] = $this->createFinancialHash($data);

        return $encryptedData;
    }

    /**
     * Desencripta datos al leer desde bases de datos secundarias
     */
    public function decryptSensitiveData(array $data): array
    {
        $decryptedData = $data;

        foreach (self::SENSITIVE_FIELDS as $field) {
            if (isset($data[$field]) && isset($data[$field.'_encrypted']) && $data[$field.'_encrypted']) {
                try {
                    $decryptedData[$field] = Crypt::decryptString($data[$field]);
                    unset($decryptedData[$field.'_encrypted']);
                } catch (\Exception $e) {
                    Log::channel('database-sync')->error("Error desencriptando campo {$field}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $decryptedData;
    }

    /**
     * Crea hash de verificación para datos financieros
     */
    public function createFinancialHash(array $data): string
    {
        $financialData = [];

        foreach (self::FINANCIAL_FIELDS as $field) {
            if (isset($data[$field])) {
                $financialData[$field] = $data[$field];
            }
        }

        return hash('sha256', json_encode($financialData).config('app.key'));
    }

    /**
     * Verifica la integridad de los datos financieros
     */
    public function verifyFinancialIntegrity(array $data): bool
    {
        if (! isset($data['financial_hash'])) {
            return false;
        }

        $originalHash = $data['financial_hash'];
        unset($data['financial_hash']);

        $calculatedHash = $this->createFinancialHash($data);

        return hash_equals($originalHash, $calculatedHash);
    }

    /**
     * Sanitiza datos antes de la sincronización
     */
    public function sanitizeData(array $data, string $table): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            // Remover campos peligrosos
            if (in_array($key, ['password', 'remember_token', 'api_token'])) {
                continue;
            }

            // Sanitizar strings
            if (is_string($value)) {
                $value = $this->sanitizeString($value);
            }

            // Validar datos financieros
            if (in_array($key, self::FINANCIAL_FIELDS) && ! $this->isValidFinancialValue($value)) {
                Log::channel('database-sync')->warning('Valor financiero inválido detectado', [
                    'table' => $table,
                    'field' => $key,
                    'value' => $value,
                ]);

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * Sanitiza strings para prevenir inyecciones
     */
    private function sanitizeString(string $value): string
    {
        // Remover caracteres peligrosos
        $value = preg_replace('/[<>"\'\\\x00\x0a\x0d\x1a]/', '', $value);

        // Limitar longitud
        return substr(trim($value), 0, 1000);
    }

    /**
     * Valida que los valores financieros sean correctos
     */
    private function isValidFinancialValue($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (! is_numeric($value)) {
            return false;
        }

        $numValue = floatval($value);

        // Verificar rangos razonables para sistema financiero
        return $numValue >= -999999999.99 && $numValue <= 999999999.99;
    }

    /**
     * Genera token seguro para autenticación entre servidores
     */
    public function generateSecureToken(string $connection, int $timestamp): string
    {
        $payload = [
            'connection' => $connection,
            'timestamp' => $timestamp,
            'nonce' => bin2hex(random_bytes(16)),
        ];

        $signature = hash_hmac('sha256', json_encode($payload), config('app.key'));

        return base64_encode(json_encode([
            'payload' => $payload,
            'signature' => $signature,
        ]));
    }

    /**
     * Verifica token de autenticación entre servidores
     */
    public function verifySecureToken(string $token, string $expectedConnection): bool
    {
        try {
            $decoded = json_decode(base64_decode($token), true);

            if (! isset($decoded['payload']) || ! isset($decoded['signature'])) {
                return false;
            }

            $payload = $decoded['payload'];
            $signature = $decoded['signature'];

            // Verificar firma
            $expectedSignature = hash_hmac('sha256', json_encode($payload), config('app.key'));
            if (! hash_equals($expectedSignature, $signature)) {
                return false;
            }

            // Verificar conexión
            if ($payload['connection'] !== $expectedConnection) {
                return false;
            }

            // Verificar que el token no sea muy antiguo (5 minutos)
            $tokenAge = time() - $payload['timestamp'];
            if ($tokenAge > 300) {
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::channel('database-sync')->error('Error verificando token', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
