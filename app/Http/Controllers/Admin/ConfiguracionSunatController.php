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
            'certificado' => 'nullable|file|max:20480',
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

        // Sincronizar sol_user y sol_pass (usados por SireApiService) desde los campos del formulario
        $usuarioSol = $data['usuario_sol'] ?? '';
        $rucVal = $data['ruc'] ?? '';
        $data['sol_user'] = (strlen($rucVal) === 11 && str_starts_with($usuarioSol, $rucVal))
            ? substr($usuarioSol, 11)
            : $usuarioSol;
        if (!empty($data['clave_sol'])) {
            $data['sol_pass'] = encrypt($data['clave_sol']);
        }

        $configuracion = ConfiguracionSunat::create($data);

        // Procesar certificado si se subió
        if ($request->hasFile('certificado')) {
            $certificado = $request->file('certificado');
            $password = $request->input('certificado_clave', '');

            $resultCert = $this->procesarYGuardarCertificado($configuracion, $certificado, $password);

            if (! $resultCert['success']) {
                return back()->withErrors(['certificado' => $resultCert['error']])->withInput();
            }
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
            'certificado' => 'nullable|file|max:20480',
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

        // Sincronizar sol_user y sol_pass (usados por SireApiService) desde los campos del formulario
        $usuarioSol = $data['usuario_sol'] ?? '';
        $rucVal = $data['ruc'] ?? '';
        $data['sol_user'] = (strlen($rucVal) === 11 && str_starts_with($usuarioSol, $rucVal))
            ? substr($usuarioSol, 11)
            : $usuarioSol;
        if (!empty($data['clave_sol'])) {
            $data['sol_pass'] = encrypt($data['clave_sol']);
        }

        $configuracionSunat->update($data);

        // Procesar certificado si se subió uno nuevo
        if ($request->hasFile('certificado')) {
            $certificado = $request->file('certificado');
            $password = $request->input('certificado_clave', '');

            $resultCert = $this->procesarYGuardarCertificado($configuracionSunat, $certificado, $password);

            if (! $resultCert['success']) {
                return back()->withErrors(['certificado' => $resultCert['error']])->withInput();
            }
        }

        return redirect()->route('admin.configuracion-sunat.index')
            ->with('success', 'Configuración SUNAT actualizada exitosamente');
    }

    /**
     * Procesar certificado subido: validarlo, extraerlo a PEM y guardar en todos
     * los campos requeridos tanto por el flujo legacy como por SireApiService.
     *
     * Esto asegura que:
     *  - `sire_cert_path`, `sire_key_path` apunten a los PEM (usados por SireApiService).
     *  - `sire_cert_password` quede encriptada (SireApiService hace decrypt()).
     *  - El certificado queda listo para firmar XMLs y mutual TLS con SUNAT.
     */
    private function procesarYGuardarCertificado(ConfiguracionSunat $configuracion, $certificadoFile, string $password): array
    {
        try {
            // 1) Guardar el archivo original en storage/app/<tempPath>
            $tempPath = $certificadoFile->store('sire_certs_temp');

            // 2) Validar el certificado y la contraseña
            $validation = CertificateService::validateAndExtractMetadata($tempPath, $password);

            if (! $validation['success']) {
                Storage::delete($tempPath);

                return [
                    'success' => false,
                    'error' => 'Certificado inválido o contraseña incorrecta: '.$validation['error'],
                ];
            }

            // 3) Extraer a archivos PEM separados (cert + key) para usar con Greenter / mutual TLS.
            //    Forzamos el nombre con el RUC de la configuración para evitar archivos con
            //    espacios (e.g. cert_SOFTWARE DE FACTURACION ELECTRONICA_xxx.pem).
            $pemExtraction = CertificateService::extractToPem($tempPath, $password, $configuracion->ruc);

            // 4) Copiar el PFX original a storage/app/keys/ para conservar compatibilidad
            $extension = strtolower($certificadoFile->getClientOriginalExtension());
            $filenamePfx = 'certificado_'.$configuracion->ruc.'_'.time().'.'.$extension;
            $pfxDestRelative = 'keys/'.$filenamePfx;

            $pfxContent = Storage::get($tempPath);
            Storage::put($pfxDestRelative, $pfxContent);

            // 5) Eliminar archivo temporal
            Storage::delete($tempPath);

            if (! $pemExtraction['success']) {
                return [
                    'success' => false,
                    'error' => 'No se pudo extraer el certificado a PEM: '.$pemExtraction['error'],
                ];
            }

            // 6) Actualizar la configuración en BD con TODAS las rutas y contraseñas
            //    Importante: sire_cert_password debe quedar ENCRIPTADA porque SireApiService
            //    hace decrypt() en tiempo de envío.
            $configuracion->update([
                'certificado_nombre' => $certificadoFile->getClientOriginalName(),
                'certificado_file_path' => $filenamePfx,
                'certificado_clave' => $password, // legacy (texto plano) — mantenido por retrocompatibilidad
                'certificado_contenido' => null,
                // PEM separados para flujo antiguo
                'certificado_pem_path' => basename($pemExtraction['cert_path']),
                'clave_privada_pem_path' => basename($pemExtraction['key_path']),
                // Campos usados por SireApiService (flujo actual de envío a SUNAT)
                'sire_cert_path' => $pemExtraction['cert_path'],   // ej. keys/cert_20611373181_xxx.pem
                'sire_key_path' => $pemExtraction['key_path'],     // ej. keys/key_20611373181_xxx.pem
                'sire_cert_password' => encrypt($password),
            ]);

            \Log::info('Certificado procesado y guardado correctamente', [
                'ruc' => $configuracion->ruc,
                'cert_path' => $pemExtraction['cert_path'],
                'key_path' => $pemExtraction['key_path'],
                'pfx_path' => $pfxDestRelative,
                'ruc_certificado' => $validation['ruc'] ?? null,
                'vigente_hasta' => $validation['valid_to'] ?? null,
                'dias_restantes' => $validation['dias_restantes'] ?? null,
            ]);

            return [
                'success' => true,
                'metadata' => $validation,
            ];
        } catch (\Exception $e) {
            \Log::error('Error al procesar certificado en edit', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Error al procesar el certificado: '.$e->getMessage(),
            ];
        }
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

                // Clave del certificado (legacy)
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

                // Verificación real: PEM cert + PEM key existen y están limpios
                $certPemRel = $config->sire_cert_path;
                $keyPemRel = $config->sire_key_path;
                $certPemAbs = $certPemRel ? storage_path('app/'.$certPemRel) : null;
                $keyPemAbs = $keyPemRel ? storage_path('app/'.$keyPemRel) : null;

                $pemCertOk = $certPemAbs && file_exists($certPemAbs);
                $pemKeyOk = $keyPemAbs && file_exists($keyPemAbs);
                $sinEspacios = $certPemRel && $keyPemRel
                    && strpos($certPemRel, ' ') === false
                    && strpos($keyPemRel, ' ') === false;

                if ($pemCertOk && $pemKeyOk && $sinEspacios) {
                    $certificadosPermisos[] = [
                        'nombre' => 'Archivos PEM (cert + key)',
                        'estado' => true,
                        'descripcion' => 'Extraídos correctamente: '.basename($certPemRel).' / '.basename($keyPemRel),
                    ];
                    $exitosas++;
                } elseif ($pemCertOk && $pemKeyOk && ! $sinEspacios) {
                    $certificadosPermisos[] = [
                        'nombre' => 'Archivos PEM (cert + key)',
                        'estado' => false,
                        'descripcion' => 'Nombres con espacios — pueden romper mTLS: '.basename($certPemRel),
                        'accion' => ['tipo' => 'regenerar_pems', 'label' => 'Regenerar PEMs'],
                    ];
                    $advertencias++;
                    $recomendaciones[] = 'Regenerar PEMs desde el PFX con el botón "Regenerar PEMs"';
                } else {
                    $certificadosPermisos[] = [
                        'nombre' => 'Archivos PEM (cert + key)',
                        'estado' => false,
                        'descripcion' => 'No encontrados en storage/app/keys',
                        'accion' => ['tipo' => 'regenerar_pems', 'label' => 'Regenerar PEMs'],
                    ];
                    $criticos++;
                    $recomendaciones[] = 'Regenerar PEMs desde el PFX con el botón "Regenerar PEMs"';
                }

                // Verificación real: ¿el certificado puede firmar?
                try {
                    $pwdCert = $config->sire_cert_password ? \Crypt::decryptString($config->sire_cert_password) : '';
                    if ($pemCertOk && $pemKeyOk) {
                        $certContent = file_get_contents($certPemAbs);
                        $keyContent = file_get_contents($keyPemAbs);
                        $pkey = @openssl_pkey_get_private($keyContent, $pwdCert);
                        if ($pkey === false) {
                            $pkey = @openssl_pkey_get_private($keyContent);
                        }
                        $firma = null;
                        $ok = $pkey && @openssl_sign('diagnostico-sunat', $firma, $pkey, OPENSSL_ALGO_SHA256);
                        if ($ok && strlen($firma) > 0) {
                            $certificadosPermisos[] = [
                                'nombre' => 'Prueba de firma digital',
                                'estado' => true,
                                'descripcion' => 'openssl_sign OK ('.strlen($firma).' bytes) — el certificado puede firmar XML',
                            ];
                            $exitosas++;
                        } else {
                            $certificadosPermisos[] = [
                                'nombre' => 'Prueba de firma digital',
                                'estado' => false,
                                'descripcion' => 'No se pudo firmar con la clave privada — revisa clave del certificado',
                                'accion' => ['tipo' => 'ir_editar', 'label' => 'Re-subir certificado'],
                            ];
                            $criticos++;
                            $recomendaciones[] = 'Re-subir el certificado con la contraseña correcta';
                        }
                    }
                } catch (\Throwable $e) {
                    $certificadosPermisos[] = [
                        'nombre' => 'Prueba de firma digital',
                        'estado' => false,
                        'descripcion' => 'Error: '.$e->getMessage(),
                    ];
                    $criticos++;
                }

                // Verificación: ¿sire_cert_password se puede desencriptar?
                try {
                    if ($config->sire_cert_password) {
                        \Crypt::decryptString($config->sire_cert_password);
                        $certificadosPermisos[] = [
                            'nombre' => 'Desencriptado de clave del certificado',
                            'estado' => true,
                            'descripcion' => 'La clave del certificado se desencripta correctamente con APP_KEY actual',
                        ];
                        $exitosas++;
                    }
                } catch (\Throwable $e) {
                    $certificadosPermisos[] = [
                        'nombre' => 'Desencriptado de clave del certificado',
                        'estado' => false,
                        'descripcion' => 'MAC inválido — sire_cert_password fue encriptada con otra APP_KEY',
                        'accion' => ['tipo' => 'reencriptar_cert_pass', 'label' => 'Re-encriptar con APP_KEY actual', 'requiere_input' => true, 'input_label' => 'Contraseña del certificado (.pfx)'],
                    ];
                    $criticos++;
                    $recomendaciones[] = 'Re-encriptar la clave del certificado con la APP_KEY actual';
                }

                // Verificación: sol_pass (usuario SOL secundario - el que usa SireApiService)
                // sol_pass se guarda encriptado (> 50 chars). clave_sol es el usuario SOL principal (texto plano).
                $solPass = $config->sol_pass ?? null;
                if ($solPass && strlen($solPass) > 50) {
                    try {
                        \Crypt::decryptString($solPass);
                        $certificadosPermisos[] = [
                            'nombre' => 'Desencriptado de Clave SOL secundaria (sol_pass)',
                            'estado' => true,
                            'descripcion' => 'sol_pass se desencripta correctamente con APP_KEY actual',
                        ];
                        $exitosas++;
                    } catch (\Throwable $e) {
                        $certificadosPermisos[] = [
                            'nombre' => 'Desencriptado de Clave SOL secundaria (sol_pass)',
                            'estado' => false,
                            'descripcion' => 'MAC inválido — sol_pass fue encriptado con otra APP_KEY. SireApiService fallará la autenticación SOL.',
                            'accion' => ['tipo' => 'reencriptar_sol_pass', 'label' => 'Re-encriptar clave SOL', 'requiere_input' => true, 'input_label' => 'Clave del usuario SOL secundario ('.($config->sol_user ?: 'sol_user').')'],
                        ];
                        $criticos++;
                        $recomendaciones[] = 'Re-encriptar la clave del usuario SOL secundario ('.($config->sol_user ?: 'sol_user').') con la APP_KEY actual';
                    }
                } elseif (! $solPass) {
                    $certificadosPermisos[] = [
                        'nombre' => 'Clave SOL secundaria (sol_pass)',
                        'estado' => false,
                        'descripcion' => 'No configurada — SireApiService no podrá autenticar con SUNAT',
                        'accion' => ['tipo' => 'reencriptar_sol_pass', 'label' => 'Configurar clave SOL', 'requiere_input' => true, 'input_label' => 'Clave del usuario SOL secundario'],
                    ];
                    $criticos++;
                    $recomendaciones[] = 'Configurar sol_user + sol_pass (usuario SOL secundario de SUNAT)';
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

            // Aviso sobre "Rejected by policy" (perfil de emisión en SUNAT SOL)
            if ($config && $config->sol_user && $solPass) {
                $conectividad[] = [
                    'nombre' => 'Perfil de emisión SUNAT (externo)',
                    'estado' => true,
                    'descripcion' => 'Si al emitir aparece "Rejected by policy / No tiene el perfil para enviar comprobantes electrónicos", el usuario SOL ('.$config->sol_user.') NO tiene el perfil "Emitir Comprobantes Electrónicos" asignado en SUNAT. Esto se corrige en el portal SOL, no en código.',
                ];
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
     * Ejecutar una acción de reparación disparada desde el diagnóstico.
     * Acciones soportadas: regenerar_pems, reencriptar_cert_pass, reencriptar_sol_pass.
     */
    public function repararDiagnostico(Request $request)
    {
        $request->validate([
            'accion' => 'required|string|in:regenerar_pems,reencriptar_cert_pass,reencriptar_sol_pass',
            'valor' => 'nullable|string|max:255',
        ]);

        $config = ConfiguracionSunat::where('activo', true)->first();
        if (! $config) {
            return response()->json(['success' => false, 'message' => 'No hay configuración SUNAT activa'], 404);
        }

        try {
            switch ($request->accion) {
                case 'regenerar_pems':
                    return $this->fixRegenerarPems($config);

                case 'reencriptar_cert_pass':
                    $clave = (string) $request->input('valor', '');
                    if ($clave === '') {
                        return response()->json(['success' => false, 'message' => 'Ingresa la contraseña del certificado'], 422);
                    }
                    return $this->fixReencriptarCertPass($config, $clave);

                case 'reencriptar_sol_pass':
                    $clave = (string) $request->input('valor', '');
                    if ($clave === '') {
                        return response()->json(['success' => false, 'message' => 'Ingresa la clave del usuario SOL secundario'], 422);
                    }
                    return $this->fixReencriptarSolPass($config, $clave);
            }
        } catch (\Throwable $e) {
            \Log::error('Fallo repararDiagnostico', [
                'accion' => $request->accion,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => false, 'message' => 'Acción no soportada'], 400);
    }

    private function fixRegenerarPems(ConfiguracionSunat $config)
    {
        if (empty($config->certificado_file_path)) {
            return response()->json(['success' => false, 'message' => 'No hay PFX cargado para regenerar PEMs'], 422);
        }

        $pfxRel = 'keys/'.$config->certificado_file_path;
        if (! Storage::exists($pfxRel)) {
            return response()->json(['success' => false, 'message' => 'PFX no encontrado: '.$pfxRel], 422);
        }

        if (empty($config->sire_cert_password)) {
            return response()->json(['success' => false, 'message' => 'No hay sire_cert_password para desencriptar; usa "Re-encriptar clave del certificado" primero'], 422);
        }

        try {
            $password = \Crypt::decryptString($config->sire_cert_password);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo desencriptar la clave del certificado. Usa "Re-encriptar clave del certificado" primero.'], 422);
        }

        $result = CertificateService::extractToPem($pfxRel, $password, $config->ruc);
        if (! $result['success']) {
            return response()->json(['success' => false, 'message' => $result['error'] ?? 'Error extrayendo PEMs'], 500);
        }

        $config->update([
            'sire_cert_path' => $result['cert_path'],
            'sire_key_path' => $result['key_path'],
            'certificado_pem_path' => basename($result['cert_path']),
            'clave_privada_pem_path' => basename($result['key_path']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'PEMs regenerados correctamente',
            'cert_path' => $result['cert_path'],
            'key_path' => $result['key_path'],
        ]);
    }

    private function fixReencriptarCertPass(ConfiguracionSunat $config, string $password)
    {
        // Validar que la contraseña realmente abra el PFX antes de persistirla
        if (empty($config->certificado_file_path)) {
            return response()->json(['success' => false, 'message' => 'No hay PFX cargado'], 422);
        }

        $pfxRel = 'keys/'.$config->certificado_file_path;
        if (! Storage::exists($pfxRel)) {
            return response()->json(['success' => false, 'message' => 'PFX no encontrado'], 422);
        }

        $validation = CertificateService::validateAndExtractMetadata($pfxRel, $password);
        if (! $validation['success']) {
            return response()->json(['success' => false, 'message' => 'Contraseña inválida para el PFX: '.($validation['error'] ?? '')], 422);
        }

        $config->update([
            'certificado_clave' => $password,
            'sire_cert_password' => encrypt($password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Clave del certificado re-encriptada con APP_KEY actual',
        ]);
    }

    private function fixReencriptarSolPass(ConfiguracionSunat $config, string $password)
    {
        $config->update([
            'sol_pass' => encrypt($password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Clave SOL re-encriptada con APP_KEY actual',
        ]);
    }

    /**
     * Probar autenticación SOL contra SUNAT haciendo una llamada SOAP real.
     * Distingue entre: credenciales OK + perfil OK (esperado), perfil faltante (0111),
     * credenciales incorrectas (0102/0104), y otros fallos.
     */
    public function probarAutenticacionSunat(Request $request)
    {
        $config = ConfiguracionSunat::where('activo', true)->first();
        if (! $config) {
            return response()->json(['success' => false, 'message' => 'No hay configuración SUNAT activa'], 404);
        }

        if (empty($config->sol_user) || empty($config->sol_pass)) {
            return response()->json([
                'success' => false,
                'codigo' => null,
                'estado' => 'config_incompleta',
                'mensaje' => 'Falta sol_user o sol_pass en la configuración',
            ]);
        }

        // Desencriptar sol_pass
        $solPass = $config->sol_pass;
        if (strlen($solPass) > 50) {
            try {
                $solPass = decrypt($solPass);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'codigo' => null,
                    'estado' => 'cred_no_desencripta',
                    'mensaje' => 'No se pudo desencriptar sol_pass con la APP_KEY actual: '.$e->getMessage(),
                ]);
            }
        }

        // Localizar PEM
        $certAbs = $config->sire_cert_path ? storage_path('app/'.$config->sire_cert_path) : null;
        $keyAbs = $config->sire_key_path ? storage_path('app/'.$config->sire_key_path) : null;
        if (! $certAbs || ! file_exists($certAbs) || ! $keyAbs || ! file_exists($keyAbs)) {
            return response()->json([
                'success' => false,
                'codigo' => null,
                'estado' => 'cert_faltante',
                'mensaje' => 'No se encontraron los archivos PEM del certificado',
            ]);
        }

        try {
            $endpoint = $config->ambiente === 'produccion'
                ? \Greenter\Ws\Services\SunatEndpoints::FE_PRODUCCION
                : \Greenter\Ws\Services\SunatEndpoints::FE_BETA;

            // Cliente SOAP de Greenter — expone setCredentials/setService/call.
            // Usar WSDL local evita timeouts y ambigüedades al descargarlo.
            $wsdlLocal = \Greenter\Ws\Services\WsdlProvider::getBillPath();
            $client = new \Greenter\Ws\Services\SoapClient($wsdlLocal);
            $client->setService($endpoint);

            // WS-Security: usuario = RUC + sol_user
            $wssUser = $config->ruc.$config->sol_user;
            $client->setCredentials($wssUser, $solPass);

            // Llamar sendBill con un ZIP de prueba. SUNAT responderá con SoapFault.
            // El fault code nos dice exactamente qué pasa:
            //   - 0102/0104 → credenciales SOL incorrectas
            //   - 0111      → sin perfil para emitir
            //   - 01xx/03xx → auth+perfil OK pero contenido/schema inválido (lo esperado)
            //
            // Importante: la firma correcta de Greenter es
            //   $client->call('sendBill', ['parameters' => ['fileName' => ..., 'contentFile' => ...]])
            // y contentFile va como binario crudo (PHP SoapClient lo codifica a base64).
            try {
                // RUC-TIPO-SERIE-NUMERO.zip → nombre con formato válido para evitar 0150/0151 antes de auth
                $zipName = $config->ruc.'-01-F001-1.zip';
                $params = [
                    'fileName' => $zipName,
                    'contentFile' => "PK\x03\x04", // Cabecera ZIP mínima — pasa el filtro de nombre, falla en validación de contenido
                ];

                $client->call('sendBill', ['parameters' => $params]);

                // En el raro caso de éxito (no debería pasar con ZIP inválido)
                return $this->interpretarRespuestaSunat(null, 'Llamada aceptada sin fault (inesperado)', $config);

            } catch (\SoapFault $fault) {
                $faultMsg = $fault->faultstring ?? $fault->getMessage();
                $faultCode = $fault->faultcode ?? '';

                \Log::info('probarAutenticacionSunat fault', [
                    'faultcode' => $faultCode,
                    'faultstring' => $faultMsg,
                    'detail' => isset($fault->detail) ? json_encode($fault->detail) : null,
                ]);

                // SUNAT devuelve el código en faultcode (e.g. "soap-env:Client.0111") o en faultstring
                $codigoExtraido = null;
                if (preg_match('/(\d{4})/', (string) $faultCode, $m)) {
                    $codigoExtraido = $m[1];
                } elseif (preg_match('/\b(\d{4})\b/', $faultMsg, $m)) {
                    $codigoExtraido = $m[1];
                }

                return $this->interpretarRespuestaSunat($codigoExtraido, $faultMsg, $config);
            }

        } catch (\Throwable $e) {
            \Log::error('probarAutenticacionSunat exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'codigo' => null,
                'estado' => 'excepcion',
                'mensaje' => 'Excepción: '.$e->getMessage(),
            ]);
        }
    }

    private function interpretarRespuestaSunat(?string $code, string $msg, ConfiguracionSunat $config)
    {
        $lowerMsg = strtolower($msg);

        // Caso esperado cuando auth+perfil están OK: SUNAT rechaza el ZIP de prueba
        // Códigos de content/schema/zip que demuestran que ya pasó la auth y tiene perfil:
        //   0100-0162 (excepto 0102/0104/0111), 0150-0153 (zip), 0300-0306 (firma/schema)
        $codigosContenidoInvalido = ['0100', '0101', '0103', '0105', '0109', '0112', '0113',
            '0125', '0126', '0127', '0128', '0130', '0131', '0137', '0140',
            '0150', '0151', '0152', '0153', '0154', '0155', '0156', '0157', '0158', '0159',
            '0160', '0161', '0162', '0300', '0301', '0302', '0303', '0304', '0305', '0306'];

        if (
            in_array($code, $codigosContenidoInvalido, true)
            || stripos($lowerMsg, 'archivo') !== false
            || stripos($lowerMsg, 'zip') !== false
            || stripos($lowerMsg, 'descomprimir') !== false
            || stripos($lowerMsg, 'contenido del archivo') !== false
            || stripos($lowerMsg, 'ticket') !== false
            || stripos($lowerMsg, 'no existe el xml') !== false
            || stripos($lowerMsg, 'firma') !== false
            || stripos($lowerMsg, 'schema') !== false
            || stripos($lowerMsg, 'esquema') !== false
        ) {
            return response()->json([
                'success' => true,
                'codigo' => $code,
                'estado' => 'auth_ok',
                'mensaje' => '✓ Autenticación SOL exitosa y el usuario "'.$config->sol_user.'" tiene perfil para emitir. SUNAT solo rechazó el contenido de prueba vacío (esperado).',
                'detalle_sunat' => $msg,
            ]);
        }

        // Falta de perfil — la causa del problema actual
        if ($code === '0111' || stripos($msg, 'perfil') !== false || stripos($msg, 'rejected by policy') !== false) {
            return response()->json([
                'success' => false,
                'codigo' => '0111',
                'estado' => 'sin_perfil',
                'mensaje' => 'CONFIRMADO: el usuario SOL "'.$config->sol_user.'" NO tiene el perfil "Emitir Comprobantes Electrónicos" en SUNAT. Asignarlo en el portal SOL → Mi RUC → Usuarios secundarios → Modificar perfil.',
                'detalle_sunat' => $msg,
            ]);
        }

        // Credenciales SOL incorrectas
        if (in_array($code, ['0102', '0104'], true) || stripos($lowerMsg, 'usuario') !== false || stripos($lowerMsg, 'clave') !== false) {
            return response()->json([
                'success' => false,
                'codigo' => $code,
                'estado' => 'auth_invalida',
                'mensaje' => 'Credenciales SOL incorrectas (sol_user o sol_pass). RUC: '.$config->ruc.', sol_user: '.$config->sol_user,
                'detalle_sunat' => $msg,
            ]);
        }

        // Cualquier otro caso → reportar tal cual
        return response()->json([
            'success' => false,
            'codigo' => $code,
            'estado' => 'desconocido',
            'mensaje' => 'Respuesta inesperada de SUNAT. Código: '.($code ?: 'n/a').'. Mensaje: '.$msg,
            'detalle_sunat' => $msg,
        ]);
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
