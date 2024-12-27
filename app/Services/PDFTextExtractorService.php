<?php

namespace App\Services;

use Exception;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

class PDFTextExtractorService
{
    private $parser;
    private const MAX_FILE_SIZE = 10485760; // 10MB
    private const SUPPORTED_MIME_TYPES = [
        'application/pdf'
    ];

    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * Extract text from PDF file with improved error handling and text cleanup
     */
    public function extract(string $filePath): string
    {
        try {
            $this->validateFile($filePath);
            
            $pdf = $this->parser->parseFile($filePath);
            $text = $this->extractAndCleanText($pdf);

            Log::info('PDF extraction completed', [
                'file' => $filePath,
                'textLength' => strlen($text)
            ]);

            return $text;
        } catch (Exception $e) {
            Log::error('PDF extraction failed', [
                'file' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to extract text from PDF: ' . $e->getMessage());
        }
    }

    /**
     * Validate PDF file before processing
     */
    private function validateFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        if (filesize($filePath) > self::MAX_FILE_SIZE) {
            throw new Exception("File exceeds maximum size of " . self::MAX_FILE_SIZE / 1048576 . "MB");
        }

        $mimeType = mime_content_type($filePath);
        if (!in_array($mimeType, self::SUPPORTED_MIME_TYPES)) {
            throw new Exception("Unsupported file type: {$mimeType}");
        }
    }

    /**
     * Extract and clean text from PDF
     */
    private function extractAndCleanText($pdf): string
    {
        // Extract text from all pages
        $text = $pdf->getText();

        // Basic text cleanup
        $text = $this->cleanupText($text);

        // Handle potential encoding issues
        $text = $this->handleEncoding($text);

        return $text;
    }

    /**
     * Clean up extracted text
     */
    private function cleanupText(string $text): string
    {
        // Remove null bytes and other control characters
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        
        // Remove multiple consecutive spaces
        $text = preg_replace('/[ ]{2,}/', ' ', $text);
        
        // Remove multiple consecutive line breaks
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Handle text encoding issues
     */
    private function handleEncoding(string $text): string
    {
        // Detect encoding
        $encoding = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'ASCII']);
        
        // Convert to UTF-8 if necessary
        if ($encoding && $encoding !== 'UTF-8') {
            $text = mb_convert_encoding($text, 'UTF-8', $encoding);
        }

        // Remove invalid UTF-8 sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        return $text;
    }

    /**
     * Get metadata from PDF
     */
    public function getMetadata(string $filePath): array
    {
        try {
            $pdf = $this->parser->parseFile($filePath);
            $details = $pdf->getDetails();

            return [
                'pages' => $pdf->getPages(),
                'pageCount' => count($pdf->getPages()),
                'metadata' => array_filter($details, function($value) {
                    return !is_null($value) && $value !== '';
                })
            ];
        } catch (Exception $e) {
            Log::error('Failed to get PDF metadata', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to extract PDF metadata: ' . $e->getMessage());
        }
    }
}