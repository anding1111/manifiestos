<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        // Validar credenciales
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Intentar iniciar sesión
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Verificar si el usuario está activo (status = 1)
            if ($user->status !== 1) {
                Auth::logout(); // Cierra sesión si el usuario no está activo
    
                // Configurar mensaje flash para cuenta inactiva o eliminada
                return back()->with('flash', [
                    'message' => 'Tu cuenta está inactiva o ha sido eliminada.',
                    'status' => 'error',
                ])->withInput();
            }
            
            // Regenerar sesión para proteger contra fijación de sesión
            $request->session()->regenerate();

            // Establece un mensaje flash para el inicio de sesión exitoso
            session()->flash('flash', [
                'message' => '¡Inicio de sesión exitoso!',
                'status' => 'success',
            ]);

            // Retorna a la página de inicio de sesión con Inertia (para que los mensajes flash funcionen)
            return inertia('Auth/Login');
        }
        // Configurar mensaje flash para error
        return back()->with('flash', [
            'message' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
            'status' => 'error',
        ])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/home');
    }
}
