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
        Schema::create('labor_rules', function (Blueprint $table) {
            $table->id();
            $table->date('vigente_desde')->index();
            $table->time('hora_diurna_inicio');
            $table->time('hora_nocturna_inicio');
            $table->decimal('recargo_nocturno', 5, 2);
            $table->decimal('recargo_dominical', 5, 2);
            $table->decimal('extra_diurna', 5, 2);
            $table->decimal('extra_nocturna', 5, 2);
            $table->decimal('horas_max_diarias', 5, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labor_rules');
    }
};
