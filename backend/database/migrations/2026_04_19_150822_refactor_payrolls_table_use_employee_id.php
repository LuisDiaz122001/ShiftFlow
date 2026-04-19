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
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'payroll_cycle_id']);
            
            $table->renameColumn('user_id', 'employee_id');
            
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->restrictOnDelete();

            $table->unique(['employee_id', 'payroll_cycle_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropUnique(['employee_id', 'payroll_cycle_id']);
            
            $table->renameColumn('employee_id', 'user_id');
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->unique(['user_id', 'payroll_cycle_id']);
        });
    }
};
