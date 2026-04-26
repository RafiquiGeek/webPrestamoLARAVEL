<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Show the form for changing password.
     */
    public function edit()
    {
        return view('admin.password.change');
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $user = Auth::user();

        // Verify current password
        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withInput()
                ->withErrors(['current_password' => 'La contraseña actual es incorrecta']);
        }

        try {
            // Update password
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            Log::info("Password changed for user: {$user->email}");

            return redirect()->route('admin.password.change')
                ->with('success', 'Contraseña actualizada correctamente');

        } catch (\Exception $e) {
            Log::error('Error changing password: '.$e->getMessage());

            return back()->withInput()
                ->with('error', 'Error al cambiar la contraseña');
        }
    }
}
