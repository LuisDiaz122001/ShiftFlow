<?php

namespace App\Console\Commands;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;

class InstallDemoDataCommand extends Command
{
    protected $signature = 'app:demo-data
                            {--fresh : Ejecuta migrate:fresh antes de sembrar}
                            {--seed-only : Solo ejecuta seeders sin migrar}';

    protected $description = 'Instala datos demo coherentes para desarrollo y pruebas de ShiftFlow';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->components->warn('Se ejecutará migrate:fresh. Todos los datos actuales se perderán.');
            if (! $this->components->confirm('¿Continuar?', ! app()->environment('production'))) {
                $this->components->info('Operación cancelada.');

                return self::SUCCESS;
            }

            $this->call('migrate:fresh', ['--force' => true]);
        } elseif (! $this->option('seed-only')) {
            $this->call('migrate', ['--force' => true]);
        }

        $this->components->info('Sembrando datos demo...');
        $this->call('db:seed', ['--class' => DemoDataSeeder::class, '--force' => true]);

        $this->newLine();
        $this->components->info('Datos demo instalados correctamente.');
        $this->table(['Rol', 'Email', 'Password'], [
            ['Admin', 'admin@test.com', 'password'],
            ['Supervisor', 'supervisor@test.com', 'password'],
            ['Empleado', 'emp@test.com', 'password'],
            ['Empleado demo', 'empleado1@test.com', 'password'],
        ]);
        $this->line('También existen empleado2@test.com ... empleado8@test.com con password: password');

        return self::SUCCESS;
    }
}
