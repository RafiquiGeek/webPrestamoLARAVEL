<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    public function index()
    {
        $brandConfig = $this->getBrandConfig();

        return view('admin.brand.index', compact('brandConfig'));
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'site_name' => 'required|string|max:255',
            'logo' => 'nullable|file|mimes:svg,png,jpg,jpeg|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Guardar el nombre del sitio en configuración
        $configPath = storage_path('app/brand_config.json');
        $config = [];

        if (file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath), true) ?? [];
        }

        $config['site_name'] = $request->site_name;

        // Manejar subida de logo si existe
        if ($request->hasFile('logo')) {
            // Eliminar logo anterior si existe
            if (isset($config['logo_path']) && Storage::disk('public')->exists($config['logo_path'])) {
                Storage::disk('public')->delete($config['logo_path']);
            }

            // Subir nuevo logo
            $logoPath = $request->file('logo')->store('brand', 'public');
            $config['logo_path'] = $logoPath;
        }

        // Guardar configuración
        file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT));

        return redirect()->back()->with('success', 'Configuración de marca actualizada correctamente.');
    }

    public function getBrandConfig()
    {
        $configPath = storage_path('app/brand_config.json');

        if (file_exists($configPath)) {
            return json_decode(file_get_contents($configPath), true);
        }

        return [
            'site_name' => config('adminlte.title', 'Banking'),
            'logo_path' => null,
        ];
    }
}
