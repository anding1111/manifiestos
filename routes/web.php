<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log; // Asegúrate de importar el Facade de Log
use App\Http\Controllers\PDFController;
use App\Http\Controllers\IMEIController;
use Inertia\Inertia;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;


Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('/upload', function () {
    return Inertia::render('UploadPDF');
})->name('upload');

Route::post('/upload-pdf', [PDFController::class, 'upload']);
Route::post('/check-imei', [IMEIController::class, 'checkImei']);
Route::get('/check-imei', function () {
    return redirect()->route('home');
});

// Nueva ruta para resaltar el IMEI en el PDF desde el cliente
Route::get('/highlight/{filename}/{imeis}', function ($filename, $imeis) {
    return Inertia::render('Highlight', ['filename' => $filename, 'imeis' => $imeis]);
});

Route::get('/highlight', function () {
    return Inertia::render('Highlight');
});

// Ruta para servir archivos desde storage/app/public/uploads a través del enlace simbólico
Route::get('/storage/uploads/{filename}', function ($filename) {
    $path = storage_path('app/public/uploads/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
});

// Ruta para servir archivos resaltados desde storage/app/public/highlight
Route::get('/storage/highlight/{filename}', function ($filename) {
    $path = storage_path('app/public/highlight/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
});


Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Rutas protegidas
Route::middleware(['auth'])->group(function () {
    Route::get('/home', function () {
        return inertia('Home'); // Renderiza la página Home con Inertia.js
    })->name('home');
});

// Route::middleware(['auth'])->group(function () {
//     Route::get('/home', function () {
//         dd(session('flash')); // Verifica si el mensaje flash está presente
//         return inertia('Home');
//     })->name('home');
// });


Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request')->middleware('guest');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('guest');

Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset')->middleware('guest');
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update')->middleware('guest');

