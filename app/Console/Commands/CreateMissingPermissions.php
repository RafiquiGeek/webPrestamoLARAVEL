<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateMissingPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:create-missing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create missing permissions for new modules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating missing permissions...');

        // Definir los permisos que faltan - Estructura completa actualizada
        $newPermissions = [
            // Operaciones Principales
            'admin.clientes.index', 'admin.clientes.create', 'admin.clientes.edit', 'admin.clientes.destroy',
            'admin.prestamos.index', 'admin.prestamos.create', 'admin.prestamos.edit', 'admin.prestamos.destroy',
            'admin.prestamos.reset-payments', // Permiso para resetear pagos de préstamos
            'admin.operaciones.index', 'admin.operaciones.create', 'admin.operaciones.edit', 'admin.operaciones.destroy',

            // Gestión Financiera
            'admin.gestiones.index', 'admin.gestiones.create', 'admin.gestiones.edit', 'admin.gestiones.destroy',
            'admin.compromisos.index', 'admin.compromisos.create', 'admin.compromisos.edit', 'admin.compromisos.destroy',
            'admin.caja.index', 'admin.caja.rendiciones',
            'admin.fondo-provisional.index', 'admin.fondo-provisional.create', 'admin.fondo-provisional.edit', 'admin.fondo-provisional.destroy',
            'admin.gastos.index', 'admin.gastos.create', 'admin.gastos.edit', 'admin.gastos.destroy',
            'admin.categorias-gastos.index', 'admin.categorias-gastos.create', 'admin.categorias-gastos.edit', 'admin.categorias-gastos.destroy',

            // Reportes y Análisis
            'admin.carteras.index', 'admin.deudas.index',
            'admin.comprobantes.index', 'admin.comprobantes.create', 'admin.comprobantes.edit', 'admin.comprobantes.destroy',
            'admin.constructor-reportes.index', 'admin.constructor-reportes.create', 'admin.constructor-reportes.edit', 'admin.constructor-reportes.destroy',

            // Recursos Humanos
            'admin.asistencia.registro', 'admin.asistencia.dashboard', 'admin.asistencia.reportes',
            'admin.asistencia.areas-laborales', 'admin.asistencia.horarios-trabajo', 'admin.asistencia.asignaciones',
            'admin.asistencia.feriados-especiales.index',

            // Administración
            'admin.usuarios.index', 'admin.usuarios.create', 'admin.usuarios.edit', 'admin.usuarios.destroy',
            'admin.roles.index', 'admin.roles.create', 'admin.roles.edit', 'admin.roles.destroy',
            'admin.zonas.index', 'admin.zonas.create', 'admin.zonas.edit', 'admin.zonas.destroy',
            'admin.sucursales.index', 'admin.sucursales.create', 'admin.sucursales.edit', 'admin.sucursales.destroy',
            'admin.bancos.index', 'admin.bancos.create', 'admin.bancos.edit', 'admin.bancos.destroy',
            'admin.cuentas.index', 'admin.cuentas.create', 'admin.cuentas.edit', 'admin.cuentas.destroy',
            'admin.billeteras-digitales.index', 'admin.billeteras-digitales.create', 'admin.billeteras-digitales.edit', 'admin.billeteras-digitales.destroy',
            'admin.metodosdepago.index', 'admin.metodosdepago.create', 'admin.metodosdepago.edit', 'admin.metodosdepago.destroy',
            'admin.estadosgestion.index', 'admin.estadosgestion.create', 'admin.estadosgestion.edit', 'admin.estadosgestion.destroy',
            'admin.etiquetas.index', 'admin.etiquetas.create', 'admin.etiquetas.edit', 'admin.etiquetas.destroy',
            'admin.tasas.index', 'admin.tasas.create', 'admin.tasas.edit', 'admin.tasas.destroy',
            'admin.plazos.index', 'admin.plazos.create', 'admin.plazos.edit', 'admin.plazos.destroy',
            'admin.api-config.index', 'admin.api-config.create', 'admin.api-config.edit', 'admin.api-config.destroy',
            'admin.api-docs.index', 'admin.api-docs.testing',

            // Seguridad
            'admin.profile.edit', 'admin.password.change',
            'admin.database-sync.dashboard', 'admin.database-sync.configuration', 'admin.database-sync.monitoring',
            'admin.database-sync.logs', 'admin.database-sync.blocked-ips', 'admin.database-sync.backups',

            // Sistema
            'admin.respaldos.index', 'admin.respaldos.create', 'admin.respaldos.restore', 'admin.respaldos.destroy',
            'admin.logs.index', 'admin.logs.clear',
            'admin.monitoreo.index', 'admin.monitoreo.alerts',
            'admin.template-editor.index', 'admin.template-editor.create', 'admin.template-editor.edit', 'admin.template-editor.destroy',
            'admin.template-editor.variables',
            'admin.configuracion-sunat.index', 'admin.configuracion-sunat.edit',
            'admin.brand.index', 'admin.brand.edit',
        ];

        // Crear permisos si no existen
        foreach ($newPermissions as $permission) {
            if (! Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
                $this->info("Permission created: {$permission}");
            } else {
                $this->comment("Permission already exists: {$permission}");
            }
        }

        // Asignar permisos a roles
        $this->assignPermissionsToRoles();

        $this->info('Missing permissions created successfully!');
    }

    private function assignPermissionsToRoles()
    {
        $this->info('Assigning permissions to roles...');

        // Permisos por rol
        $rolePermissions = [
            'Admin' => [
                // Admin tiene TODOS los permisos disponibles
                'admin.clientes.index', 'admin.clientes.create', 'admin.clientes.edit', 'admin.clientes.destroy',
                'admin.prestamos.index', 'admin.prestamos.create', 'admin.prestamos.edit', 'admin.prestamos.destroy',
                'admin.operaciones.index', 'admin.operaciones.create', 'admin.operaciones.edit', 'admin.operaciones.destroy',
                'admin.gestiones.index', 'admin.gestiones.create', 'admin.gestiones.edit', 'admin.gestiones.destroy',
                'admin.compromisos.index', 'admin.compromisos.create', 'admin.compromisos.edit', 'admin.compromisos.destroy',
                'admin.caja.index', 'admin.caja.rendiciones',
                'admin.fondo-provisional.index', 'admin.fondo-provisional.create', 'admin.fondo-provisional.edit', 'admin.fondo-provisional.destroy',
                'admin.gastos.index', 'admin.gastos.create', 'admin.gastos.edit', 'admin.gastos.destroy',
                'admin.categorias-gastos.index', 'admin.categorias-gastos.create', 'admin.categorias-gastos.edit', 'admin.categorias-gastos.destroy',
                'admin.carteras.index', 'admin.deudas.index',
                'admin.comprobantes.index', 'admin.comprobantes.create', 'admin.comprobantes.edit', 'admin.comprobantes.destroy',
                'admin.constructor-reportes.index', 'admin.constructor-reportes.create', 'admin.constructor-reportes.edit', 'admin.constructor-reportes.destroy',
                'admin.asistencia.registro', 'admin.asistencia.dashboard', 'admin.asistencia.reportes',
                'admin.asistencia.areas-laborales', 'admin.asistencia.horarios-trabajo', 'admin.asistencia.asignaciones',
                'admin.asistencia.feriados-especiales.index',
                'admin.usuarios.index', 'admin.usuarios.create', 'admin.usuarios.edit', 'admin.usuarios.destroy',
                'admin.roles.index', 'admin.roles.create', 'admin.roles.edit', 'admin.roles.destroy',
                'admin.zonas.index', 'admin.zonas.create', 'admin.zonas.edit', 'admin.zonas.destroy',
                'admin.sucursales.index', 'admin.sucursales.create', 'admin.sucursales.edit', 'admin.sucursales.destroy',
                'admin.bancos.index', 'admin.bancos.create', 'admin.bancos.edit', 'admin.bancos.destroy',
                'admin.cuentas.index', 'admin.cuentas.create', 'admin.cuentas.edit', 'admin.cuentas.destroy',
                'admin.billeteras-digitales.index', 'admin.billeteras-digitales.create', 'admin.billeteras-digitales.edit', 'admin.billeteras-digitales.destroy',
                'admin.metodosdepago.index', 'admin.metodosdepago.create', 'admin.metodosdepago.edit', 'admin.metodosdepago.destroy',
                'admin.estadosgestion.index', 'admin.estadosgestion.create', 'admin.estadosgestion.edit', 'admin.estadosgestion.destroy',
                'admin.etiquetas.index', 'admin.etiquetas.create', 'admin.etiquetas.edit', 'admin.etiquetas.destroy',
                'admin.tasas.index', 'admin.tasas.create', 'admin.tasas.edit', 'admin.tasas.destroy',
                'admin.plazos.index', 'admin.plazos.create', 'admin.plazos.edit', 'admin.plazos.destroy',
                'admin.api-config.index', 'admin.api-config.create', 'admin.api-config.edit', 'admin.api-config.destroy',
                'admin.api-docs.index', 'admin.api-docs.testing',
                'admin.profile.edit', 'admin.password.change',
                'admin.database-sync.dashboard', 'admin.database-sync.configuration', 'admin.database-sync.monitoring',
                'admin.database-sync.logs', 'admin.database-sync.blocked-ips', 'admin.database-sync.backups',
                'admin.respaldos.index', 'admin.respaldos.create', 'admin.respaldos.restore', 'admin.respaldos.destroy',
                'admin.logs.index', 'admin.logs.clear',
                'admin.monitoreo.index', 'admin.monitoreo.alerts',
                'admin.template-editor.index', 'admin.template-editor.create', 'admin.template-editor.edit', 'admin.template-editor.destroy',
                'admin.template-editor.variables',
                'admin.configuracion-sunat.index', 'admin.configuracion-sunat.edit',
                'admin.brand.index', 'admin.brand.edit',
            ],
            'JCC' => [
                'admin.gestiones.index', 'admin.gestiones.create', 'admin.gestiones.edit',
                'admin.compromisos.index', 'admin.compromisos.create', 'admin.compromisos.edit',
                'admin.carteras.index', 'admin.deudas.index',
                'admin.caja.index', 'admin.caja.rendiciones',
                'admin.gastos.index', 'admin.gastos.create', 'admin.gastos.edit',
                'admin.comprobantes.index',
                'admin.profile.edit', 'admin.password.change',
            ],
            'Asesor' => [
                'admin.gestiones.index', 'admin.gestiones.create', 'admin.gestiones.edit',
                'admin.compromisos.index', 'admin.compromisos.create', 'admin.compromisos.edit',
                'admin.carteras.index', 'admin.deudas.index',
                'admin.caja.index',
                'admin.gastos.index', 'admin.gastos.create',
                'admin.comprobantes.index',
                'admin.profile.edit', 'admin.password.change',
            ],
            'Analista' => [
                'admin.gestiones.index',
                'admin.compromisos.index',
                'admin.carteras.index', 'admin.deudas.index',
                'admin.gastos.index',
                'admin.comprobantes.index',
                'admin.profile.edit', 'admin.password.change',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                foreach ($permissions as $permissionName) {
                    $permission = Permission::where('name', $permissionName)->first();
                    if ($permission && ! $role->hasPermissionTo($permission)) {
                        $role->givePermissionTo($permission);
                        $this->info("Assigned {$permissionName} to {$roleName}");
                    }
                }
            }
        }
    }
}
