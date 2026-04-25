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
        // Re-creating payrolls table from scratch as per new requirements
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('payroll_details'); // Drop dependent table first
        Schema::dropIfExists('payrolls');
        Schema::enableForeignKeyConstraints();
        
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('total_hours', 10, 2);
            $table->decimal('diurnas_hours', 10, 2);
            $table->decimal('nocturnas_hours', 10, 2);
            $table->decimal('total_pago', 12, 2);
            $table->string('estado')->default('pending'); // pending | paid
            $table->timestamps();

            // Unique constraint to prevent duplicate payrolls for the same period and employee
            $table->unique(['employee_id', 'fecha_inicio', 'fecha_fin'], 'unique_payroll_period');
            $table->index(['fecha_inicio', 'fecha_fin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
