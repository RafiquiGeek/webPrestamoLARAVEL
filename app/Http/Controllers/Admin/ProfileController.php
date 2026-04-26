<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Telefono;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        $user = Auth::user()->load([
            'persona.telefonos' => function ($query) {
                $query->where('tipo_telefono', 'celular');
            },
            'roles',
            'sucursales',
        ]);

        return view('admin.profile.edit', compact('user'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'nombres' => 'required|string|max:255',
            'ape_pat' => 'required|string|max:255',
            'ape_mat' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'telefono' => 'required|string|max:15',
        ]);

        try {
            DB::beginTransaction();

            // Update persona data
            if ($user->persona) {
                $user->persona->update([
                    'nombres' => $request->nombres,
                    'ape_pat' => $request->ape_pat,
                    'ape_mat' => $request->ape_mat,
                    'email' => $request->email,
                ]);

                // Update or create phone
                $telefono = Telefono::where('persona_id', $user->persona->id)
                    ->where('tipo_telefono', 'celular')
                    ->first();

                if ($telefono) {
                    $telefono->update(['numero' => $request->telefono]);
                } else {
                    Telefono::create([
                        'persona_id' => $user->persona->id,
                        'tipo_telefono' => 'celular',
                        'numero' => $request->telefono,
                    ]);
                }
            }

            // Update user data
            $user->update([
                'email' => $request->email,
                'name' => $request->nombres,
            ]);

            DB::commit();

            return redirect()->route('admin.profile.edit')
                ->with('success', 'Perfil actualizado correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating profile: '.$e->getMessage());

            return back()->withInput()
                ->with('error', 'Error al actualizar el perfil: '.$e->getMessage());
        }
    }
}
