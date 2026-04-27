<?php

namespace App\Services;

use App\Models\ConfiguracionSunat;
use Illuminate\Support\Facades\Schema;

class MenuBuilderService
{
    /**
     * URL directa al formulario de edición de la configuración SUNAT activa.
     * Se construye como path relativo porque el menú se evalúa durante el boot
     * de la configuración, cuando el URL/Route generator aún no tiene Request.
     */
    private static function getConfiguracionSunatUrl(): string
    {
        try {
            if (! Schema::hasTable('configuracion_sunats')) {
                return 'admin/configuracion-sunat';
            }

            $config = ConfiguracionSunat::where('activo', true)->first()
                ?? ConfiguracionSunat::orderBy('id')->first();

            if ($config) {
                return 'admin/configuracion-sunat/'.$config->id.'/edit';
            }
        } catch (\Throwable $e) {
            // fall through al listado
        }

        return 'admin/configuracion-sunat';
    }

    /**
     * Construir el menú principal del sistema
     */
    public static function buildMainMenu(): array
    {
        return [
            // Navbar items
            ...self::getNavbarItems(),

            // Sidebar search
            self::getSidebarSearch(),

            // Secciones principales del menú
            ...self::getOperationsMenu(),
            ...self::getFinancialMenu(),
            ...self::getInvoicingMenu(),  // Nueva sección de Facturación
            ...self::getReportsMenu(),
            ...self::getMetasMenu(),
            ...self::getHRMenu(),
            ...self::getAdministrationMenu(),
            ...self::getSecurityMenu(),
            ...self::getSystemMenu(),
        ];
    }

    /**
     * Items de la barra de navegación superior
     */
    private static function getNavbarItems(): array
    {
        return [
            [
                'type' => 'navbar-search',
                'text' => 'search',
                'topnav_right' => true,
            ],
            [
                'type' => 'fullscreen-widget',
                'topnav_right' => true,
            ],
        ];
    }

    /**
     * Barra de búsqueda del sidebar
     */
    private static function getSidebarSearch(): array
    {
        return [
            'type' => 'sidebar-menu-search',
            'text' => 'search',
        ];
    }

    /**
     * Menú de operaciones principales
     */
    private static function getOperationsMenu(): array
    {
        return [
            /*[
                'header' => 'OPERACIONES PRINCIPALES'
            ],*/
            [
                'text' => 'Clientes',
                'icon' => 'fas fa-fw fa-users',
                'route' => 'admin.clientes.index',
                'can' => 'admin.clientes.index',
            ],
            [
                'text' => 'Préstamos',
                'route' => 'admin.prestamos.index',
                'icon' => 'fas fa-fw fa-handshake',
                'can' => 'admin.prestamos.index',
            ],
            [
                'text' => 'Convenios de Pago',
                'route' => 'admin.convenios.index',
                'icon' => 'fas fa-fw fa-file-contract',
                /*'can' => 'admin.convenios.index',*/
            ],
            [
                'text' => 'Zonificador Tramos',
                'route' => 'admin.zonificador-tramos.index',
                'icon' => 'fas fa-fw fa-map-marked-alt',
            ],
            [
                'text' => 'Operaciones',
                'route' => 'admin.operaciones.index',
                'icon' => 'fas fa-fw fa-dollar-sign',
            ],
            [
                'text' => 'Validación Operaciones',
                'route' => 'admin.validacion-operaciones.index',
                'icon' => 'fas fa-fw fa-check-double',
                'can' => 'admin.operaciones.index',
            ],
        ];
    }

