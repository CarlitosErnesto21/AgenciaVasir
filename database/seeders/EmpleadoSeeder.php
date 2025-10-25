<?php

namespace Database\Seeders;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmpleadoSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuarios para los empleados
        $usuarios = [
            [
                'name' => 'Roberto García',
                'email' => 'roberto.garcia@agencia.com',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Carmen López',
                'email' => 'carmen.lopez@agencia.com',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Miguel Torres',
                'email' => 'miguel.torres@agencia.com',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Isabel Fernández',
                'email' => 'isabel.fernandez@agencia.com',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Diego Morales',
                'email' => 'diego.morales@agencia.com',
                'password' => Hash::make('password123'),
            ],
        ];

        $userIds = [];
        foreach ($usuarios as $userData) {
            $user = User::create($userData);
            $userIds[] = $user->id;
        }

        // ✅ EMPLEADOS PARA GESTIÓN DE RESERVAS (NO VENTAS)
        $empleados = [
            [
                'cargo' => 'Agente de Viajes',      // ✅ Especializado en reservas
                'telefono' => '55501001',           // ✅ char(8) - exactamente 8 caracteres
                'user_id' => $userIds[0],           // ✅ FK a users
            ],
            [
                'cargo' => 'Supervisor Reservas',   // ✅ Supervisor de reservas
                'telefono' => '55501002',           // ✅ char(8)
                'user_id' => $userIds[1],
            ],
            [
                'cargo' => 'Agente Senior',         // ✅ Agente experimentado
                'telefono' => '55501003',           // ✅ char(8)
                'user_id' => $userIds[2],
            ],
            [
                'cargo' => 'Coordinador Tours',     // ✅ Especialista en tours
                'telefono' => '55501004',           // ✅ char(8)
                'user_id' => $userIds[3],
            ],
            [
                'cargo' => 'Gerente Reservas',      // ✅ Gerente del área
                'telefono' => '55501005',           // ✅ char(8)
                'user_id' => $userIds[4],
            ],
        ];

        foreach ($empleados as $empleado) {
            Empleado::create($empleado);
        }
    }
}
