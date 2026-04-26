<?php

namespace App\Services;

use App\Models\ConfiguracionSunat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Servicio para autenticación OAuth2 con la API de SUNAT
 *
 * Implementa el flujo de autenticación OAuth2 según la documentación oficial de SUNAT:
 * https://api-seguridad.sunat.gob.pe/v1/clientessol/{client_id}/oauth2/token/
 *
 * Referencias:
 * - Servicio 5.1: Generación del Token de Acceso
 * - El token expira en 1 hora (3600 segundos)
 */
class SunatOAuth2Service
{
    protected $configuracion;
    protected $baseUrl;

    public function __construct()
    {
        $this->configuracion = ConfiguracionSunat::obtenerActiva();

        if (!$this->configuracion) {
            throw new \Exception('No hay configuración SUNAT activa');
        }

        // URL base de autenticación SUNAT (misma para producción y testing)
        $this->baseUrl = 'https://api-seguridad.sunat.gob.pe';
    }

    /**
     * Obtener token de acceso OAuth2 válido
     *
     * Si el token almacenado no ha expirado, lo retorna.
     * Si expiró o no existe, genera uno nuevo.
     *
     * @return array ['success' => bool, 'access_token' => string|null, 'error' => string|null]
     */
    public function getAccessToken(): array
    {
        try {
            // Verificar si el token actual sigue vigente
            if ($this->hasValidToken()) {
                Log::info('Usando token OAuth2 existente', [
                    'expires_at' => $this->configuracion->sire_token_expires_at,
                    'remaining_minutes' => Carbon::parse($this->configuracion->sire_token_expires_at)->diffInMinutes(now())
                ]);

                return [
                    'success' => true,
                    'access_token' => decrypt($this->configuracion->sire_access_token),
                ];
            }

            // Token expirado o inexistente, generar nuevo
            Log::info('Generando nuevo token OAuth2 SUNAT');

            return $this->generateNewToken();

        } catch (\Exception $e) {
            Log::error('Error al obtener token OAuth2', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verificar si el token actual es válido (no expirado)
     *
     * @return bool
     */
    protected function hasValidToken(): bool
    {
        if (empty($this->configuracion->sire_access_token) || empty($this->configuracion->sire_token_expires_at)) {
            return false;
        }

        // Verificar que no haya expirado (con margen de 5 minutos para renovación)
        $expiresAt = Carbon::parse($this->configuracion->sire_token_expires_at);
        $now = now()->addMinutes(5); // Renovar 5 minutos antes de expirar

        return $now->lessThan($expiresAt);
    }

    /**
     * Generar nuevo token OAuth2 desde la API de SUNAT
     *
     * @return array
     */
    protected function generateNewToken(): array
    {
        // Validar que las credenciales OAuth2 estén configuradas
        if (empty($this->configuracion->sire_client_id) || empty($this->configuracion->sire_client_secret)) {
            return [
                'success' => false,
                'error' => 'Credenciales OAuth2 no configuradas (client_id y client_secret son requeridos). Configure estas credenciales en el Portal SOL de SUNAT.',
            ];
        }

        // Validar que las credenciales SOL estén configuradas
        if (empty($this->configuracion->ruc) || empty($this->configuracion->sol_user) || empty($this->configuracion->sol_pass)) {
            return [
                'success' => false,
                'error' => 'Credenciales SOL no configuradas (RUC, usuario SOL y contraseña SOL son requeridos)',
            ];
        }

        try {
            // Desencriptar client_secret si está encriptado
            $clientSecret = $this->configuracion->sire_client_secret;
            if (strlen($clientSecret) > 50) {
                try {
                    $clientSecret = decrypt($clientSecret);
                } catch (\Exception $e) {
                    Log::warning('No se pudo desencriptar client_secret, usando valor original');
                }
            }

            // Desencriptar sol_pass si está encriptado
            $solPass = $this->configuracion->sol_pass;
            if (strlen($solPass) > 50) {
                try {
                    $solPass = decrypt($solPass);
                } catch (\Exception $e) {
                    Log::warning('No se pudo desencriptar sol_pass, usando valor original');
                }
            }

            // Formato del username: RUC + Usuario SOL (sin espacios)
            // Ejemplo: 20100012345USUARIO
            $username = $this->configuracion->ruc . $this->configuracion->sol_user;

            Log::info('Solicitando token OAuth2 a SUNAT', [
                'client_id' => $this->configuracion->sire_client_id,
                'username' => $username,
                'ruc' => $this->configuracion->ruc,
                'sol_user' => $this->configuracion->sol_user,
            ]);

            // Endpoint de autenticación
            $endpoint = "{$this->baseUrl}/v1/clientessol/{$this->configuracion->sire_client_id}/oauth2/token/";

            // Realizar petición POST con form-urlencoded
            $response = Http::asForm()->post($endpoint, [
                'grant_type' => 'password',
                'scope' => 'https://api-sire.sunat.gob.pe',
                'client_id' => $this->configuracion->sire_client_id,
                'client_secret' => $clientSecret,
                'username' => $username,
                'password' => $solPass,
            ]);

            if (!$response->successful()) {
                $error = $response->json();

                Log::error('Error al obtener token OAuth2', [
                    'status' => $response->status(),
                    'error' => $error['error'] ?? 'Error desconocido',
                    'error_description' => $error['error_description'] ?? null,
                    'response_body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => $error['error_description'] ?? $error['error'] ?? 'Error al autenticar con SUNAT OAuth2',
                    'status_code' => $response->status(),
                ];
            }

            $data = $response->json();

            // Validar respuesta
            if (empty($data['access_token'])) {
                return [
                    'success' => false,
                    'error' => 'Respuesta inválida de SUNAT: no se recibió access_token',
                ];
            }

            // Calcular fecha de expiración (default: 3600 segundos = 1 hora)
            $expiresIn = $data['expires_in'] ?? 3600;
            $expiresAt = now()->addSeconds($expiresIn);

            // Guardar token encriptado en la BD
            $this->configuracion->update([
                'sire_access_token' => encrypt($data['access_token']),
                'sire_token_expires_at' => $expiresAt,
            ]);

            Log::info('Token OAuth2 generado exitosamente', [
                'expires_in_seconds' => $expiresIn,
                'expires_at' => $expiresAt->toDateTimeString(),
                'token_type' => $data['token_type'] ?? 'bearer',
            ]);

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'expires_in' => $expiresIn,
                'expires_at' => $expiresAt,
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al generar token OAuth2', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Error inesperado al generar token: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Forzar renovación del token OAuth2
     *
     * @return array
     */
    public function refreshToken(): array
    {
        Log::info('Forzando renovación de token OAuth2');

        // Borrar token actual
        $this->configuracion->update([
            'sire_access_token' => null,
            'sire_token_expires_at' => null,
        ]);

        return $this->generateNewToken();
    }

    /**
     * Probar autenticación OAuth2 con SUNAT
     *
     * @return array
     */
    public function testAuthentication(): array
    {
        try {
            $result = $this->getAccessToken();

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'],
                    'message' => 'Falló la autenticación OAuth2 con SUNAT',
                ];
            }

            return [
                'success' => true,
                'message' => 'Autenticación OAuth2 exitosa',
                'data' => [
                    'expires_at' => $this->configuracion->sire_token_expires_at,
                    'remaining_minutes' => Carbon::parse($this->configuracion->sire_token_expires_at)->diffInMinutes(now()),
                ],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error al probar autenticación',
            ];
        }
    }

    /**
     * Obtener información del token actual
     *
     * @return array|null
     */
    public function getTokenInfo(): ?array
    {
        if (empty($this->configuracion->sire_access_token) || empty($this->configuracion->sire_token_expires_at)) {
            return null;
        }

        $expiresAt = Carbon::parse($this->configuracion->sire_token_expires_at);
        $now = now();

        return [
            'has_token' => true,
            'is_valid' => $this->hasValidToken(),
            'expires_at' => $expiresAt->toDateTimeString(),
            'is_expired' => $now->greaterThan($expiresAt),
            'remaining_minutes' => $now->lessThan($expiresAt) ? $expiresAt->diffInMinutes($now) : 0,
            'remaining_seconds' => $now->lessThan($expiresAt) ? $expiresAt->diffInSeconds($now) : 0,
        ];
    }
}
