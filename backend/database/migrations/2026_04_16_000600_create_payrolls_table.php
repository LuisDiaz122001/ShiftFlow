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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('payroll_cycle_id')
                ->constrained()
                ->restrictOnDelete();
            $table->decimal('salario_base_pagado', 12, 2);
            $table->decimal('recargos_pagados', 12, 2)->default(0);
            $table->decimal('total_pagado', 12, 2);
            $table->tinyInteger('tipo_pago'); // 15 o 30
            $table->date('fecha_pago');
            $table->timestamps();

            $table->unique(['user_id', 'payroll_cycle_id']);
            $table->index(['payroll_cycle_id', 'fecha_pago']);
            $table->index('tipo_pago');
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
