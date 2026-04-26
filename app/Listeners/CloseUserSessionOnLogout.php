<?php

namespace App\Listeners;

use App\Models\ModuleTimeTracking;
use App\Models\UserSession;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;

class CloseUserSessionOnLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        try {
            $user = $event->user;
            $sessionId = session()->getId();

            if (!$user) {
                return;
            }

            // Buscar la sesión activa del usuario
            $userSession = UserSession::where('user_id', $user->id)
                ->where('session_id', $sessionId)
                ->whereNull('logout_time')
                ->first();

            if ($userSession) {
                $logoutTime = now();
                $totalDuration = $logoutTime->diffInSeconds($userSession->login_time);

                // Actualizar sesión con logout_time y duración total
                $userSession->update([
                    'logout_time' => $logoutTime,
                    'total_duration' => $totalDuration,
                ]);

                // Cerrar cualquier tracking de módulos activo
                $this->closeActiveModuleTracking($userSession->id, $user->id);

                Log::info("Sesión cerrada correctamente", [
                    'user_id' => $user->id,
                    'session_id' => $sessionId,
                    'duration' => $totalDuration
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al cerrar sesión de usuario: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Cierra cualquier tracking de módulos activo para la sesión
     */
    private function closeActiveModuleTracking(int $userSessionId, int $userId): void
    {
        try {
            $activeTracking = ModuleTimeTracking::where('user_session_id', $userSessionId)
                ->where('user_id', $userId)
                ->whereNull('end_time')
                ->get();

            foreach ($activeTracking as $tracking) {
                $endTime = now();
                $duration = $endTime->diffInSeconds($tracking->start_time);

                $tracking->update([
                    'end_time' => $endTime,
                    'duration' => $duration,
                ]);
            }

            if ($activeTracking->count() > 0) {
                Log::info("Cerrados {$activeTracking->count()} trackings de módulos activos");
            }
        } catch (\Exception $e) {
            Log::error('Error al cerrar trackings de módulos: ' . $e->getMessage());
        }
    }
}
