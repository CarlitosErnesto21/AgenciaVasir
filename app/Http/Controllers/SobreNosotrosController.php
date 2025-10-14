<?php

namespace App\Http\Controllers;

use App\Models\CompanyValue;
use Illuminate\Http\Request;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SobreNosotrosController extends Controller
{
    public function index()
    {
        // Forzar recarga de datos sin cache
        $mission = SiteSetting::where('key', 'company_mission')->first();
        $vision = SiteSetting::where('key', 'company_vision')->first();
        $description = SiteSetting::where('key', 'company_description')->first();

        // Obtener valores corporativos desde la base de datos
        $companyValues = CompanyValue::getAllValues();

        $siteSettings = [
            'mission' => $mission ? $mission->value : '',
            'vision' => $vision ? $vision->value : '',
            'description' => $description ? $description->value : '',
        ];

        return Inertia::render('VistasClientes/SobreNosotros', [
            'siteSettings' => $siteSettings,
            'companyValues' => $companyValues
        ]);
    }
}
