<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('clientes', function (Blueprint $table) {
            try {
                $table->dropForeign(['zona_id']);
            } catch (\Exception $e) {
            }
            
            try {
                $table->dropForeign(['sucursal_id']);
            } catch (\Exception $e) {
            }
            
            try {
                $table->dropForeign(['user_id']);
            } catch (\Exception $e) {
            }
        });

        DB::statement('ALTER TABLE `clientes` MODIFY COLUMN `zona_id` INT UNSIGNED NULL COMMENT "FK a tabla zonas"');
        DB::statement('ALTER TABLE `clientes` MODIFY COLUMN `sucursal_id` INT UNSIGNED NULL COMMENT "FK a tabla sucursales"');
        DB::statement('ALTER TABLE `clientes` MODIFY COLUMN `user_id` BIGINT UNSIGNED NULL COMMENT "FK a tabla users"');

        Schema::table('clientes', function (Blueprint $table) {
            $table->foreign('zona_id', 'fk_clientes_zona')
                ->references('id')
                ->on('zonas')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('sucursal_id', 'fk_clientes_sucursal')
                ->references('id')
                ->on('sucursales')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('user_id', 'fk_clientes_user')
                ->references('id')
                ->on('users')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign('fk_clientes_zona');
            $table->dropForeign('fk_clientes_sucursal');
            $table->dropForeign('fk_clientes_user');
        });

        DB::statement('ALTER TABLE `clientes` MODIFY COLUMN `zona_id` INT NULL');
        DB::statement('ALTER TABLE `clientes` MODIFY COLUMN `sucursal_id` INT NULL');
        DB::statement('ALTER TABLE `clientes` MODIFY COLUMN `user_id` INT NULL');

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
