<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meta_configuracion', function (Blueprint $table) {
            $table->id();
            $table->decimal('umbral_morosidad', 5, 2)->default(20.00);
            $table->timestamps();
        });

        // Insert default configuration
        DB::table('meta_configuracion')->insert([
            'umbral_morosidad' => 20.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_configuracion');
    }
};
