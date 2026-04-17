<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\User;
use App\Services\ContractResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ContractResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_the_contract_active_for_a_given_date(): void
    {
        $employee = $this->createEmployee();

        Contract::query()->create([
            'employee_id' => $employee->id,
            'salario_base' => 1800000,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-03-31',
            'estado' => Contract::ESTADO_ACTIVO,
        ]);

        $expected = Contract::query()->create([
            'employee_id' => $employee->id,
            'salario_base' => 2400000,
            'fecha_inicio' => '2026-04-01',
            'fecha_fin' => null,
            'estado' => Contract::ESTADO_ACTIVO,
        ]);

        Contract::query()->create([
            'employee_id' => $employee->id,
            'salario_base' => 9999999,
            'fecha_inicio' => '2026-04-01',
            'fecha_fin' => null,
            'estado' => Contract::ESTADO_INACTIVO,
        ]);

        $resolved = app(ContractResolver::class)->resolve($employee, Carbon::parse('2026-04-15'));

        $this->assertTrue($resolved->is($expected));
    }

    public function test_it_throws_when_no_contract_is_active_for_the_requested_date(): void
    {
        $employee = $this->createEmployee();

        Contract::query()->create([
            'employee_id' => $employee->id,
            'salario_base' => 2400000,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-01-31',
            'estado' => Contract::ESTADO_ACTIVO,
        ]);

        $this->expectException(RuntimeException::class);

        app(ContractResolver::class)->resolve($employee, Carbon::parse('2026-04-15'));
    }

    private function createEmployee(): Employee
    {
        $user = User::query()->create([
            'name' => 'Empleado Resolver',
            'email' => 'resolver@example.com',
            'password' => 'secret123',
        ]);

        return Employee::query()->create([
            'user_id' => $user->id,
            'nombre' => $user->name,
            'estado' => Employee::ESTADO_ACTIVO,
        ]);
    }
}
