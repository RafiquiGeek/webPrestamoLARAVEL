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
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->timestamp('login_time');
            $table->timestamp('logout_time')->nullable();
            $table->integer('total_duration')->default(0); // en segundos
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->boolean('forced_logout')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'login_time']);
            $table->index('session_id');
            $table->index('login_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
