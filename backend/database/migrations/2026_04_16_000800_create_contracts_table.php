<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained()
                ->restrictOnDelete();
            $table->decimal('salario_base', 10, 2);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->timestamps();

            $table->index(['employee_id', 'estado', 'fecha_inicio']);
        });

        $now = now();
        $rows = DB::table('employees')
            ->join('users', 'users.id', '=', 'employees.user_id')
            ->whereNotNull('users.salario_base')
            ->select(
                'employees.id as employee_id',
                'users.salario_base',
                'users.created_at',
                'users.updated_at'
            )
            ->orderBy('employees.id')
            ->get()
            ->map(function (object $row) use ($now): array {
                $createdAt = $row->created_at !== null
                    ? Carbon::parse($row->created_at)
                    : Carbon::parse($now);

                return [
                    'employee_id' => $row->employee_id,
                    'salario_base' => round((float) $row->salario_base, 2),
                    'fecha_inicio' => $createdAt->toDateString(),
                    'fecha_fin' => null,
                    'estado' => 'activo',
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $row->updated_at ?? $now,
                ];
            })
            ->all();

        if ($rows !== []) {
            DB::table('contracts')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
