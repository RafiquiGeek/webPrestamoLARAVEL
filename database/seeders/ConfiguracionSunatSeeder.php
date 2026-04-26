<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ConfiguracionSunatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el contenido del certificado de prueba
        $certificadoPath = storage_path('app/keys/certificado_prueba.pem');
        $certificadoContenido = null;

        if (\Illuminate\Support\Facades\File::exists($certificadoPath)) {
            $certificadoContenido = base64_encode(\Illuminate\Support\Facades\File::get($certificadoPath));
        }

        \App\Models\ConfiguracionSunat::create([
            'ruc' => '20000000001',
            'usuario_sol' => 'MODDATOS',
            'clave_sol' => 'MODDATOS',
            'ambiente' => 'beta',
            'certificado_nombre' => 'certificado_prueba.pem',
            'certificado_contenido' => $certificadoContenido,
            'certificado_clave' => null, // Los certificados .pem generalmente no requieren contraseña
            'razon_social' => 'MI EMPRESA DE PRUEBA S.A.C.',
            'nombre_comercial' => 'MI EMPRESA',
            'direccion' => 'AV. PRINCIPAL 123, LIMA',
            'ubigeo' => '150101',
            'distrito' => 'Lima',
            'provincia' => 'Lima',
            'departamento' => 'Lima',
            'serie_factura' => 'F001',
            'serie_boleta' => 'B001',
            'activo' => true,
        ]);
    }
}
