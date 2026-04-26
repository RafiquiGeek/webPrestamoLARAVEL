<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccessCodeController extends Controller
{
    public function index()
    {
        $codes = AccessCode::with('creator')->latest()->get();

        return view('admin.asistencia.codigos', compact('codes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:access_codes,code',
            'description' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date|after:now',
            'max_usage' => 'nullable|integer|min:1',
            'allowed_roles' => 'nullable|array',
            'allowed_roles.*' => 'string|in:Admin,Supervisor,JCC,Asesor,Analista',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        $data['code'] = strtoupper(trim($data['code']));
        $data['created_by'] = Auth::id();
        $data['is_active'] = $request->has('is_active');

        if ($request->expires_at) {
            $data['expires_at'] = Carbon::parse($request->expires_at);
        }

        AccessCode::create($data);

        return response()->json(['message' => 'Código de acceso creado exitosamente']);
    }

    public function show(AccessCode $code)
    {
        return response()->json($code);
    }

    public function update(Request $request, AccessCode $code)
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:access_codes,code,'.$code->id,
            'description' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date|after:now',
            'max_usage' => 'nullable|integer|min:1',
            'allowed_roles' => 'nullable|array',
            'allowed_roles.*' => 'string|in:Admin,Supervisor,JCC,Asesor,Analista',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $request->has('is_active');

        if ($request->expires_at) {
            $data['expires_at'] = Carbon::parse($request->expires_at);
        } else {
            $data['expires_at'] = null;
        }

        $code->update($data);

        return response()->json(['message' => 'Código de acceso actualizado exitosamente']);
    }

    public function destroy(AccessCode $code)
    {
        $code->delete();

        return response()->json(['message' => 'Código de acceso eliminado exitosamente']);
    }

    public function toggle(Request $request, AccessCode $code)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $code->update(['is_active' => $request->is_active]);

        $status = $request->is_active ? 'activado' : 'desactivado';

        return response()->json(['message' => "Código {$status} exitosamente"]);
    }

    public static function validateAccessCode($codeValue, $userRole = null)
    {
        $code = AccessCode::where('code', strtoupper($codeValue))
            ->active()
            ->first();

        if (! $code) {
            return [
                'valid' => false,
                'message' => 'Código de acceso inválido o expirado',
            ];
        }

        if ($userRole && ! $code->canBeUsedByRole($userRole)) {
            return [
                'valid' => false,
                'message' => 'Este código no está permitido para su rol',
            ];
        }

        $code->incrementUsage();

        return [
            'valid' => true,
            'message' => 'Código de acceso válido',
            'code' => $code,
        ];
    }
}
