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
        Schema::create('module_time_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_session_id')->constrained()->onDelete('cascade');
            $table->string('module_name'); // prestamos, clientes, reportes, etc.
            $table->string('module_section')->nullable(); // create, edit, index, etc.
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->integer('duration')->default(0); // en segundos
            $table->string('url');
            $table->timestamps();

            $table->index(['user_id', 'module_name', 'start_time']);
            $table->index(['user_session_id', 'module_name']);
            $table->index('start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_time_tracking');
    }
};
