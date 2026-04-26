<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ApiDocsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:admin.api-docs.index')->only(['index']);
        $this->middleware('can:admin.api-docs.testing')->only(['testing']);
    }

    /**
     * Mostrar la documentación de la API móvil
     */
    public function index()
    {
        try {
            // Cargar el contenido del archivo de documentación
            $documentationPath = base_path('API_DOCUMENTATION.md');
            $documentation = '';

            if (file_exists($documentationPath)) {
                $documentation = file_get_contents($documentationPath);
            }

            return view('admin.api-docs.index', compact('documentation'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al cargar la documentación: '.$e->getMessage());
        }
    }

    /**
     * Mostrar página de testing de API
     */
    public function testing()
    {
        try {
            $apiUrl = config('app.url').'/api';
            $endpoints = $this->getApiEndpoints();

            return view('admin.api-docs.testing', compact('apiUrl', 'endpoints'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al cargar las herramientas de testing: '.$e->getMessage());
        }
    }

    /**
     * Obtener lista de endpoints principales de la API
     */
    private function getApiEndpoints()
    {
        return [
            'Autenticación' => [
                'POST /api/auth/login' => 'Iniciar sesión',
                'POST /api/auth/logout' => 'Cerrar sesión',
                'GET /api/auth/me' => 'Información del usuario',
            ],
            'Solicitudes de Préstamos' => [
                'GET /api/solicitudes' => 'Listar solicitudes',
                'POST /api/solicitudes' => 'Crear solicitud',
                'POST /api/solicitudes/calcular' => 'Simular préstamo',
            ],
            'Pagos' => [
                'POST /api/pagos' => 'Registrar pago',
                'GET /api/pagos/prestamo/{id}' => 'Pagos de préstamo',
                'GET /api/pagos/prestamo/{id}/cuotas-pendientes' => 'Cuotas pendientes',
            ],
            'Estados de Cuenta' => [
                'GET /api/estados-cuenta/prestamo/{id}' => 'Estado de cuenta',
                'GET /api/estados-cuenta/prestamo/{id}/pdf' => 'Generar PDF',
                'GET /api/estados-cuenta/resumen-cartera' => 'Resumen de cartera',
            ],
            'Gestiones' => [
                'GET /api/gestiones' => 'Listar gestiones',
                'POST /api/gestiones' => 'Crear gestión',
                'GET /api/gestiones/agenda/programadas' => 'Agenda programada',
            ],
            'Compromisos' => [
                'GET /api/compromisos' => 'Listar compromisos',
                'POST /api/compromisos' => 'Crear compromiso',
                'POST /api/compromisos/{id}/cumplir' => 'Cumplir compromiso',
            ],
            'Fondos Provisionales' => [
                'GET /api/fondos-provisionales' => 'Listar fondos',
                'POST /api/fondos-provisionales' => 'Solicitar fondo',
                'GET /api/fondos-provisionales/mis-fondos/listado' => 'Mis fondos',
            ],
            'Asistencia' => [
                'POST /api/asistencia/entrada' => 'Registrar entrada',
                'POST /api/asistencia/salida' => 'Registrar salida',
                'GET /api/asistencia/hoy' => 'Asistencia de hoy',
            ],
            'Perfil' => [
                'GET /api/perfil' => 'Ver perfil',
                'PUT /api/perfil' => 'Actualizar perfil',
                'POST /api/perfil/foto' => 'Subir foto',
            ],
        ];
    }
}
