<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\IMEIController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use Inertia\Inertia;

// Rutas públicas
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request')->middleware('guest');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('guest');
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset')->middleware('guest');
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update')->middleware('guest');

// Rutas protegidas
Route::middleware(['auth'])->group(function () {
    // Página principal
    Route::get('/', [IMEIController::class, 'index'])->name('home');
    Route::get('/home', [IMEIController::class, 'index']);

    // Subida de archivos
    Route::get('/upload', [PDFController::class, 'index'])->name('upload');
    Route::post('/upload-pdf', [PDFController::class, 'upload']);

    // Usuarios
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Perfil y cambio de contraseña
    Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/change-password', [ProfileController::class, 'changePassword'])->name('password.change');
});

// Rutas auxiliares
Route::post('/check-imei', [IMEIController::class, 'checkImei'])->name('check.imei');
Route::get('/check-imei', function () {
    return redirect()->route('home');
});

Route::get('/highlight/{filename}/{imeis}', function ($filename, $imeis) {
    return Inertia::render('Highlight', ['filename' => $filename, 'imeis' => $imeis]);
})->name('highlight');
Route::get('/highlight', function () {
    return Inertia::render('Highlight');
});

Route::get('/storage/uploads/{filename}', function ($filename) {
    $path = storage_path('app/public/uploads/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
});
