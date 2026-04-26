<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiConfigController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $configs = ApiConfig::orderBy('name')->get();

        return view('admin.Api.index', compact('configs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.Api.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|unique:api_configs,key',
            'name' => 'required|string|max:255',
            'value' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        ApiConfig::create([
            'key' => $request->key,
            'name' => $request->name,
            'value' => $request->value,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.api-config.index')
            ->with('success', 'Configuración de API creada exitosamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $config = ApiConfig::findOrFail($id);

        return view('admin.Api.edit', compact('config'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $config = ApiConfig::findOrFail($id);

        $request->validate([
            'key' => 'required|string|unique:api_configs,key,'.$id,
            'name' => 'required|string|max:255',
            'value' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $config->update([
            'key' => $request->key,
            'name' => $request->name,
            'value' => $request->value,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.api-config.index')
            ->with('success', 'Configuración de API actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $config = ApiConfig::findOrFail($id);
        $config->delete();

        return redirect()->route('admin.api-config.index')
            ->with('success', 'Configuración de API eliminada exitosamente.');
    }

    /**
     * Inicializar configuraciones por defecto
     */
    public function initializeDefaults()
    {
        $defaults = [
            [
                'key' => 'dni_api_url',
                'name' => 'URL API DNI',
                'value' => 'https://api.factiliza.com/v1/dni/info/{dni}',
                'description' => 'URL base para consultas de DNI. Use {dni} como placeholder para el número de DNI.',
            ],
            [
                'key' => 'dni_api_token',
                'name' => 'Token API DNI',
                'value' => 'ece92d9d2a2bb54717373740c3180e722cf7a94b5501ed01a263e21e64a4b6a7',
                'description' => 'Token de autorización para la API de DNI',
            ],
            [
                'key' => 'dni_api_method',
                'name' => 'Método HTTP API DNI',
                'value' => 'GET',
                'description' => 'Método HTTP para las consultas (GET o POST)',
            ],
        ];

        foreach ($defaults as $default) {
            ApiConfig::setValue(
                $default['key'],
                $default['value'],
                $default['name'],
                $default['description']
            );
        }

        return redirect()->route('admin.api-config.index')
            ->with('success', 'Configuraciones por defecto inicializadas exitosamente.');
    }

    /**
     * Probar la configuración actual de la API DNI
     */
    public function testDniApi(Request $request)
    {
        $request->validate([
            'dni' => 'required|string|size:8',
        ]);

        $dni = $request->dni;
        $url = ApiConfig::getValue('dni_api_url', 'https://api.factiliza.com/v1/dni/info/{dni}');
        $token = ApiConfig::getValue('dni_api_token');
        $method = ApiConfig::getValue('dni_api_method', 'GET');

        if (! $token) {
            return response()->json(['success' => false, 'message' => 'Token de API no configurado']);
        }

        try {
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

                // Verificar si la respuesta contiene los datos esperados de DNI
                $isValidDniData = isset($data['nombres']) && isset($data['apellido_paterno']) && isset($data['apellido_materno']);

                return response()->json([
                    'success' => true,
                    'message' => $isValidDniData ? 'API funcionando correctamente - DNI encontrado' : 'API respondió pero formato inesperado',
                    'data' => $data,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error HTTP: '.$response->status(),
                    'error' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ]);
        }
    }
}
