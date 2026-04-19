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
        Schema::table('labor_rules', function (Blueprint $table) {
            $table->decimal('porcentaje_salud', 5, 2)->default(4.00);
            $table->decimal('porcentaje_pension', 5, 2)->default(4.00);
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('deduccion_salud', 12, 2)->default(0);
            $table->decimal('deduccion_pension', 12, 2)->default(0);
            $table->decimal('neto_pagado', 12, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('labor_rules', function (Blueprint $table) {
            $table->dropColumn(['porcentaje_salud', 'porcentaje_pension']);
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['deduccion_salud', 'deduccion_pension', 'neto_pagado']);
        });
    }
};
