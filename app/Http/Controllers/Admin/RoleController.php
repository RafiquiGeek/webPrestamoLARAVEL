<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    // Mostrar listado de roles
    public function index()
    {
        $roles = Role::paginate(10);

        return view('admin.Roles.index', compact('roles'));
    }

    // Mostrar formulario para crear rol
    public function create()
    {
        return view('admin.Roles.create');
    }

    // Guardar una nueva rol
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|unique:roles,name',
        ]);

        Role::create($validatedData);

        return redirect()->route('admin.roles.index')->with('info', 'Rol creado con éxito.');
    }

    // Mostrar formulario para editar rol
    public function edit(string $id)
    {
        $role = Role::findOrFail($id);

        return view('admin.Roles.edit', compact('role'));
    }

    // Actualizar rol
    public function update(Request $request, Role $role)
    {
        $validatedData = $request->validate([
            'name' => 'required|unique:roles,name,'.$role->id,
        ]);

        $role->update($validatedData);

        return redirect()->route('admin.roles.index')->with('info', 'Rol actualizado con éxito.');
    }

    // Eliminar rol
    public function destroy(Role $role)
    {
        if (DB::table('model_has_roles')->where('role_id', $role->id)->count() > 0) {
            return redirect()->route('admin.roles.index')->with('error', 'El rol ha sido asignado a uno o mas usuarios, quitar la asignación para eliminar el rol.');
        }
        DB::table('role_has_permissions')->where('role_id', $role->id)->delete();
        $role->delete();

        return redirect()->route('admin.roles.index')->with('info', 'Rol eliminado con éxito.');
    }

    // Mostrar panel de permisos para un rol
    public function permissions(Role $role)
    {
        // Organizar permisos por módulo basándose en el menú
        $menuStructure = $this->getMenuStructure();

        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('admin.Roles.permissions', compact('role', 'menuStructure', 'rolePermissions'));
    }

    // Actualizar permisos de un rol
    public function updatePermissions(Request $request, Role $role)
    {
        $permissions = $request->input('permissions', []);

        // Sincronizar permisos
        $role->syncPermissions($permissions);

        return redirect()->route('admin.roles.permissions', $role)
            ->with('success', 'Permisos actualizados correctamente para el rol: '.$role->name);
    }

    // Estructura del menú para organizar permisos - Actualizada según MenuBuilderService
    private function getMenuStructure()
    {
        return [
            'Operaciones Principales' => [
                'icon' => 'fas fa-briefcase',
                'permissions' => [
                    'admin.clientes.index' => 'Ver clientes',
                    'admin.clientes.create' => 'Crear clientes',
                    'admin.clientes.edit' => 'Editar clientes',
                    'admin.clientes.destroy' => 'Eliminar clientes',
                    'admin.prestamos.index' => 'Ver préstamos',
                    'admin.prestamos.create' => 'Crear préstamos',
                    'admin.prestamos.edit' => 'Editar préstamos',
                    'admin.prestamos.destroy' => 'Eliminar préstamos',
                    'admin.prestamos.reset-payments' => 'Resetear pagos de préstamos',
                    'admin.operaciones.index' => 'Ver operaciones',
                    'admin.operaciones.create' => 'Crear operaciones',
                    'admin.operaciones.edit' => 'Editar operaciones',
                    'admin.operaciones.destroy' => 'Eliminar operaciones',
                ],
            ],
            'Cobranzas' => [
                'icon' => 'fas fa-hand-holding-usd',
                'permissions' => [
                    'admin.gestiones.index' => 'Ver gestiones',
                    'admin.gestiones.create' => 'Crear gestiones',
                    'admin.gestiones.edit' => 'Editar gestiones',
                    'admin.gestiones.destroy' => 'Eliminar gestiones',
                    'admin.compromisos.index' => 'Ver compromisos',
                    'admin.compromisos.create' => 'Crear compromisos',
                    'admin.compromisos.edit' => 'Editar compromisos',
                    'admin.compromisos.destroy' => 'Eliminar compromisos',
                ],
            ],
            'Caja y Tesorería' => [
                'icon' => 'fas fa-cash-register',
                'permissions' => [
                    'admin.caja.index' => 'Ver caja diaria',
                    'admin.caja.rendiciones' => 'Ver rendiciones',
                    'admin.fondo-provisional.index' => 'Ver fondos provisionales',
                    'admin.fondo-provisional.create' => 'Crear fondos provisionales',
                    'admin.fondo-provisional.edit' => 'Editar fondos provisionales',
                    'admin.fondo-provisional.destroy' => 'Eliminar fondos provisionales',
                ],
            ],
            'Gastos' => [
                'icon' => 'fas fa-receipt',
                'permissions' => [
                    'admin.gastos.index' => 'Ver gastos',
                    'admin.gastos.create' => 'Crear gastos',
                    'admin.gastos.edit' => 'Editar gastos',
                    'admin.gastos.destroy' => 'Eliminar gastos',
                    'admin.categorias-gastos.index' => 'Ver categorías de gastos',
                    'admin.categorias-gastos.create' => 'Crear categorías de gastos',
                    'admin.categorias-gastos.edit' => 'Editar categorías de gastos',
                    'admin.categorias-gastos.destroy' => 'Eliminar categorías de gastos',
                ],
            ],
            'Reportes' => [
                'icon' => 'fas fa-chart-bar',
                'permissions' => [
                    'admin.carteras.index' => 'Ver carteras',
                    'admin.deudas.index' => 'Ver cuotas/moras',
                    'admin.comprobantes.index' => 'Ver facturación',
                    'admin.comprobantes.create' => 'Crear comprobantes',
                    'admin.comprobantes.edit' => 'Editar comprobantes',
                    'admin.comprobantes.destroy' => 'Eliminar comprobantes',
                    'admin.constructor-reportes.index' => 'Constructor de reportes',
                    'admin.constructor-reportes.create' => 'Crear reportes personalizados',
                    'admin.constructor-reportes.edit' => 'Editar reportes personalizados',
                    'admin.constructor-reportes.destroy' => 'Eliminar reportes personalizados',
                ],
            ],
            'Asistencia' => [
                'icon' => 'fas fa-clock',
                'permissions' => [
                    'admin.asistencia.registro' => 'Registrar mi asistencia',
                    'admin.asistencia.dashboard' => 'Ver dashboard de asistencia',
                    'admin.asistencia.reportes' => 'Ver reportes de asistencia',
                    'admin.asistencia.areas-laborales' => 'Gestionar áreas laborales',
                    'admin.asistencia.horarios-trabajo' => 'Gestionar horarios de trabajo',
                    'admin.asistencia.asignaciones' => 'Gestionar asignaciones',
                    'admin.asistencia.feriados-especiales.index' => 'Gestionar feriados especiales',
                ],
            ],
            'Usuarios y Roles' => [
                'icon' => 'fas fa-user-shield',
                'permissions' => [
                    'admin.usuarios.index' => 'Ver usuarios',
                    'admin.usuarios.create' => 'Crear usuarios',
                    'admin.usuarios.edit' => 'Editar usuarios',
                    'admin.usuarios.destroy' => 'Eliminar usuarios',
                    'admin.roles.index' => 'Ver roles',
                    'admin.roles.create' => 'Crear roles',
                    'admin.roles.edit' => 'Editar roles',
                    'admin.roles.destroy' => 'Eliminar roles',
                ],
            ],
            'Configuración' => [
                'icon' => 'fas fa-cogs',
                'permissions' => [
                    'admin.zonas.index' => 'Gestionar zonas',
                    'admin.zonas.create' => 'Crear zonas',
                    'admin.zonas.edit' => 'Editar zonas',
                    'admin.zonas.destroy' => 'Eliminar zonas',
                    'admin.sucursales.index' => 'Gestionar sucursales',
                    'admin.sucursales.create' => 'Crear sucursales',
                    'admin.sucursales.edit' => 'Editar sucursales',
                    'admin.sucursales.destroy' => 'Eliminar sucursales',
                    'admin.bancos.index' => 'Gestionar bancos',
                    'admin.bancos.create' => 'Crear bancos',
                    'admin.bancos.edit' => 'Editar bancos',
                    'admin.bancos.destroy' => 'Eliminar bancos',
                    'admin.cuentas.index' => 'Gestionar cuentas bancarias',
                    'admin.cuentas.create' => 'Crear cuentas bancarias',
                    'admin.cuentas.edit' => 'Editar cuentas bancarias',
                    'admin.cuentas.destroy' => 'Eliminar cuentas bancarias',
                    'admin.billeteras-digitales.index' => 'Gestionar billeteras digitales',
                    'admin.billeteras-digitales.create' => 'Crear billeteras digitales',
                    'admin.billeteras-digitales.edit' => 'Editar billeteras digitales',
                    'admin.billeteras-digitales.destroy' => 'Eliminar billeteras digitales',
                    'admin.metodosdepago.index' => 'Gestionar métodos de pago',
                    'admin.metodosdepago.create' => 'Crear métodos de pago',
                    'admin.metodosdepago.edit' => 'Editar métodos de pago',
                    'admin.metodosdepago.destroy' => 'Eliminar métodos de pago',
                    'admin.estadosgestion.index' => 'Gestionar estados de gestión',
                    'admin.estadosgestion.create' => 'Crear estados de gestión',
                    'admin.estadosgestion.edit' => 'Editar estados de gestión',
                    'admin.estadosgestion.destroy' => 'Eliminar estados de gestión',
                    'admin.etiquetas.index' => 'Gestionar etiquetas',
                    'admin.etiquetas.create' => 'Crear etiquetas',
                    'admin.etiquetas.edit' => 'Editar etiquetas',
                    'admin.etiquetas.destroy' => 'Eliminar etiquetas',
                    'admin.tasas.index' => 'Gestionar tasas de interés',
                    'admin.tasas.create' => 'Crear tasas de interés',
                    'admin.tasas.edit' => 'Editar tasas de interés',
                    'admin.tasas.destroy' => 'Eliminar tasas de interés',
                    'admin.plazos.index' => 'Gestionar plazos',
                    'admin.plazos.create' => 'Crear plazos',
                    'admin.plazos.edit' => 'Editar plazos',
                    'admin.plazos.destroy' => 'Eliminar plazos',
                ],
            ],
            'API Móvil' => [
                'icon' => 'fas fa-mobile-alt',
                'permissions' => [
                    'admin.api-config.index' => 'Ver configuración API',
                    'admin.api-config.create' => 'Crear configuración API',
                    'admin.api-config.edit' => 'Editar configuración API',
                    'admin.api-config.destroy' => 'Eliminar configuración API',
                    'admin.api-docs.index' => 'Ver panel de API',
                    'admin.api-docs.testing' => 'Testing API móvil',
                ],
            ],
            'Mi Perfil' => [
                'icon' => 'fas fa-user-circle',
                'permissions' => [
                    'admin.profile.edit' => 'Ver/Editar mi perfil',
                    'admin.password.change' => 'Cambiar mi contraseña',
                ],
            ],
            'Sincronización DB' => [
                'icon' => 'fas fa-sync-alt',
                'permissions' => [
                    'admin.database-sync.dashboard' => 'Ver dashboard sincronización DB',
                    'admin.database-sync.configuration' => 'Configurar sincronización DB',
                    'admin.database-sync.monitoring' => 'Monitorear sincronización DB',
                    'admin.database-sync.logs' => 'Ver logs de seguridad',
                    'admin.database-sync.blocked-ips' => 'Gestionar IPs bloqueadas',
                    'admin.database-sync.backups' => 'Gestionar respaldos seguros',
                ],
            ],
            'Administración Sistema' => [
                'icon' => 'fas fa-server',
                'permissions' => [
                    'admin.respaldos.index' => 'Ver respaldos BD',
                    'admin.respaldos.create' => 'Crear respaldos BD',
                    'admin.respaldos.restore' => 'Restaurar respaldos BD',
                    'admin.respaldos.destroy' => 'Eliminar respaldos BD',
                    'admin.logs.index' => 'Ver logs del sistema',
                    'admin.logs.clear' => 'Limpiar logs del sistema',
                    'admin.monitoreo.index' => 'Ver monitoreo del sistema',
                    'admin.monitoreo.alerts' => 'Gestionar alertas del sistema',
                    'admin.template-editor.index' => 'Editor de plantillas',
                    'admin.template-editor.create' => 'Crear plantillas',
                    'admin.template-editor.edit' => 'Editar plantillas',
                    'admin.template-editor.destroy' => 'Eliminar plantillas',
                    'admin.template-editor.variables' => 'Gestionar variables del sistema',
                    'admin.configuracion-sunat.index' => 'Ver configuración SUNAT',
                    'admin.configuracion-sunat.edit' => 'Editar configuración SUNAT',
                    'admin.brand.index' => 'Ver configuración de marca',
                    'admin.brand.edit' => 'Editar configuración de marca',
                ],
            ],
        ];
    }
}
