<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SiteSetting;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'company_mission',
                'value' => '',
                'type' => 'textarea',
                'label' => 'Misión de la Empresa',
                'description' => 'Misión corporativa que aparece en la página Sobre Nosotros'
            ],
            [
                'key' => 'company_vision',
                'value' => '',
                'type' => 'textarea',
                'label' => 'Visión de la Empresa',
                'description' => 'Visión corporativa que aparece en la página Sobre Nosotros'
            ],
            [
                'key' => 'company_description',
                'value' => '',
                'type' => 'textarea',
                'label' => 'Descripción de la Empresa',
                'description' => 'Descripción principal que aparece en el encabezado de la página Sobre Nosotros'
            ]
        ];

        foreach ($settings as $setting) {
            SiteSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}