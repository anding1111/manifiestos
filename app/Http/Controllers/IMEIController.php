<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Imei;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

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
        Log::warning('Intento de acceso sin autenticación');

        return Inertia::render('Home', [
            'results' => $results,
            'loggedInUser' => auth()->user(), // Enviar el usuario autenticado
        ]);
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
        if (!in_array($user->role, ['Administrador', 'Trabajador', 'Cliente'])) {
            Log::warning('Usuario sin permisos intentó acceder a Home', [
                'id' => $user->id,
                'role' => $user->role,
            ]);

            return redirect('/')->with('error', 'No tienes permiso para acceder a esta sección.');
        }

        // Renderizar la vista Home y pasar los datos del usuario
        return Inertia::render('Home', [
            'loggedInUser' => $user,
        ]);
    }
}