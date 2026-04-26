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
        Schema::create('login_requests', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('user_name');
            $table->string('access_code', 6)->unique()->index();
            $table->enum('status', ['pending', 'approved', 'denied', 'used', 'expired'])->default('pending');
            $table->string('user_agent')->nullable();
            $table->string('ip_address', 45);
            $table->timestamp('expires_at');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'expires_at']);
            $table->index(['email', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_requests');
    }
};
