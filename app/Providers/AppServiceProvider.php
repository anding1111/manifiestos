<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    // public function boot(): void
    // {
    //     Inertia::share('app.name', config('app.name'));
    // }

    public function boot()
    {
        Inertia::share([
            // Nombre de la aplicación (puedes dejarlo como está)
            'app' => [
                'name' => config('app.name'),
            ],
    
            // Compartir el usuario autenticado
            'auth' => function () {
                return [
                    'user' => Auth::user(),
                ];
            },
            'flash' => function () {
                return session()->get('flash');
            },
    
            // Compartir errores de validación (automáticamente enviados por Laravel)
            'errors' => function () {
                return Session::get('errors')
                    ? Session::get('errors')->getBag('default')->getMessages()
                    : (object) [];
            },
        ]);
    }

}
