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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('documento')->nullable()->after('nombre');
            $table->string('telefono')->nullable()->after('documento');
            $table->decimal('salario_base', 10, 2)->default(0)->after('telefono');
            $table->boolean('activo')->default(true)->after('salario_base');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->unique('documento');
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['documento']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['documento', 'telefono', 'salario_base', 'activo']);
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
