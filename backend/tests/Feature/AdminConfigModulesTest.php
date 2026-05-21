<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LaborRule;
use App\Models\PayrollCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminConfigModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_can_access_config_modules(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('contracts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Contracts/Index'));

        $this->actingAs($admin)
            ->get(route('labor-rules.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('LaborRules/Index'));

        $this->actingAs($admin)
            ->get(route('holidays.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Holidays/Index'));
    }

    public function test_supervisor_cannot_access_admin_config_modules(): void
    {
        $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

        $this->actingAs($supervisor)->get(route('contracts.index'))->assertForbidden();
        $this->actingAs($supervisor)->get(route('labor-rules.index'))->assertForbidden();
        $this->actingAs($supervisor)->get(route('holidays.index'))->assertForbidden();
    }

    public function test_admin_can_create_payroll_cycle_without_overlap(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('payrolls.periods.store'), [
                'fecha_inicio' => '2026-06-01',
                'fecha_fin' => '2026-06-15',
                'fecha_pago' => '2026-06-20',
            ])
            ->assertRedirect(route('payrolls.periods'));

        $this->assertDatabaseCount('payroll_cycles', 1);
        $cycle = PayrollCycle::first();
        $this->assertSame(PayrollCycle::STATUS_OPEN, $cycle->estado);
        $this->assertSame('2026-06-01', $cycle->fecha_inicio->toDateString());
        $this->assertSame('2026-06-15', $cycle->fecha_fin->toDateString());
    }

    public function test_admin_cannot_create_overlapping_payroll_cycle(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        PayrollCycle::factory()->create([
            'fecha_inicio' => '2026-06-01',
            'fecha_fin' => '2026-06-15',
            'fecha_pago' => '2026-06-20',
        ]);

        $this->actingAs($admin)
            ->post(route('payrolls.periods.store'), [
                'fecha_inicio' => '2026-06-10',
                'fecha_fin' => '2026-06-25',
                'fecha_pago' => '2026-06-30',
            ])
            ->assertSessionHasErrors('fecha_inicio');
    }

    public function test_admin_can_manage_holiday_crud(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('holidays.store'), [
                'fecha' => '2026-12-25',
                'nombre' => 'Navidad',
            ])
            ->assertRedirect(route('holidays.index'));

        $holiday = Holiday::first();
        $this->assertNotNull($holiday);

        $this->actingAs($admin)
            ->put(route('holidays.update', $holiday), [
                'fecha' => '2026-12-25',
                'nombre' => 'Navidad Nacional',
            ])
            ->assertRedirect(route('holidays.index'));

        $this->actingAs($admin)
            ->delete(route('holidays.destroy', $holiday))
            ->assertRedirect(route('holidays.index'));

        $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);
    }

    public function test_admin_can_process_payroll_cycle(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        LaborRule::factory()->create();
        $cycle = PayrollCycle::factory()->create([
            'fecha_inicio' => now()->startOfMonth()->toDateString(),
            'fecha_fin' => now()->endOfMonth()->toDateString(),
            'fecha_pago' => now()->endOfMonth()->toDateString(),
            'estado' => PayrollCycle::STATUS_OPEN,
        ]);

        $this->actingAs($admin)
            ->post(route('payrolls.periods.process', $cycle))
            ->assertRedirect();

        $cycle->refresh();
        $this->assertSame(PayrollCycle::STATUS_GENERATED, $cycle->estado);
    }

    public function test_employees_index_returns_paginated_props(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Employee::factory()->count(2)->create();

        $this->actingAs($admin)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Employees/Index')
                ->has('employees.data', 2)
                ->has('employees.links'));
    }
}
