<?php

namespace App\Http\Controllers;

use App\Models\Imei;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Inertia\Inertia;
use Smalot\PdfParser\Parser as PdfParser;
use App\Services\IMEIProcessingService;
use App\Services\PDFTextExtractorService;

class PDFController extends Controller
{
    private $imeiProcessor;
    private $pdfExtractor;

    public function __construct(
        IMEIProcessingService $imeiProcessor,
        PDFTextExtractorService $pdfExtractor
    ) {
        $this->imeiProcessor = $imeiProcessor;
        $this->pdfExtractor = $pdfExtractor;
    }

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
            'file' => 'required|mimes:pdf|max:10240',
        ]);

        try {
            // Store file
            $file = $request->file('file');
            $fileName = $this->generateFileName();
            $filePath = $file->storeAs('public/uploads', $fileName);

            // Extract and process text
            $pdfText = $this->pdfExtractor->extract(
                storage_path('app/' . $filePath)
            );
            $foundImeis = $this->imeiProcessor->processText($pdfText);

            // Save IMEIs
            $this->saveImeis($fileName, $foundImeis);

            return back()->with('message', 'Archivo procesado exitosamente. IMEIs encontrados: ' . count($foundImeis));
        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    private function generateFileName(): string
    {
        return Carbon::now()->format('Ymd_His') . '_' . 
               Str::upper(Str::random(3)) . '.pdf';
    }

    private function saveImeis(string $fileName, array $imeis): void
    {
        $now = Carbon::now();
        $batchSize = 1000; // Tamaño del lote para los inserts
        $batches = array_chunk($imeis, $batchSize); // Divide los IMEIs en lotes

        foreach ($batches as $batch) {
            $records = array_map(function($imei) use ($fileName, $now) {
                return [
                    'name_pdf' => $fileName,
                    'imei' => $imei,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $batch);

            try {
                Imei::insert($records); // Inserta el lote en la base de datos
            } catch (\Exception $e) {
                Log::error('Error al insertar IMEIs en lote', [
                    'error' => $e->getMessage(),
                    'batch' => $batch,
                ]);
            }
        }
    }

    
    private function extractTextFromPDF($filePath)
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        return $text;
    }
}