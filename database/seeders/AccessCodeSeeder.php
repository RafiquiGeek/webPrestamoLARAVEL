<?php

namespace Database\Seeders;

use App\Models\AccessCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AccessCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        if (! $admin) {
            $admin = User::first();
        }

        if (! $admin) {
            $this->command->warn('No se encontró un usuario administrador. Creando códigos sin asignar creador.');
            $adminId = 1;
        } else {
            $adminId = $admin->id;
        }

        // Código general para todos los roles
        AccessCode::create([
            'code' => 'GENERAL',
            'description' => 'Código de acceso general para todos los usuarios',
            'is_active' => true,
            'expires_at' => null,
            'usage_count' => 0,
            'max_usage' => null,
            'allowed_roles' => null,
            'created_by' => $adminId,
        ]);

        // Código específico para administradores
        AccessCode::create([
            'code' => 'ADMIN123',
            'description' => 'Código exclusivo para administradores',
            'is_active' => true,
            'expires_at' => Carbon::now()->addMonths(6),
            'usage_count' => 0,
            'max_usage' => 50,
            'allowed_roles' => ['Admin'],
            'created_by' => $adminId,
        ]);

        // Código temporal para nuevos empleados
        AccessCode::create([
            'code' => 'NEWSTAFF',
            'description' => 'Código temporal para nuevos empleados',
            'is_active' => true,
            'expires_at' => Carbon::now()->addDays(30),
            'usage_count' => 0,
            'max_usage' => 20,
            'allowed_roles' => ['Asesor', 'Analista'],
            'created_by' => $adminId,
        ]);

        // Código para supervisores
        AccessCode::create([
            'code' => 'SUPER2025',
            'description' => 'Código para supervisores y JCC',
            'is_active' => true,
            'expires_at' => Carbon::now()->addYear(),
            'usage_count' => 0,
            'max_usage' => 100,
            'allowed_roles' => ['Supervisor', 'JCC'],
            'created_by' => $adminId,
        ]);

        // Código de emergencia para administrador (SIEMPRE ACTIVO)
        AccessCode::create([
            'code' => 'ADMIN911',
            'description' => '🚨 CÓDIGO DE EMERGENCIA - Solo para administrador principal',
            'is_active' => true,
            'expires_at' => null,
            'usage_count' => 0,
            'max_usage' => null, // Sin límite de uso
            'allowed_roles' => ['Admin'],
            'created_by' => $adminId,
        ]);

        // Código de emergencia adicional (inactivo por defecto)
        AccessCode::create([
            'code' => 'BACKUP2025',
            'description' => 'Código de respaldo para situaciones críticas',
            'is_active' => false,
            'expires_at' => null,
            'usage_count' => 0,
            'max_usage' => 10,
            'allowed_roles' => ['Admin', 'Supervisor'],
            'created_by' => $adminId,
        ]);

        $this->command->info('Se crearon 5 códigos de acceso de ejemplo.');
    }
}
