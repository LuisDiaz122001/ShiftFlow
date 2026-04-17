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
        Schema::create('shift_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->decimal('horas_diurnas', 8, 2)->default(0);
            $table->decimal('horas_nocturnas', 8, 2)->default(0);
            $table->decimal('horas_extra_diurnas', 8, 2)->default(0);
            $table->decimal('horas_extra_nocturnas', 8, 2)->default(0);
            $table->decimal('valor_total', 12, 2)->default(0);
            $table->json('detalle_json')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_calculations');
    }
};