    /**
     * Menú financiero y cobranzas
     */
    private static function getFinancialMenu(): array
    {
        return [
            /*[
                'header' => 'GESTIÓN FINANCIERA'
            ],*/
            [
                'text' => 'Cobranzas',
                'icon' => 'fas fa-fw fa-hand-holding-usd',
                'can' => ['admin.gestiones.index', 'admin.compromisos.index'],
                'submenu' => [
                    [
                        'text' => 'Gestiones',
                        'route' => 'admin.gestiones.index',
                        'icon' => 'fas fa-fw fa-phone',
                        'can' => 'admin.gestiones.index',
                    ],
                    [
                        'text' => 'Compromisos',
                        'route' => 'admin.compromisos.index',
                        'icon' => 'fas fa-fw fa-handshake',
                        'can' => 'admin.compromisos.index',
                    ],
                ],
            ],
            [
                'text' => 'Caja y Tesorería',
                'icon' => 'fas fa-fw fa-cash-register',
                'can' => 'admin.caja.index',
                'submenu' => [
                    [
                        'text' => 'Caja Diaria',
                        'icon' => 'fas fa-fw fa-cash-register',
                        'route' => 'admin.caja.index',
                        'can' => 'admin.caja.index',
                    ],
                    [
                        'text' => 'Fondos Provisionales',
                        'icon' => 'fas fa-fw fa-piggy-bank',
                        'route' => 'admin.fondo-provisional.index',
                        'can' => 'admin.caja.index',
                    ],
                    [
                        'text' => 'Rendiciones',
                        'icon' => 'fas fa-fw fa-history',
                        'route' => 'admin.caja.historialRendiciones',
                        'can' => 'admin.caja.rendiciones',
                    ],
                ],
            ],
            [
                'text' => 'Gastos',
                'icon' => 'fas fa-fw fa-receipt',
                'can' => 'admin.gastos.index',
                'submenu' => [
                    [
                        'text' => 'Listado de Gastos',
                        'icon' => 'fas fa-fw fa-list',
                        'route' => 'admin.gastos.index',
                        'can' => 'admin.gastos.index',
                    ],
                    [
                        'text' => 'Categorías',
                        'icon' => 'fas fa-fw fa-tags',
                        'route' => 'admin.categorias-gastos.index',
                        'can' => 'admin.categorias-gastos.index',
                    ],
                ],
            ],
        ];
    }

    /**
     * Menú de facturación electrónica y comprobantes
     */
    private static function getInvoicingMenu(): array
    {
        return [
            [
                'text' => 'Facturación',
                'icon' => 'fas fa-file-invoice-dollar',
                'can' => 'admin.comprobantes.index',
                'submenu' => [
                    [
                        'text' => 'Dashboard Facturación',
                        'route' => 'admin.facturacion.dashboard',
                        'icon' => 'fas fa-fw fa-tachometer-alt',
                        'can' => 'admin.comprobantes.index',
                    ],
                    [
                        'text' => 'Comprobantes',
                        'route' => 'admin.comprobantes.index',
                        'icon' => 'fas fa-fw fa-file-invoice',
                        'can' => 'admin.comprobantes.index',
                    ],
                    /*[
                        'text' => 'Declarados',
                        'route' => 'admin.comprobantes.declarados',
                        'icon' => 'fas fa-fw fa-check-circle',
                        'can' => 'admin.comprobantes.index',
                    ],*/
                    [
                        'text' => 'SIRE - Comprobantes Electrónicos',
                        'route' => 'admin.sire.index',
                        'icon' => 'fas fa-fw fa-file-alt',
                        'can' => 'admin.comprobantes.index',
                    ],
                    /*[
                        'text' => 'Estado SUNAT',
                        'icon' => 'fas fa-fw fa-heartbeat',
                        'route' => 'admin.sunat-status.index',
                        'can' => 'admin.comprobantes.index',
                    ],*/
                    [
                        'text' => 'Configuración SUNAT',
                        'icon' => 'fas fa-fw fa-cog',
                        'url' => self::getConfiguracionSunatUrl(),
                        'can' => 'admin.configuracion-sunat.index',
                    ],
                ],
            ],
        ];
    }

    /**
     * Menú de reportes
     */
    private static function getReportsMenu(): array
    {
        return [
            [
                'text' => 'Reportes',
                'icon' => 'fas fa-chart-bar',
                'can' => ['admin.constructor-reportes.index'],
                'submenu' => [
                    [
                        'text' => 'Tramos',
                        'route' => 'admin.deudas.tramos',
                        'icon' => 'fas fa-exclamation-triangle',
                        'can' => 'admin.deudas.index',
                    ],
                    [
                        'text' => 'Reporte Ventas',
                        'route' => 'admin.reportes-sale.index',
                        'icon' => 'fas fa-exclamation-triangle',
                        // 'can' => 'admin.reportes-sale.index',
                    ],
                    [
                        'text' => 'Cuotas / Moras',
                        'route' => 'admin.deudas.index',
                        'icon' => 'fas fa-exclamation-triangle',
                        'can' => 'admin.deudas.index',
                    ],
                    [
                        'text' => 'Clientes por Usuario',
                        'route' => 'admin.reportes-clientes.index',
                        'icon' => 'fas fa-users',
                        'can' => 'admin.constructor-reportes.index',
                    ],
                    [
                        'text' => 'Registros Huérfanos',
                        'route' => 'admin.huerfanas.index',
                        'icon' => 'fas fa-ghost',
                        'can' => 'admin.usuarios.index',
                    ],
                    [
                        'text' => 'Constructor Reportes',
                        'route' => 'admin.constructor-reportes.index',
                        'icon' => 'fas fa-tools',
                        'can' => 'admin.constructor-reportes.index',
                    ],
                ],
            ],
        ];
    }

