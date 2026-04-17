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
        Schema::create('payroll_cycles', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->date('fecha_pago');
            $table->string('estado', 20)->default('pendiente');
            $table->timestamps();

            $table->unique(['fecha_inicio', 'fecha_fin']);
            $table->index(['estado', 'fecha_inicio']);
            $table->index('fecha_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_cycles');
    }
};
