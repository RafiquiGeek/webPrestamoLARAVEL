<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiConfig;
use App\Models\BilleteraDigital;
use App\Models\Cliente;
use App\Models\Conyuge;
use App\Models\CuentaCliente;
use App\Models\Departamento;
use App\Models\Direccion;
use App\Models\Distrito;
use App\Models\DocumentoCliente;
use App\Models\EntidadBancaria;
use App\Models\Etiqueta;
use App\Models\EtiquetaCliente;
use App\Models\Laboral;
use App\Models\Persona;
use App\Models\Provincia;
use App\Models\Sucursal;
use App\Models\Telefono;
use App\Models\TipoCuenta;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ClientesController extends Controller
{
    public function index(Request $request)
    {
        // Obtener clientes con sus personas
        $clientes = Cliente::with('persona')->get();

        // Obtener personas que NO son clientes
        $personasNoClientes = Persona::whereNotIn('id', function ($query) {
            $query->select('persona_id')->from('clientes');
        })->get();

        $prestamos = null;

        if ($request->has('cliente_id')) {
            $cliente_id = $request->input('cliente_id');
            $prestamos = Prestamo::where('cliente_id', $cliente_id)->get();
        }

        return view('admin.Clientes.index', compact('clientes', 'personasNoClientes', 'prestamos'));
    }

    public function consultarDNI(Request $request)
    {
        $request->validate([
            'nDocumento' => 'required|numeric',
        ]);

        $dni = $request->input('nDocumento');

        // Verificar si ya es cliente (no solo si existe en personas)
        if (verificar_dni($dni) > 0) {
            return response()->json(['valid' => false, 'error' => 'already_registered']);
        }

        $persona = Persona::where('documento', $dni)->first();

        if ($persona) {
            // La persona existe, devolver sus datos
            $data = [
                'nombres' => $persona->nombres,
                'apellido_paterno' => $persona->ape_pat,
                'apellido_materno' => $persona->ape_mat,
                'fecha_nacimiento' => $persona->fecha_nacimiento,
                'persona_existe' => true,
                'persona_id' => $persona->id,
            ];

            return response()->json(['valid' => true, 'data' => $data]);
        }

        try {
            // Obtener configuración de API desde la base de datos
            $url = ApiConfig::getValue('dni_api_url', 'https://api.factiliza.com/v1/dni/info/{dni}');
            $token = ApiConfig::getValue('dni_api_token', 'ece92d9d2a2bb54717373740c3180e722cf7a94b5501ed01a263e21e64a4b6a7');
            $method = ApiConfig::getValue('dni_api_method', 'GET');

            $finalUrl = str_replace('{dni}', $dni, $url);

            $httpClient = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ]);

            if (strtoupper($method) === 'POST') {
                $response = $httpClient->post($finalUrl, ['dni' => $dni]);
            } else {
                $response = $httpClient->get($finalUrl);
            }

            if ($response->successful()) {
                $data = $response->json();

                // Manejar respuesta de API Factiliza
                if (isset($data['success']) && $data['success'] && isset($data['data'])) {
                    // Nueva respuesta de Factiliza con wrapper success
                    $apiData = $data['data'];

                    // Mapear campos para consistencia
                    $mappedData = [
                        'nombres' => $apiData['nombres'] ?? '',
                        'apellido_paterno' => $apiData['apellido_paterno'] ?? '',
                        'apellido_materno' => $apiData['apellido_materno'] ?? '',
                        'fecha_nacimiento' => $apiData['fecha_nacimiento'] ?? '',
                        'direccion' => $apiData['direccion'] ?? '',
                        'direccion_completa' => $apiData['direccion_completa'] ?? '',
                        'departamento' => $apiData['departamento'] ?? '',
                        'provincia' => $apiData['provincia'] ?? '',
                        'distrito' => $apiData['distrito'] ?? '',
                        'ubigeo' => $apiData['ubigeo'] ?? null,
                    ];

                    // Registrar automáticamente la persona en la base de datos
                    try {
                        // Convertir fecha si es necesario
                        $fechaNacimientoFormatted = null;
                        $fechaNacimiento = $mappedData['fecha_nacimiento'];
                        if ($fechaNacimiento) {
                            if (strpos($fechaNacimiento, '/') !== false) {
                                $fechaParts = explode('/', $fechaNacimiento);
                                if (count($fechaParts) === 3) {
                                    $fechaNacimientoFormatted = $fechaParts[2].'-'.str_pad($fechaParts[1], 2, '0', STR_PAD_LEFT).'-'.str_pad($fechaParts[0], 2, '0', STR_PAD_LEFT);
                                }
                            } elseif (strpos($fechaNacimiento, '-') !== false) {
                                $fechaNacimientoFormatted = $fechaNacimiento;
                            }
                        }

                        $persona = Persona::create([
                            'documento' => $dni,
                            'nombres' => $mappedData['nombres'],
                            'ape_pat' => $mappedData['apellido_paterno'],
                            'ape_mat' => $mappedData['apellido_materno'],
                            'fecha_nacimiento' => $fechaNacimientoFormatted,
                        ]);

                        Log::info('Persona registrada automáticamente desde API en consultarDNI', [
                            'dni' => $dni,
                            'nombres' => $mappedData['nombres'],
                            'apellidos' => $mappedData['apellido_paterno'].' '.$mappedData['apellido_materno'],
                        ]);

                        $mappedData['persona_existe'] = false; // Es nueva
                        $mappedData['persona_id'] = $persona->id;

                    } catch (\Exception $e) {
                        Log::error('Error al registrar persona desde API en consultarDNI: '.$e->getMessage());
                        // Continuar sin fallar si no se pudo registrar
                    }

                    return response()->json(['valid' => true, 'data' => $mappedData]);
                } elseif (isset($data['nombres']) && isset($data['apellido_paterno']) && isset($data['apellido_materno'])) {
                    // Respuesta directa de Factiliza (formato anterior)
                    return response()->json(['valid' => true, 'data' => $data]);
                } elseif (isset($data['data']) && is_array($data['data'])) {
                    // Respuesta con data wrapper
                    return response()->json(['valid' => true, 'data' => $data['data']]);
                } else {
                    // Intentar con la respuesta completa
                    return response()->json(['valid' => true, 'data' => $data]);
                }
            } else {
                return response()->json(['valid' => false, 'error' => 'HTTP Error: '.$response->status().' - Response: '.$response->body()], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json(['valid' => false, 'error' => 'Exception: '.$e->getMessage()], 500);
        }
    }

    public function create(Request $request)
    {
        $sucursales = Sucursal::all();
        $departamentos = Departamento::all();
        $entBancarias = EntidadBancaria::all();
        $billeterasDigitales = BilleteraDigital::where('status', 1)->get();
        $tiposCuenta = TipoCuenta::all();
        $etiquetas = Etiqueta::all();
        $zonas = Zona::all();

        // Si viene con persona_id, precargar los datos
        $persona = null;
        if ($request->has('persona_id')) {
            $persona = Persona::find($request->persona_id);
        }

        return view('admin.Clientes.create', compact('sucursales', 'departamentos', 'entBancarias', 'billeterasDigitales', 'tiposCuenta', 'etiquetas', 'zonas', 'persona'));
    }

    public function createEmbedded()
    {
        $sucursales = Sucursal::all();
        $departamentos = Departamento::all();
        $entBancarias = EntidadBancaria::all();
        $tiposCuenta = TipoCuenta::all();

        return view('admin.Clientes.create-embedded', compact('sucursales', 'departamentos', 'entBancarias', 'tiposCuenta'));
    }

    public function store(Request $request)
    {
        \Log::info('=== STORE CLIENT START ===');
        \Log::info('Request data: ', $request->all());

        // Validaciones
        $rules = [
            'nDocumento' => 'required|numeric|digits:8',
            'nombres' => 'required|string|max:255',
            'aPaterno' => 'required|string|max:255',
            'aMaterno' => 'required|string|max:255',
            'fecha_nacimiento' => 'required|date',
            'estado_civil' => 'required|in:Soltero,Casado,Conviviente,Divorciado,Viudo',
            'zona_direccion.*' => 'required|exists:zonas,id',
            'sucursal_direccion.*' => 'required|exists:sucursales,id',
            'tCuenta' => 'required|exists:tipos_cuenta,id',

            // Validaciones para arrays de direcciones
            'departamento.*' => 'required|exists:departamentos,id',
            'provincia.*' => 'required|exists:provincias,id',
            'distrito.*' => 'required|exists:distritos,id',
            'direccion.*' => 'required|string|max:255',
            'referencia.*' => 'nullable|string|max:255',
            'material_inmueble.*' => 'required|string|max:255',
            'cantPisos.*' => 'required|numeric',
            'tipo_residencia.*' => 'required|string|max:255',
            'tiempo_residencia.*' => 'required|numeric',
            'anios_meses.*' => 'required|in:años,meses',

            // Validaciones para datos laborales (opcionales)
            'actividad_economica.*' => 'nullable|string|max:255',
            'nombre_lugar_trabajo.*' => 'nullable|string|max:255',
            'cargo.*' => 'nullable|string|max:255',
            'direccion_trabajo.*' => 'nullable|string|max:255',
        ];

        // Validaciones condicionales para finanzas
        if ($request->tCuenta > 1) { // Si no es "Efectivo"
            // Validaciones para cuentas propias
            if ($request->has('cuentas.propia')) {
                if (isset($request->cuentas['propia']['bancarias'])) {
                    $rules['cuentas.propia.bancarias.*.entidad_id'] = 'required|exists:entidades_bancarias,id';
                    $rules['cuentas.propia.bancarias.*.numero_cuenta'] = 'required|string|max:255';
                    $rules['cuentas.propia.bancarias.*.tipo_cuenta_id'] = 'required|in:2';
                }
                if (isset($request->cuentas['propia']['digitales'])) {
                    $rules['cuentas.propia.digitales.*.billetera_id'] = 'required|exists:billeteras_digitales,id';
                    $rules['cuentas.propia.digitales.*.numero_telefono'] = 'required|string|regex:/^9[0-9]{8}$/';
                    $rules['cuentas.propia.digitales.*.tipo_cuenta_id'] = 'required|in:2';
                }
            }

            // Validaciones para cuentas de terceros
            if ($request->has('cuentas.terceros')) {
                if (isset($request->cuentas['terceros']['bancarias'])) {
                    $rules['cuentas.terceros.bancarias.*.entidad_id'] = 'required|exists:entidades_bancarias,id';
                    $rules['cuentas.terceros.bancarias.*.numero_cuenta'] = 'required|string|max:255';
                    $rules['cuentas.terceros.bancarias.*.titular'] = 'required|string|max:255';
                    $rules['cuentas.terceros.bancarias.*.tipo_cuenta_id'] = 'required|in:3';
                }
                if (isset($request->cuentas['terceros']['digitales'])) {
                    $rules['cuentas.terceros.digitales.*.billetera_id'] = 'required|exists:billeteras_digitales,id';
                    $rules['cuentas.terceros.digitales.*.numero_telefono'] = 'required|string|regex:/^9[0-9]{8}$/';
                    $rules['cuentas.terceros.digitales.*.titular'] = 'required|string|max:255';
                    $rules['cuentas.terceros.digitales.*.tipo_cuenta_id'] = 'required|in:3';
                }
            }
        }

        // Validar teléfonos si existen
        if ($request->has('telefono')) {
            $rules['telefono.*'] = 'required|numeric';
            $rules['tipo.*'] = 'required|in:casa,celular,trabajo,otro';
            $rules['comentario.*'] = 'nullable|string|max:255';
        }

        // Validar archivos si existen (aceptar nombres usados en formularios: files/texts y files_to_upload/descripciones)
        $rules['files.*'] = 'nullable|file|mimes:png,jpg,jpeg,gif,bmp,webp,pdf|max:20480';
        $rules['texts.*'] = 'nullable|string|max:255';
        $rules['files_to_upload.*'] = 'nullable|file|mimes:png,jpg,jpeg,gif,bmp,webp,pdf,doc,docx|max:20480';
        $rules['descripciones.*'] = 'nullable|string|max:255';

        try {
            $request->validate($rules);
            \Log::info('Validation passed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed: ', $e->errors());
            throw $e;
        }

        // Verificar si el DNI ya existe
        if (verificar_dni($request->nDocumento) > 0) {
            return redirect()->route('admin.clientes.index')
                ->with('status', 'Error al crear cliente')
                ->with('error_message', 'El cliente ya ha sido registrado previamente');
        }

        $fileNames = [];

        // Manejo de la foto del cliente
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
            $nombreImagen = time().'_'.$file->getClientOriginalName();

            // Asegurar que el directorio existe
            $directorio = public_path('img/clientes_img');
            if (!file_exists($directorio)) {
                mkdir($directorio, 0755, true);
            }

            // Mover el archivo
            $file->move($directorio, $nombreImagen);
        } else {
            $nombreImagen = null;
        }

        // Manejo de archivos adicionales (compatibilidad con ambos nombres de campo)
        $uploadedFiles = []; // array de ['nombre' => ..., 'descripcion' => ...]

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $index => $file) {
                if ($file && $file->isValid()) {
                    $nombreArchivo = time().'_'.$file->getClientOriginalName();
                    $file->move(public_path('files/client_files'), $nombreArchivo);
                    $descripcion = $request->input('texts.' . $index) ?? ($request->texts[$index] ?? '');
                    $uploadedFiles[] = ['nombre' => $nombreArchivo, 'descripcion' => $descripcion];
                }
            }
        }

        if ($request->hasFile('files_to_upload')) {
            foreach ($request->file('files_to_upload') as $index => $file) {
                if ($file && $file->isValid()) {
                    $nombreArchivo = time().'_'.$file->getClientOriginalName();
                    $file->move(public_path('files/client_files'), $nombreArchivo);
                    $descripcion = $request->input('descripciones.' . $index) ?? ($request->descripciones[$index] ?? '');
                    $uploadedFiles[] = ['nombre' => $nombreArchivo, 'descripcion' => $descripcion];
                }
            }
        }

        try {
            \DB::beginTransaction();

            // Registro o actualización de persona
            $persona = Persona::where('documento', $request->nDocumento)->first();
            if (! $persona) {
                $persona = new Persona;
                $persona->documento = $request->nDocumento;
            }

            // Siempre actualizar los campos de la persona con los datos enviados
            $persona->nombres = $request->nombres;
            $persona->ape_pat = $request->aPaterno;
            $persona->ape_mat = $request->aMaterno;
            // Si se subió una imagen en este request, actualizarla; si no, conservar la existente
            if (! empty($nombreImagen)) {
                $persona->imagen = $nombreImagen;
            }
            $persona->fecha_nacimiento = $request->fecha_nacimiento;
            $persona->estado_civil = $request->estado_civil;
            $persona->save();

            // Dirección
            // Crear direcciones (ahora como arrays)
            if ($request->has('direccion') && is_array($request->direccion)) {
                foreach ($request->direccion as $index => $dir) {
                    $tipoDireccion = $request->tipo_direccion[$index] ?? 'secundario';

                    // Si se marca como principal, cambiar todas las demás a secundario
                    if ($tipoDireccion === 'principal') {
                        Direccion::where('persona_id', $persona->id)
                            ->update(['tipo_direccion' => 'secundario']);
                    }

                    $direccion = new Direccion;
                    $direccion->persona_id = $persona->id;
                    $direccion->distrito_id = $request->distrito[$index];
                    $direccion->sucursal_id = $request->sucursal_direccion[$index] ?? null;
                    $direccion->zona_id = $request->zona_direccion[$index] ?? null;
                    $direccion->direccion = $dir;
                    $direccion->numero = $request->nLotes[$index] ?? '';
                    $direccion->referencia = $request->referencia[$index] ?? '';
                    $direccion->tipo_direccion = $tipoDireccion;
                    $direccion->estado = 1;
                    $direccion->save();
                }
            }

            // Teléfonos (solo si se enviaron)
            if ($request->has('telefono') && is_array($request->telefono)) {
                foreach ($request->telefono as $index => $tel) {
                    $telefono = new Telefono;
                    $telefono->persona_id = $persona->id;
                    $telefono->tipo_telefono = $request->tipo[$index];
                    $telefono->numero = $tel;
                    $telefono->comentario = $request->comentario[$index] ?? '';
                    $telefono->save();
                }
            }

            // Datos del cliente
            $latestCliente = Cliente::latest()->first();
            $latestCode = $latestCliente ? $latestCliente->id + 1 : 1;
            $cliente = new Cliente;
            $cliente->codigo = 'CL-'.str_pad($latestCode, 3, '0', STR_PAD_LEFT);
            $cliente->persona_id = $persona->id;
            // Registrar el usuario que creó el cliente (si hay sesión activa)
            try {
                $cliente->user_id = auth()->id() ?? null;
            } catch (\Exception $e) {
                // Si no hay auth disponible en este contexto, no bloquear la creación
                $cliente->user_id = $cliente->user_id ?? null;
            }
            $cliente->save();

            // Datos de cuentas (múltiples)
            if ($request->tCuenta > 1) { // Si no es "Efectivo"
                $cuentasData = $request->input('cuentas', []);

                // Procesar cuentas propias
                if (isset($cuentasData['propia'])) {
                    // Cuentas bancarias propias
                    if (isset($cuentasData['propia']['bancarias'])) {
                        foreach ($cuentasData['propia']['bancarias'] as $cuentaBancaria) {
                            $cuenta = new CuentaCliente;
                            $cuenta->cliente_id = $cliente->id;
                            $cuenta->tipo_cuenta_id = $cuentaBancaria['tipo_cuenta_id'];
                            $cuenta->entidad_bancaria_id = $cuentaBancaria['entidad_id'];
                            $cuenta->numero_cuenta = $cuentaBancaria['numero_cuenta'];
                            $cuenta->titular_cuenta = null; // Cuenta propia, no hay titular
                            $cuenta->status = 1;
                            $cuenta->save();
                        }
                    }

                    // Billeteras digitales propias
                    if (isset($cuentasData['propia']['digitales'])) {
                        foreach ($cuentasData['propia']['digitales'] as $billetera) {
                            $cuenta = new CuentaCliente;
                            $cuenta->cliente_id = $cliente->id;
                            $cuenta->tipo_cuenta_id = $billetera['tipo_cuenta_id'];
                            $cuenta->billetera_digital_id = $billetera['billetera_id']; // CORREGIDO: usar billetera_digital_id
                            $cuenta->entidad_bancaria_id = null; // Las billeteras NO tienen entidad bancaria
                            $cuenta->numero_cuenta = $billetera['numero_telefono'];
                            $cuenta->titular_cuenta = null;
                            $cuenta->status = 1;
                            $cuenta->save();
                        }
                    }
                }

                // Procesar cuentas de terceros
                if (isset($cuentasData['terceros'])) {
                    // Cuentas bancarias de terceros
                    if (isset($cuentasData['terceros']['bancarias'])) {
                        foreach ($cuentasData['terceros']['bancarias'] as $cuentaBancaria) {
                            $cuenta = new CuentaCliente;
                            $cuenta->cliente_id = $cliente->id;
                            $cuenta->tipo_cuenta_id = $cuentaBancaria['tipo_cuenta_id'];
                            $cuenta->entidad_bancaria_id = $cuentaBancaria['entidad_id'];
                            $cuenta->numero_cuenta = $cuentaBancaria['numero_cuenta'];
                            $cuenta->titular_cuenta = $cuentaBancaria['titular'];
                            $cuenta->status = 1;
                            $cuenta->save();
                        }
                    }

                    // Billeteras digitales de terceros
                    if (isset($cuentasData['terceros']['digitales'])) {
                        foreach ($cuentasData['terceros']['digitales'] as $billetera) {
                            $cuenta = new CuentaCliente;
                            $cuenta->cliente_id = $cliente->id;
                            $cuenta->tipo_cuenta_id = $billetera['tipo_cuenta_id'];
                            $cuenta->billetera_digital_id = $billetera['billetera_id']; // CORREGIDO: usar billetera_digital_id
                            $cuenta->entidad_bancaria_id = null; // Las billeteras NO tienen entidad bancaria
                            $cuenta->numero_cuenta = $billetera['numero_telefono'];
                            $cuenta->titular_cuenta = $billetera['titular'];
                            $cuenta->status = 1;
                            $cuenta->save();
                        }
                    }
                }
            } else { // Si es "Efectivo"
                $cuenta = new CuentaCliente;
                $cuenta->cliente_id = $cliente->id;
                $cuenta->tipo_cuenta_id = $request->tCuenta;
                $cuenta->entidad_bancaria_id = null;
                $cuenta->numero_cuenta = null;
                $cuenta->titular_cuenta = null;
                $cuenta->status = 1;
                $cuenta->save();
            }

            // Archivos: persistir los archivos subidos (soporta ambos formularios)
            if (! empty($uploadedFiles)) {
                foreach ($uploadedFiles as $fileEntry) {
                    $doc = new DocumentoCliente;
                    $doc->tipo_documento = $fileEntry['descripcion'] ?? '';
                    $doc->cliente_id = $cliente->id;
                    $doc->ruta_archivo = $fileEntry['nombre'];
                    $doc->save();
                }
            }

            // DATOS LABORALES: guardar trabajos enviados en el formulario de creación
            if ($request->has('actividad_economica') && is_array($request->actividad_economica)) {
                foreach ($request->actividad_economica as $index => $actEconomica) {
                    if (empty($actEconomica)) {
                        continue;
                    }

                    $laboral = new Laboral;
                    $laboral->cliente_id = $cliente->id;
                    $laboral->actividad_economica = $actEconomica;
                    $laboral->nombre_lugar_trabajo = $request->nombre_lugar_trabajo[$index] ?? '';
                    $laboral->cargo = $request->cargo[$index] ?? '';
                    $laboral->direccion = $request->direccion_trabajo[$index] ?? '';
                    $laboral->status = 1;
                    $laboral->save();
                }
            }

            // CÓNYUGE - Guardar si el estado civil es Casado o Conviviente
            \Log::info('Verificando datos de cónyuge...');
            \Log::info('conyuge_dni: ' . $request->conyuge_dni);
            \Log::info('estado_civil: ' . $request->estado_civil);
            \Log::info('conyuge_nombre: ' . $request->conyuge_nombre);
            \Log::info('conyuge_telefono: ' . $request->conyuge_telefono);

            if (($request->estado_civil === 'Casado' || $request->estado_civil === 'Conviviente') && ($request->conyuge_dni || $request->conyuge_nombre)) {
                \Log::info('Guardando cónyuge...');
                $persona_conyuge = Persona::where('documento', $request->conyuge_dni)->first();
                if (! $persona_conyuge) {
                    $persona_conyuge = new Persona;
                    $persona_conyuge->documento = $request->conyuge_dni;
                }

                $persona_conyuge->nombres = $request->conyuge_nombre;
                $persona_conyuge->ape_pat = $request->conyuge_apellido_pat;
                $persona_conyuge->ape_mat = $request->conyuge_apellido_mat;
                $persona_conyuge->save();

                $conyuge = new Conyuge;
                $conyuge->cliente_id = $cliente->id;
                $conyuge->persona_id = $persona_conyuge->id;
                $conyuge->oficio = $request->conyuge_actividad;
                $conyuge->direccion_trabajo = $request->conyuge_direccion_trabajo;
                $conyuge->referencia_direccion = $request->ref_conyuge_direccion_trabajo;
                $conyuge->save();

                // Teléfono del cónyuge
                if ($request->conyuge_telefono) {
                    $telefono_conyuge = new Telefono;
                    $telefono_conyuge->persona_id = $persona_conyuge->id;
                    $telefono_conyuge->tipo_telefono = 'celular';
                    $telefono_conyuge->numero = $request->conyuge_telefono;
                    $telefono_conyuge->save();
                }

                // Carga familiar
                if ($request->carga_familiar) {
                    $cliente->carga_familiar = $request->carga_familiar;
                    $cliente->save();
                }
            }

            \DB::commit();

            // Si es una petición AJAX (desde formulario embebido), devolver JSON
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cliente creado con éxito',
                    'cliente' => [
                        'id' => $cliente->id,
                        'codigo' => $cliente->codigo,
                        'persona' => [
                            'nombres' => $persona->nombres,
                            'ape_pat' => $persona->ape_pat,
                            'ape_mat' => $persona->ape_mat,
                            'documento' => $persona->documento,
                        ],
                    ],
                ]);
            }

            \Log::info('=== CLIENT CREATED SUCCESSFULLY ===');

            return redirect()->route('admin.clientes.index')
                ->with('status', 'Cliente creado con éxito')
                ->with('show_loan_prompt', true)
                ->with('client_id', $cliente->id)
                ->with('client_name', $persona->nombres.' '.$persona->ape_pat.' '.$persona->ape_mat);
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('=== ERROR CREATING CLIENT ===');
            \Log::error('Error: '.$e->getMessage());
            \Log::error('Line: '.$e->getLine());
            \Log::error('File: '.$e->getFile());

            // Si es una petición AJAX, devolver error JSON
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear cliente: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('admin.clientes.index')
                ->with('status', 'Error al crear cliente')
                ->with('error_message', $e->getMessage());
        }
    }

    public function show(string $id)
    {
        $cliente = Cliente::with([
            'persona',
            'sucursal',
            'prestamos.carterasJcc.user.persona',
            'prestamos.carterasAsesor.user.persona',
            'prestamos.carterasAnalista.user.persona',
        ])->findOrFail($id);

        return view('admin.Clientes.show', compact('cliente'));
    }

    public function edit(string $id)
    {
        $sucursales = Sucursal::all();
        $departamentos = Departamento::all();
        $entBancarias = EntidadBancaria::all();
        $billeterasDigitales = BilleteraDigital::where('status', 1)->get();
        $tiposCuenta = TipoCuenta::all();
        $etiquetas = Etiqueta::all();
        $zonas = Zona::all();
        $cliente = Cliente::with([
            'persona.direcciones.zona',
            'persona.direcciones.sucursal',
            'persona.direcciones.distrito.provincia.departamento',
            'persona.telefonos',
            'conyuge.persona.telefonos',
            'cuentasCliente.entidadBancaria',
            'cuentasCliente.billeteraDigital',
            'laborales',
            'documentosCliente',
            'etiquetasCliente'
        ])->find($id);
        $provincias = [];
        $distritos = [];
        foreach ($cliente->persona->direcciones as $direccion) {
            array_push($distritos, Distrito::where('provincia_id', $direccion->distrito->provincia_id)->get());
            array_push($provincias, Provincia::where('departamento_id', $direccion->distrito->provincia->departamento_id)->get());
        }

        // Obtener datos del cónyuge si existe
        $conyuge = $cliente->conyuge;

        return view('admin.Clientes.edit', compact(
            'cliente',
            'conyuge',
            'sucursales',
            'departamentos',
            'provincias',
            'distritos',
            'entBancarias',
            'billeterasDigitales',
            'tiposCuenta',
            'etiquetas',
            'zonas'
        ));
    }

    public function update(Request $request, Cliente $cliente)
    {
        \Log::debug('Iniciando actualización de cliente: '.$cliente->id);
        \Log::debug('Datos recibidos: ', $request->all());

        // Validaciones básicas
        $rules = [
            'nombres' => 'required|string|max:255',
            'aPaterno' => 'required|string|max:255',
            'aMaterno' => 'required|string|max:255',
            'fecha_nacimiento' => 'required|date',
            'estado_civil' => 'required|in:Soltero,Casado,Conviviente,Divorciado,Viudo',
            'email' => 'nullable|email|max:255',
            'file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20480',
            'zona' => 'nullable|exists:zonas,id',
            'sucursal' => 'nullable|exists:sucursales,id',
            'tCuenta' => 'nullable|exists:tipos_cuenta,id',
        ];

        // Validar arrays de datos
        if ($request->has('telefono') && is_array($request->telefono)) {
            $rules['telefono.*'] = 'nullable|numeric';
            $rules['tipo.*'] = 'nullable|in:casa,celular,trabajo,otro';
        }

        if ($request->has('direccion') && is_array($request->direccion)) {
            $rules['direccion.*'] = 'nullable|string|max:255';
            $rules['distrito.*'] = 'nullable|exists:distritos,id';
        }

        try {
            $request->validate($rules);
            \Log::debug('Validación exitosa');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación: ', $e->errors());

            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        try {
            \DB::beginTransaction();

            // Datos de la persona
            $persona = $cliente->persona;
            if (! $persona) {
                throw new \Exception('No se encontró la persona asociada al cliente.');
            }

            $persona->nombres = $request->nombres;
            $persona->ape_pat = $request->aPaterno;
            $persona->ape_mat = $request->aMaterno;
            $persona->fecha_nacimiento = $request->fecha_nacimiento;
            $persona->email = $request->email;
            $persona->estado_civil = $request->estado_civil;

            // Foto del cliente
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $file = $request->file('file');
                $nombreImagen = time().'_'.$file->getClientOriginalName();

                // Asegurar que el directorio existe
                $directorio = public_path('img/clientes_img');
                if (!file_exists($directorio)) {
                    mkdir($directorio, 0755, true);
                }

                // Mover el archivo
                $file->move($directorio, $nombreImagen);
                $persona->imagen = $nombreImagen;
            }

            $persona->save();

            // Asegurarse de que el cliente tenga asignado un usuario creador si aún no tiene
            if (! $cliente->user_id) {
                try {
                    $cliente->user_id = auth()->id() ?? $cliente->user_id;
                    $cliente->save();
                } catch (\Exception $e) {
                    // No bloquear la actualización si no hay contexto de autorización
                }
            }

            // 1. TELÉFONOS
            \Log::debug('Procesando teléfonos...');
            // Primero, guardar IDs existentes para identificar eliminados después
            $idsTelefonos = $persona->telefonos ? $persona->telefonos->pluck('id')->toArray() : [];
            $idsGuardadosTelefonos = [];

            // Teléfonos: actualizar existentes y crear nuevos
            if ($request->telefono && is_array($request->telefono)) {
                \Log::debug('Teléfonos recibidos: ', $request->telefono);
                foreach ($request->telefono as $index => $tel) {
                    if (empty($tel)) {
                        continue;
                    } // Saltar teléfonos vacíos

                    $telefono = null;

                    if ($request->telefono_id && is_array($request->telefono_id) && isset($request->telefono_id[$index])) {
                        $telefono = Telefono::find($request->telefono_id[$index]);
                        if ($telefono) {
                            $idsGuardadosTelefonos[] = $telefono->id;
                        }
                    }

                    if (! $telefono) {
                        $telefono = new Telefono;
                        $telefono->persona_id = $persona->id;
                    }

                    $telefono->tipo_telefono = isset($request->tipo[$index]) ? $request->tipo[$index] : 'celular';
                    $telefono->numero = $tel;
                    $telefono->comentario = isset($request->comentario[$index]) ? $request->comentario[$index] : '';
                    $telefono->save();

                    if (! in_array($telefono->id, $idsGuardadosTelefonos)) {
                        $idsGuardadosTelefonos[] = $telefono->id;
                    }
                }
            }

            // Eliminar teléfonos que ya no existen en el formulario
            $idsEliminarTelefonos = array_diff($idsTelefonos, $idsGuardadosTelefonos);
            if (! empty($idsEliminarTelefonos)) {
                Telefono::whereIn('id', $idsEliminarTelefonos)->delete();
            }

            // 2. DIRECCIONES
            \Log::debug('Procesando direcciones...');
            // Primero, guardar IDs existentes para identificar eliminados después
            $idsDirecciones = $persona->direcciones ? $persona->direcciones->pluck('id')->toArray() : [];
            $idsGuardadosDirecciones = [];

            // Direcciones: actualizar existentes y crear nuevas
            if ($request->direccion && is_array($request->direccion)) {
                \Log::debug('Direcciones recibidas: ', $request->direccion);
                foreach ($request->direccion as $index => $dir) {
                    if (empty($dir)) {
                        continue;
                    } // Saltar direcciones vacías

                    // Verificar que distrito_id no sea null
                    if (empty($request->distrito[$index])) {
                        throw new \Exception('El campo Distrito es obligatorio para todas las direcciones.');
                    }

                    $objDireccion = null;

                    if ($request->id_direccion && is_array($request->id_direccion) && isset($request->id_direccion[$index])) {
                        $objDireccion = Direccion::find($request->id_direccion[$index]);
                        if ($objDireccion) {
                            $idsGuardadosDirecciones[] = $objDireccion->id;
                        }
                    }

                    if (! $objDireccion) {
                        $objDireccion = new Direccion;
                        $objDireccion->persona_id = $persona->id;
                    }

                    $tipoDireccion = $request->tipo_direccion[$index] ?? 'secundario';

                    // Si se marca como principal, cambiar todas las demás a secundario
                    if ($tipoDireccion === 'principal') {
                        Direccion::where('persona_id', $persona->id)
                            ->where('id', '!=', $objDireccion->id ?? 0)
                            ->update(['tipo_direccion' => 'secundario']);
                    }

                    $objDireccion->distrito_id = $request->distrito[$index];
                    $objDireccion->sucursal_id = $request->sucursal_direccion[$index] ?? null;
                    $objDireccion->zona_id = $request->zona_direccion[$index] ?? null;
                    $objDireccion->direccion = $dir;
                    $objDireccion->numero = $request->nLotes[$index] ?? '';
                    $objDireccion->referencia = $request->referencia[$index] ?? '';
                    $objDireccion->material_inmueble = $request->material_inmueble[$index] ?? '';
                    $objDireccion->cant_pisos = ! empty($request->cantPisos[$index]) ? $request->cantPisos[$index] : null;
                    $objDireccion->tipo_residencia = $request->tipo_residencia[$index] ?? '';
                    $objDireccion->tiempo_residencia = ! empty($request->tiempo_residencia[$index]) ? $request->tiempo_residencia[$index] : null;
                    $objDireccion->anios_meses = $request->anios_meses[$index] ?? '';
                    $objDireccion->nombre_propietario = $request->nombre_propietario[$index] ?? '';
                    $objDireccion->telefono_propietario = $request->telefono_propietario[$index] ?? '';
                    $objDireccion->tipo_direccion = $tipoDireccion;
                    $objDireccion->estado = 1;
                    $objDireccion->save();

                    if (! in_array($objDireccion->id, $idsGuardadosDirecciones)) {
                        $idsGuardadosDirecciones[] = $objDireccion->id;
                    }
                }
            }

            // Eliminar direcciones que ya no existen en el formulario
            $idsEliminarDirecciones = array_diff($idsDirecciones, $idsGuardadosDirecciones);
            if (! empty($idsEliminarDirecciones)) {
                Direccion::whereIn('id', $idsEliminarDirecciones)->delete();
            }

            // 3. CÓNYUGE
            \Log::debug('Verificando datos de cónyuge en update...');
            \Log::debug('conyuge_dni: ' . $request->conyuge_dni);
            \Log::debug('estado_civil: ' . $request->estado_civil);
            \Log::debug('conyuge_nombre: ' . $request->conyuge_nombre);
            \Log::debug('conyuge_telefono: ' . $request->conyuge_telefono);

            if (($request->estado_civil === 'Casado' || $request->estado_civil === 'Conviviente') && ($request->conyuge_dni || $request->conyuge_nombre)) {
                \Log::debug('Actualizando cónyuge...');
                $persona_conyuge = Persona::where('documento', $request->conyuge_dni)->first();
                if (! $persona_conyuge) {
                    $persona_conyuge = new Persona;
                    $persona_conyuge->documento = $request->conyuge_dni;
                }

                $persona_conyuge->nombres = $request->conyuge_nombre;
                $persona_conyuge->ape_pat = $request->conyuge_apellido_pat;
                $persona_conyuge->ape_mat = $request->conyuge_apellido_mat;
                $persona_conyuge->save();

                $conyuge = $cliente->conyuge;
                if (! $conyuge) {
                    $conyuge = new Conyuge;
                    $conyuge->cliente_id = $cliente->id;
                }

                $conyuge->persona_id = $persona_conyuge->id;
                $conyuge->oficio = $request->conyuge_actividad;
                $conyuge->direccion_trabajo = $request->conyuge_direccion_trabajo;
                $conyuge->referencia_direccion = $request->ref_conyuge_direccion_trabajo;
                $conyuge->save();

                // Teléfono del cónyuge
                if ($request->conyuge_telefono) {
                    $telefono_conyuge = null;

                    if ($persona_conyuge->telefonos && $persona_conyuge->telefonos->count() > 0) {
                        $telefono_conyuge = $persona_conyuge->telefonos->first();
                    }

                    if (! $telefono_conyuge) {
                        $telefono_conyuge = new Telefono;
                        $telefono_conyuge->persona_id = $persona_conyuge->id;
                        $telefono_conyuge->tipo_telefono = 'celular';
                    }

                    $telefono_conyuge->numero = $request->conyuge_telefono;
                    $telefono_conyuge->save();
                }

                // Carga familiar
                $cliente->carga_familiar = $request->carga_familiar;
                $cliente->save();
            } else {
                // Solo eliminar cónyuge si el estado civil NO es Casado/Conviviente
                if ($cliente->conyuge && !($request->estado_civil === 'Casado' || $request->estado_civil === 'Conviviente')) {
                    \Log::debug('Eliminando cónyuge porque estado civil cambió a no pareja...');
                    $cliente->conyuge->delete();
                }
            }

            // 4. CUENTAS BANCARIAS Y BILLETERAS DIGITALES
            $idsCuentas = $cliente->cuentasCliente ? $cliente->cuentasCliente->pluck('id')->toArray() : [];
            $idsGuardadosCuentas = [];

            // Manejar el tipo de cuenta principal (radio button)
            if ($request->has('tCuenta') && ! is_array($request->tCuenta)) {
                $tipoCuentaPrincipal = $request->tCuenta;

                // Si es efectivo (tipo 1), crear/actualizar una sola cuenta de efectivo
                if ($tipoCuentaPrincipal == 1) {
                    $cuentaEfectivo = $cliente->cuentasCliente->first();
                    if (! $cuentaEfectivo) {
                        $cuentaEfectivo = new CuentaCliente;
                        $cuentaEfectivo->cliente_id = $cliente->id;
                    }
                    $cuentaEfectivo->tipo_cuenta_id = 1;
                    $cuentaEfectivo->entidad_bancaria_id = null;
                    $cuentaEfectivo->billetera_digital_id = null;
                    $cuentaEfectivo->numero_cuenta = null;
                    $cuentaEfectivo->titular_cuenta = null;
                    $cuentaEfectivo->status = 1;
                    $cuentaEfectivo->save();
                    $idsGuardadosCuentas[] = $cuentaEfectivo->id;
                } else {
                    // Para cuentas bancarias/digitales, mantener las existentes que coincidan con el tipo
                    $cuentasExistentes = $cliente->cuentasCliente->where('tipo_cuenta_id', $tipoCuentaPrincipal);
                    foreach ($cuentasExistentes as $cuentaExistente) {
                        $idsGuardadosCuentas[] = $cuentaExistente->id;
                    }
                }
            }

            // Manejar cuentas individuales si se envían como array (formulario dinámico)
            if ($request->tCuenta && is_array($request->tCuenta)) {
                foreach ($request->tCuenta as $index => $tCta) {
                    $cuenta = null;

                    if ($request->id_cuenta_cliente && is_array($request->id_cuenta_cliente) && isset($request->id_cuenta_cliente[$index])) {
                        $cuenta = CuentaCliente::find($request->id_cuenta_cliente[$index]);
                        if ($cuenta) {
                            $idsGuardadosCuentas[] = $cuenta->id;
                        }
                    }

                    if (! $cuenta) {
                        $cuenta = new CuentaCliente;
                        $cuenta->cliente_id = $cliente->id;
                    }

                    $cuenta->tipo_cuenta_id = $tCta;
                    $cuenta->status = 1;

                    // Para cuentas que no son efectivo (tipo > 1)
                    if ($tCta > 1) {
                        // Verificar si es billetera digital o cuenta bancaria
                        $esBilleteraDigital = $request->has('billetera_digital') &&
                                             is_array($request->billetera_digital) &&
                                             isset($request->billetera_digital[$index]) &&
                                             !empty($request->billetera_digital[$index]);

                        if ($esBilleteraDigital) {
                            // Es una billetera digital
                            $cuenta->billetera_digital_id = $request->billetera_digital[$index];
                            $cuenta->entidad_bancaria_id = null;
                            $cuenta->numero_cuenta = isset($request->f_nCuenta[$index]) ? $request->f_nCuenta[$index] : '';

                            // LOG TEMPORAL PARA DEBUG
                            \Log::info("Guardando billetera digital", [
                                'index' => $index,
                                'billetera_id' => $request->billetera_digital[$index],
                                'numero_cuenta_enviado' => $request->f_nCuenta[$index] ?? 'NO EXISTE',
                                'todos_f_nCuenta' => $request->f_nCuenta,
                                'numero_cuenta_guardado' => $cuenta->numero_cuenta
                            ]);
                        } else {
                            // Es una cuenta bancaria
                            $cuenta->entidad_bancaria_id = isset($request->entidad_financiera[$index]) ? $request->entidad_financiera[$index] : null;
                            $cuenta->billetera_digital_id = null;
                            $cuenta->numero_cuenta = isset($request->f_nCuenta[$index]) ? $request->f_nCuenta[$index] : '';
                        }

                        // Para cuentas de terceros (tipo = 3)
                        if ($tCta == 3) {
                            $cuenta->titular_cuenta = isset($request->ct_Titular[$index]) ? $request->ct_Titular[$index] : '';
                        } else {
                            $cuenta->titular_cuenta = null;
                        }
                    } else {
                        // Para cuentas de efectivo
                        $cuenta->entidad_bancaria_id = null;
                        $cuenta->billetera_digital_id = null;
                        $cuenta->numero_cuenta = null;
                        $cuenta->titular_cuenta = null;
                    }

                    $cuenta->save();

                    if (! in_array($cuenta->id, $idsGuardadosCuentas)) {
                        $idsGuardadosCuentas[] = $cuenta->id;
                    }
                }
            }

            // Eliminar cuentas que ya no existen en el formulario
            $idsEliminarCuentas = array_diff($idsCuentas, $idsGuardadosCuentas);
            if (! empty($idsEliminarCuentas)) {
                CuentaCliente::whereIn('id', $idsEliminarCuentas)->delete();
            }

            // 5. DATOS LABORALES
            $idsLaborales = $cliente->laborales ? $cliente->laborales->pluck('id')->toArray() : [];
            $idsGuardadosLaborales = [];

            if ($request->actividad_economica && is_array($request->actividad_economica)) {
                foreach ($request->actividad_economica as $index => $actEconomica) {
                    if (empty($actEconomica)) {
                        continue;
                    }

                    $laboral = null;

                    if ($request->id_laboral && is_array($request->id_laboral) && isset($request->id_laboral[$index])) {
                        $laboral = Laboral::find($request->id_laboral[$index]);
                        if ($laboral) {
                            $idsGuardadosLaborales[] = $laboral->id;
                        }
                    }

                    if (! $laboral) {
                        $laboral = new Laboral;
                        $laboral->cliente_id = $cliente->id;
                    }

                    $laboral->actividad_economica = $actEconomica;
                    $laboral->nombre_lugar_trabajo = isset($request->nombre_lugar_trabajo[$index]) ? $request->nombre_lugar_trabajo[$index] : '';
                    $laboral->cargo = isset($request->cargo[$index]) ? $request->cargo[$index] : '';
                    $laboral->direccion = isset($request->direccion_trabajo[$index]) ? $request->direccion_trabajo[$index] : '';
                    $laboral->status = 1;
                    $laboral->save();

                    if (! in_array($laboral->id, $idsGuardadosLaborales)) {
                        $idsGuardadosLaborales[] = $laboral->id;
                    }
                }
            }

            // Eliminar laborales que ya no existen en el formulario
            $idsEliminarLaborales = array_diff($idsLaborales, $idsGuardadosLaborales);
            if (! empty($idsEliminarLaborales)) {
                Laboral::whereIn('id', $idsEliminarLaborales)->delete();
            }

            // 6. ETIQUETAS
            $idsEtiquetas = $cliente->etiquetasCliente ? $cliente->etiquetasCliente->pluck('id')->toArray() : [];
            $idsGuardadosEtiquetas = [];

            if ($request->etiqueta && is_array($request->etiqueta)) {
                foreach ($request->etiqueta as $index => $etiq) {
                    if (empty($etiq)) {
                        continue;
                    }

                    $etiqueta = null;

                    if ($request->id_etiqueta && is_array($request->id_etiqueta) && isset($request->id_etiqueta[$index])) {
                        $etiqueta = EtiquetaCliente::find($request->id_etiqueta[$index]);
                        if ($etiqueta) {
                            $idsGuardadosEtiquetas[] = $etiqueta->id;
                        }
                    }

                    if (! $etiqueta) {
                        $etiqueta = new EtiquetaCliente;
                        $etiqueta->cliente_id = $cliente->id;
                    }

                    $etiqueta->etiqueta_id = $etiq;
                    $etiqueta->observacion = isset($request->observacion[$index]) ? $request->observacion[$index] : '';
                    $etiqueta->save();

                    if (! in_array($etiqueta->id, $idsGuardadosEtiquetas)) {
                        $idsGuardadosEtiquetas[] = $etiqueta->id;
                    }
                }
            }

            // Eliminar etiquetas que ya no existen en el formulario
            $idsEliminarEtiquetas = array_diff($idsEtiquetas, $idsGuardadosEtiquetas);
            if (! empty($idsEliminarEtiquetas)) {
                EtiquetaCliente::whereIn('id', $idsEliminarEtiquetas)->delete();
            }

            // 7. ARCHIVOS
            $idsArchivos = $cliente->documentosCliente ? $cliente->documentosCliente->pluck('id')->toArray() : [];
            $idsGuardadosArchivos = [];

            if ($request->uploaded_files && is_array($request->uploaded_files)) {
                foreach ($request->uploaded_files as $archivo_id) {
                    $idsGuardadosArchivos[] = $archivo_id;
                }
            }

            // Eliminar archivos que ya no existen en el formulario
            $idsEliminarArchivos = array_diff($idsArchivos, $idsGuardadosArchivos);
            if (! empty($idsEliminarArchivos)) {
                DocumentoCliente::whereIn('id', $idsEliminarArchivos)->delete();
            }

            // Nuevos archivos
            $fileNames = [];
            if ($request->hasFile('files_to_upload')) {
                foreach ($request->file('files_to_upload') as $index => $file) {
                    if ($file && $file->isValid()) {
                        $nombreArchivo = time().'_'.$file->getClientOriginalName();
                        $file->move(public_path('files/client_files'), $nombreArchivo);

                        if (isset($request->descripciones[$index])) {
                            $doc = new DocumentoCliente;
                            $doc->tipo_documento = $request->descripciones[$index];
                            $doc->cliente_id = $cliente->id;
                            $doc->ruta_archivo = $nombreArchivo;
                            $doc->save();
                        }
                    }
                }
            }

            \DB::commit();
            \Log::debug('Cliente actualizado exitosamente: '.$cliente->id);

            return redirect()->route('admin.clientes.index')->with('status', 'Cliente actualizado con éxito');
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error al actualizar cliente: '.$e->getMessage());
            \Log::error('Archivo: '.$e->getFile());
            \Log::error('Línea: '.$e->getLine());
            \Log::error('Traza de error: '.$e->getTraceAsString());

            // Mensaje de error más específico
            $errorMessage = 'Error al actualizar cliente: '.$e->getMessage();
            if (config('app.debug')) {
                $errorMessage .= ' (Línea: '.$e->getLine().')';
            }

            return redirect()->route('admin.clientes.edit', ['cliente' => $cliente->id])
                ->with('status', 'Error al actualizar cliente')
                ->with('error_message', $errorMessage)
                ->withInput();
        }
    }

    public function consultarDNIParaEdicion(Request $request)
    {
        $request->validate([
            'nDocumento' => 'required|numeric',
        ]);

        $dni = $request->input('nDocumento');

        // NO validar si ya existe - esto es para edición, no creación
        // Consultar directamente la API, no la base de datos local

        try {
            // Obtener configuración de API desde la base de datos
            $url = ApiConfig::getValue('dni_api_url', 'https://api.factiliza.com/v1/dni/info/{dni}');
            $token = ApiConfig::getValue('dni_api_token', 'ece92d9d2a2bb54717373740c3180e722cf7a94b5501ed01a263e21e64a4b6a7');
            $method = ApiConfig::getValue('dni_api_method', 'GET');

            $finalUrl = str_replace('{dni}', $dni, $url);

            $httpClient = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ]);

            if (strtoupper($method) === 'POST') {
                $response = $httpClient->post($finalUrl, ['dni' => $dni]);
            } else {
                $response = $httpClient->get($finalUrl);
            }

            if ($response->successful()) {
                $data = $response->json();

                // Manejar respuesta de API Factiliza
                if (isset($data['success']) && $data['success'] && isset($data['data'])) {
                    // Nueva respuesta de Factiliza con wrapper success
                    $apiData = $data['data'];

                    // Mapear campos para consistencia - SOLO datos básicos sin direcciones adicionales
                    $mappedData = [
                        'nombres' => $apiData['nombres'] ?? '',
                        'apellido_paterno' => $apiData['apellido_paterno'] ?? '',
                        'apellido_materno' => $apiData['apellido_materno'] ?? '',
                        'fecha_nacimiento' => $apiData['fecha_nacimiento'] ?? '',
                        'direccion' => $apiData['direccion'] ?? '',
                        'direccion_completa' => $apiData['direccion_completa'] ?? '',
                        'departamento' => $apiData['departamento'] ?? '',
                        'provincia' => $apiData['provincia'] ?? '',
                        'distrito' => $apiData['distrito'] ?? '',
                        'ubigeo' => $apiData['ubigeo'] ?? null,
                    ];

                    return response()->json(['valid' => true, 'data' => $mappedData]);
                } elseif (isset($data['nombres']) && isset($data['apellido_paterno']) && isset($data['apellido_materno'])) {
                    // Respuesta directa de Factiliza (formato anterior)
                    return response()->json(['valid' => true, 'data' => $data]);
                } elseif (isset($data['data']) && is_array($data['data'])) {
                    // Respuesta con data wrapper
                    return response()->json(['valid' => true, 'data' => $data['data']]);
                } else {
                    // Intentar con la respuesta completa
                    return response()->json(['valid' => true, 'data' => $data]);
                }
            } else {
                return response()->json(['valid' => false, 'error' => 'HTTP Error: '.$response->status().' - Response: '.$response->body()], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json(['valid' => false, 'error' => 'Exception: '.$e->getMessage()], 500);
        }
    }

    public function consultarDNIConyuge(Request $request)
    {
        $request->validate([
            'dni' => 'required|numeric|digits:8',
        ]);

        $dni = $request->input('dni');

        $persona = Persona::where('documento', $dni)->first();

        if ($persona) {
            $data = [
                'nombres' => $persona->nombres,
                'apellido_paterno' => $persona->ape_pat,
                'apellido_materno' => $persona->ape_mat,
            ];

            return response()->json(['valid' => true, 'data' => $data]);
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ece92d9d2a2bb54717373740c3180e722cf7a94b5501ed01a263e21e64a4b6a7',
            ])->post('https://apiperu.dev/api/dni', [
                'dni' => $dni,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['success']) && $data['success']) {
                    return response()->json(['valid' => true, 'data' => $data['data']]);
                }
            }

            return response()->json(['valid' => false, 'error' => 'DNI no encontrado']);
        } catch (\Exception $e) {
            return response()->json(['valid' => false, 'error' => $e->getMessage()]);
        }
    }

    public function provincias(Departamento $departamento)
    {
        \Log::debug('Consultando provincias para departamento: '.$departamento->id);
        $provincias = $departamento->provincias;
        \Log::debug('Provincias encontradas: '.$provincias->count());

        return response()->json($provincias);
    }

    public function getDatosSucursal($id)
    {
        $sucursal = Sucursal::with(['provincia.departamento', 'distrito'])->find($id);

        return response()->json($sucursal);
    }

    public function destroy(string $id)
    {
        //
    }

    public function getDirecciones($clienteId)
    {
        $cliente = Cliente::findOrFail($clienteId);  // Buscar el cliente
        $persona = $cliente->persona;                // Obtener la persona asociada
        $direcciones = $persona->direcciones;        // Obtener las direcciones de la persona

        // Retornar las direcciones en formato JSON para el AJAX
        return response()->json([
            'direcciones' => $direcciones,
        ]);
    }

    public function getCuentasCliente($clienteId)
    {
        $cuentasCliente = CuentaCliente::with(['entidadBancaria', 'billeteraDigital', 'tipoCuenta'])
            ->where('cliente_id', $clienteId)
            ->get();

        return response()->json(['cuentas' => $cuentasCliente]);
    }

    /**
     * Muestra la vista de importación de clientes
     */
    public function mostrarImportar()
    {
        return view('admin.Clientes.importar');
    }

    /**
     * Descargar plantilla Excel para importación
     */
    public function descargarPlantilla()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = [
            'nDocumento' => 'DNI (8 dígitos)',
            'nombres' => 'Nombres completos',
            'aPaterno' => 'Apellido paterno',
            'aMaterno' => 'Apellido materno',
            'telefono' => 'Teléfono',
            'tipo' => 'Tipo (celular/fijo)',
            'zona' => 'ID Zona',
            'sucursal' => 'ID Sucursal',
            'tCuenta' => 'ID Tipo Cuenta',
        ];

        $col = 'A';
        foreach ($headers as $header => $description) {
            $sheet->setCellValue($col.'1', $header);
            $sheet->setCellValue($col.'2', $description);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Estilo para headers
        $sheet->getStyle('A1:I2')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:I1')->getFont()->getColor()->setRGB('FFFFFF');

        // Ejemplo de datos (usar diferentes zonas y sucursales)
        $ejemplos = [
            ['12345678', 'JUAN CARLOS', 'PEREZ', 'GARCIA', '987654321', 'celular', '1', '1', '1'],
            ['87654321', 'MARIA ELENA', 'RODRIGUEZ', 'LOPEZ', '987654322', 'celular', '2', '3', '1'],
            ['11223344', 'PEDRO LUIS', 'MARTINEZ', 'TORRES', '987654323', 'celular', '3', '2', '2'],
        ];

        $row = 4; // Empezar en la fila 4
        foreach ($ejemplos as $ejemplo) {
            $col = 'A';
            foreach ($ejemplo as $valor) {
                $sheet->setCellValue($col.$row, $valor);
                $col++;
            }
            $row++;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        $fileName = 'plantilla_importar_clientes_'.date('Y-m-d').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'plantilla');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Procesar importación de clientes desde Excel
     */
    public function procesarImportacion(Request $request)
    {
        set_time_limit(0); // Sin límite de tiempo
        ini_set('memory_limit', '512M'); // Aumentar memoria

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB
            'validar_dni' => 'nullable|in:0,1,true,false',
        ]);

        \Log::info('=== IMPORTACIÓN EXCEL INICIADA ===');
        \Log::info('Archivo:', ['name' => $request->file('excel_file')->getClientOriginalName()]);
        \Log::info('Validar DNI value:', [$request->input('validar_dni'), gettype($request->input('validar_dni'))]);

        $validarDni = in_array($request->input('validar_dni'), ['1', 'true', true, 1], true);

        \Log::info('Final boolean values:', ['validarDni' => $validarDni]);

        $exitosos = 0;
        $errores = 0;
        $detalles_errores = [];

        try {
            $archivo = $request->file('excel_file');
            $spreadsheet = IOFactory::load($archivo->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Remover header
            array_shift($rows);
            if (! empty($rows) && (empty($rows[0][0]) || $rows[0][0] === 'DNI (8 dígitos)')) {
                array_shift($rows); // Remover descripción también
            }

            \Log::info('Filas a procesar: '.count($rows));

            foreach ($rows as $index => $row) {
                $fila = $index + 3; // +3 porque removemos headers y empezamos desde 1

                try {
                    // Saltear filas vacías
                    if (empty($row[0]) && empty($row[1])) {
                        continue;
                    }

                    // Validar campos requeridos
                    $datos_requeridos = [
                        'nDocumento' => trim($row[0] ?? ''),
                        'nombres' => trim($row[1] ?? ''),
                        'aPaterno' => trim($row[2] ?? ''),
                        'aMaterno' => trim($row[3] ?? ''),
                        'telefono' => trim($row[4] ?? ''),
                        'tipo' => trim($row[5] ?? 'celular'),
                        'zona' => trim($row[6] ?? ''),
                        'sucursal' => trim($row[7] ?? ''),
                        'tCuenta' => trim($row[8] ?? '1'),
                    ];

                    // Validar datos requeridos
                    foreach (['nDocumento', 'nombres', 'aPaterno', 'aMaterno', 'telefono', 'zona', 'sucursal'] as $campo) {
                        if (empty($datos_requeridos[$campo])) {
                            throw new \Exception("Campo requerido faltante: $campo");
                        }
                    }

                    // Validar DNI
                    if (! preg_match('/^\d{8}$/', $datos_requeridos['nDocumento'])) {
                        throw new \Exception('DNI debe tener exactamente 8 dígitos');
                    }

                    // Verificar si el DNI ya existe
                    if (verificar_dni($datos_requeridos['nDocumento']) > 0) {
                        throw new \Exception('El DNI ya está registrado en el sistema');
                    }

                    // Construir array de datos como lo hace el formulario normal
                    $datosCliente = [
                        'nDocumento' => $datos_requeridos['nDocumento'],
                        'nombres' => $datos_requeridos['nombres'],
                        'aPaterno' => $datos_requeridos['aPaterno'],
                        'aMaterno' => $datos_requeridos['aMaterno'],
                        'estado_civil' => 'Soltero',
                        'email' => null,
                        'telefono' => [$datos_requeridos['telefono']],
                        'tipo' => [$datos_requeridos['tipo']],
                        'comentario' => [null],
                        'zona' => $datos_requeridos['zona'],
                        'sucursal' => $datos_requeridos['sucursal'],
                        'tCuenta' => $datos_requeridos['tCuenta'],
                    ];

                    // Obtener datos del DNI si está habilitado
                    if ($validarDni) {
                        $datosDni = $this->consultarDniParaImportacion($datos_requeridos['nDocumento']);
                        if ($datosDni) {
                            $datosCliente['fecha_nacimiento'] = $datosDni['fecha_nacimiento'];

                            // Datos de dirección
                            if (! empty($datosDni['direccion'])) {
                                $datosCliente['departamento'] = [$datosDni['departamento_id'] ?? 6];
                                $datosCliente['provincia'] = [$datosDni['provincia_id'] ?? 54];
                                $datosCliente['distrito'] = [$datosDni['distrito_id'] ?? 551];
                                $datosCliente['direccion'] = [$datosDni['direccion']];
                                $datosCliente['nLotes'] = [null];
                                $datosCliente['referencia'] = ['Dirección obtenida automáticamente del DNI'];
                                $datosCliente['material_inmueble'] = ['material_noble'];
                                $datosCliente['cantPisos'] = ['1'];
                                $datosCliente['tipo_residencia'] = ['Propia'];
                                $datosCliente['tiempo_residencia'] = ['1'];
                                $datosCliente['anios_meses'] = ['meses'];
                                $datosCliente['nombre_propietario'] = [null];
                                $datosCliente['telefono_propietario'] = [null];
                            }
                        }
                    }

                    // Valores por defecto si no se obtuvo del DNI
                    if (! isset($datosCliente['fecha_nacimiento'])) {
                        $datosCliente['fecha_nacimiento'] = '1990-01-01';
                    }
                    if (! isset($datosCliente['direccion'])) {
                        // Intentar obtener distrito de la zona/sucursal especificada en el Excel
                        $distritoId = $this->obtenerDistritoDeSucursal($datos_requeridos['sucursal']);

                        // Dirección por defecto usando datos de la zona/sucursal del Excel
                        $datosCliente['departamento'] = [$this->obtenerDepartamentoDeDistrito($distritoId)];
                        $datosCliente['provincia'] = [$this->obtenerProvinciaDeDistrito($distritoId)];
                        $datosCliente['distrito'] = [$distritoId];
                        $datosCliente['direccion'] = ['DIRECCIÓN NO DISPONIBLE'];
                        $datosCliente['nLotes'] = [null];
                        $datosCliente['referencia'] = [null];
                        $datosCliente['material_inmueble'] = ['material_noble'];
                        $datosCliente['cantPisos'] = ['1'];
                        $datosCliente['tipo_residencia'] = ['Propia'];
                        $datosCliente['tiempo_residencia'] = ['1'];
                        $datosCliente['anios_meses'] = ['meses'];
                        $datosCliente['nombre_propietario'] = [null];
                        $datosCliente['telefono_propietario'] = [null];
                    }

                    // Crear el cliente usando la misma lógica del store
                    $this->crearClienteDesdeImportacion($datosCliente);

                    $exitosos++;

                } catch (\Exception $e) {
                    $errores++;
                    $detalles_errores[] = [
                        'fila' => $fila,
                        'dni' => $datos_requeridos['nDocumento'] ?? 'N/A',
                        'mensaje' => $e->getMessage(),
                    ];
                    \Log::error("Error en fila $fila: ".$e->getMessage());
                }
            }

            \Log::info("Importación completada. Exitosos: $exitosos, Errores: $errores");

            return response()->json([
                'success' => true,
                'exitosos' => $exitosos,
                'errores' => $errores,
                'detalles_errores' => $detalles_errores,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error general en importación: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Consultar DNI para importación (versión simplificada)
     */
    private function consultarDniParaImportacion($dni)
    {
        try {
            $apiConfig = ApiConfig::where('nombre', 'api_peru')->first();
            if (! $apiConfig || ! $apiConfig->activo) {
                return null;
            }

            $url = $apiConfig->url.'?documento='.$dni;
            $response = Http::timeout(10)->withHeaders([
                'Authorization' => 'Bearer '.$apiConfig->token,
                'Content-Type' => 'application/json',
            ])->get($url);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                    'direccion' => $data['direccion'] ?? $data['direccion_completa'] ?? null,
                    'departamento_id' => 6, // Por defecto Cajamarca
                    'provincia_id' => 54,   // Por defecto Cajamarca
                    'distrito_id' => 551,    // Por defecto Cajamarca
                ];
            }
        } catch (\Exception $e) {
            \Log::warning("Error consultando DNI $dni: ".$e->getMessage());
        }

        return null;
    }

    /**
     * Crear cliente desde importación (versión simplificada del store)
     */
    private function crearClienteDesdeImportacion($datos)
    {
        \DB::beginTransaction();

        try {
            // Crear persona
            $persona = new Persona;
            $persona->nombres = $datos['nombres'];
            $persona->ape_pat = $datos['aPaterno'];
            $persona->ape_mat = $datos['aMaterno'];
            $persona->documento = $datos['nDocumento'];
            $persona->fecha_nacimiento = $datos['fecha_nacimiento'];
            $persona->estado_civil = $datos['estado_civil'];
            $persona->email = $datos['email'];
            $persona->save();

            // Crear dirección si existe
            if (isset($datos['direccion'])) {
                $direccion = new Direccion;
                $direccion->persona_id = $persona->id;
                $direccion->distrito_id = $datos['distrito'][0];
                $direccion->sucursal_id = $datos['sucursal'];
                $direccion->zona_id = $datos['zona'];
                $direccion->direccion = $datos['direccion'][0];
                $direccion->numero = $datos['nLotes'][0] ?? '';
                $direccion->referencia = $datos['referencia'][0] ?? '';
                $direccion->estado = 1;
                $direccion->save();
            }

            // Crear teléfono
            if (isset($datos['telefono'][0])) {
                $telefono = new Telefono;
                $telefono->persona_id = $persona->id;
                $telefono->tipo_telefono = $datos['tipo'][0];
                $telefono->numero = $datos['telefono'][0];
                $telefono->comentario = $datos['comentario'][0] ?? '';
                $telefono->save();
            }

            // Crear cliente
            $latestCliente = Cliente::latest()->first();
            $latestCode = $latestCliente ? $latestCliente->id + 1 : 1;
            $cliente = new Cliente;
            $cliente->codigo = 'CL-'.str_pad($latestCode, 3, '0', STR_PAD_LEFT);
            $cliente->persona_id = $persona->id;
            $cliente->save();

            // Documento cliente - omitido en importación por Excel

            \DB::commit();

            return $cliente;

        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener distrito_id de una sucursal
     */
    private function obtenerDistritoDeSucursal($sucursalId)
    {
        try {
            $sucursal = \App\Models\Sucursal::find($sucursalId);
            if ($sucursal && $sucursal->distrito_id) {
                return $sucursal->distrito_id;
            }

            // Fallback a distrito por defecto (Cajamarca)
            return 551;
        } catch (\Exception $e) {
            \Log::warning("Error obteniendo distrito de sucursal {$sucursalId}: ".$e->getMessage());

            return 551; // Fallback
        }
    }

    /**
     * Obtener departamento_id de un distrito
     */
    private function obtenerDepartamentoDeDistrito($distritoId)
    {
        try {
            $distrito = \App\Models\Distrito::find($distritoId);
            if ($distrito && $distrito->provincia && $distrito->provincia->departamento_id) {
                return $distrito->provincia->departamento_id;
            }

            // Fallback a departamento por defecto (Cajamarca)
            return 6;
        } catch (\Exception $e) {
            \Log::warning("Error obteniendo departamento de distrito {$distritoId}: ".$e->getMessage());

            return 6; // Fallback
        }
    }

    /**
     * Obtener provincia_id de un distrito
     */
    private function obtenerProvinciaDeDistrito($distritoId)
    {
        try {
            $distrito = \App\Models\Distrito::find($distritoId);
            if ($distrito && $distrito->provincia_id) {
                return $distrito->provincia_id;
            }

            // Fallback a provincia por defecto (Cajamarca)
            return 54;
        } catch (\Exception $e) {
            \Log::warning("Error obteniendo provincia de distrito {$distritoId}: ".$e->getMessage());

            return 54; // Fallback
        }
    }

    /**
     * Obtener etiquetas del cliente
     */
    public function obtenerEtiquetas(Cliente $cliente)
    {
        try {
            $etiquetas = $cliente->etiquetasCliente()
                ->with('etiqueta')
                ->get();

            return response()->json([
                'success' => true,
                'etiquetas' => $etiquetas,
                'count' => $etiquetas->count(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener etiquetas del cliente '.$cliente->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudieron cargar las etiquetas del cliente.',
                'etiquetas' => [],
                'count' => 0,
            ], 500);
        }
    }

    /**
     * Buscar clientes para Select2 (usado en vincular préstamos)
     */
    public function buscar(Request $request)
    {
        \Log::info('🔍 MÉTODO BUSCAR LLAMADO');
        try {
            $term = $request->get('q');
            $page = $request->get('page', 1);
            $perPage = 30;

            \Log::info('Búsqueda de clientes - Término: '.$term.', Página: '.$page);
            \Log::info('Es numérico: '.(is_numeric($term) ? 'Sí' : 'No'));

            $query = Cliente::with('persona')
                ->whereHas('persona', function ($q) use ($term) {
                    if (is_numeric($term)) {
                        // Si es numérico, buscar por documento (DNI)
                        $q->where('documento', 'like', '%'.$term.'%');
                    } else {
                        // Si no es numérico, buscar por nombres (campos correctos de Persona)
                        $q->where('nombres', 'like', '%'.$term.'%')
                            ->orWhere('ape_pat', 'like', '%'.$term.'%')
                            ->orWhere('ape_mat', 'like', '%'.$term.'%');
                    }
                });

            $total = $query->count();
            $clientes = $query->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            $items = $clientes->map(function ($cliente) {
                return [
                    'id' => $cliente->id,
                    'dni' => $cliente->persona->documento ?? '',
                    'nombres' => $cliente->persona->nombres ?? '',
                    'apellido_paterno' => $cliente->persona->ape_pat ?? '',
                    'apellido_materno' => $cliente->persona->ape_mat ?? '',
                    'text' => ($cliente->persona->documento ?? '').' - '.
                             ($cliente->persona->nombres ?? '').' '.
                             ($cliente->persona->ape_pat ?? '').' '.
                             ($cliente->persona->ape_mat ?? ''),
                ];
            });

            \Log::info('Clientes encontrados: '.$total.', Enviando: '.$items->count());

            // Log sample items for debugging
            if ($items->count() > 0) {
                \Log::info('Primer elemento: '.json_encode($items->first()));
            }

            return response()->json([
                'items' => $items,
                'total_count' => $total,
                'incomplete_results' => ($page * $perPage) < $total,
                'debug' => [
                    'term_received' => $term,
                    'is_numeric' => is_numeric($term),
                    'query_count' => $total,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en búsqueda de clientes: '.$e->getMessage());

            return response()->json([
                'items' => [],
                'total_count' => 0,
                'incomplete_results' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Buscar personas para Select2 (para vincular préstamos)
     */
    public function buscarPersonas(Request $request)
    {
        try {
            $search = $request->get('q');
            \Log::info('🔍 Buscando personas con término: '.$search);

            if (! $search || strlen($search) < 2) {
                return response()->json([
                    'results' => [],
                ]);
            }

            $personas = Persona::where(function ($query) use ($search) {
                $query->where('documento', 'LIKE', '%'.$search.'%')
                    ->orWhere('nombres', 'LIKE', '%'.$search.'%')
                    ->orWhere('ape_pat', 'LIKE', '%'.$search.'%')
                    ->orWhere('ape_mat', 'LIKE', '%'.$search.'%');
            })
                ->limit(20)
                ->get();

            $results = $personas->map(function ($persona) {
                return [
                    'id' => $persona->id,
                    'text' => $persona->documento.' - '.
                             ($persona->nombres ?? '').' '.
                             ($persona->ape_pat ?? '').' '.
                             ($persona->ape_mat ?? ''),
                    'persona' => [
                        'id' => $persona->id,
                        'dni' => $persona->documento,
                        'nombres' => $persona->nombres,
                        'apellido_paterno' => $persona->ape_pat,
                        'apellido_materno' => $persona->ape_mat,
                        'nombre_completo' => ($persona->nombres ?? '').' '.
                                           ($persona->ape_pat ?? '').' '.
                                           ($persona->ape_mat ?? ''),
                    ],
                ];
            });

            return response()->json([
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en búsqueda de personas: '.$e->getMessage());

            return response()->json([
                'results' => [],
            ]);
        }
    }

    /**
     * Buscar clientes válidos para préstamos
     */
    public function buscarClientesParaPrestamo(Request $request)
    {
        try {
            $search = $request->get('q');
            \Log::info('🔍 Buscando clientes para préstamo con término: '.$search);
            \Log::info('📋 Parámetros de request: '.json_encode($request->all()));

            if (! $search || strlen($search) < 2) {
                return response()->json([
                    'results' => [],
                ]);
            }

            // Buscar clientes que no tengan préstamos vigentes o en proceso
            $estadosActivos = ['En Análisis', 'Aprobado', 'Por Desembolsar', 'Vigente', 'Moroso', 'Nueva Solicitud', 'Desembolsado'];

            // Si el search es numérico y tiene más de 3 dígitos, buscar también por ID
            $clientes = Cliente::with('persona')
                ->where(function ($query) use ($search) {
                    $query->whereHas('persona', function ($subQuery) use ($search) {
                        $subQuery->where('documento', 'LIKE', '%'.$search.'%')
                            ->orWhere('nombres', 'LIKE', '%'.$search.'%')
                            ->orWhere('ape_pat', 'LIKE', '%'.$search.'%')
                            ->orWhere('ape_mat', 'LIKE', '%'.$search.'%');
                    });

                    // Si es numérico, también buscar por ID del cliente
                    if (is_numeric($search)) {
                        $query->orWhere('id', $search);
                    }
                })
                // FILTRO ESPECIAL: Para Asesor, Analista y JCC
                // Solo pueden buscar: 1) Clientes de su cartera, 2) Clientes NUEVOS sin préstamos
                ->when($this->debeAplicarFiltroCartera(), function ($query) use ($estadosActivos) {
                    $userId = auth()->id();

                    $query->where(function ($subQuery) use ($userId, $estadosActivos) {
                        // Opción 1: Clientes que están en la cartera del usuario (incluye préstamos Liquidados, Cancelados, Con Convenio)
                        // Si tiene múltiples roles, buscar en TODAS sus carteras (OR)
                        $subQuery->whereHas('prestamos', function ($prestamoQuery) use ($userId) {
                            $prestamoQuery->where(function ($carteraOr) use ($userId) {
                                if (auth()->user()->hasRole('Asesor')) {
                                    $carteraOr->orWhereHas('carterasAsesor', function ($carteraQuery) use ($userId) {
                                        $carteraQuery->where('asesor_id', $userId);
                                    });
                                }
                                if (auth()->user()->hasRole('Analista')) {
                                    $carteraOr->orWhereHas('carterasAnalista', function ($carteraQuery) use ($userId) {
                                        $carteraQuery->where('analista_id', $userId);
                                    });
                                }
                                if (auth()->user()->hasRole('JCC')) {
                                    $carteraOr->orWhereHas('carterasJcc', function ($carteraQuery) use ($userId) {
                                        $carteraQuery->where('jcc_id', $userId);
                                    });
                                }
                            });
                        })
                        // Opción 2: Clientes NUEVOS (sin préstamos previos)
                        ->orWhereDoesntHave('prestamos');
                    });
                })
                // Para todos: No mostrar clientes con préstamos activos
                ->whereDoesntHave('prestamos', function ($query) use ($estadosActivos) {
                    $query->whereIn('estado', $estadosActivos);
                })
                ->limit(20)
                ->get();

            $results = $clientes->map(function ($cliente) {
                $persona = $cliente->persona;

                return [
                    'id' => $cliente->id,
                    'nombre' => trim(($persona->nombres ?? '').' '.
                               ($persona->ape_pat ?? '').' '.
                               ($persona->ape_mat ?? '')),
                    'dni' => $persona->documento ?? '',
                    'text' => ($persona->documento ?? '').' - '.
                             trim(($persona->nombres ?? '').' '.
                                  ($persona->ape_pat ?? '').' '.
                                  ($persona->ape_mat ?? '')),
                ];
            });

            return response()->json([
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en búsqueda de clientes para préstamo: '.$e->getMessage());

            return response()->json([
                'results' => [],
            ]);
        }
    }

    /**
     * Búsqueda de clientes para autocompletado AJAX
     */
    public function buscarClientesAutocompletado(Request $request)
    {
        $termino = $request->get('q', '');

        if (strlen($termino) < 2) {
            return response()->json([]);
        }

        $clientes = Cliente::select('clientes.id', 'personas.nombres', 'personas.ape_pat', 'personas.ape_mat', 'personas.documento')
            ->join('personas', 'clientes.persona_id', '=', 'personas.id')
            ->where(function ($query) use ($termino) {
                $query->where('personas.nombres', 'like', '%'.$termino.'%')
                    ->orWhere('personas.ape_pat', 'like', '%'.$termino.'%')
                    ->orWhere('personas.ape_mat', 'like', '%'.$termino.'%')
                    ->orWhere('personas.documento', 'like', '%'.$termino.'%')
                    ->orWhereRaw("CONCAT(personas.nombres, ' ', personas.ape_pat, ' ', personas.ape_mat) LIKE ?", ['%'.$termino.'%']);
            })
            ->limit(10)
            ->get()
            ->map(function ($cliente) {
                return [
                    'id' => $cliente->id,
                    'nombre_completo' => $cliente->nombres.' '.$cliente->ape_pat.' '.$cliente->ape_mat,
                    'documento' => $cliente->documento,
                    'label' => $cliente->nombres.' '.$cliente->ape_pat.' '.$cliente->ape_mat.' - DNI: '.$cliente->documento,
                ];
            });

        return response()->json($clientes);
    }

    /**
     * Determina si se debe aplicar filtro de cartera según el rol del usuario
     * Solo se aplica a: Asesor, Analista y JCC
     * Roles sin restricción: Admin, Oficina, GS, etc.
     */
    private function debeAplicarFiltroCartera(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $rolesRestringidos = ['Asesor', 'Analista', 'JCC'];

        foreach ($rolesRestringidos as $rol) {
            if (auth()->user()->hasRole($rol)) {
                return true;
            }
        }

        return false;
    }
}
