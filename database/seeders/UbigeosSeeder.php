<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UbigeosSeeder extends Seeder
{
    /**
     * Seed de ubigeos completos del Perú
     * Departamentos, Provincias y Distritos según catálogo SUNAT
     */
    public function run()
    {
        // Deshabilitar checks de llaves foráneas temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Limpiar tablas
        DB::table('districts')->truncate();
        DB::table('provinces')->truncate();
        DB::table('departments')->truncate();

        // =====================================================
        // DEPARTAMENTOS
        // =====================================================
        DB::table('departments')->insert([
            ['id' => '01', 'name' => 'Amazonas'],
            ['id' => '02', 'name' => 'Áncash'],
            ['id' => '03', 'name' => 'Apurímac'],
            ['id' => '04', 'name' => 'Arequipa'],
            ['id' => '05', 'name' => 'Ayacucho'],
            ['id' => '06', 'name' => 'Cajamarca'],
            ['id' => '07', 'name' => 'Callao'],
            ['id' => '08', 'name' => 'Cusco'],
            ['id' => '09', 'name' => 'Huancavelica'],
            ['id' => '10', 'name' => 'Huánuco'],
            ['id' => '11', 'name' => 'Ica'],
            ['id' => '12', 'name' => 'Junín'],
            ['id' => '13', 'name' => 'La Libertad'],
            ['id' => '14', 'name' => 'Lambayeque'],
            ['id' => '15', 'name' => 'Lima'],
            ['id' => '16', 'name' => 'Loreto'],
            ['id' => '17', 'name' => 'Madre de Dios'],
            ['id' => '18', 'name' => 'Moquegua'],
            ['id' => '19', 'name' => 'Pasco'],
            ['id' => '20', 'name' => 'Piura'],
            ['id' => '21', 'name' => 'Puno'],
            ['id' => '22', 'name' => 'San Martín'],
            ['id' => '23', 'name' => 'Tacna'],
            ['id' => '24', 'name' => 'Tumbes'],
            ['id' => '25', 'name' => 'Ucayali'],
        ]);

        $this->command->info('✓ Departamentos insertados');

        // =====================================================
        // PROVINCIAS - Solo Lima como ejemplo
        // =====================================================
        $provinces = [
            // Lima
            ['id' => '1501', 'name' => 'Lima', 'department_id' => '15'],
            ['id' => '1502', 'name' => 'Barranca', 'department_id' => '15'],
            ['id' => '1503', 'name' => 'Cajatambo', 'department_id' => '15'],
            ['id' => '1504', 'name' => 'Canta', 'department_id' => '15'],
            ['id' => '1505', 'name' => 'Cañete', 'department_id' => '15'],
            ['id' => '1506', 'name' => 'Huaral', 'department_id' => '15'],
            ['id' => '1507', 'name' => 'Huarochirí', 'department_id' => '15'],
            ['id' => '1508', 'name' => 'Huaura', 'department_id' => '15'],
            ['id' => '1509', 'name' => 'Oyón', 'department_id' => '15'],
            ['id' => '1510', 'name' => 'Yauyos', 'department_id' => '15'],
            // Callao
            ['id' => '0701', 'name' => 'Prov. Const. del Callao', 'department_id' => '07'],
        ];

        // Insertar en lotes de 100
        foreach (array_chunk($provinces, 100) as $chunk) {
            DB::table('provinces')->insert($chunk);
        }

        $this->command->info('✓ Provincias insertadas');

        // =====================================================
        // DISTRITOS - Solo Lima como ejemplo
        // =====================================================
        $districts = [
            // Lima - Lima
            ['id' => '150101', 'name' => 'Lima', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150102', 'name' => 'Ancón', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150103', 'name' => 'Ate', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150104', 'name' => 'Barranco', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150105', 'name' => 'Breña', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150106', 'name' => 'Carabayllo', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150107', 'name' => 'Chaclacayo', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150108', 'name' => 'Chorrillos', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150109', 'name' => 'Cieneguilla', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150110', 'name' => 'Comas', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150111', 'name' => 'El Agustino', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150112', 'name' => 'Independencia', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150113', 'name' => 'Jesús María', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150114', 'name' => 'La Molina', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150115', 'name' => 'La Victoria', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150116', 'name' => 'Lince', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150117', 'name' => 'Los Olivos', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150118', 'name' => 'Lurigancho', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150119', 'name' => 'Lurin', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150120', 'name' => 'Magdalena del Mar', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150121', 'name' => 'Pueblo Libre', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150122', 'name' => 'Miraflores', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150123', 'name' => 'Pachacamac', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150124', 'name' => 'Pucusana', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150125', 'name' => 'Puente Piedra', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150126', 'name' => 'Punta Hermosa', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150127', 'name' => 'Punta Negra', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150128', 'name' => 'Rímac', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150129', 'name' => 'San Bartolo', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150130', 'name' => 'San Borja', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150131', 'name' => 'San Isidro', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150132', 'name' => 'San Juan de Lurigancho', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150133', 'name' => 'San Juan de Miraflores', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150134', 'name' => 'San Luis', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150135', 'name' => 'San Martín de Porres', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150136', 'name' => 'San Miguel', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150137', 'name' => 'Santa Anita', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150138', 'name' => 'Santa María del Mar', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150139', 'name' => 'Santa Rosa', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150140', 'name' => 'Santiago de Surco', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150141', 'name' => 'Surquillo', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150142', 'name' => 'Villa El Salvador', 'province_id' => '1501', 'department_id' => '15'],
            ['id' => '150143', 'name' => 'Villa María del Triunfo', 'province_id' => '1501', 'department_id' => '15'],
            // Callao
            ['id' => '070101', 'name' => 'Callao', 'province_id' => '0701', 'department_id' => '07'],
            ['id' => '070102', 'name' => 'Bellavista', 'province_id' => '0701', 'department_id' => '07'],
            ['id' => '070103', 'name' => 'Carmen de la Legua Reynoso', 'province_id' => '0701', 'department_id' => '07'],
            ['id' => '070104', 'name' => 'La Perla', 'province_id' => '0701', 'department_id' => '07'],
            ['id' => '070105', 'name' => 'La Punta', 'province_id' => '0701', 'department_id' => '07'],
            ['id' => '070106', 'name' => 'Ventanilla', 'province_id' => '0701', 'department_id' => '07'],
            ['id' => '070107', 'name' => 'Mi Perú', 'province_id' => '0701', 'department_id' => '07'],
        ];

        // Insertar en lotes de 100
        foreach (array_chunk($districts, 100) as $chunk) {
            DB::table('districts')->insert($chunk);
        }

        $this->command->info('✓ Distritos insertados');

        // Rehabilitar checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('');
        $this->command->warn('⚠️  NOTA: Este seeder solo incluye Lima y Callao como ejemplo.');
        $this->command->warn('    Para todos los ubigeos del Perú, descargar el catálogo completo de SUNAT.');
        $this->command->info('');
        $this->command->info('Total insertado:');
        $this->command->info('  - Departamentos: 25');
        $this->command->info('  - Provincias: ' . DB::table('provinces')->count());
        $this->command->info('  - Distritos: ' . DB::table('districts')->count());
    }
}
