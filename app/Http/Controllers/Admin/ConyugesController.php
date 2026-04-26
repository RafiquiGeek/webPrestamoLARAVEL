<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiConfig;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ConyugesController extends Controller
{
    public function consultarDNI(Request $request)
    {
        $request->validate([
            'dni' => 'required|numeric',
        ]);

        $dni = $request->dni;

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
                if (isset($data['nombres']) && isset($data['apellido_paterno']) && isset($data['apellido_materno'])) {
                    // Respuesta directa de Factiliza
                    return response()->json(['valid' => true, 'data' => $data]);
                } elseif (isset($data['success']) && $data['success'] && isset($data['data'])) {
                    // Respuesta con wrapper success
                    return response()->json(['valid' => true, 'data' => $data['data']]);
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
}