    /**
     * Menú de Metas y Comisiones (Nueva sección dedicada)
     */
    private static function getMetasMenu(): array
    {
        return [
            [
                'text' => 'METAS',
                'icon' => 'fas fa-fw fa-bullseye',
                'can' => 'admin.usuarios.index',
                'submenu' => [
                    [
                        'text' => 'Metas y Comisiones',
                        'route' => 'admin.metas.index',
                        'icon' => 'fas fa-fw fa-chart-line',
                    ],
                    [
                        'text' => 'Asignación de Metas',
                        'route' => 'admin.metas.create',
                        'icon' => 'fas fa-fw fa-plus-circle',
                    ],
                    [
                        'text' => 'Configuración',
                        'route' => 'admin.metas.comisiones',
                        'icon' => 'fas fa-fw fa-cog',
                    ],
                ],
            ],
        ];
    }

    /**
     * Menú de recursos humanos
     */
    private static function getHRMenu(): array
    {
        return [
            /*[
                'header' => 'RECURSOS HUMANOS'
            ],*/
            [
                'text' => 'Asistencia',
                'icon' => 'fas fa-fw fa-clock',
                'submenu' => [
                    [
                        'text' => 'Mi Asistencia',
                        'icon' => 'fas fa-fw fa-fingerprint',
                        'route' => 'admin.asistencia.registro',
                    ],
                    [
                        'text' => 'Dashboard Asistencia',
                        'icon' => 'fas fa-fw fa-chart-line',
                        'route' => 'admin.asistencia.dashboard',
                    ],
                    [
                        'text' => 'Reportes Asistencia',
                        'icon' => 'fas fa-fw fa-file-alt',
                        'route' => 'admin.asistencia.reportes',
                    ],
                    [
                        'text' => 'Áreas Laborales',
                        'icon' => 'fas fa-fw fa-building',
                        'route' => 'admin.asistencia.areas-laborales',
                        'can' => 'admin.usuarios.index',
                    ],
                    [
                        'text' => 'Horarios de Trabajo',
                        'icon' => 'fas fa-fw fa-clock',
                        'route' => 'admin.asistencia.horarios-trabajo',
                        'can' => 'admin.usuarios.index',
                    ],
                    [
                        'text' => 'Asignaciones',
                        'icon' => 'fas fa-fw fa-user-tie',
                        'route' => 'admin.asistencia.asignaciones',
                        'can' => 'admin.usuarios.index',
                    ],
                    [
                        'text' => 'Feriados',
                        'icon' => 'fas fa-fw fa-calendar-alt',
                        'route' => 'admin.asistencia.feriados-especiales.index',
                        'can' => 'admin.usuarios.index',
                    ],
                    [
                        'text' => 'Códigos de Acceso',
                        'icon' => 'fas fa-fw fa-key',
                        'route' => 'admin.asistencia.codigos',
                        'can' => 'admin.usuarios.index',
                    ],
                    [
                        'text' => 'Solicitudes de Acceso',
                        'icon' => 'fas fa-fw fa-user-check',
                        'route' => 'admin.asistencia.solicitudes',
                        'can' => 'admin.usuarios.index',
                    ],
                    [
                        'text' => 'Reporte de Accesos',
                        'icon' => 'fas fa-fw fa-chart-line',
                        'route' => 'admin.asistencia.accesos',
                        'can' => 'admin.usuarios.index',
                    ],
                ],
            ],
            [
                'text' => 'Gestión de Tareas',
                'icon' => 'fas fa-fw fa-tasks',
                'route' => 'admin.tareas.index',
                /*'can' => 'admin.tareas.index',*/
            ],
        ];
    }

