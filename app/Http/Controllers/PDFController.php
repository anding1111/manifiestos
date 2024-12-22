<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Imei;
use Inertia\Inertia;
use Smalot\PdfParser\Parser as PdfParser;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PDFController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:pdf|max:2048',
        ]);
    
        $file = $request->file('file');
        $fileName = Carbon::now()->format('Ymd_His') . '_' . Str::upper(Str::random(3)) . '.pdf';
        $filePath = $file->storeAs('public/uploads', $fileName); // Guardar en 'public/uploads'
    
        $pdfText = $this->extractTextFromPDF(storage_path('app/' . $filePath));
    
        // Preprocesar texto línea por línea para manejar IMEIs divididos y separadores
        $lines = explode("\n", $pdfText); // Dividir el texto en líneas
        $processedText = '';
    
        for ($i = 0; $i < count($lines); $i++) {
            $currentLine = trim($lines[$i]); // Línea actual
            $nextLine = isset($lines[$i + 1]) ? trim($lines[$i + 1]) : ''; // Línea siguiente (si existe)
    
            // Verificar si el final de la línea actual contiene parte de un IMEI
            if (preg_match('/\d{1,14}$/', $currentLine, $matches)) {
                // Si la siguiente línea empieza con números, unir ambas
                if (preg_match('/^\d+/', $nextLine)) {
                    $currentLine .= $nextLine; // Unir líneas
                    $lines[$i + 1] = ''; // Vaciar la línea siguiente para evitar duplicados
                }
            }
    
            // Agregar la línea procesada al texto final
            $processedText .= $currentLine . ' ';
        }
    
        // Reemplazar separadores comunes por comas
        $processedText = preg_replace("/[,\s\/\\\\]+/", ",", $processedText);
    
        // Patrón para encontrar números de 15 dígitos (IMEI)
        $pattern = '/\b\d{15}\b/';
        preg_match_all($pattern, $processedText, $matches);
    
        $foundImeis = $matches[0];
    
        // Guardar los IMEIs en la base de datos
        foreach ($foundImeis as $imei) {
            Imei::create([
                'name_pdf' => $fileName,
                'imei' => $imei,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    
        return back()->with('message', 'Archivo subido y procesado exitosamente.');
    }
    public function index()
    {
        if (!auth()->check()) {
            Log::warning('Intento de acceso sin autenticación');
            return redirect('/login'); // Redirige al login si no está autenticado
        }
        /**
         * @var \App\Models\User $user
         */
        // Recuperar el usuario autenticado
        $user = auth()->user();
        if ($user) {
            Log::info('Usuario autenticado:', $user->toArray());
        } else {
            Log::info('No hay usuario autenticado.');
        }

        // Renderizar la vista Home y pasar los datos del usuario
        return Inertia::render('Upload', [
            'loggedInUser' => $user,
        ]);
    }
    
    private function extractTextFromPDF($filePath)
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        return $text;
    }
}