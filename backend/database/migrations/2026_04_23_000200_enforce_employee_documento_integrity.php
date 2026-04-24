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
        $invalidDocumentCount = DB::table('employees')
            ->whereNull('documento')
            ->orWhere('documento', '')
            ->count();

        if ($invalidDocumentCount > 0) {
            throw new RuntimeException(
                'No se puede endurecer employees.documento porque existen empleados sin documento real cargado.'
            );
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->string('documento')->nullable(false)->change();
            $table->boolean('activo')->default(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('documento')->nullable()->change();
        });
    }
};
