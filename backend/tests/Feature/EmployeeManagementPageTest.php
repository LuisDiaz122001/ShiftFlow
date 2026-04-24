<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployeeManagementPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_access_employee_management_page(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get('/employees')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Employees/Index'));
    }

    public function test_supervisor_can_access_employee_management_page(): void
    {
        $supervisor = User::factory()->create([
            'role' => User::ROLE_SUPERVISOR,
        ]);

        $this->actingAs($supervisor)
            ->get('/employees')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Employees/Index'));
    }

    public function test_employee_cannot_access_employee_management_page(): void
    {
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $this->actingAs($employee)
            ->get('/employees')
            ->assertForbidden();
    }
}
