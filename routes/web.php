<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log; // Asegúrate de importar el Facade de Log
use App\Http\Controllers\PDFController;
use App\Http\Controllers\IMEIController;
use Inertia\Inertia;

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
