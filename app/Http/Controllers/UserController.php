<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        // Devuelve la lista de usuarios
        // $users = User::all();
        // $users = User::where('status', 1)->get();
        $users = User::whereIn('status', [1, 2])->get();
        return Inertia::render('User', [
            'users' => $users,
            'loggedInUser' => auth()->user(), // Enviar el usuario logeado
        ]);
    }

    public function store(Request $request)
    {
        // Valida los datos recibidos
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'status' => 'required|int|in:1,2,3', // Permitir 1 (activo), 2 (inactivo), 3 (eliminado)
        ]);

        // Crea el usuario
        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
        ]);

        return redirect()->back()->with('success', 'Usuario creado correctamente');
    }

    public function update(Request $request, User $user)
    {
        // Validar los datos recibidos
        $validated = $request->validate([
            'name' => $request->status != 3 ? 'required|string|max:255' : 'nullable',
            'email' => $request->status != 3 ? 'required|email|unique:users,email,' . $user->id : 'nullable',
            'role' => $request->status != 3 ? 'required|string|max:255' : 'nullable',
            'status' => 'required|int|in:1,2,3',
            'password' => 'nullable|string|min:6', // Contraseña opcional en la edición
        ]);
    
        // Manejar soft delete
        if ($request->status == 3) {
            $user->update(['status' => 3]); // Cambiar el estado a eliminado
            return back()->with('success', 'Usuario marcado como eliminado.');
        }
    
        // Manejar actualización normal
        // Construir los datos de actualización
        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'status' => $validated['status'], // Actualizar el estado si es necesario
        ];

        // Solo agregar la contraseña si está presente
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        // Actualizar el usuario
        $user->update($updateData);
    
        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        // Elimina el usuario
        $user->delete();

        return redirect()->back()->with('success', 'Usuario eliminado correctamente');
    }
}
