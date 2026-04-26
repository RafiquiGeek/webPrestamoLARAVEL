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
        Schema::create('sire_historial', function (Blueprint $table) {
            $table->id();

            // Datos del comprobante
            $table->string('tipo_comprobante', 2)->index(); // 01=Factura, 03=Boleta, etc.
            $table->string('serie', 10)->index();
            $table->string('numero', 20);
            $table->date('fecha_emision')->index();
            $table->string('moneda', 3)->default('PEN');
            $table->decimal('total', 12, 2);

            // Datos del cliente
            $table->string('cliente_tipo_doc', 1); // 1=DNI, 6=RUC
            $table->string('cliente_numero_doc', 20)->index();
            $table->string('cliente_razon_social', 200);

            // Estado del documento
            $table->enum('estado', [
                'pendiente',
                'procesando',
                'enviado',
                'aceptado',
                'rechazado',
                'error'
            ])->default('pendiente')->index();

            // Información de envío
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamp('fecha_respuesta')->nullable();
            $table->integer('tiempo_respuesta_ms')->nullable(); // Tiempo de respuesta en ms

            // Respuesta SUNAT
            $table->integer('sunat_codigo')->nullable();
            $table->text('sunat_mensaje')->nullable();
            $table->text('sunat_response')->nullable(); // Respuesta completa

            // XMLs
            $table->longText('xml_generado')->nullable();
            $table->longText('xml_firmado')->nullable();
            $table->string('hash_xml', 64)->nullable()->index();

            // CDR (Constancia de Recepción)
            $table->text('cdr_zip')->nullable(); // Base64 del ZIP
            $table->string('cdr_hash', 64)->nullable();

            // Tracking y auditoría
            $table->string('ip_origen', 45)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('origen_sistema', 50)->nullable(); // 'grupo_santiago', 'manual', etc.
            $table->json('metadata')->nullable(); // Datos adicionales flexibles

            // Reintentos
            $table->integer('intentos')->default(0);
            $table->timestamp('ultimo_intento')->nullable();

            // Errores
            $table->string('error_codigo', 50)->nullable();
            $table->text('error_mensaje')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Para soft delete

            // Índices compuestos
            $table->index(['tipo_comprobante', 'serie', 'numero']);
            $table->index(['estado', 'created_at']);
            $table->index(['fecha_emision', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sire_historial');
    }
};
