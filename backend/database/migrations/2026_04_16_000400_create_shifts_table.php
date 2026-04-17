<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->foreignId('payroll_cycle_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'fecha_inicio']);
            $table->index(['payroll_cycle_id', 'fecha_inicio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
