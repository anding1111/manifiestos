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
    public function index()
    {
        // Validar que el usuario esté autenticado
        if (!auth()->check()) {
            Log::warning('Intento de acceso sin autenticación');
            return redirect('/login'); // Redirige al login si no está autenticado
        }

        /**
         * @var \App\Models\User $user
         */
        // Recuperar el usuario autenticado
        $user = auth()->user();

        // Lógica de control de roles (opcional)
        if (!in_array($user->role, ['Administrador', 'Trabajador'])) {
            Log::warning('Acceso denegado: usuario no autorizado', ['user_id' => $user->id]);
            return redirect('/')->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        // Renderizar la vista con los datos del usuario autenticado
        return Inertia::render('UploadPDF', [
            'loggedInUser' => $user,
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:pdf|max:2048',
        ]);
    
        $file = $request->file('file');
        $fileName = Carbon::now()->format('Ymd_His') . '_' . Str::upper(Str::random(3)) . '.pdf';
        $filePath = $file->storeAs('public/uploads', $fileName); // Guardar en 'public/uploads'
    
        $pdfText = $this->extractTextFromPDF(storage_path('app/' . $filePath));
    
        // Preprocesar texto para manejar líneas divididas y eliminar tabuladores
        $lines = explode("\n", $pdfText);
        $buffer = ''; // Para almacenar números IMEI parcialmente encontrados
        $processedText = '';
    
        foreach ($lines as $lineNumber => $line) {
            $originalLine = $line; // Guardar la línea original para log
            $line = trim($line);
    
            // Eliminar tabuladores (\t) y otros caracteres no deseados
            $line = preg_replace('/\t+/', '', $line);
    
            // Log de la línea original y la procesada
            Log::info('Procesando línea', [
                'lineNumber' => $lineNumber + 1,
                'originalLine' => $originalLine,
                'processedLine' => $line,
            ]);
    
            // Si el buffer tiene datos, intentar completar el IMEI
            if ($buffer && preg_match('/^\d+/', $line)) {
                $buffer .= $line; // Completar el IMEI
                $line = $buffer;
                $buffer = ''; // Limpiar el buffer
            }
    
            // Detectar si la línea contiene fragmentos de IMEIs separados
            if (preg_match_all('/(\d+)[,\s\/\\\\]+(\d+)/', $line, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $potentialImei = $match[1] . $match[2];
                    if (strlen($potentialImei) === 15 && ctype_digit($potentialImei)) {
                        // Reemplazar el fragmento por el IMEI completo
                        $line = str_replace($match[0], $potentialImei, $line);
                    }
                }
            }
    
            // Verificar si la línea contiene un IMEI completo al final
            if (preg_match('/\b\d{15}\b$/', $line)) {
                $processedText .= $line . ' '; // Agregar la línea completa
                continue; // No es necesario almacenarlo en el buffer
            }
    
            // Detectar si la línea termina con un fragmento de IMEI
            if (preg_match('/\d{1,14}$/', $line)) {
                $buffer = $line; // Almacenar la línea incompleta en el buffer
                continue;
            }
    
            $processedText .= $line . ' '; // Agregar la línea procesada
        }
    
        // Procesar cualquier dato restante en el buffer
        if ($buffer) {
            $processedText .= $buffer;
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
     
    
    private function extractTextFromPDF($filePath)
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        return $text;
    }
}