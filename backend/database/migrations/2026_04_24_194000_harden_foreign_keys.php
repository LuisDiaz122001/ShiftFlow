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
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->foreignId('employee_id')
                ->change()
                ->constrained()
                ->restrictOnDelete();
        });
        
        Schema::table('payroll_shift', function (Blueprint $table) {
            $table->dropForeign(['payroll_id']);
            $table->foreignId('payroll_id')
                ->change()
                ->constrained()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->foreignId('employee_id')
                ->change()
                ->constrained()
                ->cascadeOnDelete();
        });
    }
};
