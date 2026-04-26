<?php

namespace App\Events;

use App\Models\Prestamo;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrestamoEventoAutomatico implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $prestamo;

    public $evento;

    public $datos_evento;

    public $usuario_id;

    public $timestamp;

    /**
     * Crear nuevo evento automático del sistema
     */
    public function __construct(
        Prestamo $prestamo,
        string $evento,
        array $datos_evento = [],
        ?int $usuario_id = null
    ) {
        $this->prestamo = $prestamo;
        $this->evento = $evento;
        $this->datos_evento = $datos_evento;
        $this->usuario_id = $usuario_id ?? auth()->id();
        $this->timestamp = now();
    }

    /**
     * Canales de broadcast para tiempo real
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('prestamos.'.$this->prestamo->id),
            new Channel('prestamos.general'), // Canal general para administradores
        ];
    }

    /**
     * Nombre del evento para el frontend
     */
    public function broadcastAs(): string
    {
        return 'prestamo.evento.automatico';
    }

    /**
     * Datos que se envían al frontend
     */
    public function broadcastWith(): array
    {
        return [
            'prestamo_id' => $this->prestamo->id,
            'evento' => $this->evento,
            'datos' => $this->datos_evento,
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s'),
            'usuario_id' => $this->usuario_id,
            'mensaje_humano' => $this->generarMensajeHumano(),
        ];
    }

    /**
     * Generar mensaje legible para humanos
     */
    private function generarMensajeHumano(): string
    {
        switch ($this->evento) {
            case 'pago_registrado':
                return "💰 Pago automático de S/{$this->datos_evento['monto']} procesado";
            case 'cuota_vencida':
                return "⏰ Cuota #{$this->datos_evento['numero_cuota']} marcada como vencida automáticamente";
            case 'mora_generada':
                return "📊 {$this->datos_evento['cantidad_moras']} mora(s) generada(s) automáticamente";
            case 'estado_actualizado':
                return "🔄 Estado cambiado automáticamente de '{$this->datos_evento['estado_anterior']}' a '{$this->datos_evento['estado_nuevo']}'";
            case 'prestamo_finalizado':
                return '✅ Préstamo finalizado automáticamente - todas las cuotas y moras están pagadas';
            case 'recalculo_completo':
                return "🔧 Recálculo automático completado: {$this->datos_evento['cuotas_actualizadas']} cuotas y {$this->datos_evento['moras_actualizadas']} moras procesadas";
            default:
                return "🤖 Evento automático: {$this->evento}";
        }
    }
}
