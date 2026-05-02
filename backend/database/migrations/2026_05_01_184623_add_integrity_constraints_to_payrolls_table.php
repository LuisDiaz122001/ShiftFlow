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
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('payrolls', function (Blueprint $table) {
            // Check constraints for non-negative values
            DB::statement('ALTER TABLE payrolls ADD CONSTRAINT check_total_amount_positive CHECK (total_amount >= 0)');
            DB::statement('ALTER TABLE payrolls ADD CONSTRAINT check_total_hours_positive CHECK (total_hours >= 0)');

            // Unique constraint for employee + period (only if NOT cancelled)
            DB::statement("ALTER TABLE payrolls ADD COLUMN active_period_uid VARCHAR(100) GENERATED ALWAYS AS (IF(estado != 'cancelled', CONCAT(employee_id, '_', period_start, '_', period_end), NULL)) VIRTUAL");
            $table->unique('active_period_uid', 'unique_active_payroll_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropUnique('unique_active_payroll_period');
            DB::statement('ALTER TABLE payrolls DROP CONSTRAINT check_total_amount_positive');
            DB::statement('ALTER TABLE payrolls DROP CONSTRAINT check_total_hours_positive');
            $table->dropColumn('active_period_uid');
        });
    }
};
