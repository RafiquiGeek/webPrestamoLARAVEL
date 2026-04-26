<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiConfig;
use App\Models\CategoriaGasto;
use App\Models\Gasto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GastosController extends Controller
{
    public function index(Request $request)
    {
        $query = Gasto::with(['categoria', 'usuario'])
            ->orderBy('fecha_gasto', 'desc')
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('categoria_id')) {
            $query->where('categoria_gasto_id', $request->categoria_id);
        }

        if ($request->filled('fecha_inicio')) {
            $query->where('fecha_gasto', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->where('fecha_gasto', '<=', $request->fecha_fin);
        }

        if ($request->filled('tipo_documento')) {
            $query->where('tipo_documento', $request->tipo_documento);
        }

        if ($request->filled('documento_identidad')) {
            $query->where('documento_identidad', 'like', '%'.$request->documento_identidad.'%');
        }

        if ($request->filled('beneficiario')) {
            $query->where(function ($q) use ($request) {
                $q->where('razon_social', 'like', '%'.$request->beneficiario.'%')
                    ->orWhere('nombres', 'like', '%'.$request->beneficiario.'%')
                    ->orWhere('apellidos', 'like', '%'.$request->beneficiario.'%');
            });
        }

        $gastos = $query->paginate(15)->appends($request->query());
        $categorias = CategoriaGasto::activas()->orderBy('nombre')->get();

        // Calcular totales
        $totalGeneral = $query->sum('monto');
        $totalPagina = $gastos->sum('monto');

        return view('admin.Gastos.index', compact('gastos', 'categorias', 'totalGeneral', 'totalPagina'));
    }

    public function create()
    {
        \Log::debug('GastosController@create: Iniciando método create');

        try {
            $categorias = CategoriaGasto::activas()->orderBy('nombre')->get();
            \Log::debug('Categorías encontradas: '.$categorias->count());

            return view('admin.Gastos.create', compact('categorias'));
        } catch (\Exception $e) {
            \Log::error('Error en GastosController@create: '.$e->getMessage());

            return redirect()->route('admin.gastos.index')
                ->with('error', 'Error al cargar el formulario de gastos: '.$e->getMessage());
        }
    }

    public function store(Request $request)
    {
        \Log::debug('GastosController@store: Iniciando método store');
        \Log::debug('Datos recibidos: '.print_r($request->all(), true));

        $rules = [
            'categoria_gasto_id' => 'required|exists:categorias_gastos,id',
            'concepto' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'monto' => 'required|numeric|min:0.01|max:999999.99',
            'fecha_gasto' => 'required|date|before_or_equal:today',
            'documento_identidad' => 'required|string|max:20',
            'tipo_documento' => 'required|in:DNI,RUC,CE,PAS',
            'tipo_comprobante' => 'required|in:factura,boleta,recibo_honorarios,ticket,sin_documento',
            'observaciones' => 'nullable|string',
        ];

        // Validaciones condicionales según tipo de documento
        if ($request->tipo_documento === 'RUC') {
            $rules['razon_social'] = 'required|string|max:200';
        } else {
            $rules['nombres'] = 'required|string|max:100';
            $rules['apellidos'] = 'required|string|max:100';
        }

        // Validaciones para comprobantes
        if ($request->tipo_comprobante !== 'sin_documento') {
            if (in_array($request->tipo_comprobante, ['factura', 'boleta'])) {
                $rules['serie_comprobante'] = 'nullable|string|max:10';
            }
            $rules['numero_comprobante'] = 'required|string|max:20';
        }

        try {
            $request->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Error de validación en store: '.print_r($e->errors(), true));

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }

        try {
            $gasto = new Gasto;
            $gasto->categoria_gasto_id = $request->categoria_gasto_id;
            $gasto->concepto = $request->concepto;
            $gasto->descripcion = $request->descripcion;
            $gasto->monto = $request->monto;
            $gasto->fecha_gasto = $request->fecha_gasto;
            $gasto->documento_identidad = $request->documento_identidad;
            $gasto->tipo_documento = $request->tipo_documento;
            $gasto->tipo_comprobante = $request->tipo_comprobante;
            $gasto->observaciones = $request->observaciones;
            $gasto->usuario_registro = Auth::id();

            // Datos del beneficiario según tipo de documento
            if ($request->tipo_documento === 'RUC') {
                $gasto->razon_social = $request->razon_social;
            } else {
                $gasto->nombres = $request->nombres;
                $gasto->apellidos = $request->apellidos;
            }

            // Datos del comprobante
            if ($request->tipo_comprobante !== 'sin_documento') {
                $gasto->serie_comprobante = $request->serie_comprobante;
                $gasto->numero_comprobante = $request->numero_comprobante;
            }

            $gasto->save();

            return redirect()->route('admin.gastos.index')
                ->with('success', 'Gasto registrado correctamente');

        } catch (\Exception $e) {
            \Log::error('Error al registrar gasto: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al registrar el gasto: '.$e->getMessage());
        }
    }

    public function show(Gasto $gasto)
    {
        $gasto->load(['categoria', 'usuario']);

        return view('admin.Gastos.show', compact('gasto'));
    }

    public function edit(Gasto $gasto)
    {
        $categorias = CategoriaGasto::activas()->orderBy('nombre')->get();

        return view('admin.Gastos.edit', compact('gasto', 'categorias'));
    }

    public function update(Request $request, Gasto $gasto)
    {
        $rules = [
            'categoria_gasto_id' => 'required|exists:categorias_gastos,id',
            'concepto' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'monto' => 'required|numeric|min:0.01|max:999999.99',
            'fecha_gasto' => 'required|date|before_or_equal:today',
            'documento_identidad' => 'required|string|max:20',
            'tipo_documento' => 'required|in:DNI,RUC,CE,PAS',
            'tipo_comprobante' => 'required|in:factura,boleta,recibo_honorarios,ticket,sin_documento',
            'observaciones' => 'nullable|string',
        ];

        // Validaciones condicionales según tipo de documento
        if ($request->tipo_documento === 'RUC') {
            $rules['razon_social'] = 'required|string|max:200';
        } else {
            $rules['nombres'] = 'required|string|max:100';
            $rules['apellidos'] = 'required|string|max:100';
        }

        // Validaciones para comprobantes
        if ($request->tipo_comprobante !== 'sin_documento') {
            if (in_array($request->tipo_comprobante, ['factura', 'boleta'])) {
                $rules['serie_comprobante'] = 'nullable|string|max:10';
            }
            $rules['numero_comprobante'] = 'required|string|max:20';
        }

        $request->validate($rules);

        try {
            $gasto->categoria_gasto_id = $request->categoria_gasto_id;
            $gasto->concepto = $request->concepto;
            $gasto->descripcion = $request->descripcion;
            $gasto->monto = $request->monto;
            $gasto->fecha_gasto = $request->fecha_gasto;
            $gasto->documento_identidad = $request->documento_identidad;
            $gasto->tipo_documento = $request->tipo_documento;
            $gasto->tipo_comprobante = $request->tipo_comprobante;
            $gasto->observaciones = $request->observaciones;

            // Limpiar campos según tipo de documento
            if ($request->tipo_documento === 'RUC') {
                $gasto->razon_social = $request->razon_social;
                $gasto->nombres = null;
                $gasto->apellidos = null;
            } else {
                $gasto->nombres = $request->nombres;
                $gasto->apellidos = $request->apellidos;
                $gasto->razon_social = null;
            }

            // Datos del comprobante
            if ($request->tipo_comprobante !== 'sin_documento') {
                $gasto->serie_comprobante = $request->serie_comprobante;
                $gasto->numero_comprobante = $request->numero_comprobante;
            } else {
                $gasto->serie_comprobante = null;
                $gasto->numero_comprobante = null;
            }

            $gasto->save();

            return redirect()->route('admin.gastos.index')
                ->with('success', 'Gasto actualizado correctamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar el gasto: '.$e->getMessage());
        }
    }

    public function destroy(Gasto $gasto)
    {
        try {
            $gasto->delete();

            return redirect()->route('admin.gastos.index')
                ->with('success', 'Gasto eliminado correctamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.gastos.index')
                ->with('error', 'Error al eliminar el gasto: '.$e->getMessage());
        }
    }

    // Método para consultar RUC/DNI via AJAX
    public function consultarDocumento(Request $request)
    {
        $request->validate([
            'documento' => 'required|string',
            'tipo' => 'required|in:DNI,RUC',
        ]);

        try {
            if ($request->tipo === 'DNI') {
                return $this->consultarDNI($request->documento);
            } else {
                return $this->consultarRUC($request->documento);
            }
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'error' => 'Error al consultar el documento',
            ], 500);
        }
    }

    private function consultarDNI($dni)
    {
        try {
            // Obtener configuración de API desde la base de datos
            $url = ApiConfig::getValue('dni_api_url', 'https://api.factiliza.com/v1/dni/info/{dni}');
            $token = ApiConfig::getValue('dni_api_token', 'ece92d9d2a2bb54717373740c3180e772cf7a94b5501ed01a263e21e64a4b6a7');
            $method = ApiConfig::getValue('dni_api_method', 'GET');

            $finalUrl = str_replace('{dni}', $dni, $url);

            $httpClient = \Illuminate\Support\Facades\Http::withHeaders([
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
                if (isset($data['nombres']) && isset($data['apellido_paterno']) && isset($data['apellido_materno'])) {
                    // Respuesta directa de Factiliza
                    return response()->json([
                        'valid' => true,
                        'data' => [
                            'nombres' => $data['nombres'],
                            'apellidos' => $data['apellido_paterno'].' '.$data['apellido_materno'],
                        ],
                    ]);
                } elseif (isset($data['success']) && $data['success'] && isset($data['data'])) {
                    // Respuesta con wrapper success
                    return response()->json([
                        'valid' => true,
                        'data' => [
                            'nombres' => $data['data']['nombres'],
                            'apellidos' => $data['data']['apellido_paterno'].' '.$data['data']['apellido_materno'],
                        ],
                    ]);
                }
            }

            return response()->json(['valid' => false, 'error' => 'DNI no encontrado']);
        } catch (\Exception $e) {
            return response()->json(['valid' => false, 'error' => 'Error al consultar DNI']);
        }
    }

    private function consultarRUC($ruc)
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ece92d9d2a2bb54717373740c3180e772cf7a94b5501ed01a263e21e64a4b6a7',
            ])->post('https://apiperu.dev/api/ruc', [
                'ruc' => $ruc,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['success']) && $data['success']) {
                    return response()->json([
                        'valid' => true,
                        'data' => [
                            'razon_social' => $data['data']['nombre_o_razon_social'],
                        ],
                    ]);
                }
            }

            return response()->json(['valid' => false, 'error' => 'RUC no encontrado']);
        } catch (\Exception $e) {
            return response()->json(['valid' => false, 'error' => 'Error al consultar RUC']);
        }
    }
}
