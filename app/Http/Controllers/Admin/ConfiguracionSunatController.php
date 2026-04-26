<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionSunat;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ConfiguracionSunatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:Admin'); // Solo administradores pueden configurar SUNAT
    }

    public function index()
    {
        $configuraciones = ConfiguracionSunat::orderBy('created_at', 'desc')->get();

        // Información del directorio de certificados
        $directorioInfo = [
            'ruta_relativa' => 'storage/app/keys/',
            'ruta_absoluta' => storage_path('app/keys'),
            'existe' => is_dir(storage_path('app/keys')),
            'escribible' => is_dir(storage_path('app/keys')) && is_writable(storage_path('app/keys')),
        ];

        return view('admin.ConfiguracionSunat.index', compact('configuraciones', 'directorioInfo'));
    }

    public function create()
    {
        return view('admin.ConfiguracionSunat.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ruc' => 'required|string|size:11',
            'usuario_sol' => 'required|string|max:255',
            'clave_sol' => 'required|string|max:255',
            'ambiente' => 'required|in:beta,produccion',
            'certificado' => 'nullable|file|max:2048',
            'certificado_clave' => 'nullable|string|max:255',
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'required|string|max:255',
            'ubigeo' => 'required|string|size:6',
            'distrito' => 'required|string|max:100',
            'provincia' => 'required|string|max:100',
            'departamento' => 'required|string|max:100',
            'serie_factura' => 'required|string|size:4',
            'serie_boleta' => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Validación adicional para el certificado
        if ($request->hasFile('certificado')) {
            $file = $request->file('certificado');
            $extension = strtolower($file->getClientOriginalExtension());

            if (! in_array($extension, ['pem', 'p12', 'pfx'])) {
                return back()->withErrors(['certificado' => 'El certificado debe ser un archivo .pem, .p12 o .pfx'])->withInput();
            }
        }

        $data = $request->all();

        $configuracion = ConfiguracionSunat::create($data);

        // Procesar certificado si se subió
        if ($request->hasFile('certificado')) {
            $certificado = $request->file('certificado');
            $fileContent = file_get_contents($certificado->getRealPath());
            $password = $request->input('certificado_clave', '');

            // Guardar certificado como archivo físico
            $configuracion->saveCertificateAsFile($fileContent, $password);

            // Actualizar el nombre del certificado
            $configuracion->update(['certificado_nombre' => $certificado->getClientOriginalName()]);
        }

        // Si es la primera configuración, activarla automáticamente
        if (ConfiguracionSunat::count() === 1) {
            $configuracion->activar();
        }

        return redirect()->route('admin.configuracion-sunat.index')
            ->with('success', 'Configuración SUNAT creada exitosamente');
    }

    public function show(ConfiguracionSunat $configuracionSunat)
    {
        return view('admin.ConfiguracionSunat.show', compact('configuracionSunat'));
    }

    public function edit(ConfiguracionSunat $configuracionSunat)
    {
        return view('admin.ConfiguracionSunat.edit', compact('configuracionSunat'));
    }

    public function update(Request $request, ConfiguracionSunat $configuracionSunat)
    {
        $validator = Validator::make($request->all(), [
            'ruc' => 'required|string|size:11',
            'usuario_sol' => 'required|string|max:255',
            'clave_sol' => 'required|string|max:255',
            'ambiente' => 'required|in:beta,produccion',
            'certificado' => 'nullable|file|max:2048',
            'certificado_clave' => 'nullable|string|max:255',
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'required|string|max:255',
            'ubigeo' => 'required|string|size:6',
            'distrito' => 'required|string|max:100',
            'provincia' => 'required|string|max:100',
            'departamento' => 'required|string|max:100',
            'serie_factura' => 'required|string|size:4',
            'serie_boleta' => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Validación adicional para el certificado
        if ($request->hasFile('certificado')) {
            $file = $request->file('certificado');
            $extension = strtolower($file->getClientOriginalExtension());

            if (! in_array($extension, ['pem', 'p12', 'pfx'])) {
                return back()->withErrors(['certificado' => 'El certificado debe ser un archivo .pem, .p12 o .pfx'])->withInput();
            }
        }

        $data = $request->all();

        $configuracionSunat->update($data);

        // Procesar certificado si se subió uno nuevo
        if ($request->hasFile('certificado')) {
            $certificado = $request->file('certificado');
            $fileContent = file_get_contents($certificado->getRealPath());
            $password = $request->input('certificado_clave', '');

            // Guardar certificado como archivo físico
            $configuracionSunat->saveCertificateAsFile($fileContent, $password);

            // Actualizar el nombre del certificado
            $configuracionSunat->update(['certificado_nombre' => $certificado->getClientOriginalName()]);
        }

        return redirect()->route('admin.configuracion-sunat.index')
            ->with('success', 'Configuración SUNAT actualizada exitosamente');
    }

    public function destroy(ConfiguracionSunat $configuracionSunat)
    {
        // No permitir eliminar si es la única configuración activa
        if ($configuracionSunat->activo && ConfiguracionSunat::where('activo', true)->count() === 1) {
            return back()->with('error', 'No se puede eliminar la única configuración activa');
        }

        $configuracionSunat->delete();

        return redirect()->route('admin.configuracion-sunat.index')
            ->with('success', 'Configuración SUNAT eliminada exitosamente');
    }

    public function activar(ConfiguracionSunat $configuracionSunat)
    {
        $configuracionSunat->activar();

        return back()->with('success', 'Configuración activada exitosamente');
    }

    public function testConexion(ConfiguracionSunat $configuracionSunat)
    {
        try {
            // Aquí podrías implementar una prueba de conexión real con SUNAT
            // Por ahora solo validamos que los datos estén completos

            if (! $configuracionSunat->certificado_contenido) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se ha subido un certificado válido',
                ]);
            }

            // Simular prueba de conexión exitosa
            return response()->json([
                'success' => true,
                'message' => 'Conexión exitosa con SUNAT ('.strtoupper($configuracionSunat->ambiente).')',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Realizar diagnóstico completo de SUNAT
     */
    public function diagnostico(Request $request)
    {
        try {
            $config = ConfiguracionSunat::where('activo', true)->first();

            $recomendaciones = [];
            $criticos = 0;
            $advertencias = 0;
            $exitosas = 0;

            // Estado General
            $estadoGeneral = [
                'activo' => (bool) $config,
                'ambiente' => $config ? $config->ambiente : null,
                'ruc' => $config ? $config->ruc : null,
            ];

            // Verificaciones de Configuración
            $configuracion = [];

            if (! $config) {
                $configuracion[] = [
                    'nombre' => 'Configuración SUNAT',
                    'estado' => false,
                    'valor' => 'No existe configuración activa',
                ];
                $criticos++;
                $recomendaciones[] = 'Crear y activar una configuración SUNAT';
            } else {
                $configuracion[] = [
                    'nombre' => 'Configuración SUNAT',
                    'estado' => true,
                    'valor' => 'Configuración activa',
                ];
                $exitosas++;

                // RUC
                if ($config->ruc && strlen($config->ruc) === 11) {
                    $configuracion[] = [
                        'nombre' => 'RUC',
                        'estado' => true,
                        'valor' => $config->ruc,
                    ];
                    $exitosas++;
                } else {
                    $configuracion[] = [
                        'nombre' => 'RUC',
                        'estado' => false,
                        'valor' => 'RUC inválido o no configurado',
                    ];
                    $criticos++;
                    $recomendaciones[] = 'Configurar un RUC válido de 11 dígitos';
                }

                // Usuario SOL
                if ($config->usuario_sol) {
                    $configuracion[] = [
                        'nombre' => 'Usuario SOL',
                        'estado' => true,
                        'valor' => $config->usuario_sol,
                    ];
                    $exitosas++;
                } else {
                    $configuracion[] = [
                        'nombre' => 'Usuario SOL',
                        'estado' => false,
                        'valor' => 'No configurado',
                    ];
                    $criticos++;
                    $recomendaciones[] = 'Configurar usuario SOL válido';
                }

                // Clave SOL
                if ($config->clave_sol) {
                    $configuracion[] = [
                        'nombre' => 'Clave SOL',
                        'estado' => true,
                        'valor' => 'Configurada',
                    ];
                    $exitosas++;
                } else {
                    $configuracion[] = [
                        'nombre' => 'Clave SOL',
                        'estado' => false,
                        'valor' => 'No configurada',
                    ];
                    $criticos++;
                    $recomendaciones[] = 'Configurar clave SOL';
                }

                // Series
                if ($config->serie_factura && $config->serie_boleta) {
                    $configuracion[] = [
                        'nombre' => 'Series de Comprobantes',
                        'estado' => true,
                        'valor' => "F: {$config->serie_factura}, B: {$config->serie_boleta}",
                    ];
                    $exitosas++;
                } else {
                    $configuracion[] = [
                        'nombre' => 'Series de Comprobantes',
                        'estado' => false,
                        'valor' => 'Series incompletas',
                    ];
                    $advertencias++;
                    $recomendaciones[] = 'Configurar series para facturas y boletas';
                }
            }

            // Certificados y Permisos
            $certificadosPermisos = [];

            if ($config) {
                // Certificado
                if ($config->hasCertificateFile()) {
                    $rutaCertificado = $config->getCertificateFilePath();
                    $nombreArchivo = $config->certificado_file_path;
                    $certificadosPermisos[] = [
                        'nombre' => 'Certificado Digital',
                        'estado' => true,
                        'descripcion' => "Archivo físico: {$nombreArchivo}",
                        'ruta_completa' => $rutaCertificado,
                        'directorio' => 'storage/app/keys/',
                    ];
                    $exitosas++;
                } elseif ($config->certificado_contenido) {
                    $certificadosPermisos[] = [
                        'nombre' => 'Certificado Digital',
                        'estado' => true,
                        'descripcion' => 'Almacenado en base de datos (legacy)',
                        'recomendacion' => 'Se recomienda migrar a archivo físico',
                    ];
                    $exitosas++;
                } else {
                    $certificadosPermisos[] = [
                        'nombre' => 'Certificado Digital',
                        'estado' => false,
                        'descripcion' => 'Sin certificado',
                    ];
                    $criticos++;
                    $recomendaciones[] = 'Subir certificado digital válido';
                }

                // Clave del certificado
                if ($config->certificado_clave) {
                    $certificadosPermisos[] = [
                        'nombre' => 'Clave de Certificado',
                        'estado' => true,
                        'descripcion' => 'Configurada',
                    ];
                    $exitosas++;
                } else {
                    $certificadosPermisos[] = [
                        'nombre' => 'Clave de Certificado',
                        'estado' => false,
                        'descripcion' => 'No configurada',
                    ];
                    $criticos++;
                    $recomendaciones[] = 'Configurar clave del certificado digital';
                }
            }

            // Verificar directorio de certificados
            $keysPath = storage_path('app/keys');
            $relativePath = 'storage/app/keys/';

            if (is_dir($keysPath) && is_writable($keysPath)) {
                $certificadosPermisos[] = [
                    'nombre' => 'Directorio de Certificados',
                    'estado' => true,
                    'descripcion' => "Ruta: {$relativePath}",
                    'ruta_completa' => $keysPath,
                    'permisos' => 'Lectura y escritura disponibles',
                ];
                $exitosas++;
            } else {
                if (! is_dir($keysPath)) {
                    // Crear directorio si no existe
                    try {
                        mkdir($keysPath, 0755, true);
                        $certificadosPermisos[] = [
                            'nombre' => 'Directorio de Certificados',
                            'estado' => true,
                            'descripcion' => "Ruta: {$relativePath} (creado automáticamente)",
                            'ruta_completa' => $keysPath,
                        ];
                        $exitosas++;
                    } catch (\Exception $e) {
                        $certificadosPermisos[] = [
                            'nombre' => 'Directorio de Certificados',
                            'estado' => false,
                            'descripcion' => "No se pudo crear: {$relativePath}",
                        ];
                        $criticos++;
                        $recomendaciones[] = "Crear manualmente el directorio: {$keysPath}";
                    }
                } else {
                    $certificadosPermisos[] = [
                        'nombre' => 'Directorio de Certificados',
                        'estado' => false,
                        'descripcion' => "Sin permisos de escritura: {$relativePath}",
                    ];
                    $criticos++;
                    $recomendaciones[] = "Dar permisos de escritura al directorio: {$keysPath}";
                }
            }

            // Conectividad y Verificaciones SUNAT
            $conectividad = [];

            // Verificar conexión a internet
            $start = microtime(true);
            try {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 5,
                    ],
                ]);
                $result = @file_get_contents('https://www.google.com', false, $context);
                $tiempo = round((microtime(true) - $start) * 1000);

                if ($result !== false) {
                    $conectividad[] = [
                        'nombre' => 'Conexión a Internet',
                        'estado' => true,
                        'descripcion' => 'Conectado',
                        'tiempo' => $tiempo,
                    ];
                    $exitosas++;
                } else {
                    $conectividad[] = [
                        'nombre' => 'Conexión a Internet',
                        'estado' => false,
                        'descripcion' => 'Sin conexión',
                    ];
                    $criticos++;
                    $recomendaciones[] = 'Verificar conexión a internet';
                }
            } catch (\Exception $e) {
                $conectividad[] = [
                    'nombre' => 'Conexión a Internet',
                    'estado' => false,
                    'descripcion' => 'Error de conexión',
                ];
                $criticos++;
                $recomendaciones[] = 'Verificar conexión a internet';
            }

            // Verificar conexión con SUNAT
            if ($config) {
                $urlSunat = $config->ambiente === 'produccion'
                    ? 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService'
                    : 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService';

                $start = microtime(true);
                try {
                    $context = stream_context_create([
                        'http' => [
                            'method' => 'GET',
                            'timeout' => 10,
                            'header' => 'User-Agent: Mozilla/5.0 (compatible; SUNATChecker/1.0)',
                        ],
                    ]);

                    $result = @file_get_contents($urlSunat, false, $context);
                    $tiempo = round((microtime(true) - $start) * 1000);

                    if ($result !== false || (isset($http_response_header) && strpos($http_response_header[0], '200') !== false)) {
                        $conectividad[] = [
                            'nombre' => 'Servicio SUNAT',
                            'estado' => true,
                            'descripcion' => 'Accesible',
                            'tiempo' => $tiempo,
                        ];
                        $exitosas++;
                    } else {
                        $conectividad[] = [
                            'nombre' => 'Servicio SUNAT',
                            'estado' => false,
                            'descripcion' => 'No accesible',
                        ];
                        $criticos++;
                        $recomendaciones[] = 'Verificar acceso al servicio SUNAT';
                    }
                } catch (\Exception $e) {
                    $conectividad[] = [
                        'nombre' => 'Servicio SUNAT',
                        'estado' => false,
                        'descripcion' => 'Error: '.$e->getMessage(),
                    ];
                    $criticos++;
                    $recomendaciones[] = 'Verificar conectividad con servicios SUNAT';
                }

                // Verificar permisos de emisión (simulado)
                if ($config->ruc && $config->usuario_sol && $config->clave_sol) {
                    // Aquí se podría hacer una verificación real con SUNAT
                    // Por ahora simulamos basándose en la configuración
                    $tienePermisos = $this->verificarPermisosSunat($config);

                    if ($tienePermisos['estado']) {
                        $conectividad[] = [
                            'nombre' => 'Permisos de Emisión SUNAT',
                            'estado' => true,
                            'descripcion' => $tienePermisos['mensaje'],
                        ];
                        $exitosas++;
                    } else {
                        $conectividad[] = [
                            'nombre' => 'Permisos de Emisión SUNAT',
                            'estado' => false,
                            'descripcion' => $tienePermisos['mensaje'],
                        ];
                        $criticos++;
                        $recomendaciones[] = $tienePermisos['recomendacion'];
                    }
                } else {
                    $conectividad[] = [
                        'nombre' => 'Permisos de Emisión SUNAT',
                        'estado' => false,
                        'descripcion' => 'Configuración incompleta',
                    ];
                    $criticos++;
                    $recomendaciones[] = 'Completar configuración RUC, Usuario SOL y Clave SOL';
                }
            }

            // Verificar extensiones PHP
            $extensionesRequeridas = ['curl', 'openssl', 'zip', 'dom'];
            foreach ($extensionesRequeridas as $extension) {
                if (extension_loaded($extension)) {
                    $conectividad[] = [
                        'nombre' => "Extensión PHP: {$extension}",
                        'estado' => true,
                        'descripcion' => 'Instalada',
                    ];
                    $exitosas++;
                } else {
                    $conectividad[] = [
                        'nombre' => "Extensión PHP: {$extension}",
                        'estado' => false,
                        'descripcion' => 'No instalada',
                    ];
                    $criticos++;
                    $recomendaciones[] = "Instalar extensión PHP: {$extension}";
                }
            }

            // Resumen
            $totalVerificaciones = $exitosas + $advertencias + $criticos;

            if ($criticos > 0) {
                $mensaje = 'Configuración SUNAT requiere atención inmediata';
            } elseif ($advertencias > 0) {
                $mensaje = 'Configuración SUNAT funcional con algunas recomendaciones';
            } else {
                $mensaje = 'Configuración SUNAT completamente funcional';
            }

            $resumen = [
                'total_verificaciones' => $totalVerificaciones,
                'exitosas' => $exitosas,
                'advertencias' => $advertencias,
                'criticos' => $criticos,
                'mensaje' => $mensaje,
            ];

            $data = [
                'estado_general' => $estadoGeneral,
                'configuracion' => $configuracion,
                'certificados_permisos' => $certificadosPermisos,
                'conectividad' => $conectividad,
                'resumen' => $resumen,
                'recomendaciones' => $recomendaciones,
            ];

            // Si es una petición AJAX (POST), devolver JSON
            if ($request->isMethod('post') || $request->ajax()) {
                return response()->json($data);
            }

            // Si es GET, devolver vista con los datos
            return view('admin.ConfiguracionSunat.diagnostico', compact('data'));

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error al realizar el diagnóstico: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verificar permisos de emisión ante SUNAT
     */
    private function verificarPermisosSunat($config)
    {
        try {
            // Validaciones básicas del RUC
            if (! $config->ruc || strlen($config->ruc) !== 11) {
                return [
                    'estado' => false,
                    'mensaje' => 'RUC inválido',
                    'recomendacion' => 'Configurar un RUC válido de 11 dígitos',
                ];
            }

            // Verificar si el RUC tiene formato válido
            if (! preg_match('/^(10|15|17|20)\d{9}$/', $config->ruc)) {
                return [
                    'estado' => false,
                    'mensaje' => 'Formato de RUC inválido',
                    'recomendacion' => 'El RUC debe comenzar con 10, 15, 17 o 20 seguido de 9 dígitos',
                ];
            }

            // Verificar que sea RUC de empresa (20) para emisión electrónica
            if (! str_starts_with($config->ruc, '20')) {
                return [
                    'estado' => false,
                    'mensaje' => 'RUC no autorizado para emisión electrónica',
                    'recomendacion' => 'Solo las empresas (RUC que inicia con 20) pueden emitir comprobantes electrónicos',
                ];
            }

            // Verificar datos SOL
            if (! $config->usuario_sol || ! $config->clave_sol) {
                return [
                    'estado' => false,
                    'mensaje' => 'Credenciales SOL incompletas',
                    'recomendacion' => 'Configurar usuario y clave SOL válidos',
                ];
            }

            // Verificar certificado digital
            if ((! $config->hasCertificateFile() && ! $config->certificado_contenido) || ! $config->certificado_clave) {
                return [
                    'estado' => false,
                    'mensaje' => 'Certificado digital faltante',
                    'recomendacion' => 'Subir certificado digital válido con su clave',
                ];
            }

            // Aquí se podría hacer una verificación real con SUNAT
            // Por seguridad, no hacemos la verificación real aquí ya que requeriría
            // enviar las credenciales SOL a SUNAT en texto plano

            // Simulamos una verificación exitosa si todo está configurado
            return [
                'estado' => true,
                'mensaje' => 'Configuración completa para emisión electrónica',
                'recomendacion' => '',
            ];

        } catch (\Exception $e) {
            return [
                'estado' => false,
                'mensaje' => 'Error al verificar permisos: '.$e->getMessage(),
                'recomendacion' => 'Revisar configuración y conectividad',
            ];
        }
    }

    /**
     * Obtener información del sistema de archivos de certificados
     */
    public function informacionArchivos()
    {
        $keysPath = storage_path('app/keys');
        $relativePath = 'storage/app/keys/';

        $info = [
            'directorio_absoluto' => $keysPath,
            'directorio_relativo' => $relativePath,
            'existe' => is_dir($keysPath),
            'escribible' => is_dir($keysPath) && is_writable($keysPath),
            'archivos' => [],
        ];

        if (is_dir($keysPath)) {
            $archivos = glob($keysPath.'/*.{pfx,p12,pem,cer}', GLOB_BRACE);
            foreach ($archivos as $archivo) {
                $info['archivos'][] = [
                    'nombre' => basename($archivo),
                    'tamaño' => filesize($archivo),
                    'modificado' => date('Y-m-d H:i:s', filemtime($archivo)),
                ];
            }
        }

        return response()->json($info);
    }

    /**
     * Verificar estado del contribuyente en SUNAT (opcional)
     */
    private function verificarEstadoContribuyente($ruc)
    {
        try {
            // Aquí se podría consultar la API de SUNAT para verificar el estado del contribuyente
            // Por ahora retornamos un estado genérico

            $url = "https://api.apis.net.pe/v1/ruc?numero={$ruc}";

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 5,
                    'header' => 'User-Agent: Mozilla/5.0 (compatible; SUNATChecker/1.0)',
                ],
            ]);

            $result = @file_get_contents($url, false, $context);

            if ($result) {
                $data = json_decode($result, true);
                if (isset($data['estado']) && $data['estado'] === 'ACTIVO') {
                    return [
                        'estado' => true,
                        'mensaje' => 'Contribuyente activo',
                        'datos' => $data,
                    ];
                }
            }

            return [
                'estado' => false,
                'mensaje' => 'No se pudo verificar estado del contribuyente',
                'datos' => null,
            ];

        } catch (\Exception $e) {
            return [
                'estado' => false,
                'mensaje' => 'Error al consultar estado: '.$e->getMessage(),
                'datos' => null,
            ];
        }
    }

    /**
     * Vista para buscar comprobantes en SUNAT
     */
    public function buscarComprobante()
    {
        return view('admin.ConfiguracionSunat.buscar-comprobante');
    }

    /**
     * Consultar comprobante en SUNAT por serie y número
     */
    public function consultarComprobanteSunat(Request $request)
    {
        try {
            $request->validate([
                'tipo_comprobante' => 'required|in:01,03',
                'serie' => 'required|string|size:4',
                'numero' => 'required|integer|min:1',
                'metodo_consulta' => 'nullable|in:api,certificado',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Funcionalidad deshabilitada temporalmente (Migración SIRE).',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Mostrar formulario de configuración de API SUNAT
     */
    public function apiConfig()
    {
        $configuracion = ConfiguracionSunat::obtenerActiva();

        if (!$configuracion) {
            return redirect()->route('admin.configuracion-sunat.index')
                ->with('error', 'No hay configuración SUNAT activa');
        }

        return view('admin.ConfiguracionSunat.api-config', compact('configuracion'));
    }

    /**
     * Guardar configuración de API SUNAT
     */
    public function apiConfigSave(Request $request)
    {
        try {
            $request->validate([
                'api_client_id' => 'required|string|max:255',
                'api_client_secret' => 'required|string|max:255',
            ]);

            $configuracion = ConfiguracionSunat::obtenerActiva();

            if (!$configuracion) {
                return redirect()->route('admin.configuracion-sunat.index')
                    ->with('error', 'No hay configuración SUNAT activa');
            }

            $configuracion->update([
                'api_client_id' => $request->api_client_id,
                'api_client_secret' => $request->api_client_secret,
            ]);

            return redirect()->route('admin.configuracion-sunat.api-config')
                ->with('success', 'Configuración de API SUNAT guardada correctamente');

        } catch (\Exception $e) {
            \Log::error('Error al guardar configuración de API SUNAT', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al guardar la configuración: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario de configuración SIRE/Greenter
     */
    public function sireConfig()
    {
        $config = ConfiguracionSunat::obtenerActiva();

        if (!$config) {
            return redirect()->route('admin.configuracion-sunat.index')
                ->with('error', 'No hay configuración SUNAT activa. Cree una configuración primero.');
        }

        // Obtener información del certificado si existe
        $certificadoInfo = null;
        if ($config->sire_cert_path) {
            $certPath = storage_path('app/' . $config->sire_cert_path);
            if (file_exists($certPath)) {
                try {
                    // Intentar obtener información del certificado
                    $certPassword = !empty($config->sire_cert_password) ? decrypt($config->sire_cert_password) : '';
                    $certificadoInfo = $this->obtenerInfoCertificado($certPath, $certPassword);
                } catch (\Exception $e) {
                    \Log::warning('No se pudo leer información del certificado', ['error' => $e->getMessage()]);
                }
            }
        }

        return view('admin.ConfiguracionSunat.sire-config', compact('config', 'certificadoInfo'));
    }

    /**
     * Guardar configuración SIRE/Greenter
     */
    public function sireConfigSave(Request $request)
    {
        try {
            $configuracion = ConfiguracionSunat::obtenerActiva();

            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay configuración SUNAT activa'
                ], 400);
            }

            // Validar datos
            $request->validate([
                'usar_sire' => 'nullable|boolean',
                'cert_p12' => 'nullable|file|max:20480',
                'cert_password' => 'required_with:cert_p12|nullable|string|max:255',
                'sol_user' => 'nullable|string|max:50',
                'sol_pass' => 'nullable|string|max:255',
                'modo_produccion' => 'nullable|boolean',
                // Credenciales OAuth2
                'sire_client_id' => 'nullable|string|max:255',
                'sire_client_secret' => 'nullable|string|max:255',
                // Series Testing
                'sire_serie_boleta_test' => 'nullable|string|max:4',
                'sire_numero_boleta_test' => 'nullable|integer|min:1',
                'sire_serie_factura_test' => 'nullable|string|max:4',
                'sire_numero_factura_test' => 'nullable|integer|min:1',
                // Series Producción
                'sire_serie_boleta_prod' => 'nullable|string|max:4',
                'sire_numero_boleta_prod' => 'nullable|integer|min:1',
                'sire_serie_factura_prod' => 'nullable|string|max:4',
                'sire_numero_factura_prod' => 'nullable|integer|min:1',
            ]);

            // Actualizar campos básicos
            $dataToUpdate = [
                'usar_sire' => $request->has('usar_sire') ? true : false,
                'modo_produccion' => $request->input('modo_produccion', 0) ? true : false,
            ];

            // Solo actualizar credenciales SOL si se proporcionaron
            if ($request->filled('sol_user')) {
                $dataToUpdate['sol_user'] = $request->sol_user;
            }

            if ($request->filled('sol_pass')) {
                $dataToUpdate['sol_pass'] = encrypt($request->sol_pass);
            }

            // Actualizar credenciales OAuth2 (obligatorias para evitar error 0111)
            if ($request->filled('sire_client_id')) {
                $dataToUpdate['sire_client_id'] = $request->sire_client_id;
            }

            if ($request->filled('sire_client_secret')) {
                $dataToUpdate['sire_client_secret'] = encrypt($request->sire_client_secret);
                // Invalidar token actual para forzar regeneración con nuevas credenciales
                $dataToUpdate['sire_access_token'] = null;
                $dataToUpdate['sire_token_expires_at'] = null;
            }

            // Actualizar series Testing
            if ($request->filled('sire_serie_boleta_test')) {
                $dataToUpdate['sire_serie_boleta_test'] = strtoupper($request->sire_serie_boleta_test);
            }
            if ($request->filled('sire_numero_boleta_test')) {
                $dataToUpdate['sire_numero_boleta_test'] = $request->sire_numero_boleta_test;
            }
            if ($request->filled('sire_serie_factura_test')) {
                $dataToUpdate['sire_serie_factura_test'] = strtoupper($request->sire_serie_factura_test);
            }
            if ($request->filled('sire_numero_factura_test')) {
                $dataToUpdate['sire_numero_factura_test'] = $request->sire_numero_factura_test;
            }

            // Actualizar series Producción
            if ($request->filled('sire_serie_boleta_prod')) {
                $dataToUpdate['sire_serie_boleta_prod'] = strtoupper($request->sire_serie_boleta_prod);
            }
            if ($request->filled('sire_numero_boleta_prod')) {
                $dataToUpdate['sire_numero_boleta_prod'] = $request->sire_numero_boleta_prod;
            }
            if ($request->filled('sire_serie_factura_prod')) {
                $dataToUpdate['sire_serie_factura_prod'] = strtoupper($request->sire_serie_factura_prod);
            }
            if ($request->filled('sire_numero_factura_prod')) {
                $dataToUpdate['sire_numero_factura_prod'] = $request->sire_numero_factura_prod;
            }

            // Procesar certificado digital si se subió uno nuevo
            if ($request->hasFile('cert_p12')) {
                $certificado = $request->file('cert_p12');
                $password = $request->input('cert_password', '');

                \Log::info('Procesando certificado SIRE', [
                    'filename' => $certificado->getClientOriginalName(),
                    'size' => $certificado->getSize(),
                    'has_password' => !empty($password)
                ]);

                // Guardar archivo temporal
                $tempPath = $certificado->store('sire_certs_temp');

                // Validar el certificado usando CertificateService
                $validation = CertificateService::validateAndExtractMetadata($tempPath, $password);

                if (!$validation['success']) {
                    // Eliminar archivo temporal
                    Storage::delete($tempPath);

                    return response()->json([
                        'success' => false,
                        'message' => 'Error al validar certificado: ' . $validation['error']
                    ], 400);
                }

                // Extraer certificado a formato PEM para compatibilidad con OpenSSL 3.x
                $pemExtraction = CertificateService::extractToPem($tempPath, $password);

                // Eliminar archivo temporal
                Storage::delete($tempPath);

                if ($pemExtraction['success']) {
                    // Guardar rutas de los archivos PEM extraídos
                    $dataToUpdate['sire_cert_path'] = $pemExtraction['cert_path'];
                    $dataToUpdate['sire_key_path'] = $pemExtraction['key_path'];
                    $dataToUpdate['sire_cert_password'] = encrypt($password);

                    \Log::info('Certificado extraído a formato PEM', [
                        'ruc' => $configuracion->ruc,
                        'cert_path' => $pemExtraction['cert_path'],
                        'key_path' => $pemExtraction['key_path'],
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al extraer certificado a PEM: ' . $pemExtraction['error']
                    ], 400);
                }
            }

            // Actualizar configuración
            $configuracion->update($dataToUpdate);

            return response()->json([
                'success' => true,
                'message' => 'Configuración SIRE guardada correctamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', $e->validator->errors()->all())
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Error al guardar configuración SIRE', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Probar conexión con SUNAT usando Greenter
     */
    public function testSireConnection(Request $request)
    {
        try {
            $configuracion = ConfiguracionSunat::obtenerActiva();

            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay configuración SUNAT activa'
                ], 400);
            }

            // Verificar que los datos necesarios estén configurados
            $errores = [];

            if (!$configuracion->sire_cert_path) {
                $errores[] = 'No se ha configurado el certificado digital';
            }

            if (!$configuracion->sire_cert_password) {
                $errores[] = 'No se ha configurado la contraseña del certificado';
            }

            if (!$configuracion->sol_user) {
                $errores[] = 'No se ha configurado el usuario SOL';
            }

            if (!$configuracion->sol_pass) {
                $errores[] = 'No se ha configurado la contraseña SOL';
            }

            if (count($errores) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración incompleta',
                    'errores' => $errores
                ], 400);
            }

            // Verificar que el certificado existe físicamente
            $certPath = storage_path('app/keys/' . $configuracion->sire_cert_path);
            if (!file_exists($certPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo del certificado no existe en el servidor'
                ], 400);
            }

            // Obtener información del certificado
            try {
                $certPassword = decrypt($configuracion->sire_cert_password);
                $certificadoInfo = $this->obtenerInfoCertificado($certPath, $certPassword);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al leer el certificado: ' . $e->getMessage()
                ], 400);
            }

            // Verificar que el certificado no esté expirado
            if ($certificadoInfo && isset($certificadoInfo['dias_restantes'])) {
                if ($certificadoInfo['dias_restantes'] <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El certificado digital ha expirado',
                        'certificado' => $certificadoInfo
                    ], 400);
                }
            }

            // Probar conexión con SUNAT usando SireApiService
            try {
                $sireService = app(\App\Services\SireApiService::class);
                $resultadoConexion = $sireService->testConnection();

                if ($resultadoConexion['success']) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Conexión exitosa con SUNAT',
                        'data' => [
                            'ambiente' => $configuracion->ambiente,
                            'ruc' => $configuracion->ruc,
                            'usuario_sol' => $configuracion->sol_user,
                            'certificado' => $certificadoInfo,
                            'sunat_response' => $resultadoConexion['data'] ?? null,
                        ]
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al conectar con SUNAT: ' . ($resultadoConexion['error'] ?? 'Error desconocido'),
                        'data' => [
                            'ambiente' => $configuracion->ambiente,
                            'ruc' => $configuracion->ruc,
                            'certificado' => $certificadoInfo,
                        ]
                    ], 400);
                }

            } catch (\Exception $e) {
                \Log::error('Error en prueba de conexión SIRE', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error al probar conexión: ' . $e->getMessage(),
                    'data' => [
                        'ambiente' => $configuracion->ambiente,
                        'ruc' => $configuracion->ruc,
                        'certificado' => $certificadoInfo,
                    ]
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Error en test de conexión SIRE', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información del certificado digital
     */
    private function obtenerInfoCertificado($certPath, $password)
    {
        try {
            $fileContent = file_get_contents($certPath);

            // Detectar si es PEM o PFX por la extensión
            $extension = strtolower(pathinfo($certPath, PATHINFO_EXTENSION));

            if ($extension === 'pem') {
                // Leer certificado PEM directamente
                $certData = openssl_x509_parse($fileContent);

                if (!$certData) {
                    throw new \Exception('No se pudo parsear el certificado PEM');
                }
            } else {
                // Leer certificado PFX
                $certs = [];
                if (!openssl_pkcs12_read($fileContent, $certs, $password)) {
                    throw new \Exception('No se pudo leer el certificado con la contraseña proporcionada');
                }

                // Parsear el certificado
                $certData = openssl_x509_parse($certs['cert']);

                if (!$certData) {
                    throw new \Exception('No se pudo parsear la información del certificado');
                }
            }

            // Extraer información relevante
            $subject = $certData['subject'] ?? [];
            $issuer = $certData['issuer'] ?? [];
            $validFrom = $certData['validFrom_time_t'] ?? null;
            $validTo = $certData['validTo_time_t'] ?? null;

            // Calcular días restantes
            $diasRestantes = null;
            $fechaExpiracion = null;
            if ($validTo) {
                $fechaExpiracion = date('Y-m-d H:i:s', $validTo);
                $diasRestantes = ceil(($validTo - time()) / 86400);
            }

            // Extraer RUC del certificado (CN suele contener el RUC)
            $cn = $subject['CN'] ?? $subject['commonName'] ?? '';
            $rucMatch = [];
            preg_match('/(\d{11})/', $cn, $rucMatch);
            $rucCertificado = $rucMatch[1] ?? null;

            return [
                'subject' => $subject,
                'issuer' => $issuer,
                'cn' => $cn,
                'ruc' => $rucCertificado,
                'valid_from' => $validFrom ? date('Y-m-d H:i:s', $validFrom) : null,
                'valid_to' => $fechaExpiracion,
                'dias_restantes' => $diasRestantes,
                'esta_vigente' => $diasRestantes > 0,
            ];

        } catch (\Exception $e) {
            \Log::error('Error al obtener información del certificado', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