    /**
     * Menú de administración
     */
    private static function getAdministrationMenu(): array
    {
        return [
            /*[
                'header' => 'ADMINISTRACIÓN'
            ],*/
            [
                'text' => 'Usuarios y Roles',
                'icon' => 'fas fa-fw fa-user-shield',
                'can' => ['admin.usuarios.index', 'admin.roles.index'],
                'submenu' => [
                    [
                        'text' => 'Usuarios',
                        'icon' => 'fas fa-fw fa-user',
                        'route' => 'admin.usuarios.index',
                        'can' => 'admin.usuarios.index',
                    ],
                    [
                        'text' => 'Roles y Permisos',
                        'icon' => 'fas fa-fw fa-user-shield',
                        'route' => 'admin.roles.index',
                        'can' => 'admin.roles.index',
                    ],
                ],
            ],
            /*[
                'header' => 'SISTEMAS'
            ],*/
            [
                'text' => 'Configuración',
                'icon' => 'fas fa-fw fa-cogs',
                'can' => ['admin.sucursales.index', 'admin.usuarios.index'],
                'submenu' => [
                    [
                        'text' => 'Zonas',
                        'icon' => 'fas fa-fw fa-map-marker-alt',
                        'route' => 'admin.zonas.index',
                        'can' => 'admin.zonas.index',
                    ],
                    [
                        'text' => 'Sucursales',
                        'icon' => 'fas fa-fw fa-building',
                        'route' => 'admin.sucursales.index',
                        'can' => 'admin.sucursales.index',
                    ],
                    [
                        'text' => 'Bancos',
                        'icon' => 'fas fa-fw fa-university',
                        'route' => 'admin.bancos.index',
                        'can' => 'admin.bancos.index',
                    ],
                    [
                        'text' => 'Cuentas Bancarias',
                        'icon' => 'fas fa-fw fa-credit-card',
                        'route' => 'admin.cuentas.index',
                        'can' => 'admin.cuentas.index',
                    ],
                    [
                        'text' => 'Billeteras Digitales',
                        'icon' => 'fas fa-fw fa-mobile-alt',
                        'route' => 'admin.billeteras-digitales.index',
                    ],
                    [
                        'text' => 'Métodos de Pago',
                        'icon' => 'fas fa-fw fa-wallet',
                        'route' => 'admin.metodosdepago.index',
                        'can' => 'admin.metodosdepago.index',
                    ],
                    [
                        'text' => 'Estados de Gestión',
                        'icon' => 'fas fa-fw fa-folder-open',
                        'route' => 'admin.estadosgestion.index',
                        'can' => 'admin.estadosgestion.index',
                    ],
                    [
                        'text' => 'Etiquetas',
                        'icon' => 'fas fa-fw fa-tags',
                        'route' => 'admin.etiquetas.index',
                        /*'can' => 'admin.etiquetas.index',*/
                    ],
                    [
                        'text' => 'Tasas de Interés',
                        'icon' => 'fas fa-fw fa-percentage',
                        'route' => 'admin.tasas.index',
                        'can' => 'admin.tasas.index',
                    ],
                    [
                        'text' => 'Plazos',
                        'icon' => 'fas fa-fw fa-calendar',
                        'route' => 'admin.plazos.index',
                        'can' => 'admin.plazos.index',
                    ],

                ],
            ],
            [
                'text' => 'API Móvil',
                'icon' => 'fas fa-fw fa-mobile-alt',
                'can' => ['admin.api-docs.index', 'admin.api-docs.testing', 'admin.api-config.index'],
                'submenu' => [
                    [
                        'text' => 'Panel de API',
                        'icon' => 'fas fa-fw fa-tachometer-alt',
                        'route' => 'admin.api-docs.index',
                        'can' => 'admin.api-docs.index',
                    ],
                    [
                        'text' => 'Testing API',
                        'icon' => 'fas fa-fw fa-vial',
                        'route' => 'admin.api-docs.testing',
                        'can' => 'admin.api-docs.testing',
                    ],
                    [
                        'text' => 'Documentación',
                        'icon' => 'fas fa-fw fa-book',
                        'url' => '/api-docs',
                        'target' => '_blank',
                    ],
                    [
                        'text' => 'Configuración',
                        'icon' => 'fas fa-fw fa-cog',
                        'route' => 'admin.api-config.index',
                        'can' => 'admin.api-config.index',
                    ],
                ],
            ],
        ];
    }

