<?php

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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained()
                ->nullOnDelete();
            $table->string('nombre');
            $table->string('estado', 20)->default('activo');
            $table->timestamps();

            $table->index('estado');
        });

        $now = now();
        $rows = DB::table('users')
            ->select('id', 'name', 'created_at', 'updated_at')
            ->orderBy('id')
            ->get()
            ->map(function (object $user) use ($now): array {
                return [
                    'user_id' => $user->id,
                    'nombre' => $user->name,
                    'estado' => 'activo',
                    'created_at' => $user->created_at ?? $now,
                    'updated_at' => $user->updated_at ?? $now,
                ];
            })
            ->all();

        if ($rows !== []) {
            DB::table('employees')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
