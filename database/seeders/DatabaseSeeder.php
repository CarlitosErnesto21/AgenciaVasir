<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */

    public function run()
    {
        $this->command->info('Iniciando seeders de VASIR...');

        $this->call([
            ControlSeeder::class,
            SiteSettingsSeeder::class,
            AdminUserSeeder::class,
        ]);

        $this->command->info('¡Base de datos inicializada correctamente!');
    }
}
