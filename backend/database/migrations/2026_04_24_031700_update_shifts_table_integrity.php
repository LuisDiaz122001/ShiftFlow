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
        // 1. Check if any shifts have employee_id = null
        if (DB::table('shifts')->whereNull('employee_id')->exists()) {
            throw new \RuntimeException(
                'Integrity Violation: Found shifts without an assigned employee (employee_id is NULL). ' .
                'Please assign employees to all shifts before running this migration.'
            );
        }

        Schema::table('shifts', function (Blueprint $table) {
            // 2. Add "notas" field
            $table->text('notas')->nullable()->after('fecha_fin');

            // 3. Update employee_id integrity
            // First, drop the existing foreign key if it exists
            // Based on previous migrations, it was likely shifts_employee_id_foreign
            $table->dropForeign(['employee_id']);

            // Re-add with NOT NULL and CASCADE ON DELETE
            $table->foreignId('employee_id')
                ->nullable(false)
                ->change()
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            
            $table->foreignId('employee_id')
                ->nullable()
                ->change()
                ->constrained()
                ->restrictOnDelete();

            $table->dropColumn('notas');
        });
    }
};
