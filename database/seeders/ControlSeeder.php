<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ControlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Empleado', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Cliente', 'guard_name' => 'web']);
    }
}
