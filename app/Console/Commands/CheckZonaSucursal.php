<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Zona;
use App\Models\Sucursal;
use Illuminate\Support\Facades\DB;

class CheckZonaSucursal extends Command
{
    protected $signature = 'check:zona-sucursal';
    protected $description = 'Verificar relaciones zona-sucursal';

    public function handle()
    {
        $this->info('=== Verificando Relaciones Zona-Sucursal ===');
        
        // Verificar datos en tabla pivot
        $pivotCount = DB::table('zona_sucursal')->count();
        $this->info("Registros en zona_sucursal: {$pivotCount}");
        
        if ($pivotCount === 0) {
            $this->warn('⚠️  La tabla zona_sucursal está VACÍA');
            $this->info('Esto explica por qué no aparecen sucursales al seleccionar zona');
        } else {
            $this->info('✅ Hay relaciones en la tabla pivot');
            
            // Mostrar algunas relaciones
            $relaciones = DB::table('zona_sucursal')
                ->join('zonas', 'zona_sucursal.zona_id', '=', 'zonas.id')
                ->join('sucursales', 'zona_sucursal.sucursal_id', '=', 'sucursales.id')
                ->select('zonas.nombre as zona', 'sucursales.sucursal')
                ->limit(10)
                ->get();
                
            $this->table(['Zona', 'Sucursal'], $relaciones->map(fn($r) => [$r->zona, $r->sucursal])->toArray());
        }
        
        // Verificar zonas
        $zonasCount = Zona::count();
        $this->info("\nTotal de Zonas: {$zonasCount}");
        
        // Verificar sucursales
        $sucursalesCount = Sucursal::count();
        $this->info("Total de Sucursales: {$sucursalesCount}");
        
        return 0;
    }
}
