<?php

namespace Tests\Feature\Api\V1;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_employee_and_its_user_in_a_single_request(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/employees', [
            'nombre' => 'Ana Gomez',
            'documento' => 'CC123456789',
            'telefono' => '3001234567',
            'salario_base' => 2500000,
            'activo' => true,
            'email' => 'ana.gomez@example.com',
            'password' => 'Secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.nombre', 'Ana Gomez')
            ->assertJsonPath('data.email', 'ana.gomez@example.com')
            ->assertJsonPath('data.user.role', User::ROLE_EMPLOYEE);

        $user = User::query()->where('email', 'ana.gomez@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('Secret123', $user->password));
        $this->assertDatabaseHas('employees', [
            'user_id' => $user->id,
            'documento' => 'CC123456789',
        ]);
    }

    public function test_admin_can_update_employee_fields_and_linked_user_email(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $employeeUser = User::factory()->create([
            'name' => 'Ana Gomez',
            'email' => 'ana.original@example.com',
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $employee = Employee::query()->create([
            'user_id' => $employeeUser->id,
            'nombre' => 'Ana Gomez',
            'documento' => 'CC987654321',
            'telefono' => '3009998888',
            'salario_base' => 2200000,
            'activo' => true,
        ]);

        $response = $this->putJson("/api/v1/employees/{$employee->id}", [
            'nombre' => 'Ana Maria Gomez',
            'telefono' => '3010001122',
            'salario_base' => 2600000,
            'activo' => false,
            'email' => 'ana.actualizada@example.com',
            'password' => 'NuevaClave123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.nombre', 'Ana Maria Gomez')
            ->assertJsonPath('data.email', 'ana.actualizada@example.com')
            ->assertJsonPath('data.activo', false);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'nombre' => 'Ana Maria Gomez',
            'telefono' => '3010001122',
            'salario_base' => 2600000,
            'activo' => 0,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $employeeUser->id,
            'name' => 'Ana Maria Gomez',
            'email' => 'ana.actualizada@example.com',
        ]);

        $employeeUser->refresh();
        $this->assertTrue(Hash::check('NuevaClave123', $employeeUser->password));
    }

    public function test_deleting_an_employee_also_deletes_the_linked_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $employeeUser = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $employee = Employee::query()->create([
            'user_id' => $employeeUser->id,
            'nombre' => 'Carlos Ruiz',
            'documento' => 'CC1122334455',
            'telefono' => null,
            'salario_base' => 2100000,
            'activo' => true,
        ]);

        $response = $this->deleteJson("/api/v1/employees/{$employee->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('users', [
            'id' => $employeeUser->id,
        ]);

        $this->assertDatabaseMissing('employees', [
            'id' => $employee->id,
        ]);
    }
}
