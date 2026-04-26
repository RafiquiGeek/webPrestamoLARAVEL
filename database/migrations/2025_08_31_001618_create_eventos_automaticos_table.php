<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('eventos_automaticos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('prestamo_id')->index();
            $table->integer('cuota_id')->nullable()->index();
            $table->unsignedInteger('operacion_id')->nullable()->index();
            $table->string('evento'); // 'pago_registrado', 'cuota_vencida', 'mora_generada', etc.
            $table->string('categoria')->index(); // 'pagos', 'moras', 'estados', 'calculos'
            $table->json('datos_antes')->nullable(); // Estado antes del evento
            $table->json('datos_despues'); // Estado después del evento
            $table->json('metadatos')->nullable(); // Información adicional del evento
            $table->string('resultado'); // 'exitoso', 'parcial', 'fallido'
            $table->text('mensaje_humano'); // Mensaje legible para mostrar al usuario
            $table->unsignedBigInteger('usuario_id')->nullable(); // Si fue iniciado por un usuario
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('procesado_en');
            $table->decimal('tiempo_procesamiento', 8, 3)->nullable(); // Milisegundos
            $table->timestamps();

            // Índices para consultas rápidas
            $table->index(['prestamo_id', 'evento', 'created_at']);
            $table->index(['categoria', 'created_at']);
            $table->index(['resultado', 'created_at']);

            // Relaciones
            $table->foreign('prestamo_id')->references('id')->on('prestamos')->onDelete('cascade');
            $table->foreign('cuota_id')->references('id')->on('cuotas')->onDelete('set null');
            $table->foreign('operacion_id')->references('id')->on('operaciones')->onDelete('set null');
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eventos_automaticos');
    }
};
