<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Crea los usuarios base del sistema con sus roles.
     * 
     * Ejecutar con: php artisan db:seed --class=UserSeeder
     */
    public function run(): void
    {
        // Admin principal del sistema
        User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name'              => 'Admin ShiftFlow',
                'password'          => Hash::make('password'),
                'role'              => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]
        );

        // Usuario de prueba para el rol Employee
        User::updateOrCreate(
            ['email' => 'emp@test.com'],
            [
                'name'              => 'Empleado Prueba',
                'password'          => Hash::make('password'),
                'role'              => User::ROLE_EMPLOYEE,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ Usuarios creados: admin@test.com / emp@test.com (password: password)');
    }
}
