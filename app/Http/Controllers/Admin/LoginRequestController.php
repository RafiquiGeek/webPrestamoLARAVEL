<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginRequestController extends Controller
{
    public function solicitudes()
    {
        $pendingRequests = LoginRequest::with('user')
            ->pending()
            ->latest()
            ->get();

        $recentRequests = LoginRequest::with(['user', 'approvedBy'])
            ->whereDate('created_at', Carbon::today())
            ->whereNotIn('status', ['pending'])
            ->latest()
            ->take(50)
            ->get();

        return view('admin.asistencia.solicitudes-acceso', compact('pendingRequests', 'recentRequests'));
    }

    public function accesos(Request $request)
    {
        $query = LoginRequest::with(['user', 'approvedBy']);

        // Filtros
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        if ($request->filled('usuario')) {
            $query->where('email', 'like', '%'.$request->usuario.'%');
        }

        if ($request->filled('estado')) {
            $query->where('status', $request->estado);
        }

        $accesos = $query->latest()->paginate(50);

        return view('admin.asistencia.accesos', compact('accesos'));
    }

    public function approve(Request $request, LoginRequest $loginRequest)
    {
        if ($loginRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Esta solicitud ya fue procesada.',
            ]);
        }

        $loginRequest->approve(Auth::id(), $request->admin_notes);

        return response()->json([
            'success' => true,
            'message' => 'Solicitud aprobada. El usuario puede usar el código: '.$loginRequest->access_code,
        ]);
    }

    public function deny(Request $request, LoginRequest $loginRequest)
    {
        if ($loginRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Esta solicitud ya fue procesada.',
            ]);
        }

        $loginRequest->deny(Auth::id(), $request->admin_notes);

        return response()->json([
            'success' => true,
            'message' => 'Solicitud denegada.',
        ]);
    }

    public function getStats()
    {
        $today = Carbon::today();

        return response()->json([
            'pending' => LoginRequest::pending()->count(),
            'today_approved' => LoginRequest::whereDate('created_at', $today)->where('status', 'approved')->count(),
            'today_denied' => LoginRequest::whereDate('created_at', $today)->where('status', 'denied')->count(),
            'today_used' => LoginRequest::whereDate('created_at', $today)->where('status', 'used')->count(),
        ]);
    }
}
