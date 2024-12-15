<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Imei;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class IMEIController extends Controller
{
    public function checkImei(Request $request)
    {
        // Validación personalizada para evitar redirección no deseada
        $validator = Validator::make($request->all(), [
            'imeis' => 'required|string',
        ]);

        if ($validator->fails()) {
            return Inertia::render('Home', [
                'errors' => $validator->errors(),
                'results' => [],
            ]);
        }

        $imeis = explode(',', $request->input('imeis'));
        $results = [];

        foreach ($imeis as $imei) {
            $imei = trim($imei);
            if (strlen($imei) === 15 && ctype_digit($imei)) {
                $imeiRecord = Imei::where('imei', $imei)->first();
                if ($imeiRecord) {
                    $imeiRecord->pdf_url = asset('storage/uploads/' . $imeiRecord->name_pdf);
                    $results[$imeiRecord->name_pdf][] = $imeiRecord;
                } else {
                    $results['N/A'][] = [
                        'imei' => $imei,
                        'name_pdf' => 'N/A',
                        'pdf_url' => 'N/A',
                        'created_at' => 'N/A',
                    ];
                }
            } else {
                $results['N/A'][] = [
                    'imei' => $imei,
                    'name_pdf' => 'N/A',
                    'pdf_url' => 'N/A',
                    'created_at' => 'Invalid IMEI format',
                ];
            }
        }

        return Inertia::render('Home', ['results' => $results]);
    }
}
