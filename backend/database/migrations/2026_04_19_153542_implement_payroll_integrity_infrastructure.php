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
        // 1. Estados de Ciclo: open, generated, closed
        Schema::table('payroll_cycles', function (Blueprint $table) {
            // Primero cambiamos el default. En SQLite/MySQL esto puede variar, 
            // pero para esta plataforma forzaremos el cambio de nombres.
            $table->string('estado', 20)->default('open')->change();
        });

        // Actualizar datos existentes
        DB::table('payroll_cycles')->where('estado', 'pendiente')->update(['estado' => 'open']);
        DB::table('payroll_cycles')->where('estado', 'procesando')->update(['estado' => 'open']); // Reiniciamos a open si estaba en el medio
        DB::table('payroll_cycles')->where('estado', 'cerrado')->update(['estado' => 'closed']);

        // 2. Nómina: Versionado y Snapshot
        Schema::table('payrolls', function (Blueprint $table) {
            $table->integer('version')->default(1)->after('tipo_pago');
            $table->json('calculation_snapshot')->nullable()->after('version');
        });

        // 3. Detalles de Nómina (Capa Contable)
        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->string('concept', 50); // tech key: night_premium
            $table->string('label', 100);  // UI label: Recargo nocturno
            $table->enum('type', ['earning', 'deduction']);
            $table->decimal('hours', 8, 2)->nullable();
            $table->decimal('rate', 12, 2)->nullable();
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->index(['payroll_id', 'concept']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_details');

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['version', 'calculation_snapshot']);
        });

        Schema::table('payroll_cycles', function (Blueprint $table) {
            $table->string('estado', 20)->default('pendiente')->change();
        });
    }
};
