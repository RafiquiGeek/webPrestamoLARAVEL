<?php

namespace App\Jobs;

use App\Models\Comprobante;
use App\Models\ComprobanteReintento;
use App\Services\GreenterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ReintentarEnvioComprobante implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1; // Solo 1 intento por job, el sistema de reintentos lo maneja
    public $timeout = 120; // 2 minutos de timeout

    protected $comprobanteId;
    protected $reintentoId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $comprobanteId, int $reintentoId)
    {
        $this->comprobanteId = $comprobanteId;
        $this->reintentoId = $reintentoId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ReintentarEnvioComprobante - Iniciando job', [
            'comprobante_id' => $this->comprobanteId,
            'reintento_id' => $this->reintentoId,
        ]);

        try {
            // Obtener el reintento y comprobante
            $reintento = ComprobanteReintento::find($this->reintentoId);
            if (!$reintento) {
                Log::warning('Reintento no encontrado', ['reintento_id' => $this->reintentoId]);
                return;
            }

            // Verificar que esté en estado pendiente
            if ($reintento->estado !== 'pendiente') {
                Log::info('Reintento ya no está pendiente', [
                    'reintento_id' => $this->reintentoId,
                    'estado' => $reintento->estado,
                ]);
                return;
            }

            // Marcar como procesando
            $reintento->marcarProcesando();

            $comprobante = Comprobante::with(['cliente.persona', 'cuota'])->find($this->comprobanteId);
            if (!$comprobante) {
                Log::error('Comprobante no encontrado', ['comprobante_id' => $this->comprobanteId]);
                $reintento->marcarCancelado('Comprobante no encontrado');
                return;
            }

            // Obtener servicio Sire
            $sireApi = app(\App\Services\SireApiService::class);

            // Preparar datos del cliente
            // Asegurarnos de que cliente y persona existen
            if (!$comprobante->cliente || !$comprobante->cliente->persona) {
                 throw new \Exception('Datos del cliente o persona incompletos en comprobante');
            }
            $persona = $comprobante->cliente->persona;
            $clientData = [
                'tipo_documento' => strlen($persona->documento) == 11 ? '6' : '1',
                'numero_documento' => $persona->documento,
                'razon_social' => trim($persona->nombres . ' ' . $persona->ape_pat . ' ' . $persona->ape_mat),
            ];

            // Decodificar items
            $items = json_decode($comprobante->items, true);

            // Payload para SIRE
            $payload = [
                'cliente' => $clientData,
                'items' => $items,
                'serie' => $comprobante->serie,
                'numero' => $comprobante->numero,
                'tipo_comprobante' => $comprobante->tipo_comprobante,
            ];

            // Enviar a SIRE
            $result = $sireApi->enviarJson($payload);

            if ($result['success']) {
                $responseData = $result['data'] ?? [];
                
                // Éxito - Actualizar comprobante
                $updateData = [
                    'estado' => 'ACEPTADO',
                    'codigo_error' => null,
                    'mensaje_error' => null,
                    'cdr_zip' => $responseData['cdr'] ?? ($result['cdr_zip'] ?? null),
                ];

                if (isset($responseData['xml_firmado'])) {
                    $updateData['xml_firmado'] = $responseData['xml_firmado'];
                }
                if (isset($responseData['xml_generado'])) {
                    $updateData['xml_content'] = $responseData['xml_generado'];
                }
                if (isset($updateData['cdr_zip'])) {
                    $updateData['hash'] = hash('sha256', $updateData['cdr_zip']);
                }

                $comprobante->update($updateData);

                // Marcar reintento como exitoso
                $reintento->marcarExitoso();

                Log::info('Comprobante reenviado exitosamente (SIRE)', [
                    'comprobante_id' => $this->comprobanteId,
                    'intentos_realizados' => $reintento->intentos + 1,
                ]);

                // Enviar notificación de éxito
                $this->enviarNotificacionExito($comprobante, $reintento);

            } else {
                // Error - Incrementar contador de reintentos
                $errorCode = $result['status'] ?? 'UNKNOWN';
                $errorMessage = $result['error'] ?? 'Error desconocido';
                if (is_array($errorMessage) || is_object($errorMessage)) {
                     $errorMessage = json_encode($errorMessage);
                }

                Log::warning('Error al reenviar comprobante (SIRE)', [
                    'comprobante_id' => $this->comprobanteId,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'intentos' => $reintento->intentos + 1,
                ]);

                // Actualizar comprobante con el último error
                $comprobante->update([
                    'codigo_error' => $errorCode,
                    'mensaje_error' => $errorMessage,
                ]);

                // Incrementar intento
                $reintento->incrementarIntento((string)$errorCode, $errorMessage);

                // Si alcanzó el máximo de intentos, notificar
                if ($reintento->estado === 'fallido') {
                    $this->enviarNotificacionFallo($comprobante, $reintento);
                }
            }


        } catch (\Exception $e) {
            Log::error('Excepción en ReintentarEnvioComprobante', [
                'comprobante_id' => $this->comprobanteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Incrementar contador de reintentos por error de sistema
            if (isset($reintento)) {
                $reintento->incrementarIntento('EXCEPTION', $e->getMessage());
            }
        }
    }

    /**
     * Enviar notificación de éxito
     */
    protected function enviarNotificacionExito(Comprobante $comprobante, ComprobanteReintento $reintento)
    {
        try {
            Log::info('✅ Comprobante enviado exitosamente después de reintentos', [
                'comprobante' => $comprobante->numero_completo,
                'cliente' => $comprobante->cliente->persona->nombre_completo,
                'intentos' => $reintento->intentos + 1,
            ]);

            // Crear notificación en la base de datos
            $mensaje = "El comprobante {$comprobante->numero_completo} fue enviado exitosamente a SUNAT " .
                      "después de {$reintento->intentos} reintento(s). " .
                      "Cliente: {$comprobante->cliente->persona->nombre_completo}";

            \App\Models\NotificacionComprobante::crearExito(
                $comprobante->id,
                $comprobante->prestamo->user_id ?? null, // Usuario que creó el préstamo
                $mensaje,
                [
                    'intentos' => $reintento->intentos + 1,
                    'tiempo_total' => now()->diffInMinutes($comprobante->created_at) . ' minutos',
                ]
            );

            // También notificar a administradores
            $adminUsers = \App\Models\User::role('Admin')->get();
            foreach ($adminUsers as $admin) {
                \App\Models\NotificacionComprobante::crearExito(
                    $comprobante->id,
                    $admin->id,
                    $mensaje,
                    ['intentos' => $reintento->intentos + 1]
                );
            }

        } catch (\Exception $e) {
            Log::error('Error al enviar notificación de éxito', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Enviar notificación de fallo definitivo
     */
    protected function enviarNotificacionFallo(Comprobante $comprobante, ComprobanteReintento $reintento)
    {
        try {
            Log::error('❌ Comprobante falló después de todos los reintentos', [
                'comprobante' => $comprobante->numero_completo,
                'cliente' => $comprobante->cliente->persona->nombre_completo,
                'intentos' => $reintento->intentos,
                'ultimo_error' => $reintento->ultimo_error_mensaje,
            ]);

            // Crear notificación de fallo
            $mensaje = "El comprobante {$comprobante->numero_completo} no pudo ser enviado a SUNAT " .
                      "después de {$reintento->intentos} intentos. " .
                      "Error: {$reintento->ultimo_error_mensaje}. " .
                      "Se requiere intervención manual.";

            \App\Models\NotificacionComprobante::crearFallo(
                $comprobante->id,
                $comprobante->prestamo->user_id ?? null,
                $mensaje,
                [
                    'intentos' => $reintento->intentos,
                    'error_code' => $reintento->ultimo_error_code,
                    'error_mensaje' => $reintento->ultimo_error_mensaje,
                ]
            );

            // Notificar a administradores de forma prioritaria
            $adminUsers = \App\Models\User::role('Admin')->get();
            foreach ($adminUsers as $admin) {
                \App\Models\NotificacionComprobante::crearFallo(
                    $comprobante->id,
                    $admin->id,
                    $mensaje,
                    [
                        'intentos' => $reintento->intentos,
                        'cliente' => $comprobante->cliente->persona->nombre_completo,
                        'requiere_atencion' => true,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error('Error al enviar notificación de fallo', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job ReintentarEnvioComprobante falló completamente', [
            'comprobante_id' => $this->comprobanteId,
            'reintento_id' => $this->reintentoId,
            'error' => $exception->getMessage(),
        ]);
    }
}
