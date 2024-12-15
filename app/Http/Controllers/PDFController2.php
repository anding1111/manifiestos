<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Imei;
use Smalot\PdfParser\Parser as PdfParser;
use Carbon\Carbon;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReader;
use Spatie\PdfToImage\Pdf;

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
        
        // Patrón para encontrar números de 15 dígitos (IMEI) ignorando saltos de línea
        $pattern = '/\b\d{15}\b/s'; 
        $pdfText = preg_replace("/\s+/", "", $pdfText); // Eliminar espacios y saltos de línea
        preg_match_all($pattern, $pdfText, $matches);
        
        $foundImeis = $matches[0];

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

    public function highlightImei($filename, $imei)
    {
        $path = storage_path('app/public/uploads/' . $filename);

        if (!file_exists($path)) {
            abort(404);
        }

        // Descomprimir el PDF
        $parser = new PdfParser();
        $pdf = $parser->parseFile($path);
        $content = $pdf->getText();

        // Inicializar FPDI
        $fpdi = new Fpdi();
        $pageCount = $fpdi->setSourceFile(StreamReader::createByString($content));

        // Agregar resaltado para el IMEI
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $fpdi->importPage($pageNo);
            $fpdi->addPage();
            $fpdi->useTemplate($templateId);

            $fpdi->SetFont('Helvetica', '', 12);
            $fpdi->SetTextColor(255, 0, 0); // Color rojo para el resaltado

            // Añadir el IMEI a cada página (ajusta la posición según sea necesario)
            // Este ejemplo asume que el IMEI está cerca de la parte superior de la página.
            // En un escenario real, podrías necesitar buscar la posición del IMEI en el texto.
            $fpdi->Text(15, 15, $imei); // Ajusta la posición según sea necesario
        }

        // Generar el nuevo PDF
        $newFilename = 'highlighted_' . $filename;
        $newPath = storage_path('app/public/uploads/' . $newFilename);
        $fpdi->Output($newPath, 'F');

        return response()->download($newPath);
    }
}
