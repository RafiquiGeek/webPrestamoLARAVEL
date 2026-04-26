<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use App\Models\ConfiguracionSunat;
use App\Services\CertificateService;

class AdminConfiguracionSunatController extends Controller
{
    public function edit()
    {
        $config = ConfiguracionSunat::obtenerActiva() ?? new ConfiguracionSunat();
        $certificateInfo = null;

        // Intenta obtener info del certificado si está configurado
        if (!empty($config->sire_cert_path)) {
            $result = CertificateService::getCertificateMetadata();
            if ($result['success']) {
                $certificateInfo = $result;
            }
        }

        return view('admin.ConfiguracionSunat.sire-config', compact('config', 'certificateInfo'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'sire_api_url' => 'nullable|url',
            'sire_api_token' => 'nullable|string',
            'usar_sire' => 'nullable|boolean',
            'cert_p12' => 'nullable|file|mimes:p12,pfx',
            'cert_password' => 'nullable|string',
        ]);

        $config = ConfiguracionSunat::obtenerActiva() ?? new ConfiguracionSunat();
        $certificateInfo = null;
        $error = null;

        if ($request->hasFile('cert_p12')) {
            $file = $request->file('cert_p12');
            $password = $request->input('cert_password');

            // Validar el .p12 antes de guardarlo
            $tempPath = $file->store('sire_certs_temp');
            $validation = CertificateService::validateAndExtractMetadata($tempPath, $password);

            if (!$validation['success']) {
                // Eliminar archivo temporal si validación falla
                Storage::delete($tempPath);
                $error = $validation['error'];
            } else {
                // Si es válido, extraer a formato PEM para compatibilidad con OpenSSL 3.x
                $pemExtraction = CertificateService::extractToPem($tempPath, $password);

                Storage::delete($tempPath);

                if ($pemExtraction['success']) {
                    // Guardar rutas de los archivos PEM extraídos
                    $config->sire_cert_path = $pemExtraction['cert_path'];
                    $config->sire_key_path = $pemExtraction['key_path'];
                    $certificateInfo = $validation;

                    \Log::info('Certificado extraído a formato PEM', [
                        'cert_path' => $pemExtraction['cert_path'],
                        'key_path' => $pemExtraction['key_path']
                    ]);
                } else {
                    // Si falla la extracción, guardar el .pfx original
                    $path = $file->store('sire_certs');
                    $config->sire_cert_path = $path;
                    $certificateInfo = $validation;

                    \Log::warning('No se pudo extraer certificado a PEM, usando .pfx original');
                }
            }
        }

        if ($request->filled('cert_password')) {
            // Encriptar la contraseña antes de guardar
            $config->sire_cert_password = Crypt::encryptString($request->input('cert_password'));
        }

        $config->sire_api_url = $request->input('sire_api_url');
        $config->sire_api_token = $request->input('sire_api_token');
        $config->usar_sire = (bool) $request->input('usar_sire', false);

        $config->save();

        if ($error) {
            return redirect()->back()->with('error', 'Error en certificado: ' . $error);
        }

        return redirect()->back()->with('status', 'Configuración SIRE actualizada.');
    }
}
