<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginWithAccessCodeRequest;
use App\Models\AccessCode;
use App\Models\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CustomAuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function submitCredentials(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string',
        ]);

        // Verificar credenciales
        if (! Auth::attempt($request->only('email', 'password'), false)) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales proporcionadas son incorrectas.',
            ]);
        }

        // Cerrar la sesión temporal
        Auth::logout();

        // Obtener datos del usuario
        $user = \App\Models\User::where('email', $request->email)->first();

        // Crear solicitud de código de acceso (estado PENDING)
        $loginRequest = LoginRequest::createRequest(
            $request->email,
            $user->name,
            $request->header('User-Agent'),
            $request->ip()
        );

        // El código ya se crea como 'approved' automáticamente

        return response()->json([
            'success' => true,
            'show_code_step' => true,
            'message' => 'Credenciales verificadas. Tu código es: '.$loginRequest->access_code,
            'access_code' => $loginRequest->access_code,
            'request_id' => $loginRequest->id,
        ]);
    }

    public function store(LoginWithAccessCodeRequest $request)
    {
        $request->ensureIsNotRateLimited();

        // Verificar si es un código estático (códigos de emergencia) primero
        $staticCode = AccessCode::where('code', strtoupper($request->access_code))
            ->active()
            ->first();

        if ($staticCode) {
            // Usar el flujo original para códigos estáticos
            return $this->handleStaticCodeLogin($request, $staticCode);
        }

        // Si no es código estático, verificar solicitudes dinámicas
        $loginRequest = LoginRequest::where('access_code', strtoupper($request->access_code))
            ->where('email', $request->email)
            ->whereIn('status', ['pending', 'approved'])
            ->where('expires_at', '>', now())
            ->first();

        if (! $loginRequest) {
            throw ValidationException::withMessages([
                'access_code' => 'Código de acceso inválido, expirado o no autorizado para este usuario.',
            ]);
        }

        // Intentar autenticar con email y password
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Verificar que el usuario esté realmente autenticado
        if (! Auth::check()) {
            throw ValidationException::withMessages([
                'email' => 'Error en la autenticación. Intenta de nuevo.',
            ]);
        }

        // Marcar la solicitud como usada
        $loginRequest->markAsUsed();

        // Limpiar el rate limiter
        $request->clearRateLimiter();

        // Log successful login for debugging
        \Log::info('Usuario autenticado exitosamente', [
            'user_id' => Auth::id(),
            'email' => Auth::user()->email,
            'redirecting_to' => route('admin.index'),
        ]);

        // Regenerar sesión por seguridad DESPUÉS del login
        $request->session()->regenerate();

        return redirect()->to(route('admin.index'));
    }

    private function handleStaticCodeLogin($request, $staticCode)
    {
        // Intentar autenticar con email y password
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Verificar que el usuario esté realmente autenticado
        if (! Auth::check()) {
            throw ValidationException::withMessages([
                'email' => 'Error en la autenticación. Intenta de nuevo.',
            ]);
        }

        // Obtener el usuario autenticado
        $user = Auth::user();

        // Verificar si el código permite el rol del usuario
        $userRole = $user->roles()->first()?->name;

        if ($userRole && ! $staticCode->canBeUsedByRole($userRole)) {
            Auth::logout();
            throw ValidationException::withMessages([
                'access_code' => 'Este código no está permitido para su rol de usuario.',
            ]);
        }

        // Incrementar uso del código estático
        $staticCode->incrementUsage();

        // Limpiar el rate limiter
        $request->clearRateLimiter();

        // Regenerar sesión por seguridad DESPUÉS del login
        $request->session()->regenerate();

        return redirect()->to(route('admin.index'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
