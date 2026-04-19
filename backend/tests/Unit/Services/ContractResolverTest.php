<?php

namespace Tests\Unit\Services;

use App\Models\Contract;
use App\Models\Employee;
use App\Services\ContractResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ContractResolverTest extends TestCase
{
    use RefreshDatabase;

    private ContractResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ContractResolver();
    }

    public function test_it_resolves_active_contract_correct_date(): void
    {
        $employee = Employee::create(['nombre' => 'Test Employee']);
        
        $contract = Contract::create([
            'employee_id' => $employee->id,
            'salario_base' => 2000000,
            'fecha_inicio' => Carbon::parse('2026-01-01'),
            'fecha_fin' => Carbon::parse('2026-12-31'),
            'estado' => Contract::ESTADO_ACTIVO,
        ]);

        $resolved = $this->resolver->resolve($employee, Carbon::parse('2026-06-01'));

        $this->assertEquals($contract->id, $resolved->id);
    }

    public function test_it_throws_exception_if_no_active_contract_on_date(): void
    {
        $employee = Employee::create(['nombre' => 'Test Employee']);
        
        Contract::create([
            'employee_id' => $employee->id,
            'salario_base' => 2000000,
            'fecha_inicio' => Carbon::parse('2026-01-01'),
            'fecha_fin' => Carbon::parse('2026-06-30'),
            'estado' => Contract::ESTADO_ACTIVO,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No existe un contrato activo');

        $this->resolver->resolve($employee, Carbon::parse('2026-07-01'));
    }

    public function test_it_ignores_inactive_contracts(): void
    {
        $employee = Employee::create(['nombre' => 'Test Employee']);
        
        Contract::create([
            'employee_id' => $employee->id,
            'salario_base' => 2000000,
            'fecha_inicio' => Carbon::parse('2026-01-01'),
            'fecha_fin' => Carbon::parse('2026-12-31'),
            'estado' => Contract::ESTADO_INACTIVO,
        ]);

        $this->expectException(RuntimeException::class);
        $this->resolver->resolve($employee, Carbon::parse('2026-06-01'));
    }
}
