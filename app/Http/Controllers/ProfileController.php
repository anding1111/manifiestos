<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ProfileController extends Controller
{
    /**
     * Actualizar el perfil del usuario logeado.
     */
    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user->update($validated);

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    /**
     * Cambiar la contraseña del usuario logeado.
     */
    public function changePassword(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:6',
        ]);


        // Verificar que la contraseña actual sea correcta
        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        // Actualizar la contraseña
        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }
}