    /**
     * Menú de seguridad y perfil
     */
    private static function getSecurityMenu(): array
    {
        return [
            /*[
                'header' => 'SEGURIDAD'
            ],*/
            [
                'text' => 'Mi Perfil',
                'icon' => 'fas fa-fw fa-user-circle',
                'submenu' => [
                    [
                        'text' => 'Ver Perfil',
                        'icon' => 'fas fa-fw fa-user',
                        'route' => 'admin.profile.edit',
                    ],
                    [
                        'text' => 'Cambiar Contraseña',
                        'icon' => 'fas fa-fw fa-lock',
                        'route' => 'admin.password.change',
                    ],
                ],
            ],
            [
                'text' => 'Sincronización DB',
                'icon' => 'fas fa-fw fa-sync-alt',
                'can' => 'admin.usuarios.index',
                'submenu' => [
                    [
                        'text' => 'Dashboard',
                        'icon' => 'fas fa-fw fa-tachometer-alt',
                        'url' => 'admin/database-sync',
                    ],
                    [
                        'text' => 'Gestión de Conexiones',
                        'icon' => 'fas fa-fw fa-database',
                        'route' => 'admin.database-sync.connections',
                    ],
                    [
                        'text' => 'Centro de Sync',
                        'icon' => 'fas fa-fw fa-sync',
                        'url' => 'admin/database-sync/syncbd',
                    ],
                    [
                        'text' => 'Configuración',
                        'icon' => 'fas fa-fw fa-cog',
                        'url' => 'admin/database-sync/configuration',
                    ],
                    [
                        'text' => 'Monitoreo',
                        'icon' => 'fas fa-fw fa-eye',
                        'url' => 'admin/database-sync/monitoring',
                    ],
                    [
                        'text' => 'Logs Seguridad',
                        'icon' => 'fas fa-fw fa-file-alt',
                        'url' => 'admin/database-sync/logs',
                    ],
                    [
                        'text' => 'IPs Bloqueadas',
                        'icon' => 'fas fa-fw fa-ban',
                        'url' => 'admin/database-sync/blocked-ips',
                    ],
                    [
                        'text' => 'Respaldos Seguros',
                        'icon' => 'fas fa-fw fa-shield-alt',
                        'url' => 'admin/database-sync/backups',
                    ],
                ],
            ],
            [
                'text' => 'Auditoría',
                'icon' => 'fas fa-fw fa-shield-alt',
                'can' => 'admin.usuarios.index',
                'submenu' => [
                    [
                        'text' => 'Actividades',
                        'icon' => 'fas fa-fw fa-list',
                        'route' => 'admin.auditoria.index',
                    ],
                    [
                        'text' => 'Resumen',
                        'icon' => 'fas fa-fw fa-chart-bar',
                        'route' => 'admin.auditoria.resumen',
                    ],
                    [
                        'text' => 'Sesiones',
                        'icon' => 'fas fa-fw fa-clock',
                        'route' => 'admin.auditoria.sesiones',
                    ],
                    [
                        'text' => 'Reporte de Sesiones',
                        'icon' => 'fas fa-fw fa-chart-pie',
                        'route' => 'admin.auditoria.reporte-sesiones',
                    ],
                ],
            ],
        ];
    }

    /**
     * Menú del sistema
     */
    private static function getSystemMenu(): array
    {
        return [
            /*[
                'header' => 'SISTEMA'
            ],*/
            [
                'text' => 'Adm. Sistema',
                'icon' => 'fas fa-fw fa-server',
                'can' => 'admin.usuarios.index',
                'submenu' => [
                    // [
                    //     'text' => 'Debug Cliente 987',
                    //     'icon' => 'fas fa-fw fa-bug',
                    //     'route' => 'admin.debug.cliente-987',
                    //     'can' => 'admin.usuarios.index',
                    // ],
                    [
                        'text' => 'Respaldos BD',
                        'icon' => 'fas fa-fw fa-database',
                        'route' => 'admin.respaldos.index',
                        'can' => 'admin.respaldos.index',
                    ],
                    [
                        'text' => 'Migraciones BD',
                        'icon' => 'fas fa-fw fa-code-branch',
                        'route' => 'admin.migraciones.index',
                        'can' => 'admin.usuarios.index',
                    ],
                    [
                        'text' => 'Logs del Sistema',
                        'icon' => 'fas fa-fw fa-exclamation-triangle',
                        'route' => 'admin.logs.index',
                        'can' => 'admin.logs.index',
                    ],
                    [
                        'text' => 'Monitoreo Sistema',
                        'icon' => 'fas fa-fw fa-heartbeat',
                        'route' => 'admin.monitoreo.index',
                        'can' => 'admin.monitoreo.index',
                    ],
                    [
                        'text' => 'Editor Plantillas',
                        'icon' => 'fas fa-fw fa-edit',
                        'route' => 'admin.template-editor.index',
                        'can' => 'admin.template-editor.index',
                    ],
                    [
                        'text' => 'Variables Sistema',
                        'icon' => 'fas fa-fw fa-code',
                        'route' => 'admin.template-editor.variables',
                        'can' => 'admin.template-editor.index',
                    ],
                    [
                        'text' => 'Marca y Diseño',
                        'icon' => 'fas fa-fw fa-palette',
                        'route' => 'admin.brand.index',
                        'can' => 'admin.brand.index',
                    ],
                ],
            ],
        ];
    }
}
