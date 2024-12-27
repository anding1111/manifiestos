<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class IMEIProcessingService
{
    private const IMEI_LENGTH = 15;

    /**
     * Procesa el texto para extraer los IMEIs válidos.
     */
    public function processText(string $text): array
    {
        $lines = explode("\n", $text);
        $buffer = '';
        $processedLines = [];

        foreach ($lines as $lineNumber => $line) {
            $line = $this->cleanLine($line);

            // Manejar el buffer con el inicio de la línea actual
            if ($buffer) {
                $line = $this->combineBufferAndLine($buffer, $line);
                $buffer = '';
            }

            // Detectar fragmento al final de la línea
            if ($this->hasPartialImeiAtEnd($line)) {
                $fragment = $this->getEndFragment($line);

                // Validar que el fragmento sea menor a 15 dígitos
                if (strlen($fragment) < self::IMEI_LENGTH) {
                    $buffer = $fragment;
                    $line = substr($line, 0, -strlen($fragment));
                }
            }

            // Procesar IMEIs divididos dentro de la línea por espacios
            $line = $this->processFragmentedIMEIs($line);

            $processedLines[] = $line;
        }

        // Agregar cualquier buffer restante
        if ($buffer) {
            $processedLines[] = $buffer;
        }

        return $this->extractIMEIs(implode(' ', $processedLines));
    }

    /**
     * Limpia y normaliza una línea.
     */
    private function cleanLine(string $line): string
    {
        $line = trim($line);
        return preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $line);
    }

    /**
     * Combina el buffer con el inicio de la línea actual.
     */
    private function combineBufferAndLine(string $buffer, string $line): string
    {
        if (preg_match('/^\d+/', $line, $matches)) {
            $potentialImei = $buffer . $matches[0];

            // Validar si el resultado es un IMEI válido o un fragmento
            if (strlen($potentialImei) <= self::IMEI_LENGTH && ctype_digit($potentialImei)) {
                return $potentialImei . substr($line, strlen($matches[0]));
            }
        }
        return $line;
    }

    /**
     * Procesa IMEIs que podrían estar divididos por espacios o separadores dentro de una línea.
     */
    private function processFragmentedIMEIs(string $line): string
    {
        return preg_replace_callback(
            '/(\d+)\s+(\d+)/',
            function ($matches) {
                $combined = $matches[1] . $matches[2];
                if ($this->isValidImeiPattern($combined)) {
                    return $combined;
                }
                return $matches[0];
            },
            $line
        );
    }

    /**
     * Verifica si la línea tiene un fragmento de IMEI al final.
     */
    private function hasPartialImeiAtEnd(string $line): bool
    {
        $fragment = $this->getEndFragment($line);
        return strlen($fragment) > 0 && strlen($fragment) < self::IMEI_LENGTH;
    }

    /**
     * Extrae el fragmento al final de la línea que podría ser parte de un IMEI.
     */
    private function getEndFragment(string $line): string
    {
        // Buscar el fragmento final que incluye posibles números separados por espacios
        if (preg_match('/(\d+)\s+(\d+)$/', $line, $matches)) {
            $firstPart = $matches[1];
            $secondPart = $matches[2];
            
            // Si al unir ambas partes se forma un número mayor a 15 dígitos
            $combined = $firstPart . $secondPart;
            if (strlen($combined) > self::IMEI_LENGTH) {
                // Retornar solo la segunda parte para el buffer
                return $secondPart;
            }
            
            // Si el resultado es válido, retornar ambas partes unidas
            return $combined;
        }

        // Si no hay espacio o es una cadena simple de números al final
        if (preg_match('/\d{1,' . self::IMEI_LENGTH . '}$/', $line, $matches)) {
            return $matches[0];
        }

        // No se encontró un fragmento válido
        return '';
    }


    /**
     * Verifica si un número cumple con el patrón de un IMEI válido.
     */
    private function isValidImeiPattern(string $number): bool
    {
        return strlen($number) === self::IMEI_LENGTH && ctype_digit($number);
    }

    /**
     * Extrae IMEIs válidos del texto procesado.
     */
    private function extractIMEIs(string $text): array
    {
        // Normaliza separadores
        $text = preg_replace("/[,\s\/\\\\\.]+/", ",", $text);

        // Busca todos los posibles IMEIs
        preg_match_all('/\b\d{' . self::IMEI_LENGTH . '}\b/', $text, $matches);

        // Filtra y valida IMEIs
        $imeis = array_filter(
            array_unique($matches[0]),
            [$this, 'isValidImeiPattern']
        );
        return array_values($imeis);
    }
}
